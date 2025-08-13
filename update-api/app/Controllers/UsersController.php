<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Controllers;

use App\Core\Controller;

class UsersController extends Controller
{
    public function handleRequest(): void
    {
        $this->render('users', []);
    }

    public function handleSubmission(): void
    {
        // TODO: handle users submission
    }
}
