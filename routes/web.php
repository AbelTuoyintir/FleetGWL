<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Ensure the test client never gets redirected from `/`
// (Some auth scaffolding uses a home redirect route; this overrides it.)



// Routes removed to avoid duplication and name collision with admin.php
require __DIR__ . '/admin.php';
require __DIR__ . '/driver.php';
require __DIR__ . '/auth.php';
