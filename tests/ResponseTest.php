<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\Response;

class ResponseTest extends TestCase
{
    public function testConstructorSetsDefaultValues(): void
    {
        $response = new Response();
        
        $this->assertSame(200, $response->status);
        $this->assertSame([], $response->headers);
        $this->assertSame('', $response->body);
        $this->assertNull($response->file);
        $this->assertNull($response->view);
        $this->assertSame([], $response->data);
    }

    public function testConstructorSetsCustomValues(): void
    {
        $response = new Response(
            status: 404,
            headers: ['Content-Type' => 'text/html'],
            body: 'Not Found',
            file: '/path/to/file',
            view: 'error',
            data: ['message' => 'Error']
        );
        
        $this->assertSame(404, $response->status);
        $this->assertSame(['Content-Type' => 'text/html'], $response->headers);
        $this->assertSame('Not Found', $response->body);
        $this->assertSame('/path/to/file', $response->file);
        $this->assertSame('error', $response->view);
        $this->assertSame(['message' => 'Error'], $response->data);
    }

    public function testRedirectCreatesRedirectResponse(): void
    {
        $response = Response::redirect('/login');
        
        $this->assertSame(302, $response->status);
        $this->assertSame(['Location' => '/login'], $response->headers);
        $this->assertSame('', $response->body);
    }

    public function testRedirectWithCustomStatus(): void
    {
        $response = Response::redirect('/moved', 301);
        
        $this->assertSame(301, $response->status);
        $this->assertSame(['Location' => '/moved'], $response->headers);
    }

    public function testViewCreatesViewResponse(): void
    {
        $response = Response::view('home', ['title' => 'Welcome']);
        
        $this->assertSame(200, $response->status);
        $this->assertSame('home', $response->view);
        $this->assertSame(['title' => 'Welcome'], $response->data);
        $this->assertSame('', $response->body);
    }

    public function testViewWithCustomStatus(): void
    {
        $response = Response::view('error', ['message' => 'Not Found'], 404);
        
        $this->assertSame(404, $response->status);
        $this->assertSame('error', $response->view);
        $this->assertSame(['message' => 'Not Found'], $response->data);
    }

    public function testTextCreatesTextResponse(): void
    {
        $response = Response::text('Hello World');
        
        $this->assertSame(200, $response->status);
        $this->assertSame(['Content-Type' => 'text/plain'], $response->headers);
        $this->assertSame('Hello World', $response->body);
    }

    public function testTextWithCustomStatus(): void
    {
        $response = Response::text('Internal Server Error', 500);
        
        $this->assertSame(500, $response->status);
        $this->assertSame('Internal Server Error', $response->body);
    }

    public function testFileCreatesFileResponse(): void
    {
        $response = Response::file('/path/to/file.zip');
        
        $this->assertSame(200, $response->status);
        $this->assertSame('/path/to/file.zip', $response->file);
        $this->assertSame('', $response->body);
    }

    public function testFileWithCustomHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="plugin.zip"'
        ];
        $response = Response::file('/path/to/plugin.zip', $headers);
        
        $this->assertSame($headers, $response->headers);
        $this->assertSame('/path/to/plugin.zip', $response->file);
    }

    public function testFileWithCustomStatus(): void
    {
        $response = Response::file('/path/to/file', [], 206);
        
        $this->assertSame(206, $response->status);
    }
}
