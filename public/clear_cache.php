<?php
/**
 * Laravel Cache Clearing Script
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your public folder via FileZilla
 * 2. Access it via browser: http://your-domain.com/clear_cache.php
 * 3. DELETE THIS FILE immediately after use for security!
 */

// Simple security - remove this check if you want, but it's recommended
// Uncomment and set your IP if you want IP restriction:
// $allowedIPs = ['YOUR_IP_ADDRESS'];
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
//     die('Access denied');
// }

header('Content-Type: text/plain; charset=utf-8');

echo "=== Laravel Cache Clearing ===" . PHP_EOL . PHP_EOL;

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "1. Clearing config cache..." . PHP_EOL;
    \Artisan::call('config:clear');
    echo "   ✓ Config cache cleared" . PHP_EOL . PHP_EOL;
    
    echo "2. Clearing application cache..." . PHP_EOL;
    \Artisan::call('cache:clear');
    echo "   ✓ Application cache cleared" . PHP_EOL . PHP_EOL;
    
    echo "3. Clearing route cache..." . PHP_EOL;
    \Artisan::call('route:clear');
    echo "   ✓ Route cache cleared" . PHP_EOL . PHP_EOL;
    
    echo "4. Clearing view cache..." . PHP_EOL;
    \Artisan::call('view:clear');
    echo "   ✓ View cache cleared" . PHP_EOL . PHP_EOL;
    
    echo "5. Rebuilding config cache..." . PHP_EOL;
    \Artisan::call('config:cache');
    echo "   ✓ Config cache rebuilt" . PHP_EOL . PHP_EOL;
    
    // Test database connection
    echo "6. Testing database connection..." . PHP_EOL;
    try {
        $config = config('database.connections.mysql');
        echo "   Host: " . $config['host'] . PHP_EOL;
        echo "   Database: " . $config['database'] . PHP_EOL;
        echo "   Username: " . $config['username'] . PHP_EOL;
        
        \DB::connection()->getPdo();
        echo "   ✓ Database connection successful!" . PHP_EOL . PHP_EOL;
    } catch (\Exception $e) {
        echo "   ✗ Database connection failed: " . $e->getMessage() . PHP_EOL . PHP_EOL;
    }
    
    echo "=== All done! ===" . PHP_EOL . PHP_EOL;
    echo "⚠️  IMPORTANT: Delete this file (clear_cache.php) now for security!" . PHP_EOL;
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . PHP_EOL;
    echo "Line: " . $e->getLine() . PHP_EOL;
}

