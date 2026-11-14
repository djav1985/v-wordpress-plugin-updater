<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: MessageHelper.php
 * Description: WordPress Update API
 */

namespace App\Helpers;

use App\Core\SessionManager;

class MessageHelper
{
    /**
     * Append a flash message to the current session's message queue.
     *
     * @param string $message The message text to store.
     * @return void
     */
    public static function addMessage(string $message): void
    {
        $messages = SessionManager::getInstance()->get('messages');
        if (!is_array($messages)) {
            $messages = [];
        }
        $messages[] = $message;
        SessionManager::getInstance()->set('messages', $messages);
    }

    /**
     * Output queued messages as toasts with a no-JavaScript fallback, then clear them.
     *
     * @return void
     */
    public static function displayAndClearMessages(): void
    {
        $messages = SessionManager::getInstance()->get('messages');
        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as $message) {
                $message = (string) $message;
                $jsonMessage = json_encode($message, JSON_INVALID_UTF8_SUBSTITUTE);
                $safeJsonMessage = str_ireplace('</script', '<\\/script', (string) $jsonMessage);
                echo '<script>showToast(' . $safeJsonMessage . ', "info");</script>';
                echo '<noscript><div class="alert alert-info flash-fallback" role="status">' .
                    htmlspecialchars($message, ENT_QUOTES, 'UTF-8') .
                    '</div></noscript>';
            }
            SessionManager::getInstance()->set('messages', []);
        }
    }
}
