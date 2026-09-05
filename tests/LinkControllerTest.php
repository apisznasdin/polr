<?php

class LinkControllerTest extends TestCase
{
    /**
     * Test LinkController
     *
     * @return void
     */
    public function testRequestGetNotExistShortUrl() {
        $response = $this->call('GET', '/notexist');
        $this->assertTrue($response->isRedirection());
        $this->assertEquals(env('SETTING_INDEX_REDIRECT'), $response->headers->get('Location'));
    }

    public function testRedirectExistingLink() {
        \App\Factories\LinkFactory::createLink('https://example.com/target', false, 'targeturl', '127.0.0.1', 'polr');
        $response = $this->call('GET', '/targeturl');
        $this->assertTrue($response->isRedirection());
        $this->assertEquals('https://example.com/target', $response->headers->get('Location'));
    }

    public function testRedirectSecretLink() {
        $link = \App\Factories\LinkFactory::createLink('https://example.com/secret', true, 'sec123', '127.0.0.1', 'polr', true);
        $secretKey = $link->secret_key;

        // Without secret key -> 403 forbidden
        $response = $this->call('GET', '/sec123');
        $this->assertEquals(403, $response->getStatusCode());

        // With secret key -> redirects to destination
        $response = $this->call('GET', '/sec123/' . $secretKey);
        $this->assertTrue($response->isRedirection());
        $this->assertEquals('https://example.com/secret', $response->headers->get('Location'));
    }

    public function testWebShortenPost() {
        $response = $this->call('POST', '/shorten', [
            'link-url' => 'https://example.com/web-shorten',
            'custom-ending' => 'webending'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('webending', $response->getContent());
    }
}

