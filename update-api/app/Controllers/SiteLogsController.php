<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: SiteLogsController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\HostsModel;
use App\Helpers\ValidationHelper;
use App\Core\ErrorManager;
use App\Core\Response;

class SiteLogsController extends Controller
{
    /**
     * Handles GET requests for site logs.
     */
    public function handleRequest(): Response
    {
        $hosts = HostsModel::getHosts();
        return Response::view('sitelogs', [
            'hosts' => $hosts,
        ]);
    }

    /**
     * Handles POST requests to fetch logs for a domain.
     */
    public function handleSubmission(): Response
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::getInstance()->log($error);
            return Response::json(['success' => false, 'message' => $error], 400);
        }

        $domain = $_POST['domain'] ?? '';
        if (empty($domain)) {
            return Response::json(['success' => false, 'message' => 'Domain is required.'], 400);
        }

        $domain = ValidationHelper::validateDomain($domain);
        if ($domain === null) {
            return Response::json(['success' => false, 'message' => 'Invalid domain.'], 400);
        }

        $lines = isset($_POST['lines']) ? (int)$_POST['lines'] : 250;

        $result = self::fetchSiteLogs($domain, $lines);

        return Response::json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Fetch logs from a remote site via REST API.
     *
     * @param string $domain The domain to fetch logs from
     * @param int $lines Number of lines to fetch
     * @return array{success: bool, logs: string, message: string}
     */
    private static function fetchSiteLogs(string $domain, int $lines = 250): array
    {
        // Get the API key for the domain
        $conn = \App\Core\DatabaseManager::getConnection();
        $keyEncrypted = $conn->fetchOne('SELECT key FROM hosts WHERE domain = ?', [$domain]);
        
        if (!$keyEncrypted) {
            return [
                'success' => false,
                'logs' => '',
                'message' => 'Domain not found in hosts table.'
            ];
        }
        
        $key = \App\Helpers\EncryptionHelper::decrypt($keyEncrypted);
        
        // Prepare the API request
        $url = 'https://' . $domain . '/wp-json/vwpd/v1/debuglog?lines=' . $lines;
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $key,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['log'])) {
                $logText = implode("\n", $data['log']);
                return [
                    'success' => true,
                    'logs' => $logText,
                    'message' => 'Logs retrieved successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'logs' => '',
                    'message' => 'Invalid response from API.'
                ];
            }
        } elseif ($httpCode === 404) {
            return [
                'success' => true,
                'logs' => 'Debug log file not found on ' . $domain,
                'message' => 'No logs available.'
            ];
        } else {
            $errorMsg = $response ?: 'Failed to fetch logs';
            return [
                'success' => false,
                'logs' => '',
                'message' => 'Failed to fetch logs from ' . $domain . ': HTTP ' . $httpCode
            ];
        }
    }
}
