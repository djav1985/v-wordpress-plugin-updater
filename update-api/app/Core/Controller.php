<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: Controller.php
 * Description: WordPress Update API
 */

namespace App\Core;

class Controller
{
    /**
     * Render a view and pass data to it.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../Views/' . $view . '.php';
    }
}
