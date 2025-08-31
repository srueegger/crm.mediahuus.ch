<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Estimate;
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

            // Set margins
            $pdf->SetMargins(20, 15, 20);
            $pdf->SetAutoPageBreak(true, 25);

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
        $pdf->Cell(0, 6, 'Schadens-/Fehlerbeschreibung', 0, 1, 'L');
        
        $currentY += 10;

        // Issue description in a box
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->Rect(20, $currentY, 170, 0, 'DF', array(), array(248, 249, 250));
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(25, $currentY + 5);
        
        // Calculate height needed for text
        $textHeight = $pdf->getStringHeight(165, $estimate->getIssueText());
        $boxHeight = max(20, $textHeight + 10);
        
        // Draw the box with proper height
        $pdf->Rect(20, $currentY, 170, $boxHeight, 'DF', array(), array(248, 249, 250));
        
        // Add text with proper line breaks
        $pdf->SetXY(25, $currentY + 5);
        $pdf->MultiCell(165, 5, $estimate->getIssueText(), 0, 'L', false);

        return $currentY + $boxHeight + 15;
    }

    private function addTotalPrice(TCPDF $pdf, Estimate $estimate, float $currentY): float
    {
        // Price box
        $pdf->SetFillColor(59, 130, 246);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Rect(20, $currentY, 170, 15, 'F');
        
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
        // Footer with additional info
        $pdf->SetY(-30);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        
        $pdf->Cell(0, 4, 'Dieser Kostenvoranschlag ist unverbindlich und 30 Tage gültig.', 0, 1, 'C');
        $pdf->Cell(0, 4, 'Alle Preise verstehen sich in CHF inkl. MwSt.', 0, 1, 'C');
        
        $pdf->SetY(-15);
        $pdf->Cell(0, 4, 'Erstellt am ' . $document->getCreatedAt()->format('d.m.Y H:i') . ' • ' . $branch->getName(), 0, 1, 'C');
    }

    public function getEstimatePdfFilename(Document $document): string
    {
        return 'Kostenvoranschlag_' . $document->getDocNumber() . '.pdf';
    }
}