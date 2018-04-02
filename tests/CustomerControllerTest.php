<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

class CustomerControllerTest extends TestCase
{
    use DatabaseTransactions;

    private $mock_customer_service;

    public function setUp(): void
    {
        parent::setUp();
        $this->mock_customer_service = Mockery::mock(\App\Providers\Freshbooks\CustomerService::class);
        $this->app->instance(\App\Providers\Freshbooks\CustomerService::class, $this->mock_customer_service);
    }

    public function testShowExists(): void
    {
        $customer = factory(\App\Customer::class)->create();
        $this->get("/customers/{$customer->id}");
        $this->seeJson([
            "name" => $customer->name,
            "email" => $customer->email,
            "created_at" => (string) $customer->created_at
        ]);
    }

    public function testShowMissing(): void
    {
        $this->get("/customers/1001");
        $this->assertResponseStatus(404);
    }

    public function testIndex(): void
    {
        factory(\App\Customer::class)->times(3)->create();
        $this->get("/customers");
        $this->seeJsonStructure([
            "*" => ["id", "name", "email"]
        ]);
    }

    public function testCreateValid(): void
    {
        $this->mock_customer_service->shouldReceive("createCustomer")->once();

        $customer = factory(\App\Customer::class)->make();
        $this->json("POST", "/customers", ["name" => $customer->name, "email" => $customer->email]);
        $this->assertResponseOk();
        $this->seeInDatabase("customers", ["name" => $customer->name, "email" => $customer->email]);
    }

    public function testCreateDuplicateEmail(): void
    {
        $existing_customer = factory(\App\Customer::class)->create();
        $this->json("POST", "/customers", ["name" => "Anything", "email" => $existing_customer->email]);
        $this->assertResponseStatus(422);
    }

    public function testCreateRejectedByFreshbooks(): void
    {
        $this->mock_customer_service->shouldReceive("createCustomer")->andThrow(\InvalidArgumentException::class);
        $this->json("POST", "/customers", ["name" => "Anything", "email" => "duplicate@example.com"]);
        $this->assertResponseStatus(422);
    }

    public function testFreshbooksExceptionPreventsCustomerRecord(): void
    {
        $this->mock_customer_service->shouldReceive("createCustomer")->andThrow(\App\Exceptions\FreshbooksServiceException::class);

        $customer = factory(\App\Customer::class)->make();
        $this->json("POST", "/customers", ["name" => $customer->name, "email" => $customer->email]);
        $this->assertResponseStatus(500);
        $this->notSeeInDatabase("customers", ["name" => $customer->name, "email" => $customer->email]);
    }
}
