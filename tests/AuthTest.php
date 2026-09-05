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

    public function testAdminPaginationUserLinks() {
        UserFactory::createUser('testuser3', 'test3@example.com', 'password123', 1);
        \App\Factories\LinkFactory::createLink('https://example.com/p1', false, 'p1', '127.0.0.1', 'testuser3');

        $request = \Illuminate\Http\Request::create('/api/v2/admin/get_user_links', 'GET', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $this->app->instance('request', $request);
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('username', 'testuser3');
        $session->put('role', '');
        $request->setLaravelSession($session);
        $this->app->instance('session.store', $session);

        $controller = new \App\Http\Controllers\AdminPaginationController();
        $response = $controller->paginateUserLinks($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals(1, $data['recordsTotal']);
    }

    public function testPaginateAdminUsersAndLinks() {
        UserFactory::createUser('adminuser', 'admin@example.com', 'password123', 1);
        $admin = \App\Models\User::where('username', 'adminuser')->first();
        $admin->role = 'admin';
        $admin->save();

        \App\Factories\LinkFactory::createLink('https://example.com/adminlink', false, 'adminlink', '127.0.0.1', 'adminuser');

        $request = \Illuminate\Http\Request::create('/api/v2/admin/get_admin_users', 'GET', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $this->app->instance('request', $request);
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('username', 'adminuser');
        $session->put('role', 'admin');
        $request->setLaravelSession($session);
        $this->app->instance('session.store', $session);

        $controller = new \App\Http\Controllers\AdminPaginationController();
        $usersResponse = $controller->paginateAdminUsers($request);
        $this->assertEquals(200, $usersResponse->getStatusCode());
        $usersData = json_decode($usersResponse->getContent(), true);
        $this->assertArrayHasKey('data', $usersData);

        $linksResponse = $controller->paginateAdminLinks($request);
        $this->assertEquals(200, $linksResponse->getStatusCode());
        $linksData = json_decode($linksResponse->getContent(), true);
        $this->assertArrayHasKey('data', $linksData);
    }
}

