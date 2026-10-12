<?php

declare(strict_types=1);

namespace App\Services;

use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;
use Sprain\SwissQrBill\QrBill;
use Sprain\SwissQrBill\Reference\RfCreditorReferenceGenerator;

class QrBillFactory
{
    /**
     * @param array<string, string> $creditorConfig
     */
    public function __construct(private array $creditorConfig, private ?QrTextNormalizer $normalizer = null)
    {
        $this->normalizer ??= new QrTextNormalizer();
    }

    /**
     * Baut eine validierte Swiss QR-Rechnung (SCOR-Referenz, CHF).
     *
     * @throws \RuntimeException wenn die QR-Rechnung ungueltig ist
     */
    public function create(
        string $invoiceNumber,
        string $amountGross,
        string $debtorName,
        string $debtorStreet,
        string $debtorZip,
        string $debtorCity
    ): QrBill {
        if (!preg_match('/^\d+\.\d{2}$/', $amountGross) || bccomp($amountGross, '0.01', 2) < 0) {
            throw new \RuntimeException('Die QR-Rechnung ist ungültig: Der Rechnungsbetrag muss mindestens 0.01 CHF betragen.');
        }

        // Zeichen ausserhalb Windows-1252 transliterieren (die Bibliothek wuerde sie sonst stillschweigend entfernen)
        $debtorName = $this->normalizer->normalize($debtorName);
        $debtorStreet = $this->normalizer->normalize($debtorStreet);
        $debtorZip = $this->normalizer->normalize($debtorZip);
        $debtorCity = $this->normalizer->normalize($debtorCity);

        $qrBill = QrBill::create();

        $qrBill->setCreditor(StructuredAddress::createWithStreet(
            $this->creditorConfig['name'],
            $this->creditorConfig['street'],
            $this->creditorConfig['building_number'],
            $this->creditorConfig['postal_code'],
            $this->creditorConfig['city'],
            $this->creditorConfig['country']
        ));
        $qrBill->setCreditorInformation(CreditorInformation::create($this->creditorConfig['iban']));
        $qrBill->setUltimateDebtor(StructuredAddress::createWithStreet(
            $debtorName,
            $debtorStreet,
            null,
            $debtorZip,
            $debtorCity,
            'CH'
        ));
        // Betrag wurde bereits als 2-stelliger Dezimalstring validiert; keine Arithmetik.
        $qrBill->setPaymentAmountInformation(PaymentAmountInformation::create('CHF', (float) $amountGross));
        $qrBill->setPaymentReference(
            PaymentReference::create(PaymentReference::TYPE_SCOR, $this->scorReference($invoiceNumber))
        );
        $qrBill->setAdditionalInformation(AdditionalInformation::create('Rechnung ' . $invoiceNumber));

        if (!$qrBill->isValid()) {
            $messages = [];
            foreach ($qrBill->getViolations() as $violation) {
                $messages[] = (string) $violation->getMessage();
            }
            throw new \RuntimeException('Die QR-Rechnung ist ungültig: ' . implode('; ', $messages));
        }

        return $qrBill;
    }

    public function scorReference(string $invoiceNumber): string
    {
        return RfCreditorReferenceGenerator::generate(
            (new InvoiceNumberGenerator())->referenceBase($invoiceNumber)
        );
    }
}
