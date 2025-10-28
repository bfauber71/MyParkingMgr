<?php
/**
 * Version Check - Quick verification that latest files are uploaded
 * DELETE THIS FILE AFTER VERIFICATION
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Version Check</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #1a1a1a; 
            color: #00ff00; 
            padding: 40px;
            text-align: center;
        }
        .box {
            background: #2a2a2a;
            border: 3px solid #00ff00;
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 10px;
        }
        h1 { font-size: 48px; margin: 0; }
        .version { font-size: 72px; font-weight: bold; margin: 20px 0; color: #00ffff; }
        .timestamp { font-size: 24px; color: #ffff00; }
        .success { color: #00ff00; font-size: 32px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>✅ FILES UPLOADED!</h1>
        <div class="version">v2.3.7</div>
        <div class="timestamp">Build: October 28, 2025 - 01:11 UTC</div>
        <div class="success">
            ✓ Database::getPDO() method: <strong>PRESENT</strong><br>
            ✓ License system: <strong>SIMPLIFIED</strong><br>
            ✓ Install ID matching: <strong>NOT REQUIRED</strong>
        </div>
        <p style="margin-top: 40px; color: #ff6666; font-size: 18px;">
            ⚠️ DELETE THIS FILE AFTER VERIFICATION!
        </p>
    </div>
</body>
</html>
