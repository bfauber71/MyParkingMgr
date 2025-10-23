<!DOCTYPE html>
<html>
<head>
    <title>Session Diagnostic - ManageMyParking</title>
    <style>
        body { font-family: Arial; background: #1a1a1a; color: #fff; padding: 20px; max-width: 900px; margin: 0 auto; }
        h1 { color: #3b82f6; }
        .box { background: #2a2a2a; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #3b82f6; }
        .pass { color: #10b981; font-weight: bold; }
        .fail { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        pre { background: #000; padding: 10px; overflow-x: auto; font-size: 12px; }
        code { background: #333; padding: 2px 5px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #444; }
        th { background: #333; }
        .btn { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <h1>🔍 Session & Path Diagnostic</h1>
    
<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/session.php';

Session::start();

echo "<div class='box'>";
echo "<h2>Current Request Info</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>Full URL</td><td>" . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>Script Name</td><td>" . htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>Document Root</td><td>" . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>Server Name</td><td>" . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>HTTPS</td><td>" . htmlspecialchars($_SERVER['HTTPS'] ?? 'N/A') . "</td></tr>";
echo "</table>";
echo "</div>";

$config = require __DIR__ . '/config.php';
echo "<div class='box'>";
echo "<h2>Config Settings</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>Base Path</td><td><code>" . htmlspecialchars($config['base_path']) . "</code></td></tr>";
echo "<tr><td>App URL</td><td>" . htmlspecialchars($config['app_url']) . "</td></tr>";
echo "<tr><td>Session Name</td><td>" . htmlspecialchars($config['session']['name']) . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>Session Status</h2>";
echo "<table>";
echo "<tr><th>Check</th><th>Status</th></tr>";
echo "<tr><td>Session Started</td><td>" . (session_status() === PHP_SESSION_ACTIVE ? "<span class='pass'>✅ YES</span>" : "<span class='fail'>❌ NO</span>") . "</td></tr>";
echo "<tr><td>Session ID</td><td><code>" . session_id() . "</code></td></tr>";
echo "<tr><td>Cookie Path</td><td><code>" . ini_get('session.cookie_path') . "</code></td></tr>";
echo "<tr><td>Cookie Secure</td><td>" . (ini_get('session.cookie_secure') ? 'YES' : 'NO') . "</td></tr>";
echo "<tr><td>Cookie HTTPOnly</td><td>" . (ini_get('session.cookie_httponly') ? 'YES' : 'NO') . "</td></tr>";
echo "<tr><td>Is Authenticated</td><td>" . (Session::isAuthenticated() ? "<span class='pass'>✅ YES</span>" : "<span class='fail'>❌ NO</span>") . "</td></tr>";

if (Session::isAuthenticated()) {
    $user = Session::user();
    echo "<tr><td>Username</td><td>" . htmlspecialchars($user['username'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>Role</td><td>" . htmlspecialchars($user['role'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>User ID</td><td>" . htmlspecialchars($user['id'] ?? 'N/A') . "</td></tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>Session Data</h2>";
echo "<pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>Cookies Sent by Browser</h2>";
if (empty($_COOKIE)) {
    echo "<p class='warning'>⚠️ No cookies received</p>";
} else {
    echo "<table>";
    echo "<tr><th>Cookie Name</th><th>Value (truncated)</th></tr>";
    foreach ($_COOKIE as $name => $value) {
        $display = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
        echo "<tr><td>" . htmlspecialchars($name) . "</td><td><code>" . htmlspecialchars($display) . "</code></td></tr>";
    }
    echo "</table>";
}
echo "</div>";

// Test API endpoint
echo "<div class='box'>";
echo "<h2>API Endpoint Test</h2>";
echo "<p>Testing if API endpoint can see your session...</p>";

$apiBase = $config['base_path'] . '/api';
echo "<p><strong>Expected API Base:</strong> <code>{$apiBase}</code></p>";

echo "<div id='apiTest' style='margin-top:10px; padding:10px; background:#000;'>";
echo "<p style='color:#888;'>JavaScript test loading...</p>";
echo "</div>";

echo "<script>
const basePath = window.location.pathname.startsWith('/jrk') ? '/jrk' : '';
const API_BASE = basePath + '/api';
document.getElementById('apiTest').innerHTML = '<p><strong>JavaScript API_BASE:</strong> <code>' + API_BASE + '</code></p>';

// Test the user endpoint
fetch(API_BASE + '/user', { credentials: 'include' })
    .then(r => r.json())
    .then(data => {
        document.getElementById('apiTest').innerHTML += '<p class=\"pass\">✅ API Call Success</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(err => {
        document.getElementById('apiTest').innerHTML += '<p class=\"fail\">❌ API Call Failed: ' + err.message + '</p>';
    });
</script>";
echo "</div>";
?>

    <div class="box">
        <h2>Actions</h2>
        <a href="/" class="btn">← Back to App</a>
        <a href="?logout=1" class="btn" style="background:#ef4444;">Logout & Test</a>
        <button class="btn" onclick="location.reload()">🔄 Refresh</button>
    </div>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    Session::destroy();
    echo "<script>alert('Session destroyed. Redirecting...'); setTimeout(() => window.location = '?', 1000);</script>";
}
?>

</body>
</html>
