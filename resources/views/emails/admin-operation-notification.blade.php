<!DOCTYPE html>
<html>
<head>
    <title>MW DirectAlert Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            text-align: center;
        }
        .header.failed {
            background-color: #f44336;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .detail-row {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
        }
        .error {
            color: #f44336;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $wasSuccessful ? '' : 'failed' }}">
            <h2>{{ ucfirst($operation) }} {{ $wasSuccessful ? 'Completed' : 'Failed' }}</h2>
        </div>
        <div class="content">
            <div class="detail-row">
                <span class="label">Operation:</span> {{ ucfirst($operation) }}
            </div>
            @if($userName)
            <div class="detail-row">
                <span class="label">User:</span> {{ $userName }}
            </div>
            @endif
            <div class="detail-row">
                <span class="label">Status:</span> {{ $wasSuccessful ? 'Success' : 'Failed' }}
            </div>
            @if($operation === 'export')
            <div class="detail-row">
                <span class="label">Records Exported:</span> {{ $recordsAffected }}
            </div>
            @elseif($operation === 'import')
            <div class="detail-row">
                <span class="label">Records Imported:</span> {{ $recordsAffected }}
            </div>
            @if($recordsSkipped > 0)
            <div class="detail-row">
                <span class="label">Records Skipped (duplicates):</span> {{ $recordsSkipped }}
            </div>
            @endif
            @if($recordsFailed > 0)
            <div class="detail-row">
                <span class="label">Records Failed:</span> {{ $recordsFailed }}
            </div>
            @endif
            @endif
            <div class="detail-row">
                <span class="label">Time:</span> {{ date('Y-m-d H:i:s') }}
            </div>
            @if(!$wasSuccessful && $errorMessage)
            <div class="error">
                <strong>Error:</strong><br>
                {{ $errorMessage }}
            </div>
            @endif
        </div>
    </div>
</body>
</html>
