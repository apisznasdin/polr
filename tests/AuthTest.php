<?php

use App\Factories\UserFactory;
use App\Helpers\UserHelper;

class AuthTest extends TestCase
{
    /**
     * Test Authentication (sign up and sign in)
     *
     * @return void
     */
    public function testLogin() {
        $user = UserFactory::createUser('testuser', 'test@example.com', 'secret123', 1);
        $this->assertTrue(UserHelper::userExists('testuser'));
        $this->assertTrue(UserHelper::emailExists('test@example.com'));

        $auth = UserHelper::checkCredentials('testuser', 'secret123');
        $this->assertNotFalse($auth);
        $this->assertEquals('testuser', $auth['username']);

        $badAuth = UserHelper::checkCredentials('testuser', 'wrongpassword');
        $this->assertFalse($badAuth);
    }

    public function testLoginHttpFlow() {
        UserFactory::createUser('testuser2', 'test2@example.com', 'password123', 1);

        $getResponse = $this->call('GET', '/login');
        $this->assertEquals(200, $getResponse->getStatusCode());

        preg_match('/name=[\'\"]_token[\'\"] value=[\'\"]([^\'\"]+)[\'\"]/', $getResponse->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $token = $matches[1];

        $postResponse = $this->call('POST', '/login', [
            'username' => 'testuser2',
            'password' => 'password123',
            '_token' => $token,
        ]);

        $this->assertTrue($postResponse->isRedirection());
        $this->assertEquals('testuser2', session('username'));
    }
}

