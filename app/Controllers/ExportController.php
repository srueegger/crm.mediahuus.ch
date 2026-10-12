<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Document;
use App\Repositories\DocumentRepository;
use App\Repositories\BranchRepository;
use App\Repositories\EstimateRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\ReceiptRepository;
use App\Repositories\InsuranceAssessmentRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\UserRepository;
use App\Services\DamageTypeService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ExportController
{
    /** Mediahuus-Grün (emerald-600) für die Kopfzeilen */
    private const HEADER_COLOR = '059669';
    private const MONEY_FORMAT = '"CHF" #,##0.00';

    public function __construct(
        private DocumentRepository $documentRepository,
        private BranchRepository $branchRepository,
        private EstimateRepository $estimateRepository,
        private PurchaseRepository $purchaseRepository,
        private ReceiptRepository $receiptRepository,
        private InsuranceAssessmentRepository $insuranceRepository,
        private InvoiceRepository $invoiceRepository,
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {
    }

    public function excel(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $dateFrom = $this->parseDate($params['date_from'] ?? '', false);
        $dateTo = $this->parseDate($params['date_to'] ?? '', true);

        // Alle Dokumente laden und nach Erstellungsdatum filtern
        $documents = $this->documentRepository->findAll();
        $documents = array_filter($documents, function (Document $doc) use ($dateFrom, $dateTo) {
            $created = $doc->getCreatedAt();
            if ($dateFrom !== null && $created < $dateFrom) {
                return false;
            }
            if ($dateTo !== null && $created > $dateTo) {
                return false;
            }
            return true;
        });

        // Lookup-Maps aufbauen (vermeidet N+1-Queries)
        $branches = [];
        foreach ($this->branchRepository->findAll() as $branch) {
            $branches[$branch->getId()] = $branch;
        }
        $estimates = $this->indexByDocumentId($this->estimateRepository->findAll());
        $purchases = $this->indexByDocumentId($this->purchaseRepository->findAll());
        $receipts = $this->indexByDocumentId($this->receiptRepository->findAll());
        $insurances = $this->indexByDocumentId($this->insuranceRepository->findAll());
        $invoices = $this->indexByDocumentId($this->invoiceRepository->findAll());
        $users = [];
        foreach ($this->userRepository->findAll(true) as $user) {
            $users[$user->getId()] = $user->getName();
        }

        // Dokumente nach Typ gruppieren
        $byType = [
            Document::TYPE_RECEIPT => [],
            Document::TYPE_PURCHASE => [],
            Document::TYPE_ESTIMATE => [],
            Document::TYPE_INSURANCE => [],
            Document::TYPE_INVOICE => [],
        ];
        foreach ($documents as $doc) {
            $byType[$doc->getDocType()][] = $doc;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Default-Sheet entfernen

        $this->buildOverviewSheet($spreadsheet, $documents, $branches, $estimates, $purchases, $receipts, $insurances, $invoices);
        $this->buildInvoicesSheet($spreadsheet, $byType[Document::TYPE_INVOICE], $branches, $invoices, $users);
        $this->buildReceiptsSheet($spreadsheet, $byType[Document::TYPE_RECEIPT], $branches, $receipts);
        $this->buildPurchasesSheet($spreadsheet, $byType[Document::TYPE_PURCHASE], $branches, $purchases);
        $this->buildEstimatesSheet($spreadsheet, $byType[Document::TYPE_ESTIMATE], $branches, $estimates);
        $this->buildInsuranceSheet($spreadsheet, $byType[Document::TYPE_INSURANCE], $branches, $insurances);

        $spreadsheet->setActiveSheetIndex(0);

        // In temporäre Datei schreiben und in Response-Body übernehmen
        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'crm_export_');
        $writer->save($tmpFile);
        $content = (string) file_get_contents($tmpFile);
        @unlink($tmpFile);
        $spreadsheet->disconnectWorksheets();

        $filename = 'mediahuus-export_' . date('Y-m-d') . '.xlsx';

        $this->logger->info('Excel export generated', [
            'documents' => count($documents),
            'date_from' => $params['date_from'] ?? null,
            'date_to' => $params['date_to'] ?? null,
        ]);

        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string) strlen($content));
    }

    private function buildOverviewSheet(
        Spreadsheet $spreadsheet,
        array $documents,
        array $branches,
        array $estimates,
        array $purchases,
        array $receipts,
        array $insurances,
        array $invoices
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Übersicht');

        $headers = ['Dok.-Nr.', 'Typ', 'Datum', 'Filiale', 'Kunde', 'Telefon', 'E-Mail', 'Betrag'];
        $this->writeHeader($sheet, $headers);

        $row = 2;
        foreach ($documents as $doc) {
            $amount = $this->primaryAmount($doc, $estimates, $purchases, $receipts, $insurances, $invoices);
            $sheet->setCellValue("A{$row}", $doc->getDocNumber());
            $sheet->setCellValue("B{$row}", $doc->getTypeLabel());
            $sheet->setCellValue("C{$row}", $doc->getCreatedAt()->format('d.m.Y H:i'));
            $sheet->setCellValue("D{$row}", $this->branchName($branches, $doc->getBranchId()));
            $sheet->setCellValue("E{$row}", $doc->getCustomerName());
            $sheet->setCellValue("F{$row}", $doc->getCustomerPhone() ?? '');
            $sheet->setCellValue("G{$row}", $doc->getCustomerEmail() ?? '');
            if ($amount !== null) {
                $sheet->setCellValue("H{$row}", $amount);
                $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
            }
            $row++;
        }

        $this->finalizeSheet($sheet, count($headers));
    }

    private function buildInvoicesSheet(Spreadsheet $spreadsheet, array $documents, array $branches, array $invoices, array $users): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Rechnungen');

        $headers = ['Nummer', 'Datum', 'Fällig', 'Empfänger', 'Adresse', 'Netto', 'MWST', 'Total', 'Erstellt von'];
        $this->writeHeader($sheet, $headers);

        $row = 2;
        foreach ($documents as $doc) {
            $invoice = $invoices[$doc->getId()] ?? null;

            $sheet->setCellValue("A{$row}", $doc->getDocNumber());
            $sheet->setCellValue("B{$row}", $invoice !== null ? $invoice->getInvoiceDate()->format('d.m.Y') : $doc->getCreatedAt()->format('d.m.Y'));
            $sheet->setCellValue("C{$row}", $invoice !== null ? $invoice->getDueDate()->format('d.m.Y') : '');
            $sheet->setCellValue("D{$row}", $invoice !== null ? $invoice->getDisplayName() : $doc->getCustomerName());
            $sheet->setCellValue("E{$row}", $doc->getCustomerAddress() ?? '');
            if ($invoice !== null) {
                foreach (['F' => $invoice->getTotalNetChf(), 'G' => $invoice->getTotalVatChf(), 'H' => $invoice->getTotalGrossChf()] as $col => $amount) {
                    $sheet->setCellValue("{$col}{$row}", (float) $amount);
                    $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
                }
            }
            $sheet->setCellValue("I{$row}", $users[$doc->getUserId()] ?? '');
            $row++;
        }

        $this->finalizeSheet($sheet, count($headers));
    }

    private function buildReceiptsSheet(Spreadsheet $spreadsheet, array $documents, array $branches, array $receipts): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Quittungen');

        $headers = ['Dok.-Nr.', 'Datum', 'Filiale', 'Kunde', 'Adresse', 'Artikel', 'Anz. Positionen', 'Gesamtbetrag'];
        $this->writeHeader($sheet, $headers);

        $row = 2;
        foreach ($documents as $doc) {
            $receipt = $receipts[$doc->getId()] ?? null;

            $itemsText = '';
            $itemCount = 0;
            if ($receipt !== null) {
                $parts = [];
                foreach ($receipt->getItems() as $item) {
                    $warranty = $item->getWarrantyLabel() ? ' [Garantie: ' . $item->getWarrantyLabel() . ']' : '';
                    $parts[] = $item->getQuantity() . '× ' . $item->getItemDescription()
                        . ' (' . $item->getFormattedUnitPrice() . ')' . $warranty;
                }
                $itemsText = implode("\n", $parts);
                $itemCount = count($receipt->getItems());
            }

            $sheet->setCellValue("A{$row}", $doc->getDocNumber());
            $sheet->setCellValue("B{$row}", $doc->getCreatedAt()->format('d.m.Y H:i'));
            $sheet->setCellValue("C{$row}", $this->branchName($branches, $doc->getBranchId()));
            $sheet->setCellValue("D{$row}", $doc->getCustomerName());
            $sheet->setCellValue("E{$row}", $doc->getCustomerAddress() ?? '');
            $sheet->setCellValueExplicit("F{$row}", $itemsText, DataType::TYPE_STRING);
            $sheet->getStyle("F{$row}")->getAlignment()->setWrapText(true);
            $sheet->setCellValue("G{$row}", $itemCount);
            if ($receipt !== null) {
                $sheet->setCellValue("H{$row}", $receipt->getTotalAmountChf());
                $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
            }
            $row++;
        }

        $this->finalizeSheet($sheet, count($headers));
    }

    private function buildPurchasesSheet(Spreadsheet $spreadsheet, array $documents, array $branches, array $purchases): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ankäufe');

        $headers = [
            'Dok.-Nr.', 'Datum', 'Filiale', 'Verkäufer', 'Verkäufer-Adresse', 'Telefon', 'E-Mail',
            'Gerätetyp', 'Marke', 'Modell', 'IMEI', 'Seriennummer', 'Zustand', 'Zubehör',
            'Ankaufspreis', 'Ausweis-Fotos',
        ];
        $this->writeHeader($sheet, $headers);

        $row = 2;
        foreach ($documents as $doc) {
            $purchase = $purchases[$doc->getId()] ?? null;

            $sheet->setCellValue("A{$row}", $doc->getDocNumber());
            $sheet->setCellValue("B{$row}", $doc->getCreatedAt()->format('d.m.Y H:i'));
            $sheet->setCellValue("C{$row}", $this->branchName($branches, $doc->getBranchId()));
            $sheet->setCellValue("D{$row}", $doc->getCustomerName());
            if ($purchase !== null) {
                $sheet->setCellValue("E{$row}", $purchase->getSellerAddress());
                $sheet->setCellValue("F{$row}", $doc->getCustomerPhone() ?? '');
                $sheet->setCellValue("G{$row}", $doc->getCustomerEmail() ?? '');
                $sheet->setCellValue("H{$row}", $purchase->getDeviceTypeName());
                $sheet->setCellValue("I{$row}", $purchase->getDeviceBrand());
                $sheet->setCellValue("J{$row}", $purchase->getDeviceModel());
                $sheet->setCellValueExplicit("K{$row}", $purchase->getImei() ?? '', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("L{$row}", $purchase->getSerialNumber() ?? '', DataType::TYPE_STRING);
                $sheet->setCellValue("M{$row}", $purchase->getDeviceCondition() ?? '');
                $sheet->setCellValue("N{$row}", $purchase->getAccessories() ?? '');
                $sheet->setCellValue("O{$row}", $purchase->getPurchasePriceChf());
                $sheet->getStyle("O{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
                $sheet->setCellValue("P{$row}", $this->idPhotoHint($purchase->getIdDocumentFront(), $purchase->getIdDocumentBack()));
            }
            $row++;
        }

        $this->finalizeSheet($sheet, count($headers));
    }

    private function buildEstimatesSheet(Spreadsheet $spreadsheet, array $documents, array $branches, array $estimates): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Kostenvoranschläge');

        $headers = [
            'Dok.-Nr.', 'Datum', 'Filiale', 'Kunde', 'Telefon', 'E-Mail',
            'Gerät', 'Seriennummer', 'Schadenstyp', 'Beschreibung', 'Preis',
        ];
        $this->writeHeader($sheet, $headers);

        $row = 2;
        foreach ($documents as $doc) {
            $estimate = $estimates[$doc->getId()] ?? null;

            $sheet->setCellValue("A{$row}", $doc->getDocNumber());
            $sheet->setCellValue("B{$row}", $doc->getCreatedAt()->format('d.m.Y H:i'));
            $sheet->setCellValue("C{$row}", $this->branchName($branches, $doc->getBranchId()));
            $sheet->setCellValue("D{$row}", $doc->getCustomerName());
            $sheet->setCellValue("E{$row}", $doc->getCustomerPhone() ?? '');
            $sheet->setCellValue("F{$row}", $doc->getCustomerEmail() ?? '');
            if ($estimate !== null) {
                $sheet->setCellValue("G{$row}", $estimate->getDeviceName());
                $sheet->setCellValueExplicit("H{$row}", $estimate->getSerialNumber(), DataType::TYPE_STRING);
                $sheet->setCellValue("I{$row}", DamageTypeService::getDamageTypeLabel($estimate->getDamageType()));
                $sheet->setCellValueExplicit("J{$row}", $estimate->getIssueText(), DataType::TYPE_STRING);
                $sheet->getStyle("J{$row}")->getAlignment()->setWrapText(true);
                $sheet->setCellValue("K{$row}", $estimate->getPriceChf());
                $sheet->getStyle("K{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
            }
            $row++;
        }

        $this->finalizeSheet($sheet, count($headers));
    }

    private function buildInsuranceSheet(Spreadsheet $spreadsheet, array $documents, array $branches, array $insurances): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Gutachten');

        $headers = [
            'Dok.-Nr.', 'Datum', 'Filiale', 'Kunde', 'Kunden-Adresse',
            'Gerät', 'Seriennummer', 'Schadenstyp', 'Schadensbeschreibung',
            'Ergebnis', 'Zeitwert', 'Reparaturkosten',
        ];
        $this->writeHeader($sheet, $headers);

        $row = 2;
        foreach ($documents as $doc) {
            $insurance = $insurances[$doc->getId()] ?? null;

            $sheet->setCellValue("A{$row}", $doc->getDocNumber());
            $sheet->setCellValue("B{$row}", $doc->getCreatedAt()->format('d.m.Y H:i'));
            $sheet->setCellValue("C{$row}", $this->branchName($branches, $doc->getBranchId()));
            $sheet->setCellValue("D{$row}", $doc->getCustomerName());
            if ($insurance !== null) {
                $sheet->setCellValue("E{$row}", $insurance->getCustomerAddress());
                $sheet->setCellValue("F{$row}", $insurance->getDeviceName());
                $sheet->setCellValueExplicit("G{$row}", $insurance->getSerialNumber(), DataType::TYPE_STRING);
                $sheet->setCellValue("H{$row}", DamageTypeService::getDamageTypeLabel($insurance->getDamageType()));
                $sheet->setCellValueExplicit("I{$row}", $insurance->getDamageDescription(), DataType::TYPE_STRING);
                $sheet->getStyle("I{$row}")->getAlignment()->setWrapText(true);
                $sheet->setCellValue("J{$row}", $insurance->getAssessmentResultLabel());
                if ($insurance->getDeviceValueChf() !== null) {
                    $sheet->setCellValue("K{$row}", $insurance->getDeviceValueChf());
                    $sheet->getStyle("K{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
                }
                if ($insurance->getRepairCostChf() !== null) {
                    $sheet->setCellValue("L{$row}", $insurance->getRepairCostChf());
                    $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
                }
            }
            $row++;
        }

        $this->finalizeSheet($sheet, count($headers));
    }

    // ---- Helpers ----------------------------------------------------------

    private function writeHeader(Worksheet $sheet, array $headers): void
    {
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . '1', $header);
            $col++;
        }
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $style = $sheet->getStyle("A1:{$lastCol}1");
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_COLOR);
    }

    private function finalizeSheet(Worksheet $sheet, int $columnCount): void
    {
        for ($col = 1; $col <= $columnCount; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }
        $lastCol = Coordinate::stringFromColumnIndex($columnCount);
        $sheet->setAutoFilter("A1:{$lastCol}1");
        $sheet->freezePane('A2');
    }

    private function indexByDocumentId(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            $map[$item->getDocumentId()] = $item;
        }
        return $map;
    }

    private function branchName(array $branches, int $branchId): string
    {
        return isset($branches[$branchId]) ? $branches[$branchId]->getName() : '';
    }

    private function primaryAmount(
        Document $doc,
        array $estimates,
        array $purchases,
        array $receipts,
        array $insurances,
        array $invoices
    ): ?float {
        return match ($doc->getDocType()) {
            Document::TYPE_ESTIMATE => isset($estimates[$doc->getId()]) ? $estimates[$doc->getId()]->getPriceChf() : null,
            Document::TYPE_PURCHASE => isset($purchases[$doc->getId()]) ? $purchases[$doc->getId()]->getPurchasePriceChf() : null,
            Document::TYPE_RECEIPT => isset($receipts[$doc->getId()]) ? $receipts[$doc->getId()]->getTotalAmountChf() : null,
            Document::TYPE_INSURANCE => isset($insurances[$doc->getId()]) ? $insurances[$doc->getId()]->getRepairCostChf() : null,
            Document::TYPE_INVOICE => isset($invoices[$doc->getId()]) ? (float) $invoices[$doc->getId()]->getTotalGrossChf() : null,
            default => null,
        };
    }

    private function idPhotoHint(?string $front, ?string $back): string
    {
        $parts = [];
        if (!empty($front)) {
            $parts[] = 'Vorderseite: ' . basename($front);
        }
        if (!empty($back)) {
            $parts[] = 'Rückseite: ' . basename($back);
        }
        return implode("\n", $parts);
    }

    private function parseDate(string $value, bool $endOfDay): ?\DateTime
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if ($date === false) {
            return null;
        }
        if ($endOfDay) {
            $date->setTime(23, 59, 59);
        } else {
            $date->setTime(0, 0, 0);
        }
        return $date;
    }
}
