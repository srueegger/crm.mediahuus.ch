<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Estimate;
use App\Models\Receipt;
use App\Models\Purchase;
use App\Models\Branch;
use TCPDF;
use Psr\Log\LoggerInterface;

class PdfService
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function generateEstimatePdf(Document $document, Estimate $estimate, Branch $branch): string
    {
        try {
            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('Mediahuus CRM');
            $pdf->SetAuthor('Mediahuus');
            $pdf->SetTitle('Kostenvoranschlag ' . $document->getDocNumber());
            $pdf->SetSubject('Kostenvoranschlag');

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins with proper bottom margin for page breaks
            $pdf->SetMargins(20, 15, 20);
            $pdf->SetAutoPageBreak(true, 25); // Enable auto page break with 25mm bottom margin

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 10);

            // Add content
            $this->addEstimateContent($pdf, $document, $estimate, $branch);

            $this->logger->info('PDF generated successfully', [
                'document_id' => $document->getId(),
                'doc_number' => $document->getDocNumber()
            ]);

            return $pdf->Output('', 'S');

        } catch (\Exception $e) {
            $this->logger->error('PDF generation failed', [
                'document_id' => $document->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function addEstimateContent(TCPDF $pdf, Document $document, Estimate $estimate, Branch $branch): void
    {
        $currentY = 15;

        // Header with Logo and Branch Info
        $currentY = $this->addHeader($pdf, $branch, $currentY);
        
        // Document Title and Number
        $currentY = $this->addDocumentTitle($pdf, $document, $currentY);
        
        // Customer Information
        $currentY = $this->addCustomerInfo($pdf, $document, $currentY);
        
        // Estimate Details
        $currentY = $this->addEstimateDetails($pdf, $estimate, $currentY);
        
        // Total Price
        $currentY = $this->addTotalPrice($pdf, $estimate, $currentY);
        
        // Footer Information
        $this->addFooter($pdf, $branch, $document);
    }

    private function addHeader(TCPDF $pdf, Branch $branch, float $currentY): float
    {
        // Logo (if exists)
        $logoPath = __DIR__ . '/../../public/assets/logo.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 20, $currentY, 40, 0, '', '', '', false, 300, '', false, false, 0);
        }

        // Branch Information (right side)
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(120, $currentY);
        $pdf->Cell(70, 6, $branch->getName(), 0, 1, 'R');
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(120, $currentY + 8);
        $pdf->Cell(70, 4, $branch->getStreet(), 0, 1, 'R');
        
        $pdf->SetXY(120, $currentY + 12);
        $pdf->Cell(70, 4, $branch->getZip() . ' ' . $branch->getCity(), 0, 1, 'R');
        
        if ($branch->getPhone()) {
            $pdf->SetXY(120, $currentY + 16);
            $pdf->Cell(70, 4, 'Tel: ' . $branch->getPhone(), 0, 1, 'R');
        }
        
        if ($branch->getEmail()) {
            $pdf->SetXY(120, $currentY + 20);
            $pdf->Cell(70, 4, 'E-Mail: ' . $branch->getEmail(), 0, 1, 'R');
        }

        return $currentY + 35;
    }

    private function addDocumentTitle(TCPDF $pdf, Document $document, float $currentY): float
    {
        // Line separator
        $pdf->SetLineWidth(0.3);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(20, $currentY, 190, $currentY);
        
        $currentY += 10;

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 8, 'KOSTENVORANSCHLAG', 0, 1, 'L');

        $currentY += 12;

        // Document number and date
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(60, 6, 'Nummer:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $document->getDocNumber(), 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY + 6);
        $pdf->Cell(60, 6, 'Datum:', 0, 0, 'L');
        $pdf->Cell(0, 6, $document->getCreatedAt()->format('d.m.Y'), 0, 1, 'L');

        return $currentY + 20;
    }

    private function addCustomerInfo(TCPDF $pdf, Document $document, float $currentY): float
    {
        // Section title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Kunde', 0, 1, 'L');
        
        $currentY += 8;

        // Customer details
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, $document->getCustomerName(), 0, 1, 'L');
        
        $currentY += 6;

        if ($document->getCustomerPhone()) {
            $pdf->SetXY(20, $currentY);
            $pdf->Cell(0, 5, 'Tel: ' . $document->getCustomerPhone(), 0, 1, 'L');
            $currentY += 5;
        }

        if ($document->getCustomerEmail()) {
            $pdf->SetXY(20, $currentY);
            $pdf->Cell(0, 5, 'E-Mail: ' . $document->getCustomerEmail(), 0, 1, 'L');
            $currentY += 5;
        }

        return $currentY + 10;
    }

    private function addEstimateDetails(TCPDF $pdf, Estimate $estimate, float $currentY): float
    {
        // Section title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Kostenvoranschlag für Geräte-Reparatur', 0, 1, 'L');
        
        $currentY += 10;

        // Standard introduction text
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY);
        $introText = "Sehr geehrte Damen und Herren,\n\nbasierend auf Ihren Angaben und der Inspektion Ihres Gerätes erstellen wir Ihnen gerne den folgenden Kostenvoranschlag für die Reparatur:";
        $pdf->MultiCell(170, 5, $introText, 0, 'L', false);
        
        $currentY = $pdf->GetY() + 10;

        // Device information section
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Geräteinformationen:', 0, 1, 'L');
        
        $currentY += 8;
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(60, 6, 'Gerät:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $estimate->getDeviceName(), 0, 1, 'L');
        
        $currentY += 6;
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(60, 6, 'Seriennummer:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $estimate->getSerialNumber(), 0, 1, 'L');

        $currentY += 15;

        // Issue description section
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Schadens-/Fehlerbeschreibung:', 0, 1, 'L');
        
        $currentY += 8;

        // Get combined issue text (damage template + custom text)
        $combinedIssueText = DamageTypeService::getCombinedIssueText(
            $estimate->getDamageType(), 
            $estimate->getIssueText()
        );
        
        // Issue description in a box
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->SetFont('helvetica', '', 10);
        
        // Convert HTML to text with basic formatting preservation
        $cleanIssueText = $this->convertHtmlToText($combinedIssueText);
        
        // Check if we need a new page before adding the text box
        $pageHeight = $pdf->getPageHeight();
        $bottomMargin = 25; // Same as auto page break margin
        $estimatedTextHeight = $pdf->getStringHeight(165, $cleanIssueText) + 10;
        
        if (($currentY + $estimatedTextHeight) > ($pageHeight - $bottomMargin)) {
            $pdf->AddPage();
            $currentY = 15; // Reset to top margin
        }
        
        // Calculate actual height needed for text
        $textHeight = $pdf->getStringHeight(165, $cleanIssueText);
        $boxHeight = max(20, $textHeight + 10);
        
        // Draw the box with proper height
        $pdf->Rect(20, $currentY, 170, $boxHeight, 'DF', array(), array(248, 249, 250));
        
        // Add text with proper line breaks
        $pdf->SetXY(25, $currentY + 5);
        $pdf->MultiCell(165, 5, $cleanIssueText, 0, 'L', false);

        $currentY = $pdf->GetY() + 15;

        // Standard closing text
        $pdf->SetFont('helvetica', '', 10);
        $closingText = "Die Reparatur wird fachgerecht von unseren qualifizierten Technikern durchgeführt. Alle verwendeten Ersatzteile entsprechen hohen Qualitätsstandards und werden mit einer Garantie von 6 Monaten abgedeckt.\n\nSollten während der Reparatur zusätzliche Defekte festgestellt werden, werden wir Sie umgehend kontaktieren, bevor weitere Arbeiten durchgeführt werden.";
        
        // Check if closing text fits on current page
        $closingTextHeight = $pdf->getStringHeight(170, $closingText);
        $pageHeight = $pdf->getPageHeight();
        $bottomMargin = 25;
        
        if (($currentY + $closingTextHeight) > ($pageHeight - $bottomMargin)) {
            $pdf->AddPage();
            $currentY = 15; // Reset to top margin
        }
        
        $pdf->SetXY(20, $currentY);
        $pdf->MultiCell(170, 5, $closingText, 0, 'L', false);
        
        return $pdf->GetY() + 15;
    }

    private function addTotalPrice(TCPDF $pdf, Estimate $estimate, float $currentY): float
    {
        // Check if price box fits on current page
        $priceBoxHeight = 15;
        $pageHeight = $pdf->getPageHeight();
        $bottomMargin = 25;
        
        if (($currentY + $priceBoxHeight + 10) > ($pageHeight - $bottomMargin)) {
            $pdf->AddPage();
            $currentY = 15; // Reset to top margin
        }
        
        // Price box - Green color #2d8f3e (45, 143, 62)
        $pdf->SetFillColor(45, 143, 62);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Rect(20, $currentY, 170, $priceBoxHeight, 'F');
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(25, $currentY + 4);
        $pdf->Cell(80, 8, 'Voraussichtliche Kosten:', 0, 0, 'L');
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(110, $currentY + 4);
        $pdf->Cell(75, 8, $estimate->getFormattedPrice(), 0, 0, 'R');
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);

        return $currentY + 25;
    }

    private function addFooter(TCPDF $pdf, Branch $branch, Document $document): void
    {
        // Add some space before footer
        $currentY = $pdf->GetY() + 20;
        
        // Simple footer without complex positioning
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        
        $pdf->Cell(0, 6, 'Dieser Kostenvoranschlag ist unverbindlich und 30 Tage gültig.', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Alle Preise verstehen sich in CHF inkl. MwSt.', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Erstellt am ' . $document->getCreatedAt()->format('d.m.Y H:i') . ' • ' . $branch->getName(), 0, 1, 'C');
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);
    }

    public function generateReceiptPdf(Document $document, Receipt $receipt, Branch $branch): string
    {
        try {
            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('Mediahuus CRM');
            $pdf->SetAuthor('Mediahuus');
            $pdf->SetTitle('Quittung ' . $document->getDocNumber());
            $pdf->SetSubject('Kaufquittung');

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins with proper bottom margin for page breaks
            $pdf->SetMargins(20, 15, 20);
            $pdf->SetAutoPageBreak(true, 25); // Enable auto page break with 25mm bottom margin

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 10);

            // Add content
            $this->addReceiptContent($pdf, $document, $receipt, $branch);

            $this->logger->info('Receipt PDF generated successfully', [
                'document_id' => $document->getId(),
                'doc_number' => $document->getDocNumber()
            ]);

            return $pdf->Output('', 'S');

        } catch (\Exception $e) {
            $this->logger->error('Receipt PDF generation failed', [
                'document_id' => $document->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getEstimatePdfFilename(Document $document): string
    {
        return 'Kostenvoranschlag_' . $document->getDocNumber() . '.pdf';
    }

    public function getReceiptPdfFilename(Document $document): string
    {
        return 'Quittung_' . $document->getDocNumber() . '.pdf';
    }

    public function generatePurchasePdf(Document $document, Purchase $purchase, Branch $branch): string
    {
        try {
            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('Mediahuus CRM');
            $pdf->SetAuthor('Mediahuus');
            $pdf->SetTitle('Ankauf ' . $document->getDocNumber());
            $pdf->SetSubject('Ankaufsbeleg');

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins with proper bottom margin for page breaks
            $pdf->SetMargins(20, 15, 20);
            $pdf->SetAutoPageBreak(true, 25);

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 10);

            // Add content
            $this->addPurchaseContent($pdf, $document, $purchase, $branch);

            $this->logger->info('Purchase PDF generated successfully', [
                'document_id' => $document->getId(),
                'doc_number' => $document->getDocNumber()
            ]);

            return $pdf->Output('', 'S');

        } catch (\Exception $e) {
            $this->logger->error('Purchase PDF generation failed', [
                'document_id' => $document->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPurchasePdfFilename(Document $document): string
    {
        return 'Ankauf_' . $document->getDocNumber() . '.pdf';
    }

    private function addReceiptContent(TCPDF $pdf, Document $document, Receipt $receipt, Branch $branch): void
    {
        $currentY = 15;

        // Header with Logo and Branch Info
        $currentY = $this->addHeader($pdf, $branch, $currentY);
        
        // Document Title and Number
        $currentY = $this->addReceiptTitle($pdf, $document, $currentY);
        
        // Skip customer information for receipts
        
        // Receipt Items Table
        $currentY = $this->addReceiptItemsTable($pdf, $receipt, $currentY);
        
        // Total Amount
        $currentY = $this->addReceiptTotal($pdf, $receipt, $currentY);
        
        // Footer Information
        $this->addReceiptFooter($pdf, $branch, $document);
    }

    private function addReceiptTitle(TCPDF $pdf, Document $document, float $currentY): float
    {
        // Line separator
        $pdf->SetLineWidth(0.3);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(20, $currentY, 190, $currentY);
        
        $currentY += 10;

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 8, 'QUITTUNG', 0, 1, 'L');

        $currentY += 12;

        // Document number and date
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(60, 6, 'Nummer:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $document->getDocNumber(), 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY + 6);
        $pdf->Cell(60, 6, 'Datum:', 0, 0, 'L');
        $pdf->Cell(0, 6, $document->getCreatedAt()->format('d.m.Y'), 0, 1, 'L');

        return $currentY + 20;
    }

    private function addReceiptItemsTable(TCPDF $pdf, Receipt $receipt, float $currentY): float
    {
        // Section title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Verkaufte Produkte/Dienstleistungen', 0, 1, 'L');
        
        $currentY += 10;

        // Table headers
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(80, 8, 'Beschreibung', 1, 0, 'L', true);
        $pdf->Cell(20, 8, 'Menge', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Einzelpreis', 1, 0, 'R', true);
        $pdf->Cell(35, 8, 'Summe', 1, 1, 'R', true);
        
        $currentY += 8;

        // Table items
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        
        foreach ($receipt->getItems() as $item) {
            // Check if we need a new page
            if ($currentY > 250) {
                $pdf->AddPage();
                $currentY = 15;
            }
            
            $pdf->SetXY(20, $currentY);
            
            // Description (with text wrapping if needed)
            $description = $item->getItemDescription();
            $descriptionHeight = max(8, $pdf->getStringHeight(80, $description));
            
            $pdf->MultiCell(80, $descriptionHeight, $description, 1, 'L', false);
            
            // Quantity
            $pdf->SetXY(100, $currentY);
            $pdf->Cell(20, $descriptionHeight, (string)$item->getQuantity(), 1, 0, 'C');
            
            // Unit price
            $pdf->SetXY(120, $currentY);
            $pdf->Cell(35, $descriptionHeight, $item->getFormattedUnitPrice(), 1, 0, 'R');
            
            // Line total
            $pdf->SetXY(155, $currentY);
            $pdf->Cell(35, $descriptionHeight, $item->getFormattedLineTotal(), 1, 0, 'R');
            
            $currentY += $descriptionHeight;
        }

        return $currentY + 10;
    }

    private function addReceiptTotal(TCPDF $pdf, Receipt $receipt, float $currentY): float
    {
        // Check if total fits on current page
        $totalHeight = 15;
        $pageHeight = $pdf->getPageHeight();
        $bottomMargin = 25;
        
        if (($currentY + $totalHeight + 10) > ($pageHeight - $bottomMargin)) {
            $pdf->AddPage();
            $currentY = 15; // Reset to top margin
        }
        
        // Total box - Green color #2d8f3e (45, 143, 62)
        $pdf->SetFillColor(45, 143, 62);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Rect(20, $currentY, 170, $totalHeight, 'F');
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(25, $currentY + 4);
        $pdf->Cell(80, 8, 'Gesamtbetrag:', 0, 0, 'L');
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(110, $currentY + 4);
        $pdf->Cell(75, 8, $receipt->getFormattedTotal(), 0, 0, 'R');
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);

        return $currentY + 25;
    }

    private function addReceiptFooter(TCPDF $pdf, Branch $branch, Document $document): void
    {
        // Add some space before footer
        $currentY = $pdf->GetY() + 20;
        
        // Simple footer without complex positioning
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        
        $pdf->Cell(0, 6, 'Vielen Dank für Ihren Einkauf bei ' . $branch->getName() . '!', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Alle Preise verstehen sich in CHF inkl. MwSt.', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Erstellt am ' . $document->getCreatedAt()->format('d.m.Y H:i') . ' • ' . $branch->getName(), 0, 1, 'C');
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);
    }

    private function addReceiptCustomerInfo(TCPDF $pdf, Document $document, float $currentY): float
    {
        // Only show customer info if phone or email is provided
        if (!$document->getCustomerPhone() && !$document->getCustomerEmail()) {
            return $currentY;
        }

        // Section title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Kundeninformationen', 0, 1, 'L');
        
        $currentY += 8;

        // Customer details (only show non-empty fields)
        $pdf->SetFont('helvetica', '', 10);
        
        if ($document->getCustomerPhone()) {
            $pdf->SetXY(20, $currentY);
            $pdf->Cell(0, 5, 'Tel: ' . $document->getCustomerPhone(), 0, 1, 'L');
            $currentY += 5;
        }

        if ($document->getCustomerEmail()) {
            $pdf->SetXY(20, $currentY);
            $pdf->Cell(0, 5, 'E-Mail: ' . $document->getCustomerEmail(), 0, 1, 'L');
            $currentY += 5;
        }

        return $currentY + 10;
    }

    private function addPurchaseContent(TCPDF $pdf, Document $document, Purchase $purchase, Branch $branch): void
    {
        $currentY = 15;

        // Header with Logo and Branch Info
        $currentY = $this->addHeader($pdf, $branch, $currentY);

        // Document Title and Number
        $currentY = $this->addPurchaseTitle($pdf, $document, $currentY);

        // Seller (Customer) Information
        $currentY = $this->addSellerInfo($pdf, $document, $purchase, $currentY);

        // Device Information
        $currentY = $this->addDeviceInfo($pdf, $purchase, $currentY);

        // Purchase Price
        $currentY = $this->addPurchasePrice($pdf, $purchase, $currentY);

        // Legal Notice with Signature Lines
        $currentY = $this->addPurchaseLegalNotice($pdf, $currentY);

        // ID Document Images
        $currentY = $this->addIdDocumentImages($pdf, $purchase, $currentY);

        // No footer needed for purchase documents
    }

    private function addPurchaseTitle(TCPDF $pdf, Document $document, float $currentY): float
    {
        // Line separator
        $pdf->SetLineWidth(0.3);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(20, $currentY, 190, $currentY);

        $currentY += 10;

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 8, 'ANKAUFSBELEG', 0, 1, 'L');

        $currentY += 12;

        // Document number and date
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(60, 6, 'Beleg-Nummer:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $document->getDocNumber(), 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY + 6);
        $pdf->Cell(60, 6, 'Datum:', 0, 0, 'L');
        $pdf->Cell(0, 6, $document->getCreatedAt()->format('d.m.Y'), 0, 1, 'L');

        return $currentY + 20;
    }

    private function addSellerInfo(TCPDF $pdf, Document $document, Purchase $purchase, float $currentY): float
    {
        // Section title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Verkäufer', 0, 1, 'L');

        $currentY += 8;

        // Seller details
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, $document->getCustomerName(), 0, 1, 'L');
        $currentY += 6;

        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 5, $purchase->getSellerAddress(), 0, 1, 'L');
        $currentY += 5;

        if ($document->getCustomerPhone()) {
            $pdf->SetXY(20, $currentY);
            $pdf->Cell(0, 5, 'Tel: ' . $document->getCustomerPhone(), 0, 1, 'L');
            $currentY += 5;
        }

        if ($document->getCustomerEmail()) {
            $pdf->SetXY(20, $currentY);
            $pdf->Cell(0, 5, 'E-Mail: ' . $document->getCustomerEmail(), 0, 1, 'L');
            $currentY += 5;
        }

        return $currentY + 10;
    }

    private function addDeviceInfo(TCPDF $pdf, Purchase $purchase, float $currentY): float
    {
        // Section title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Angekauftes Gerät', 0, 1, 'L');

        $currentY += 10;

        // Device information in a styled box
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetDrawColor(229, 231, 235);

        // Calculate box height based on content
        $boxHeight = 50;
        if ($purchase->getDeviceCondition() || $purchase->getAccessories()) {
            $boxHeight = 70;
        }

        $pdf->Rect(20, $currentY, 170, $boxHeight, 'DF', array(), array(248, 249, 250));

        $contentY = $currentY + 5;

        // Device type and model
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(25, $contentY);
        $pdf->Cell(0, 6, $purchase->getDeviceFullName(), 0, 1, 'L');
        $contentY += 8;

        $pdf->SetFont('helvetica', '', 10);

        // Device type
        $pdf->SetXY(25, $contentY);
        $pdf->Cell(60, 5, 'Gerätetyp:', 0, 0, 'L');
        $pdf->Cell(0, 5, $purchase->getDeviceTypeName(), 0, 1, 'L');
        $contentY += 5;

        // IMEI
        if ($purchase->getImei()) {
            $pdf->SetXY(25, $contentY);
            $pdf->Cell(60, 5, 'IMEI:', 0, 0, 'L');
            $pdf->SetFont('courier', '', 10);
            $pdf->Cell(0, 5, $purchase->getImei(), 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $contentY += 5;
        }

        // Serial Number
        if ($purchase->getSerialNumber()) {
            $pdf->SetXY(25, $contentY);
            $pdf->Cell(60, 5, 'Seriennummer:', 0, 0, 'L');
            $pdf->SetFont('courier', '', 10);
            $pdf->Cell(0, 5, $purchase->getSerialNumber(), 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $contentY += 5;
        }

        // Condition
        if ($purchase->getDeviceCondition()) {
            $contentY += 3;
            $pdf->SetXY(25, $contentY);
            $pdf->Cell(60, 5, 'Zustand:', 0, 0, 'L');
            $conditionText = substr($purchase->getDeviceCondition(), 0, 80);
            if (strlen($purchase->getDeviceCondition()) > 80) {
                $conditionText .= '...';
            }
            $pdf->MultiCell(100, 5, $conditionText, 0, 'L');
            $contentY = $pdf->GetY();
        }

        // Accessories
        if ($purchase->getAccessories()) {
            $contentY += 3;
            $pdf->SetXY(25, $contentY);
            $pdf->Cell(60, 5, 'Zubehör:', 0, 0, 'L');
            $accessoriesText = substr($purchase->getAccessories(), 0, 80);
            if (strlen($purchase->getAccessories()) > 80) {
                $accessoriesText .= '...';
            }
            $pdf->MultiCell(100, 5, $accessoriesText, 0, 'L');
        }

        $currentY += $boxHeight + 10;

        return $currentY;
    }

    private function addPurchasePrice(TCPDF $pdf, Purchase $purchase, float $currentY): float
    {
        $priceBoxHeight = 15;

        // Price box - Green color #10b981 (emerald-500)
        $pdf->SetFillColor(16, 185, 129);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Rect(20, $currentY, 170, $priceBoxHeight, 'F');

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(25, $currentY + 4);
        $pdf->Cell(80, 8, 'Ankaufspreis:', 0, 0, 'L');

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(110, $currentY + 4);
        $pdf->Cell(75, 8, $purchase->getFormattedPrice(), 0, 0, 'R');

        // Reset text color
        $pdf->SetTextColor(0, 0, 0);

        return $currentY + 20;
    }

    private function addPurchaseLegalNotice(TCPDF $pdf, float $currentY): float
    {
        $currentY += 5;

        // Section title
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Bestätigung', 0, 1, 'L');

        $currentY += 8;

        // Legal text - kompakter
        $pdf->SetFont('helvetica', '', 8);
        $legalText = "Mit meiner Unterschrift bestätige ich:\n";
        $legalText .= "• Dass ich der rechtmässige Eigentümer des oben genannten Geräts bin\n";
        $legalText .= "• Dass das Gerät nicht gestohlen ist und keine Rechte Dritter verletzt\n";
        $legalText .= "• Dass ich den vereinbarten Ankaufspreis vollständig erhalten habe\n";
        $legalText .= "• Dass ich meine Personalien wahrheitsgemäss angegeben habe";

        $pdf->SetXY(20, $currentY);
        $pdf->MultiCell(170, 3.5, $legalText, 0, 'L', false);

        $currentY = $pdf->GetY() + 8;

        // Signature lines
        $pdf->SetLineWidth(0.3);
        $pdf->SetDrawColor(0, 0, 0);

        // Verkäufer Unterschrift
        $pdf->Line(20, $currentY, 90, $currentY);
        $pdf->SetXY(20, $currentY + 2);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(70, 4, 'Unterschrift Verkäufer', 0, 0, 'L');

        // Datum
        $pdf->SetXY(95, $currentY + 2);
        $pdf->Cell(25, 4, 'Datum', 0, 0, 'L');

        // Mediahuus Unterschrift
        $pdf->Line(125, $currentY, 190, $currentY);
        $pdf->SetXY(125, $currentY + 2);
        $pdf->Cell(65, 4, 'Unterschrift Mediahuus', 0, 0, 'L');

        return $currentY + 10;
    }

    private function addIdDocumentImages(TCPDF $pdf, Purchase $purchase, float $currentY): float
    {
        $currentY += 5;

        $uploadDir = __DIR__ . '/../../public/uploads/id_documents';
        $imageWidth = 80; // Width of each image
        $imageX = 20;
        $estimatedImageHeight = 60; // Estimated height needed for image

        // Check if we have enough space for title + at least one image
        // If not, start on a new page
        if ($currentY + $estimatedImageHeight + 15 > 270) {
            $pdf->AddPage();
            $currentY = 15;
        }

        // Section title - now on same page as images
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(20, $currentY);
        $pdf->Cell(0, 6, 'Ausweis-Dokumentation', 0, 1, 'L');

        $currentY += 8;

        // Front ID Document
        $frontPath = $uploadDir . '/' . $purchase->getIdDocumentFront();
        if (file_exists($frontPath)) {
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetXY($imageX, $currentY);
            $pdf->Cell($imageWidth, 4, 'Vorderseite / Pass', 0, 1, 'L');

            try {
                $pdf->Image($frontPath, $imageX, $currentY + 5, $imageWidth, 0, '', '', '', false, 300, '', false, false, 1);
                $currentY += 55; // Estimated height for image + spacing
            } catch (\Exception $e) {
                $this->logger->warning('Failed to add front ID image to PDF', ['error' => $e->getMessage()]);
            }
        }

        // Back ID Document (if exists)
        if ($purchase->getIdDocumentBack()) {
            $backPath = $uploadDir . '/' . $purchase->getIdDocumentBack();
            if (file_exists($backPath)) {
                // Check if we need a new page
                if ($currentY + 60 > 270) {
                    $pdf->AddPage();
                    $currentY = 15;
                } else {
                    $currentY += 5;
                }

                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetXY($imageX, $currentY);
                $pdf->Cell($imageWidth, 4, 'Rückseite', 0, 1, 'L');

                try {
                    $pdf->Image($backPath, $imageX, $currentY + 5, $imageWidth, 0, '', '', '', false, 300, '', false, false, 1);
                    $currentY += 55;
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to add back ID image to PDF', ['error' => $e->getMessage()]);
                }
            }
        }

        return $currentY + 5;
    }

    private function addPurchaseFooter(TCPDF $pdf, Branch $branch, Document $document, float $currentY): void
    {
        // Add space before footer - use passed currentY position
        $currentY += 10;

        // Check if we need a new page for footer
        if ($currentY + 20 > 270) {
            $pdf->AddPage();
            $currentY = 15;
        }

        // Simple footer
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);

        $pdf->Cell(0, 5, 'Vielen Dank für Ihren Verkauf an ' . $branch->getName() . '!', 0, 1, 'C');
        $pdf->Cell(0, 4, 'Alle Preise verstehen sich in CHF.', 0, 1, 'C');
        $pdf->Cell(0, 4, 'Erstellt am ' . $document->getCreatedAt()->format('d.m.Y H:i') . ' • ' . $branch->getName(), 0, 1, 'C');

        // Reset text color
        $pdf->SetTextColor(0, 0, 0);
    }

    private function convertHtmlToText(string $html): string
    {
        // Replace HTML formatting with text equivalents
        $text = $html;

        // Convert line breaks
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $text);
        $text = str_replace('</p>', "\n\n", $text);

        // Convert lists with bullet points
        $text = preg_replace('/<li[^>]*>/i', '• ', $text);
        $text = str_replace('</li>', "\n", $text);
        $text = preg_replace('/<\/?(ul|ol)[^>]*>/i', "\n", $text);

        // Remove other HTML tags but keep their content
        $text = strip_tags($text);

        // Clean up extra whitespace and line breaks
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text); // Max 2 consecutive line breaks
        $text = trim($text);

        return $text;
    }
}