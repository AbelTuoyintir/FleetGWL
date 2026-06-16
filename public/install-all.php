<?php
echo "<pre>";
set_time_limit(0);
ini_set('max_execution_time', 300);

$base = dirname(__DIR__);
chdir($base);

echo "=== Starting Composer Installation ===\n\n";

// Step 1: Install Composer
function installComposer($base) {
    echo "Step 1: Downloading Composer...\n";
    
    $installer = file_get_contents('https://getcomposer.org/installer');
    if ($installer === false) {
        return "Failed to download Composer installer. Check if allow_url_fopen is enabled.";
    }
    
    file_put_contents($base . '/composer-setup.php', $installer);
    echo "Installer downloaded.\n";
    
    // Run the installer using PHP
    echo "Running Composer installer...\n";
    exec('php ' . $base . '/composer-setup.php --install-dir=' . $base . ' --filename=composer.phar 2>&1', $output, $code);
    echo implode("\n", $output) . "\n";
    
    unlink($base . '/composer-setup.php');
    
    if (file_exists($base . '/composer.phar')) {
        echo "✅ Composer installed successfully!\n";
        return true;
    } else {
        return "❌ Composer installation failed.";
    }
}

// Step 2: Use Composer to install package
function installPackage($base) {
    echo "\nStep 2: Installing maatwebsite/excel...\n";
    
    // Create a temporary script to run composer commands
    $composerScript = $base . '/run-composer.php';
    $phpCode = '<?php
    chdir("' . $base . '");
    $output = [];
    exec("php ' . $base . '/composer.phar require maatwebsite/excel --no-interaction --no-progress 2>&1", $output, $code);
    echo implode("\n", $output);
    exit($code);
    ';
    
    file_put_contents($composerScript, $phpCode);
    
    // Execute the script
    exec('php ' . $composerScript . ' 2>&1', $output, $code);
    echo implode("\n", $output) . "\n";
    
    unlink($composerScript);
    
    if ($code === 0) {
        echo "✅ Package installed successfully!\n";
        return true;
    } else {
        echo "⚠️ Package installation had issues. Trying alternative...\n";
        return false;
    }
}

// Step 3: Fix composer.json if needed
function fixComposerJson($base) {
    $composerJson = $base . '/composer.json';
    if (file_exists($composerJson)) {
        $content = file_get_contents($composerJson);
        if (strpos($content, 'maatwebsite/excel') === false) {
            // Add the package manually
            $data = json_decode($content, true);
            if (!isset($data['require'])) {
                $data['require'] = [];
            }
            $data['require']['maatwebsite/excel'] = '^3.1';
            file_put_contents($composerJson, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✅ Added maatwebsite/excel to composer.json\n";
            return true;
        }
    }
    return true;
}

// Step 4: Clear caches
function clearCaches($base) {
    echo "\nStep 3: Clearing Laravel caches...\n";
    chdir($base);
    exec('php artisan config:clear 2>&1', $out1);
    exec('php artisan cache:clear 2>&1', $out2);
    exec('php artisan view:clear 2>&1', $out3);
    echo "Caches cleared.\n";
    
    // Remove cached config file
    $cachedConfig = $base . '/bootstrap/cache/config.php';
    if (file_exists($cachedConfig)) {
        unlink($cachedConfig);
        echo "Removed cached config file.\n";
    }
}

// Step 5: Set permissions
function setPermissions($base) {
    echo "\nStep 4: Setting permissions...\n";
    exec('chmod -R 775 ' . $base . '/storage 2>&1');
    exec('chmod -R 775 ' . $base . '/bootstrap/cache 2>&1');
    echo "Permissions set.\n";
}

// Execute all steps
$result1 = installComposer($base);
if ($result1 === true) {
    fixComposerJson($base);
    installPackage($base);
}
clearCaches($base);
setPermissions($base);

echo "\n=== DONE ===\n";
echo "Try reloading: https://gwc.wodabre.com\n";
echo "If still errors, run this script again.\n";
echo "</pre>";