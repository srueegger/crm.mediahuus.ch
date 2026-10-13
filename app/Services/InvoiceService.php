<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Repositories\BranchRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\InvoiceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class InvoiceService
{
    private const MAX_ATTEMPTS = 3;

    /**
     * @param array<string, mixed> $invoiceConfig Inhalt von config/invoice.php
     */
    public function __construct(
        private Connection $connection,
        private DocumentRepository $documentRepository,
        private InvoiceRepository $invoiceRepository,
        private BranchRepository $branchRepository,
        private InvoiceCalculator $calculator,
        private InvoiceNumberGenerator $numberGenerator,
        private QrBillFactory $qrBillFactory,
        private array $invoiceConfig,
        private CustomerResolver $customerResolver
    ) {
    }

    /**
     * Erstellt Dokument, Rechnung und Positionen in einer Transaktion.
     *
     * @param array<string, mixed> $data validierte Daten aus InvoiceInputValidator::validate()['data']
     * @return int document_id
     * @throws \RuntimeException bei ungültiger QR-Rechnung, fehlender Filiale oder Nummernkollision
     */
    public function create(array $data, int $userId, ?int $selectedCustomerId = null): int
    {
        $branchName = (string) $this->invoiceConfig['creditor']['branch_name'];
        $branch = $this->branchRepository->findByName($branchName);
        if ($branch === null || $branch->getId() === null) {
            throw new \RuntimeException("Filiale '{$branchName}' wurde nicht gefunden.");
        }

        $company = (string) ($data['company_name'] ?? '');
        $contact = (string) ($data['contact_name'] ?? '');
        $displayName = $company !== '' ? $company : $contact;

        /** @var \DateTimeImmutable $invoiceDate */
        $invoiceDate = $data['invoice_date'];
        $termDays = (int) $data['payment_term_days'];
        $dueDate = $invoiceDate->modify('+' . $termDays . ' days');

        $calc = $this->calculator->calculate(array_map(
            static fn(array $i): array => [
                'quantity' => $i['quantity'],
                'unit_price_gross' => $i['unit_price_gross'],
                'vat_rate' => $i['vat_rate'],
            ],
            $data['items']
        ));

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $number = $this->numberGenerator->next(
                $invoiceDate,
                $this->documentRepository->findLastInvoiceNumberForDate($invoiceDate)
            );

            // Wirft RuntimeException, bevor etwas gespeichert wird
            $this->qrBillFactory->create(
                $number,
                $calc['total_gross'],
                $displayName,
                (string) $data['street'],
                (string) $data['zip'],
                (string) $data['city']
            );

            try {
                return $this->connection->transactional(
                    function () use ($selectedCustomerId, $number, $branch, $userId, $displayName, $data, $company, $contact, $invoiceDate, $termDays, $dueDate, $calc): int {
                        $customerId = $this->customerResolver->resolve(
                            CustomerFieldMap::dataFromForm(Document::TYPE_INVOICE, $data),
                            $selectedCustomerId
                        );

                        $documentId = $this->documentRepository->create(new Document(
                            Document::TYPE_INVOICE,
                            $number,
                            (int) $branch->getId(),
                            $userId,
                            $displayName,
                            null,
                            null,
                            null,
                            null,
                            null,
                            (string) $data['street'],
                            (string) $data['zip'],
                            (string) $data['city'],
                            $customerId
                        ));

                        $items = [];
                        foreach (array_values($data['items']) as $index => $item) {
                            $items[] = new InvoiceItem(
                                position: $index + 1,
                                description: $item['description'],
                                quantity: $item['quantity'],
                                unitPriceGrossChf: $item['unit_price_gross'],
                                vatRate: $item['vat_rate'],
                                lineTotalGrossChf: $calc['lines'][$index]['line_total_gross']
                            );
                        }

                        $this->invoiceRepository->create(new Invoice(
                            documentId: $documentId,
                            companyName: $company !== '' ? $company : null,
                            contactName: $contact !== '' ? $contact : null,
                            invoiceDate: $invoiceDate,
                            paymentTermDays: $termDays,
                            dueDate: $dueDate,
                            totalNetChf: $calc['total_net'],
                            totalVatChf: $calc['total_vat'],
                            totalGrossChf: $calc['total_gross'],
                            qrReference: $this->qrBillFactory->scorReference($number),
                            items: $items
                        ));

                        return $documentId;
                    }
                );
            } catch (UniqueConstraintViolationException) {
                // Nummer wurde parallel vergeben: neu ermitteln und erneut versuchen
            }
        }

        throw new \RuntimeException('Rechnungsnummer konnte nicht vergeben werden. Bitte erneut versuchen.');
    }
}
