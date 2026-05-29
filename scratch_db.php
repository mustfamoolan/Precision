<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$reminders = \App\Models\Reminder::all()->toArray();
print_r($reminders);
