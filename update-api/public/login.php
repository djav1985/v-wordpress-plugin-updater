<?php
/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 * @version 3.0.0
 *
 * File: login.php
 * Description: WordPress Update API
 */

$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'httponly' => true,
    'secure'   => $secureFlag,
    'samesite' => 'Lax',
]);
session_start();

require_once '../config.php';
require_once '../../vendor/autoload.php';

\App\Controllers\AuthController::handleRequest();
