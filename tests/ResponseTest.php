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
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getHeaders());
        $this->assertSame('', $response->getBody());
        $this->assertNull($response->getFile());
        $this->assertNull($response->getView());
        $this->assertSame([], $response->getViewData());
    }

    public function testConstructorSetsCustomValues(): void
    {
        $response = new Response(
            statusCode: 404,
            headers: ['Content-Type' => 'text/html'],
            body: 'Not Found',
            reasonPhrase: 'Custom'
        );
        
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Custom', $response->getReasonPhrase());
        $this->assertSame('Not Found', $response->getBody());
    }

    public function testRedirectCreatesRedirectResponse(): void
    {
        $response = Response::redirect('/login');
        
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['/login'], $response->getHeader('Location'));
        $this->assertSame('', $response->getBody());
    }

    public function testRedirectWithCustomStatus(): void
    {
        $response = Response::redirect('/moved', 301);
        
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(['/moved'], $response->getHeader('Location'));
    }

    public function testViewCreatesViewResponse(): void
    {
        $response = Response::view('home', ['title' => 'Welcome']);
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('home', $response->getView());
        $this->assertSame(['title' => 'Welcome'], $response->getViewData());
        $this->assertSame('', $response->getBody());
    }

    public function testViewWithCustomStatus(): void
    {
        $response = Response::view('error', ['message' => 'Not Found'], 404);
        
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('error', $response->getView());
        $this->assertSame(['message' => 'Not Found'], $response->getViewData());
    }

    public function testTextCreatesTextResponse(): void
    {
        $response = Response::text('Hello World');
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['text/plain; charset=UTF-8'], $response->getHeader('Content-Type'));
        $this->assertSame('Hello World', $response->getBody());
    }

    public function testTextWithCustomStatus(): void
    {
        $response = Response::text('Internal Server Error', 500);
        
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getBody());
    }

    public function testFileCreatesFileResponse(): void
    {
        $response = Response::file('/path/to/file.zip');
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('/path/to/file.zip', $response->getFile());
        $this->assertSame('', $response->getBody());
    }

    public function testFileWithContentType(): void
    {
        $response = Response::file('/path/to/plugin.zip', 'application/zip');
        
        $this->assertSame(['application/zip'], $response->getHeader('Content-Type'));
        $this->assertSame('/path/to/plugin.zip', $response->getFile());
    }

    public function testFileWithCustomStatus(): void
    {
        $response = Response::file('/path/to/file', 'application/octet-stream', 206);
        
        $this->assertSame(206, $response->getStatusCode());
    }

    public function testJsonCreatesJsonResponse(): void
    {
        $data = ['success' => true, 'message' => 'OK'];
        $response = Response::json($data);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['application/json; charset=UTF-8'], $response->getHeader('Content-Type'));
        $this->assertSame(json_encode($data), $response->getBody());
        $this->assertNull($response->getFile());
    }

    public function testJsonWithCustomStatus(): void
    {
        $response = Response::json(['error' => 'Not found'], 404);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(json_encode(['error' => 'Not found']), $response->getBody());
    }

    public function testWithStatus(): void
    {
        $response = (new Response(200))->withStatus(404);
        
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(200, (new Response(200))->getStatusCode());
    }

    public function testWithHeader(): void
    {
        $response = (new Response())->withHeader('X-Custom-Header', 'value');
        
        $this->assertSame(['value'], $response->getHeader('X-Custom-Header'));
    }

    public function testWithAddedHeader(): void
    {
        $response = (new Response())
            ->withHeader('Set-Cookie', 'session=abc')
            ->withAddedHeader('Set-Cookie', 'user=john');
        
        $headers = $response->getHeader('Set-Cookie');
        $this->assertCount(2, $headers);
        $this->assertContains('session=abc', $headers);
        $this->assertContains('user=john', $headers);
    }

    public function testWithBody(): void
    {
        $response = (new Response())->withBody('Hello');
        
        $this->assertSame('Hello', $response->getBody());
    }

    public function testWithView(): void
    {
        $response = (new Response())->withView('home', ['title' => 'Home']);
        
        $this->assertSame('home', $response->getView());
        $this->assertSame(['title' => 'Home'], $response->getViewData());
    }

    public function testWithFile(): void
    {
        $response = (new Response())->withFile('/path/to/file.pdf');
        
        $this->assertSame('/path/to/file.pdf', $response->getFile());
    }

    public function testHeaderNormalization(): void
    {
        $response = (new Response())->withHeader('content-type', 'text/plain');
        
        $this->assertSame(['text/plain'], $response->getHeader('Content-Type'));
        $this->assertSame(['text/plain'], $response->getHeader('content-type'));
    }

    public function testHasHeader(): void
    {
        $response = (new Response())->withHeader('X-Custom', 'value');
        
        $this->assertTrue($response->hasHeader('X-Custom'));
        $this->assertTrue($response->hasHeader('x-custom'));
        $this->assertFalse($response->hasHeader('X-Missing'));
    }

    public function testGetHeaderLine(): void
    {
        $response = (new Response())
            ->withHeader('Accept', 'text/html')
            ->withAddedHeader('Accept', 'application/json');
        
        $this->assertSame('text/html, application/json', $response->getHeaderLine('Accept'));
    }
}
