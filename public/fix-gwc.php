<?php
$base = dirname(__DIR__);
chdir($base);

echo "<pre>";

// 1. Install missing package
echo "Installing maatwebsite/excel...\n";
exec('composer require maatwebsite/excel --no-interaction 2>&1', $output1, $code1);
echo implode("\n", $output1) . "\n";
echo "Exit code: $code1\n\n";

// 2. Clear all caches
echo "Clearing caches...\n";
exec('php artisan config:clear 2>&1', $output2);
exec('php artisan cache:clear 2>&1', $output3);
exec('php artisan view:clear 2>&1', $output4);
exec('php artisan route:clear 2>&1', $output5);
echo "Caches cleared\n\n";

// 3. Fix log configuration (create missing log channel)
$envFile = $base . '/.env';
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    if (strpos($env, 'LOG_CHANNEL=') === false) {
        file_put_contents($envFile, "\nLOG_CHANNEL=stack\n", FILE_APPEND);
        echo "Added LOG_CHANNEL to .env\n";
    }
} else {
    echo "Creating .env file...\n";
    copy($base . '/.env.example', $envFile);
}

// 4. Generate app key
exec('php artisan key:generate --force 2>&1', $output6, $code6);
echo "Key generated (code $code6): " . implode("\n", $output6) . "\n\n";

// 5. Optimize
exec('php artisan config:cache 2>&1', $output7);
exec('php artisan route:cache 2>&1', $output8);
echo "Configuration cached\n";

// 6. Set permissions
exec('chmod -R 775 storage bootstrap/cache 2>&1');
echo "Permissions set\n";

echo "\n✅ DONE! Try reloading https://gwc.wodabre.com\n";
echo "</pre>";