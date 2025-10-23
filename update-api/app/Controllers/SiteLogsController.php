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
use App\Helpers\MessageHelper;
use App\Core\Csrf;
use App\Core\ErrorManager;

class SiteLogsController extends Controller
{
    /**
     * Handles GET requests for site logs.
     */
    public function handleRequest(): void
    {
        $hosts = HostsModel::getHosts();
        $this->render('sitelogs', [
            'hosts' => $hosts,
        ]);
    }

    /**
     * Handles POST requests to fetch logs for a domain.
     */
    public function handleSubmission(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::getInstance()->log($error);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $error]);
            exit();
        }

        $domain = $_POST['domain'] ?? '';
        if (empty($domain)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Domain is required.']);
            exit();
        }

        $lines = isset($_POST['lines']) ? (int)$_POST['lines'] : 250;
        
        $result = self::fetchSiteLogs($domain, $lines);
        
        http_response_code($result['success'] ? 200 : 500);
        echo json_encode($result);
        exit();
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
        $key_encrypted = $conn->fetchOne('SELECT key FROM hosts WHERE domain = ?', [$domain]);
        
        if (!$key_encrypted) {
            return [
                'success' => false,
                'logs' => '',
                'message' => 'Domain not found in hosts table.'
            ];
        }
        
        $key = \App\Helpers\Encryption::decrypt($key_encrypted);
        
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
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['log'])) {
                $log_text = implode("\n", $data['log']);
                return [
                    'success' => true,
                    'logs' => $log_text,
                    'message' => 'Logs retrieved successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'logs' => '',
                    'message' => 'Invalid response from API.'
                ];
            }
        } elseif ($http_code === 404) {
            return [
                'success' => true,
                'logs' => 'Debug log file not found on ' . $domain,
                'message' => 'No logs available.'
            ];
        } else {
            $error_msg = $response ?: 'Failed to fetch logs';
            return [
                'success' => false,
                'logs' => '',
                'message' => 'Failed to fetch logs from ' . $domain . ': HTTP ' . $http_code
            ];
        }
    }
}
