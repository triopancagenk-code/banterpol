<?php

use Illuminate\Support\Facades\Route;
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MikrotikController;

Route::get('/mikrotik/active-users', [MikrotikController::class, 'getActiveUsers']);