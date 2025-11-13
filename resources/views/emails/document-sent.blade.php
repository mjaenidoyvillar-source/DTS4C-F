<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Sent to You</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            border: 1px solid #e0e0e0;
        }
        .header {
            background-color: #FF9800;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin: -30px -30px 20px -30px;
        }
        .content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .document-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin: 15px 0;
            padding: 10px;
            background-color: #ecf0f1;
            border-left: 4px solid #FF9800;
        }
        .message {
            margin: 20px 0;
            padding: 15px;
            background-color: #fff3e0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">Document Sent to You</h1>
        </div>
        
        <div class="content">
            <p>Hello,</p>
            
            <p>A document has been sent to your department for processing.</p>
            
            <div class="document-title">
                Document: {{ $documentTitle }}
            </div>
            
            <div class="message">
                <p><strong>The document "{{ $documentTitle }}" has been sent to your department for processing.</strong></p>
                @if($senderName)
                <p>Sent by: <strong>{{ $senderName }}</strong></p>
                @endif
                <p>Please log in to the system to receive and process this document.</p>
            </div>
            
            <p>If you have any questions or concerns, please contact the system administrator.</p>
            
            <p>Best regards,<br>Document Tracking System</p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>

