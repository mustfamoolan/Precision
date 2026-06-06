<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$request = Request::create('/reports-all', 'GET');
$app->instance('request', $request);
$user = \App\Models\User::first();
if ($user) {
    auth()->login($user);
    echo "Logged in as user: " . $user->email . "\n";
} else {
    echo "No users found in database!\n";
}

$route = Route::getRoutes()->match($request);
echo "Matched route action: " . $route->getActionName() . "\n";

$response = Route::dispatch($request);
echo "Response class: " . get_class($response) . "\n";
echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response headers: " . json_encode($response->headers->all()) . "\n";
echo "Response content length: " . strlen($response->getContent()) . "\n";
echo "Response content start: " . substr($response->getContent(), 0, 500) . "\n";
