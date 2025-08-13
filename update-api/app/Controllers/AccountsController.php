<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Controllers;

use App\Core\Controller;

class AccountsController extends Controller
{
    public function handleRequest(): void
    {
        $this->render('accounts', []);
    }

    public function handleSubmission(): void
    {
        // TODO: handle accounts submission
    }
}
