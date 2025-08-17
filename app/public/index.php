<?php
// SimpleWatch - Uptime Monitor Dashboard
$config = [
    'app_name' => getenv('APP_NAME') ?: 'SimpleWatch',
    'check_interval' => getenv('CHECK_INTERVAL') ?: '60',
    'version' => '1.0.0'
];

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_password = getenv('DB_PASSWORD') ?: '';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $config['app_name'] ?></title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; }
        .status { padding: 20px; background: #4CAF50; color: white; border-radius: 5px; }
        .info { margin: 20px 0; padding: 15px; background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="status">
        <h1>✅ <?= $config['app_name'] ?> is Running</h1>
        <p>Version: <?= $config['version'] ?></p>
    </div>
    
    <div class="info">
        <h3>Configuration (from ConfigMap/Env):</h3>
        <ul>
            <li>App Name: <?= $config['app_name'] ?></li>
            <li>Check Interval: <?= $config['check_interval'] ?> seconds</li>
            <li>DB Host: <?= $db_host ?></li>
            <li>DB Password: <?= $db_password ? '***hidden***' : 'Not set' ?></li>
        </ul>
    </div>

    <div class="info">
        <h3>Pod Info:</h3>
        <ul>
            <li>Hostname: <?= gethostname() ?></li>
            <li>Server Time: <?= date('Y-m-d H:i:s') ?></li>
        </ul>
    </div>
</body>
</html>
