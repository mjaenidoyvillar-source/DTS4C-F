// QR Code Generation and Scanning Handler
// Uses CDN libraries: qrcode.js and html5-qrcode

class QRCodeHandler {
    constructor() {
        this.html5QrCode = null;
        this.isScanning = false;
    }

    /**
     * Generate QR code for a document ID
     * @param {number} documentId - The document ID
     * @param {HTMLElement} canvasElement - Canvas element to render QR code
     * @param {number} size - Size of QR code (default: 200)
     */
    async generateQRCode(documentId, canvasElement, size = 200) {
        try {
            if (typeof QRCode === 'undefined') {
                console.error('QRCode library not loaded');
                return false;
            }
            // Use absolute URL from config or fallback to window.location.origin
            // This ensures QR codes work when deployed to production
            const baseUrl = window.APP_URL || window.location.origin;
            const qrUrl = `${baseUrl}/documents/${documentId}/details`;
            await QRCode.toCanvas(canvasElement, qrUrl, {
                width: size,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            });
            return true;
        } catch (error) {
            console.error('Error generating QR code:', error);
            return false;
        }
    }

    /**
     * Generate QR code as data URL (for img src)
     * @param {number} documentId - The document ID
     * @param {number} size - Size of QR code (default: 200)
     * @returns {Promise<string>} Data URL of QR code image
     */
    async generateQRCodeDataURL(documentId, size = 200) {
        try {
            if (typeof QRCode === 'undefined') {
                console.error('QRCode library not loaded');
                return null;
            }
            // Use absolute URL from config or fallback to window.location.origin
            const baseUrl = window.APP_URL || window.location.origin;
            const qrUrl = `${baseUrl}/documents/${documentId}/details`;
            const dataUrl = await QRCode.toDataURL(qrUrl, {
                width: size,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            });
            return dataUrl;
        } catch (error) {
            console.error('Error generating QR code:', error);
            return null;
        }
    }

    /**
     * Start QR code scanning
     * @param {string} scanElementId - ID of element to show camera preview
     * @param {Function} onScanSuccess - Callback when QR code is scanned
     * @param {Function} onScanError - Callback for scan errors
     */
    async startScanning(scanElementId, onScanSuccess, onScanError) {
        if (this.isScanning) {
            console.warn('Scanning already in progress');
            return;
        }

        if (typeof Html5Qrcode === 'undefined') {
            if (onScanError) {
                onScanError('QR Scanner library not loaded. Please refresh the page.');
            }
            return;
        }

        try {
            this.html5QrCode = new Html5Qrcode(scanElementId);
            this.isScanning = true;

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            await this.html5QrCode.start(
                { facingMode: "environment" }, // Use back camera
                config,
                (decodedText, decodedResult) => {
                    // Success callback
                    if (onScanSuccess) {
                        onScanSuccess(decodedText, decodedResult);
                    }
                },
                (errorMessage) => {
                    // Error callback - ignore if scanning is stopped
                    if (this.isScanning && onScanError && errorMessage) {
                        // Only show errors that aren't just "not found" messages
                        if (!errorMessage.includes('No QR code found')) {
                            // onScanError(errorMessage);
                        }
                    }
                }
            );
        } catch (error) {
            this.isScanning = false;
            console.error('Error starting QR scanner:', error);
            
            // Provide user-friendly error messages
            let errorMessage = 'Failed to start camera. ';
            const errorMsg = error?.message || '';
            const errorName = error?.name || '';
            
            if (errorName === 'NotAllowedError' || errorMsg.includes('Permission dismissed')) {
                errorMessage += 'Camera permission was denied. Please allow camera access in your browser settings and try again.';
            } else if (errorName === 'NotFoundError') {
                errorMessage += 'No camera found. Please ensure your device has a camera.';
            } else if (errorName === 'NotReadableError') {
                errorMessage += 'Camera is already in use by another application.';
            } else {
                errorMessage += errorMsg || 'Unknown error occurred.';
            }
            
            if (onScanError) {
                onScanError(errorMessage);
            }
        }
    }

    /**
     * Stop QR code scanning
     */
    async stopScanning() {
        if (!this.isScanning || !this.html5QrCode) {
            // Scanner is not running, nothing to stop
            return;
        }

        try {
            await this.html5QrCode.stop();
            this.html5QrCode.clear();
            this.html5QrCode = null;
            this.isScanning = false;
        } catch (error) {
            // If scanner is already stopped or paused, just reset state
            const errorMsg = error?.message || '';
            if (errorMsg.includes('not running') || errorMsg.includes('not paused')) {
                // Scanner was already stopped, just reset state
                this.html5QrCode = null;
                this.isScanning = false;
            } else {
                console.error('Error stopping QR scanner:', error);
            }
        }
    }

    /**
     * Extract document ID from scanned QR code URL
     * @param {string} qrUrl - The scanned QR code URL
     * @returns {number|null} Document ID or null if invalid
     */
    extractDocumentId(qrUrl) {
        try {
            // Match pattern: /documents/{id}/details or /documents/{id}/qr
            // Handles both absolute URLs (http://domain.com/documents/123/details) and relative URLs (/documents/123/details)
            const match = qrUrl.match(/\/documents\/(\d+)\/(?:details|qr|view)/);
            if (match && match[1]) {
                return parseInt(match[1], 10);
            }
            return null;
        } catch (error) {
            console.error('Error extracting document ID:', error);
            return null;
        }
    }
}

// Create global instance
window.QRCodeHandler = new QRCodeHandler();
