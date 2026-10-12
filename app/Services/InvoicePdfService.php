<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Invoice;
use Psr\Log\LoggerInterface;
use Sprain\SwissQrBill\PaymentPart\Output\DisplayOptions;
use Sprain\SwissQrBill\PaymentPart\Output\TcPdfOutput\TcPdfOutput;
use TCPDF;

class InvoicePdfService
{
    /** Der Zahlteil ist 105 mm hoch und beginnt auf A4 bei y = 297 - 105 mm. */
    private const QR_PART_TOP = 192.0;
    private const PAGE_BOTTOM = 277.0;

    /**
     * @param array<string, mixed> $config Inhalt von config/invoice.php
     */
    public function __construct(
        private array $config,
        private QrBillFactory $qrBillFactory,
        private LoggerInterface $logger
    ) {
    }

    public function getFilename(Document $document): string
    {
        return 'Rechnung_' . $document->getDocNumber() . '.pdf';
    }

    public function needsSeparateQrPage(float $currentY): bool
    {
        return $currentY > self::QR_PART_TOP;
    }

    public function generate(Document $document, Invoice $invoice, Branch $branch): string
    {
        try {
            $pdf = $this->createPdf();
            $pdf->SetCreator('Mediahuus CRM');
            $pdf->SetAuthor('Mediahuus');
            $pdf->SetTitle('Rechnung ' . $document->getDocNumber());
            $pdf->SetSubject('Rechnung');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(20, 15, 20);
            $pdf->SetAutoPageBreak(true, 20);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 10);

            $this->addHeader($pdf, $branch);
            $recipientBottom = $this->addRecipient($pdf, $document, $invoice);
            $y = $this->addTitle($pdf, $document, $invoice, max(80.0, $recipientBottom + 8));
            $y = $this->addItemsTable($pdf, $invoice, $y);
            $y = $this->addTotals($pdf, $invoice, $y);
            $y = $this->addPaymentHint($pdf, $invoice, $y);

            $pdf->SetAutoPageBreak(false);
            if ($this->needsSeparateQrPage($y)) {
                $pdf->AddPage();
            }
            $this->addPaymentPart($pdf, $document, $invoice);

            $this->logger->info('Invoice PDF generated', ['doc_number' => $document->getDocNumber()]);

            return $pdf->Output('', 'S');
        } catch (\Throwable $e) {
            $this->logger->error('Invoice PDF generation failed', [
                'doc_number' => $document->getDocNumber(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function createPdf(): TCPDF
    {
        return new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    }

    private function addHeader(TCPDF $pdf, Branch $branch): void
    {
        $logoPath = __DIR__ . '/../../public/assets/logo.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 20, 15, 40, 0, '', '', '', false, 300, '', false, false, 0);
        }

        $creditor = $this->config['creditor'];
        $y = 15.0;

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(120, $y);
        $pdf->Cell(70, 6, $creditor['name'], 0, 1, 'R');

        $pdf->SetFont('helvetica', '', 9);
        $lines = [
            $creditor['street'] . ' ' . $creditor['building_number'],
            $creditor['postal_code'] . ' ' . $creditor['city'],
        ];
        if ($branch->getPhone()) {
            $lines[] = 'Tel: ' . $branch->getPhone();
        }
        if (!empty($creditor['email'])) {
            $lines[] = 'E-Mail: ' . $creditor['email'];
        }
        $lines[] = $creditor['vat_number'];

        $y += 8;
        foreach ($lines as $line) {
            $pdf->SetXY(120, $y);
            $pdf->Cell(70, 4, $line, 0, 1, 'R');
            $y += 4;
        }
        // Letzte Zeile endet bei y = 15 + 8 + 5 * 4 = 43 mm (< 45 mm).
    }

    private function addRecipient(TCPDF $pdf, Document $document, Invoice $invoice): float
    {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(118, 55);
        $pdf->MultiCell(80, 5, $invoice->getDisplayName(), 0, 'L', false, 1, 118, 55);

        $lines = [];
        if ($invoice->getCompanyName() !== null && $invoice->getCompanyName() !== ''
            && $invoice->getContactName() !== null && $invoice->getContactName() !== '') {
            $lines[] = $invoice->getContactName();
        }
        $lines[] = (string) $document->getCustomerStreet();
        $lines[] = trim($document->getCustomerZip() . ' ' . $document->getCustomerCity());

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $pdf->SetX(118);
            $pdf->MultiCell(80, 5, $line, 0, 'L', false, 1);
        }

        return $pdf->GetY();
    }

    private function addTitle(TCPDF $pdf, Document $document, Invoice $invoice, float $y): float
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(20, $y);
        $pdf->Cell(0, 8, 'Rechnung ' . $document->getDocNumber(), 0, 1, 'L');

        $y += 10;
        $pdf->SetFont('helvetica', '', 10);
        $rows = [
            'Rechnungsdatum:' => $invoice->getInvoiceDate()->format('d.m.Y'),
            'Zahlbar bis:' => $invoice->getDueDate()->format('d.m.Y'),
            'Zahlungsfrist:' => $invoice->getPaymentTermDays() . ' Tage',
        ];
        foreach ($rows as $label => $value) {
            $pdf->SetXY(20, $y);
            $pdf->Cell(35, 4.5, $label, 0, 0, 'L');
            $pdf->Cell(60, 4.5, $value, 0, 1, 'L');
            $y += 4.5;
        }

        return $y + 4;
    }

    private function addTableHeader(TCPDF $pdf, float $y): float
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetXY(20, $y);
        $pdf->Cell(10, 7, 'Pos.', 0, 0, 'L', true);
        $pdf->Cell(62, 7, 'Beschreibung', 0, 0, 'L', true);
        $pdf->Cell(16, 7, 'Menge', 0, 0, 'R', true);
        $pdf->Cell(28, 7, 'Einzelpreis', 0, 0, 'R', true);
        $pdf->Cell(16, 7, 'MWST', 0, 0, 'R', true);
        $pdf->Cell(38, 7, 'Total CHF', 0, 1, 'R', true);

        return $y + 7;
    }

