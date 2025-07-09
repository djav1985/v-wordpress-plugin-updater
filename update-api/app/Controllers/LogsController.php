<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: LogsController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\LogModel;

class LogsController extends Controller
{
    /**
     * Handles the request for the logs page.
     *
     * Generates log output for plugins and themes and includes the log view.
     *
     * @return void
     */
    public static function handleRequest(): void
    {
        $ploutput = LogModel::processLogFile('plugin.log');
        $thoutput = LogModel::processLogFile('theme.log');

        // Use the render method to include the logs view
        (new self())->render('logs', [
            'ploutput' => $ploutput,
            'thoutput' => $thoutput,
        ]);
    }

}
