<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

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
        $messages = SessionHelper::get('messages');
        if (!is_array($messages)) {
            $messages = [];
        }
        $messages[] = $message;
        SessionHelper::set('messages', $messages);
    }

    /**
     * Output all queued flash messages as JavaScript toast calls and clear the queue.
     *
     * @return void
     */
    public static function displayAndClearMessages(): void
    {
        $messages = SessionHelper::get('messages');
        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as $message) {
                echo '<script>showToast(' . json_encode($message) . ');</script>';
            }
            SessionHelper::set('messages', []);
        }
    }
}
