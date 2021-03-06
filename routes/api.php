<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Routes Auth
include_once  'path/routeAuth.php';

// Route Community
include_once 'path/routeCommunity.php';

// Route Pieces
include_once 'path/routePieces.php';

// Route users
include_once 'path/routeUsers.php';

// Route Converts
include_once 'path/routeConverts.php';