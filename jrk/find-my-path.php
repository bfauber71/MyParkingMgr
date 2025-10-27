<?php
/**
 * MyParkingManager Path Diagnostic Tool
 * Upload this file to your server and access it in your browser
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Path Diagnostic - MyParkingManager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .result-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .highlight {
            background: yellow;
            padding: 2px 5px;
            font-weight: bold;
            color: black;
        }
        h1 { color: #333; }
        h2 { color: #0066cc; margin-top: 0; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <h1>🔍 MyParkingManager Path Diagnostic</h1>
    
    <div class="result-box">
        <h2>📂 Your Installation Path</h2>
        <?php
        // Get the current script path
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        $fullPath = $_SERVER['SCRIPT_FILENAME'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $httpHost = $_SERVER['HTTP_HOST'];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        
        // Extract directory path
        $directory = dirname($scriptPath);
        $basePath = $directory === '/' ? '' : $directory;
        
        echo "<p><strong>Full URL:</strong> <code>{$protocol}://{$httpHost}{$scriptPath}</code></p>";
        echo "<p><strong>Base Path:</strong> <code class='highlight'>{$basePath}</code></p>";
        echo "<p><strong>Document Root:</strong> <code>{$docRoot}</code></p>";
        echo "<p><strong>Full File Path:</strong> <code>{$fullPath}</code></p>";
        ?>
    </div>
    
    <div class="result-box">
        <h2>✅ Your Correct .htaccess File</h2>
        <p>Copy this entire text and save it as <strong>.htaccess</strong> in your installation folder:</p>
        <div class="code"># ManageMyParking - Apache Configuration
# This file enables URL rewriting for the application

&lt;IfModule mod_rewrite.c&gt;
    RewriteEngine On
    RewriteBase <?php echo $basePath === '' ? '/' : $basePath . '/'; ?>

    
    # Don't rewrite if already requesting index.php
    RewriteRule ^index\.php$ - [L]
    
    # Don't rewrite existing files or directories
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Route everything through index.php
    RewriteRule ^(.*)$ index.php [QSA,L]
&lt;/IfModule&gt;

# Security Headers
&lt;IfModule mod_headers.c&gt;
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    
    # Disable caching for JavaScript and HTML (force fresh load)
    &lt;FilesMatch "\.(js|html)$"&gt;
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires "0"
    &lt;/FilesMatch&gt;
&lt;/IfModule&gt;

# Disable directory browsing
Options -Indexes

# Protect sensitive files
&lt;FilesMatch "(^\.htaccess|^config\.php|\.sql$)"&gt;
    Require all denied
&lt;/FilesMatch&gt;</div>
    </div>
    
    <div class="result-box">
        <h2>📝 Instructions</h2>
        <ol>
            <li>Copy the .htaccess text above (from the gray box)</li>
            <li>Create a new file on your computer called <strong>.htaccess</strong></li>
            <li>Paste the text into that file</li>
            <li>Upload it to: <code class="highlight"><?php echo dirname($fullPath); ?></code></li>
            <li><strong>Overwrite</strong> your existing .htaccess file</li>
            <li>Try accessing your app again: <code><?php echo $protocol . '://' . $httpHost . $basePath; ?>/</code></li>
            <li><strong>Delete this diagnostic file</strong> after fixing: <code>find-my-path.php</code></li>
        </ol>
    </div>
    
    <div class="result-box">
        <h2>🧪 Test Your Fix</h2>
        <p>After uploading the new .htaccess, test these URLs:</p>
        <ul>
            <li><a href="<?php echo $basePath; ?>/api/app-config" target="_blank"><?php echo $basePath; ?>/api/app-config</a> - Should show JSON config</li>
            <li><a href="<?php echo $basePath; ?>/api/csrf-token" target="_blank"><?php echo $basePath; ?>/api/csrf-token</a> - Should show token JSON</li>
            <li><a href="<?php echo $basePath; ?>/" target="_blank"><?php echo $basePath; ?>/</a> - Should load your app</li>
        </ul>
    </div>
</body>
</html>
