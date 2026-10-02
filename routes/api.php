<?php

use App\Http\Controllers\Api\CepController;
use Illuminate\Support\Facades\Route;

Route::get('/cep/{cep}', CepController::class)->name('api.cep.show');
