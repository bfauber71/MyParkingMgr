<?php
/**
 * Force Session Reset Tool
 * This completely destroys all sessions and forces new cookie settings
 */

// Start with current session to destroy it
session_start();

// Get session name
$sessionName = session_name();

// Destroy session completely
$_SESSION = [];

// Delete the session cookie
if (isset($_COOKIE[$sessionName])) {
    setcookie($sessionName, '', time() - 3600, '/');
    setcookie($sessionName, '', time() - 3600, '/jrk/');
}

// Destroy session
session_destroy();

// Clear any other managemyparking cookies
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'managemyparking') !== false) {
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/jrk/');
    }
}

?><!DOCTYPE html>
<html>
<head>
    <title>Session Reset Complete</title>
    <style>
        body { font-family: Arial; background: #1a1a1a; color: #fff; padding: 40px; text-align: center; }
        .box { background: #2a2a2a; padding: 30px; margin: 20px auto; border-radius: 10px; max-width: 600px; }
        h1 { color: #10b981; }
        .btn { background: #3b82f6; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; font-size: 16px; }
        .btn:hover { background: #2563eb; }
        .instructions { text-align: left; margin: 20px 0; line-height: 1.8; }
        .step { background: #1a1a1a; padding: 10px; margin: 10px 0; border-left: 4px solid #10b981; }
    </style>
</head>
<body>
    <div class="box">
        <h1>✅ Session Reset Complete!</h1>
        <p>All sessions and cookies have been destroyed.</p>
        
        <div class="instructions">
            <h2>Next Steps:</h2>
            <div class="step">
                <strong>1.</strong> Close this page
            </div>
            <div class="step">
                <strong>2.</strong> Close ALL browser tabs for 2clv.com
            </div>
            <div class="step">
                <strong>3.</strong> Open a NEW tab (or use incognito/private mode)
            </div>
            <div class="step">
                <strong>4.</strong> Go to: <code>https://2clv.com/jrk</code>
            </div>
            <div class="step">
                <strong>5.</strong> Log in with your credentials
            </div>
            <div class="step">
                <strong>6.</strong> Click a "✱Violations Exist" button
            </div>
        </div>
        
        <p style="margin-top: 30px;">
            <a href="/" class="btn">Go to Login Page</a>
        </p>
    </div>
    
    <script>
        // Also clear any JavaScript-accessible cookies
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/jrk/"); 
        });
    </script>
</body>
</html>
