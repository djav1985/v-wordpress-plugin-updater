<?php
namespace App\Controllers;

class HomeController
{
    public function handleRequest(): void
    {
        echo 'home';
    }
}

class ApiController
{
    public function handleRequest(): void
    {
        echo 'api';
    }
}

class LoginController
{
    public function handleRequest(): void {}
    public function handleSubmission(): void {}
}
