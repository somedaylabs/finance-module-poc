<?php

class FreshbooksTest extends TestCase
{
    public function testStoredBearerToken(): void
    {
        $auth = $this->app->make(\App\Providers\Freshbooks\Authentication::class);
        $this->assertEquals(64, strlen($auth->getBearerToken()));
    }

    public function testAccountIds(): void
    {
        $identity = $this->app->make(\App\Providers\Freshbooks\Identity::class);
        $this->assertGreaterThan(0, $identity->getUserId());
        $this->assertGreaterThan(0, $identity->getBusinessId());
        $this->assertGreaterThan(1, strlen($identity->getAccountId()));
    }
}
