<?php
namespace App\Controllers;

use App\Core\ResponseManager;

class HomeController
{
    public function handleRequest(): ResponseManager
    {
        return new ResponseManager(200, [], 'home');
    }
}

class ApiController
{
    public function handleRequest(): ResponseManager
    {
        return new ResponseManager(200, [], 'api');
    }
}

class LoginController
{
    public function handleRequest(): ResponseManager
    {
        return new ResponseManager(200);
    }
    public function handleSubmission(): ResponseManager
    {
        return new ResponseManager(200);
    }
}
