<?php
namespace App\Controllers;

use App\Core\Response;

class HomeController
{
    public function handleRequest(): Response
    {
        return new Response(200, [], 'home');
    }
}

class ApiController
{
    public function handleRequest(): Response
    {
        return new Response(200, [], 'api');
    }
}

class LoginController
{
    public function handleRequest(): Response
    {
        return new Response(200);
    }
    public function handleSubmission(): Response
    {
        return new Response(200);
    }
}
