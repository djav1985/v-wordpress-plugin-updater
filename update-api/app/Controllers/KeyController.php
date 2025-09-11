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
use App\Core\Response;

class KeyController extends Controller
{
    /**
     * Handle API requests for retrieving host keys.
     */
    public function handleRequest(): Response
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (Blacklist::isBlacklisted($ip) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            ErrorManager::getInstance()->log('Forbidden or invalid request from ' . $ip);
            return new Response(403);
        }

        $required = ['type', 'domain'];
        foreach ($required as $p) {
            if (!isset($_GET[$p]) || $_GET[$p] === '') {
                ErrorManager::getInstance()->log('Bad request missing parameter: ' . $p);
                return new Response(400);
            }
        }
        if ($_GET['type'] !== 'auth') {
            ErrorManager::getInstance()->log('Bad request invalid type');
            return new Response(400);
        }

        $domain = Validation::validateDomain($_GET['domain']);
        if ($domain === null) {
            ErrorManager::getInstance()->log('Bad request invalid parameter: domain');
            return new Response(400);
        }

        $key = HostsModel::getKeyIfSendAuth($domain);
        if ($key !== null) {
            return Response::text($key);
        }

        return new Response(403);
    }
}
