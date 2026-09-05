<?php

use App\Factories\UserFactory;
use App\Factories\LinkFactory;
use App\Models\User;
use App\Models\Link;
use App\Models\Click;

class HousekeepingTest extends TestCase
{
    protected function setupAdminUser()
    {
        $user = UserFactory::createUser('houseadmin', 'houseadmin@example.com', 'adminpass123', 1);
        $userModel = User::where('username', 'houseadmin')->first();
        $userModel->role = 'admin';
        $userModel->save();

        return $userModel;
    }

    protected function setupRegularUser()
    {
        return UserFactory::createUser('houseregular', 'houseregular@example.com', 'userpass123', 1);
    }

    public function testHousekeepingRequiresAdmin()
    {
        $this->setupRegularUser();

        $request = \Illuminate\Http\Request::create('/api/v2/admin/housekeeping/preview', 'POST', [
            'pattern' => 'spam.xyz',
        ]);

        $this->app->instance('request', $request);
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('username', 'houseregular');
        $session->put('role', '');
        $request->setLaravelSession($session);
        $this->app->instance('session.store', $session);

        $controller = new \App\Http\Controllers\AjaxController();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->previewHousekeepingLinks($request);
    }

    public function testPreviewHousekeepingLinks()
    {
        $this->setupAdminUser();

        // Create several links (2 spam, 1 legit)
        LinkFactory::createLink('https://spammy-site.xyz/promo/1', false, 'spam1', '127.0.0.1', 'polr');
        LinkFactory::createLink('https://another.spammy-site.xyz/landing', false, 'spam2', '127.0.0.1', 'polr');
        LinkFactory::createLink('https://legit-site.com/about', false, 'legit1', '127.0.0.1', 'houseadmin');

        $request = \Illuminate\Http\Request::create('/api/v2/admin/housekeeping/preview', 'POST', [
            'pattern' => 'spammy-site.xyz',
            'match_type' => 'domain',
            'scope' => 'all',
        ]);

        $this->app->instance('request', $request);
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('username', 'houseadmin');
        $session->put('role', 'admin');
        $request->setLaravelSession($session);
        $this->app->instance('session.store', $session);

        $controller = new \App\Http\Controllers\AjaxController();
        $response = $controller->previewHousekeepingLinks($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals(2, $data['count']);
        $this->assertCount(2, $data['sample']);
    }

    public function testPerformHousekeepingDisable()
    {
        $this->setupAdminUser();

        $l1 = LinkFactory::createLink('https://phishing.xyz/login', false, 'phish1', '127.0.0.1', 'polr', true);
        $l2 = LinkFactory::createLink('https://phishing.xyz/verify', false, 'phish2', '127.0.0.1', 'polr', true);

        $this->assertEquals(0, $l1->is_disabled);
        $this->assertEquals(0, $l2->is_disabled);

        $request = \Illuminate\Http\Request::create('/api/v2/admin/housekeeping/clean', 'POST', [
            'pattern' => 'phishing.xyz',
            'match_type' => 'domain',
            'scope' => 'all',
            'action_type' => 'disable',
        ]);

        $this->app->instance('request', $request);
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('username', 'houseadmin');
        $session->put('role', 'admin');
        $request->setLaravelSession($session);
        $this->app->instance('session.store', $session);

        $controller = new \App\Http\Controllers\AjaxController();
        $response = $controller->performHousekeepingClean($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('disable', $data['action']);
        $this->assertEquals(2, $data['affected']);

        // Check disabled status in DB
        $updatedL1 = Link::where('short_url', 'phish1')->first();
        $this->assertEquals(1, $updatedL1->is_disabled);
    }

    public function testPerformHousekeepingDelete()
    {
        $this->setupAdminUser();

        $l1 = LinkFactory::createLink('https://malware.top/payload', false, 'mal1', '127.0.0.1', 'polr', true);

        // Add dummy click
        $click = new Click();
        $click->link_id = $l1->id;
        $click->ip = '127.0.0.1';
        $click->save();

        $this->assertNotNull(Link::where('short_url', 'mal1')->first());
        $this->assertEquals(1, Click::where('link_id', $l1->id)->count());

        $request = \Illuminate\Http\Request::create('/api/v2/admin/housekeeping/clean', 'POST', [
            'pattern' => 'malware.top',
            'match_type' => 'domain',
            'scope' => 'all',
            'action_type' => 'delete',
        ]);

        $this->app->instance('request', $request);
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('username', 'houseadmin');
        $session->put('role', 'admin');
        $request->setLaravelSession($session);
        $this->app->instance('session.store', $session);

        $controller = new \App\Http\Controllers\AjaxController();
        $response = $controller->performHousekeepingClean($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('delete', $data['action']);
        $this->assertEquals(1, $data['affected']);

        $this->assertNull(Link::where('short_url', 'mal1')->first());
        $this->assertEquals(0, Click::where('link_id', $l1->id)->count());
    }
}
