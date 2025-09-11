<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: KeyController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Helpers\Validation;
use App\Models\HostsModel;
use App\Models\Blacklist;
use App\Core\ErrorManager;
use App\Core\Controller;

class KeyController extends Controller
{
    /**
     * Handle API requests for retrieving host keys.
     */
    public function handleRequest(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (Blacklist::isBlacklisted($ip) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(403);
            ErrorManager::getInstance()->log('Forbidden or invalid request from ' . $ip);
            return;
        }

        $required = ['type', 'domain'];
        foreach ($required as $p) {
            if (!isset($_GET[$p]) || $_GET[$p] === '') {
                http_response_code(400);
                ErrorManager::getInstance()->log('Bad request missing parameter: ' . $p);
                return;
            }
        }
        if ($_GET['type'] !== 'auth') {
            http_response_code(400);
            ErrorManager::getInstance()->log('Bad request invalid type');
            return;
        }

        $domain = Validation::validateDomain($_GET['domain']);
        if ($domain === null) {
            http_response_code(400);
            ErrorManager::getInstance()->log('Bad request invalid parameter: domain');
            return;
        }

        $key = HostsModel::getKeyIfSendAuth($domain);
        if ($key !== null) {
            header('Content-Type: text/plain');
            http_response_code(200);
            echo $key;
            return;
        }

        http_response_code(403);
        return;
    }
}
