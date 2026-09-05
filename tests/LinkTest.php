<?php

use App\Factories\LinkFactory;
use App\Helpers\LinkHelper;
use App\Models\Link;

class LinkTest extends TestCase
{
    public function testCreateAndShortenLink()
    {
        $link = LinkFactory::createLink('https://google.com/test', false, 'mytest', '127.0.0.1', 'polrci', true);
        $this->assertInstanceOf(Link::class, $link);
        $this->assertEquals('mytest', $link->short_url);
        $this->assertEquals('https://google.com/test', $link->long_url);

        $exists = LinkHelper::linkExists('mytest');
        $this->assertNotFalse($exists);
        $this->assertEquals('mytest', $exists->short_url);

        // Test redirection
        $response = $this->call('GET', '/mytest');
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('https://google.com/test', $response->headers->get('Location'));
    }

    public function testSecretLink()
    {
        $link = LinkFactory::createLink('https://example.com/secret', true, null, '127.0.0.1', 'polrci', true);
        $this->assertNotEmpty($link->secret_key);

        // Accessing secret link without secret key should return 403
        $response = $this->call('GET', '/' . $link->short_url);
        $this->assertEquals(403, $response->getStatusCode());

        // Accessing secret link with correct key should redirect 301
        $response = $this->call('GET', '/' . $link->short_url . '/' . $link->secret_key);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('https://example.com/secret', $response->headers->get('Location'));
    }
}
