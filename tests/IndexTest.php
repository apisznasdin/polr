<?php

class IndexTest extends TestCase
{
    /**
     * Test Index
     *
     * @return void
     */
    public function testIndex() {
        $response = $this->call('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString('<h1 class=\'title\'>'. env('APP_NAME') .'</h1>', $content);
        $this->assertStringContainsString('<meta name="csrf-token"', $content);
        $this->assertStringContainsString('>Sign In</a>', $content);
        $this->assertStringNotContainsString('SQLSTATE', $content);
    }
}

