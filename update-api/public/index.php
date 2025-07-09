<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: index.php
 * Description: WordPress Update API
 */

use App\Core\Router;

$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
                           'httponly' => true,
                           'secure'   => $secureFlag,
                           'samesite' => 'Lax',
                          ]);
session_start();
session_regenerate_id(true);

require_once '../config.php';
require_once '../autoload.php';

$router = new Router();
$router->dispatch($_SERVER['REQUEST_URI']);
