<!DOCTYPE html>
<html>
<head>
    <title>Config Debug</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
        .box { background: #000; padding: 15px; margin: 10px 0; border: 2px solid #0f0; }
        h2 { color: #0ff; }
        .fail { color: #f00; }
        .pass { color: #0f0; }
        .warn { color: #ff0; }
        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 8px; border: 1px solid #0f0; text-align: left; }
        th { background: #003300; }
    </style>
</head>
<body>
    <h1>🔍 CONFIG.PHP DIAGNOSTIC</h1>

<?php
echo "<div class='box'>";
echo "<h2>Environment Detection</h2>";
echo "<table>";
echo "<tr><th>Check</th><th>Value</th></tr>";

$replId = getenv('REPL_ID');
echo "<tr><td>getenv('REPL_ID')</td><td>" . ($replId !== false ? "<span class='warn'>'{$replId}'</span>" : "<span class='pass'>FALSE (good for production)</span>") . "</td></tr>";

$phpSapi = PHP_SAPI;
echo "<tr><td>PHP_SAPI</td><td>" . ($phpSapi === 'cli-server' ? "<span class='warn'>{$phpSapi}</span>" : "<span class='pass'>{$phpSapi}</span>") . "</td></tr>";

$isReplit = getenv('REPL_ID') !== false || PHP_SAPI === 'cli-server';
echo "<tr><td>\$isReplit calculation</td><td>" . ($isReplit ? "<span class='fail'>TRUE (BAD! Will use empty basePath)</span>" : "<span class='pass'>FALSE (good)</span>") . "</td></tr>";

$basePath = $isReplit ? '' : '/jrk';
echo "<tr><td>\$basePath</td><td><span class='" . ($basePath === '/jrk' ? 'pass' : 'fail') . "'>'{$basePath}'</span> (should be '/jrk')</td></tr>";

echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>Actual Config Values</h2>";
$config = require __DIR__ . '/config.php';
echo "<table>";
echo "<tr><th>Config Key</th><th>Value</th></tr>";
echo "<tr><td>base_path</td><td><strong>" . htmlspecialchars($config['base_path']) . "</strong></td></tr>";
echo "<tr><td>app_url</td><td>" . htmlspecialchars($config['app_url']) . "</td></tr>";
echo "<tr><td>session[name]</td><td>" . htmlspecialchars($config['session']['name']) . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>Session Cookie Path Calculation</h2>";
$cookiePath = $config['base_path'] ? $config['base_path'] . '/' : '/';
echo "<table>";
echo "<tr><th>Step</th><th>Value</th></tr>";
echo "<tr><td>config['base_path']</td><td>'{$config['base_path']}'</td></tr>";
echo "<tr><td>Is truthy?</td><td>" . ($config['base_path'] ? 'YES' : 'NO') . "</td></tr>";
echo "<tr><td>Final \$cookiePath</td><td><strong class='" . ($cookiePath === '/jrk/' ? 'pass' : 'fail') . "'>{$cookiePath}</strong> (should be '/jrk/')</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>File Modification Times</h2>";
$files = [
    'config.php' => __DIR__ . '/config.php',
    'includes/session.php' => __DIR__ . '/includes/session.php',
    'public/assets/app.js' => __DIR__ . '/public/assets/app.js',
];
echo "<table>";
echo "<tr><th>File</th><th>Last Modified</th><th>Size</th></tr>";
foreach ($files as $name => $path) {
    if (file_exists($path)) {
        $mtime = filemtime($path);
        $size = filesize($path);
        echo "<tr><td>{$name}</td><td>" . date('Y-m-d H:i:s', $mtime) . "</td><td>" . number_format($size) . " bytes</td></tr>";
    } else {
        echo "<tr><td>{$name}</td><td colspan='2'><span class='fail'>FILE NOT FOUND</span></td></tr>";
    }
}
echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>Session.php First 50 Lines</h2>";
$sessionFile = __DIR__ . '/includes/session.php';
if (file_exists($sessionFile)) {
    $lines = file($sessionFile);
    $first50 = array_slice($lines, 0, 50);
    echo "<pre>";
    foreach ($first50 as $i => $line) {
        $lineNum = $i + 1;
        echo sprintf("%2d: %s", $lineNum, htmlspecialchars($line));
    }
    echo "</pre>";
    
    // Check for the specific line we need
    $content = file_get_contents($sessionFile);
    if (strpos($content, '$cookiePath = $config[\'base_path\'] ? $config[\'base_path\'] . \'/\' : \'/\';') !== false) {
        echo "<p class='pass'>✅ Updated cookie path code FOUND in session.php</p>";
    } else {
        echo "<p class='fail'>❌ Updated cookie path code NOT FOUND in session.php - OLD VERSION!</p>";
    }
} else {
    echo "<p class='fail'>session.php not found</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>App.js First 10 Lines</h2>";
$appJsFile = __DIR__ . '/public/assets/app.js';
if (file_exists($appJsFile)) {
    $lines = file($appJsFile);
    $first10 = array_slice($lines, 0, 10);
    echo "<pre>";
    foreach ($first10 as $i => $line) {
        $lineNum = $i + 1;
        echo sprintf("%2d: %s", $lineNum, htmlspecialchars($line));
    }
    echo "</pre>";
    
    $content = file_get_contents($appJsFile);
    if (strpos($content, 'window.location.pathname.startsWith(\'/jrk\')') !== false) {
        echo "<p class='pass'>✅ Updated pathname detection FOUND in app.js</p>";
    } else {
        echo "<p class='fail'>❌ Updated pathname detection NOT FOUND in app.js - OLD VERSION!</p>";
    }
} else {
    echo "<p class='fail'>app.js not found</p>";
}
echo "</div>";

?>

    <p><a href="check-session.php" style="color:#0ff;">← Back to Session Check</a> | <a href="/" style="color:#0ff;">← Back to App</a></p>
</body>
</html>
