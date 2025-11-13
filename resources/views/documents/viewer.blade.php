<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - {{ $document->title ?? 'Document' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .viewer-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
        }
        .document-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .details-section {
            margin-bottom: 30px;
        }
        .detail-row {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 4px;
        }
        .detail-value {
            color: #212529;
        }
        .description-box {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .file-info-box {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .download-btn-large {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="viewer-container">
            <div class="document-header">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="mb-2">{{ $document->title ?? 'Document' }}</h2>
                        <p class="text-muted mb-0">
                            <i class="bi bi-file-earmark"></i> Document Code: #{{ $document->id }}
                        </p>
                    </div>
                    <div>
                        <a href="javascript:window.close()" class="btn btn-secondary me-2">
                            <i class="bi bi-x-circle"></i> Close
                        </a>
                        @if($document->file_data)
                        <a href="{{ route('documents.file', $document) }}" class="btn btn-primary download-btn-large" download>
                            <i class="bi bi-download"></i> Download File
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="details-section">
                <h4 class="mb-4"><i class="bi bi-info-circle"></i> Document Details</h4>
                
                <!-- QR Code Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h5 class="card-title"><i class="bi bi-qr-code"></i> Document QR Code</h5>
                                <p class="text-muted small mb-3">Scan this QR code to quickly access document details</p>
                                <canvas id="qr-code-canvas" class="mb-3"></canvas>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="downloadQRCode()">
                                        <i class="bi bi-download"></i> Download QR Code
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="detail-label">Document Type</div>
                            <div class="detail-value">{{ $document->document_type ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="badge bg-primary">{{ $document->current_status ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="detail-label">Purpose</div>
                            <div class="detail-value">{{ $document->purpose ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="detail-label">Created Date</div>
                            <div class="detail-value">{{ $document->created_at->format('Y-m-d H:i:s') ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="detail-label">Department</div>
                            <div class="detail-value">{{ $document->department->name ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="detail-label">Receiving Department</div>
                            <div class="detail-value">{{ $receivingDept->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                <div class="detail-row mt-3">
                    <div class="detail-label">Description</div>
                    <div class="description-box">
                        {{ $document->description ?? 'No description provided.' }}
                    </div>
                </div>
            </div>

            @if($document->file_data)
            <div class="file-info-box">
                <h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> File Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="detail-label">File Name</div>
                            <div class="detail-value">{{ $document->file_name ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="detail-label">File Type</div>
                            <div class="detail-value">{{ $document->file_mime ?? '—' }}</div>
                        </div>
                    </div>
                </div>
                @if($document->file_size)
                <div class="detail-row">
                    <div class="detail-label">File Size</div>
                    <div class="detail-value">
                        @if($document->file_size < 1024)
                            {{ $document->file_size }} bytes
                        @elseif($document->file_size < 1048576)
                            {{ number_format($document->file_size / 1024, 2) }} KB
                        @else
                            {{ number_format($document->file_size / 1048576, 2) }} MB
                        @endif
                    </div>
                </div>
                @endif
                <div class="mt-4 text-center">
                    <a href="{{ route('documents.file', $document) }}" class="btn btn-primary btn-lg download-btn-large" download>
                        <i class="bi bi-download me-2"></i>Download File to View
                    </a>
                    <p class="text-muted mt-2 mb-0">Download the file to your device to view its contents</p>
                </div>
            </div>
            @else
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> No file attached to this document.
            </div>
            @endif
        </div>
    </div>

    <!-- QR Code Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="{{ asset('js/qrcode-handler.js') }}"></script>
    
    <script>
        // Generate QR code when page loads
        document.addEventListener('DOMContentLoaded', async function() {
            const documentId = {{ $document->id }};
            const canvas = document.getElementById('qr-code-canvas');
            
            if (canvas && window.QRCodeHandler) {
                await window.QRCodeHandler.generateQRCode(documentId, canvas, 250);
            }
        });

        // Download QR code as image
        function downloadQRCode() {
            const canvas = document.getElementById('qr-code-canvas');
            if (!canvas) return;
            
            const link = document.createElement('a');
            link.download = 'document-{{ $document->id }}-qr-code.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }
    </script>
</body>
</html>
