<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Controllers;

use App\Core\Controller;

class InfoController extends Controller
{
    public function handleRequest(): void
    {
        $this->render('info', []);
    }

    public function handleSubmission(): void
    {
        // TODO: handle info submission
    }
}
