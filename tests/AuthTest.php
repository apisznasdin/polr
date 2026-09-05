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
}

