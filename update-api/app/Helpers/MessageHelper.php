<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: MessageHelper.php
 * Description: WordPress Update API
 */

namespace App\Helpers;

use App\Core\SessionManager;

class MessageHelper
{
    public static function addMessage(string $message): void
    {
        $session = SessionManager::getInstance();
        $messages = $session->get('messages');
        if (!is_array($messages)) {
            $messages = [];
        }
        $messages[] = $message;
        $session->set('messages', $messages);
    }

    public static function displayAndClearMessages(): void
    {
        $session = SessionManager::getInstance();
        $messages = $session->get('messages');
        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as $message) {
                echo '<script>showToast(' . json_encode($message) . ');</script>';
            }
            $session->set('messages', []);
        }
    }
}
