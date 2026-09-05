<?php

use App\Factories\UserFactory;
use App\Models\User;

class ApiTest extends TestCase
{
    protected function createApiUser()
    {
        return UserFactory::createUser('apiuser', 'api@example.com', 'apipassword', 1, '127.0.0.1', 'testapikey12345', 1);
    }

    public function testApiShortenLink()
    {
        $user = $this->createApiUser();

        $response = $this->call('POST', '/api/v2/action/shorten', [
            'key' => 'testapikey12345',
            'url' => 'https://example.com/api-test',
            'response_type' => 'json'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('shorten', $data['action']);
        $this->assertStringContainsString('http://localhost/', $data['result']);
    }

    public function testApiLookupLink()
    {
        $user = $this->createApiUser();

        // First shorten a link
        $shortenResponse = $this->call('POST', '/api/v2/action/shorten', [
            'key' => 'testapikey12345',
            'url' => 'https://example.com/lookup-test',
            'custom_ending' => 'lookup123',
            'response_type' => 'json'
        ]);
        $this->assertEquals(200, $shortenResponse->getStatusCode());

        // Then lookup the link
        $lookupResponse = $this->call('POST', '/api/v2/action/lookup', [
            'key' => 'testapikey12345',
            'url_ending' => 'lookup123',
            'response_type' => 'json'
        ]);
        $this->assertEquals(200, $lookupResponse->getStatusCode());
        $data = json_decode($lookupResponse->getContent(), true);
        $this->assertEquals('lookup', $data['action']);
        $this->assertEquals('https://example.com/lookup-test', $data['result']['long_url']);
    }

    public function testApiUnauthorized()
    {
        $response = $this->call('POST', '/api/v2/action/shorten', [
            'key' => 'invalidkey',
            'url' => 'https://example.com/fail',
            'response_type' => 'json'
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testApiShortenBulk()
    {
        $this->createApiUser();

        $payload = json_encode([
            'links' => [
                ['url' => 'https://example.com/bulk-1'],
                ['url' => 'https://example.com/bulk-2'],
            ]
        ]);

        $response = $this->call('POST', '/api/v2/action/shorten_bulk', [
            'key' => 'testapikey12345',
            'data' => $payload,
            'response_type' => 'json'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('shorten_bulk', $data['action']);
        $this->assertCount(2, $data['result']['shortened_links']);
    }

    public function testApiAnalytics()
    {
        $this->createApiUser();

        // Shorten first
        $this->call('POST', '/api/v2/action/shorten', [
            'key' => 'testapikey12345',
            'url' => 'https://example.com/analytics-test',
            'custom_ending' => 'analyticstest',
            'response_type' => 'json'
        ]);

        $response = $this->call('GET', '/api/v2/data/link', [
            'key' => 'testapikey12345',
            'url_ending' => 'analyticstest',
            'stats_type' => 'day',
            'response_type' => 'json'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('data_link_day', $data['action']);
        $this->assertArrayHasKey('data', $data['result']);
    }
}
