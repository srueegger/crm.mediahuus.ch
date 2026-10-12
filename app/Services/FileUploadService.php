<?php
declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;
use RuntimeException;

class FileUploadService
{
    private string $uploadDir;
    private LoggerInterface $logger;
    // Formate, die TCPDF direkt einbetten kann und unverändert gespeichert werden.
    // Die Endung kommt nie aus Client-Angaben, sondern immer aus dem erkannten Inhalt.
    private array $extensionsByMimeType = [
        'image/jpeg' => 'jpeg',
        'image/jpg' => 'jpeg',
        'image/png' => 'png',
    ];
    // Alle übrigen Bildformate (HEIC, WebP, AVIF, TIFF, ...) und PDF werden mit Imagick
    // in JPEG umgewandelt. SVG ist bewusst ausgeschlossen (aktiver Inhalt).
    private array $blockedMimeTypes = [
        'image/svg+xml',
    ];
    private int $maxFileSize = 10485760; // 10MB
    private int $maxImageDimension = 2400;

    public function __construct(string $uploadDir, LoggerInterface $logger)
    {
        $this->uploadDir = rtrim($uploadDir, '/');
        $this->logger = $logger;

        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                throw new RuntimeException("Failed to create upload directory: {$this->uploadDir}");
            }
        }
    }

    /**
     * Handle base64 encoded upload (camera capture or file picker, images and PDF)
     */
    public function handleBase64Upload(string $base64Data, string $prefix = 'id'): string
    {
        // Remove data URI prefix if present (the declared type is ignored, content is sniffed below)
        if (preg_match('/^data:[^,]*;base64,/i', $base64Data)) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        }

        $imageData = base64_decode($base64Data, true);

        if ($imageData === false) {
            throw new RuntimeException('Die Datei konnte nicht übertragen werden. Bitte erneut versuchen.');
        }

        // Validate file size
        if (strlen($imageData) > $this->maxFileSize) {
            throw new RuntimeException('Die Datei ist zu gross (maximal 10 MB).');
        }

        [$imageData, $extension, $mimeType] = $this->normalizeImage($imageData);

        // Generate unique filename
        $filename = $this->generateUniqueFilename($prefix, $extension);
        $filepath = $this->uploadDir . '/' . $filename;

        // Save file
        if (file_put_contents($filepath, $imageData) === false) {
            throw new RuntimeException('Failed to save uploaded file');
        }

        $this->logger->info('File uploaded successfully', [
            'filename' => $filename,
            'size' => strlen($imageData),
            'mime_type' => $mimeType
        ]);

        return $filename;
    }

    /**
     * Handle traditional file upload
     */
    public function handleFileUpload(array $file, string $prefix = 'id'): string
    {
        // Validate upload errors
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid file upload');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('File size exceeds maximum allowed size');
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file was uploaded');
            default:
                throw new RuntimeException('File upload error');
        }

        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            throw new RuntimeException('File size exceeds maximum allowed size of 10MB');
        }

        $imageData = file_get_contents($file['tmp_name']);
        if ($imageData === false) {
            throw new RuntimeException('Failed to read uploaded file');
        }

        [$imageData, $extension, $mimeType] = $this->normalizeImage($imageData);

        // Generate unique filename
        $filename = $this->generateUniqueFilename($prefix, $extension);
        $filepath = $this->uploadDir . '/' . $filename;

        if (file_put_contents($filepath, $imageData) === false) {
            throw new RuntimeException('Failed to save uploaded file');
        }

        $this->logger->info('File uploaded successfully', [
            'filename' => $filename,
            'size' => strlen($imageData),
            'mime_type' => $mimeType
        ]);

        return $filename;
    }

    /**
     * Delete an uploaded file
     */
    public function deleteFile(string $filename): bool
    {
        $filepath = $this->uploadDir . '/' . $filename;

        if (!file_exists($filepath)) {
            return false;
        }

        $result = unlink($filepath);

        if ($result) {
            $this->logger->info('File deleted successfully', ['filename' => $filename]);
        }

        return $result;
    }

    /**
     * Get full path to uploaded file
     */
    public function getFilePath(string $filename): string
    {
        return $this->uploadDir . '/' . $filename;
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $filename): bool
    {
        return file_exists($this->uploadDir . '/' . $filename);
    }

    /**
     * Validate the uploaded content and convert it into a format TCPDF can embed.
     *
     * @return array{0: string, 1: string, 2: string} [data, extension, detected MIME type]
     */
    private function normalizeImage(string $data): array
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->buffer($data);

        if (isset($this->extensionsByMimeType[$mimeType])) {
            return [$data, $this->extensionsByMimeType[$mimeType], $mimeType];
        }

        $isConvertible = (str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf')
            && !in_array($mimeType, $this->blockedMimeTypes, true);

        if (!$isConvertible) {
            throw new RuntimeException('Ungültiger Dateityp. Erlaubt sind Bilder (z. B. JPEG, PNG, HEIC, WebP) und PDF.');
        }

        return [$this->convertToJpeg($data, $mimeType), 'jpeg', $mimeType];
    }

    /**
     * Convert any image format supported by Imagick (or the first page of a PDF) to JPEG
     */
    private function convertToJpeg(string $data, string $mimeType): string
    {
        if (!class_exists(\Imagick::class)) {
            throw new RuntimeException('Dieses Dateiformat kann auf dem Server nicht verarbeitet werden. Bitte JPEG oder PNG verwenden.');
        }

        try {
            $imagick = new \Imagick();
            if ($mimeType === 'application/pdf') {
                // Resolution must be set before reading, otherwise PDFs are rasterized at 72 dpi
                $imagick->setResolution(200, 200);
            }
            $imagick->readImageBlob($data);

            // Multi-frame sources (PDF pages, animated GIF, HEIC sequences): use the first frame
            $imagick->setIteratorIndex(0);
            $image = $imagick->getImage();
            $imagick->clear();

            if (method_exists($image, 'autoOrient')) {
                $image->autoOrient();
            }

            // Flatten transparency onto white (JPEG has no alpha channel)
            $image->setImageBackgroundColor('white');
            $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            $image->transformImageColorspace(\Imagick::COLORSPACE_SRGB);

            if ($image->getImageWidth() > $this->maxImageDimension || $image->getImageHeight() > $this->maxImageDimension) {
                $image->thumbnailImage($this->maxImageDimension, $this->maxImageDimension, true);
            }

            $image->stripImage();
            $image->setImageFormat('jpeg');
            $image->setImageCompressionQuality(85);

            $jpeg = $image->getImageBlob();
            $image->clear();
        } catch (\ImagickException $e) {
            $this->logger->warning('Image conversion failed', [
                'mime_type' => $mimeType,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Die Datei konnte nicht gelesen werden. Bitte ein anderes Bild oder PDF verwenden.');
        }

        $this->logger->info('Image converted to JPEG', [
            'source_mime_type' => $mimeType,
            'size' => strlen($jpeg),
        ]);

        return $jpeg;
    }

    /**
     * Generate unique filename with timestamp and random string
     */
    private function generateUniqueFilename(string $prefix, string $extension): string
    {
        $timestamp = date('Ymd_His');
        $random = bin2hex(random_bytes(8));
        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }
}