    private function addItemsTable(TCPDF $pdf, Invoice $invoice, float $y): float
    {
        $y = $this->addTableHeader($pdf, $y);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetDrawColor(220, 220, 220);

        foreach ($invoice->getItems() as $item) {
            $height = max(6.0, $pdf->getStringHeight(62, $item->getDescription()) + 2);
            if ($y + $height > self::PAGE_BOTTOM) {
                $pdf->AddPage();
                $y = $this->addTableHeader($pdf, 15.0);
                $pdf->SetFont('helvetica', '', 9);
            }

            $pdf->SetXY(20, $y);
            $pdf->Cell(10, $height, (string) $item->getPosition(), 'B', 0, 'L');
            $pdf->MultiCell(62, $height, $item->getDescription(), 'B', 'L', false, 0, 30, $y, true, 0, false, true, $height, 'M');
            $pdf->Cell(16, $height, $this->formatQuantity($item->getQuantity()), 'B', 0, 'R');
            $pdf->Cell(28, $height, $this->formatChf($item->getUnitPriceGrossChf()), 'B', 0, 'R');
            $pdf->Cell(16, $height, $item->getVatRate() . ' %', 'B', 0, 'R');
            $pdf->Cell(38, $height, $this->formatChf($item->getLineTotalGrossChf()), 'B', 1, 'R');
            $y += $height;
        }

        return $y + 4;
    }

    private function addTotals(TCPDF $pdf, Invoice $invoice, float $y): float
    {
        $groups = [];
        foreach ($invoice->getVatGroups() as $rate => $group) {
            $groups[(string) $rate] = $group;
        }
        krsort($groups, SORT_NUMERIC);

        $needed = 5 * (2 + count($groups)) + 6;
        if ($y + $needed > self::PAGE_BOTTOM) {
            $pdf->AddPage();
            $y = 15.0;
        }

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(100, $y);
        $pdf->Cell(60, 5, 'Total exkl. MWST', 0, 0, 'L');
        $pdf->Cell(30, 5, $this->formatChf($invoice->getTotalNetChf()), 0, 1, 'R');
        $y += 5;

        foreach ($groups as $rate => $group) {
            $pdf->SetXY(100, $y);
            $pdf->Cell(60, 5, 'MWST ' . $rate . ' % auf ' . $this->formatChf($group['net']), 0, 0, 'L');
            $pdf->Cell(30, 5, $this->formatChf($group['vat']), 0, 1, 'R');
            $y += 5;
        }

        $y += 1;
        $pdf->SetDrawColor(120, 120, 120);
        $pdf->Line(100, $y, 190, $y);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(100, $y + 1);
        $pdf->Cell(60, 6, 'Total CHF inkl. MWST', 0, 0, 'L');
        $pdf->Cell(30, 6, $this->formatChf($invoice->getTotalGrossChf()), 0, 1, 'R');

        return $y + 7 + 3;
    }

    private function addPaymentHint(TCPDF $pdf, Invoice $invoice, float $y): float
    {
        $iban = trim(chunk_split((string) $this->config['creditor']['iban'], 4, ' '));
        $text = 'Bitte überweisen Sie den Betrag bis zum ' . $invoice->getDueDate()->format('d.m.Y')
            . ' mit dem untenstehenden QR-Einzahlungsschein.';

        if ($y + 16 > self::PAGE_BOTTOM) {
            $pdf->AddPage();
            $y = 15.0;
        }

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(20, $y);
        $pdf->MultiCell(170, 4.5, $text, 0, 'L', false, 1);
        $pdf->SetX(20);
        $pdf->Cell(170, 4.5, 'IBAN: ' . $iban, 0, 1, 'L');

        return $pdf->GetY() + 2;
    }

    private function addPaymentPart(TCPDF $pdf, Document $document, Invoice $invoice): void
    {
        $qrBill = $this->qrBillFactory->create(
            $document->getDocNumber(),
            $invoice->getTotalGrossChf(),
            $invoice->getDisplayName(),
            (string) $document->getCustomerStreet(),
            (string) $document->getCustomerZip(),
            (string) $document->getCustomerCity()
        );

        // Die Bibliothek positioniert absolut auf A4 (Zahlteil ab y = 192 mm); Offsets daher 0.
        $output = new TcPdfOutput($qrBill, 'de', $pdf, 0, 0);
        $output->setDisplayOptions((new DisplayOptions())->setPrintable(false));
        $output->getPaymentPart();
    }

    private function formatChf(string $amount): string
    {
        $parts = explode('.', $amount);
        $int = preg_replace('/\B(?=(\d{3})+(?!\d))/', "'", $parts[0]);

        return $int . '.' . str_pad($parts[1] ?? '00', 2, '0');
    }

    private function formatQuantity(string $quantity): string
    {
        if (str_contains($quantity, '.')) {
            $quantity = rtrim(rtrim($quantity, '0'), '.');
        }

        return $quantity === '' ? '0' : $quantity;
    }
}
