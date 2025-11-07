<?php
declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;
use RuntimeException;

class FileUploadService
{
    private string $uploadDir;
    private LoggerInterface $logger;
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];
    private int $maxFileSize = 10485760; // 10MB

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
     * Handle base64 encoded image upload from mobile camera
     */
    public function handleBase64Upload(string $base64Data, string $prefix = 'id'): string
    {
        // Remove data URI prefix if present
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $imageType = $matches[1];
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        } else {
            $imageType = 'jpg'; // default
        }

        $imageData = base64_decode($base64Data);

        if ($imageData === false) {
            throw new RuntimeException('Invalid base64 image data');
        }

        // Validate file size
        if (strlen($imageData) > $this->maxFileSize) {
            throw new RuntimeException('File size exceeds maximum allowed size of 10MB');
        }

        // Validate image
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);

        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new RuntimeException('Invalid file type. Only JPEG, PNG, and WebP images are allowed');
        }

        // Generate unique filename
        $filename = $this->generateUniqueFilename($prefix, $imageType);
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

        // Validate MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new RuntimeException('Invalid file type. Only JPEG, PNG, and WebP images are allowed');
        }

        // Get file extension from original filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $extension = 'jpg'; // fallback
        }

        // Generate unique filename
        $filename = $this->generateUniqueFilename($prefix, $extension);
        $filepath = $this->uploadDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new RuntimeException('Failed to move uploaded file');
        }

        $this->logger->info('File uploaded successfully', [
            'filename' => $filename,
            'size' => $file['size'],
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
     * Generate unique filename with timestamp and random string
     */
    private function generateUniqueFilename(string $prefix, string $extension): string
    {
        $timestamp = date('Ymd_His');
        $random = bin2hex(random_bytes(8));
        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }
}
