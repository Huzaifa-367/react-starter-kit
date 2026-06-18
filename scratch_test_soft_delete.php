<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$reflector = new ReflectionMethod(User::class, 'fresh');
echo $reflector->getFileName() . ':' . $reflector->getStartLine() . "\n";
