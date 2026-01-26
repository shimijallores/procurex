<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function (): \Inertia\Response|\Inertia\ResponseFactory {
    return inertia('Welcome');
});
