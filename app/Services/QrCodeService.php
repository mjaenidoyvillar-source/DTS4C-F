<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Generate a QR code for a document
     * 
     * @param int $documentId
     * @return string Path to the QR code image
     */
    public function generateForDocument(int $documentId): string
    {
        // Generate unique QR code identifier
        $qrCode = Str::random(32) . '_' . $documentId;
        
        // Create QR code data (URL to track the document)
        $trackingUrl = url("/api/documents/track/{$qrCode}");
        
        // For now, we'll store the QR code identifier
        // In production, you would use a QR code library like simplesoftwareio/simple-qrcode
        // or endroid/qr-code to generate the actual image
        
        // Store QR code data
        $qrPath = "qr-codes/{$qrCode}.txt";
        Storage::disk('public')->put($qrPath, $trackingUrl);
        
        return $qrPath;
    }

    /**
     * Get QR code tracking URL from QR code identifier
     * 
     * @param string $qrCode
     * @return string|null
     */
    public function getTrackingUrl(string $qrCode): ?string
    {
        $qrPath = "qr-codes/{$qrCode}.txt";
        
        if (Storage::disk('public')->exists($qrPath)) {
            return Storage::disk('public')->get($qrPath);
        }
        
        return null;
    }
}

