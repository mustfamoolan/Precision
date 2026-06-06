<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ActivityLog;

$events = ActivityLog::select('event')->distinct()->pluck('event');
echo "Unique events: " . json_encode($events) . "\n";
