<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

class BillingControllerTest extends TestCase
{
    use DatabaseTransactions;

    private $mock_invoice_service;

    public function setUp(): void
    {
        parent::setUp();
        $this->mock_invoice_service = Mockery::mock(\App\Providers\Freshbooks\InvoiceService::class);
        $this->app->instance(\App\Providers\Freshbooks\InvoiceService::class, $this->mock_invoice_service);
    }

    public function testShowExists(): void
    {
        $billing = factory(\App\Billing::class)->create();
        $billing->populateLineItems([
            ["line_number" => 1, "description" => "Item", "amount" => $billing->total]
        ]);
        $this->get("/billings/{$billing->id}");
        $this->seeJson([
            "billing_date" => $billing->billing_date->format("Y-m-d"),
            "total" => $billing->total,
            "created_at" => (string) $billing->created_at
        ]);
        $this->seeJson([
            "line_number" => 1,
            "description" => "Item",
            "billing_id" => $billing->id
        ]);
    }

    public function testShowMissing(): void
    {
        $this->get("/billings/5555");
        $this->assertResponseStatus(404);
    }

    public function testIndex(): void
    {
        $billing = factory(\App\Billing::class)->create();
        $this->get("/customers/{$billing->customer->id}/billings");
        $this->seeJsonStructure([
            "*" => ["id", "billing_date", "status", "total"]
        ]);
    }

    public function testCreateValid(): void
    {
        $customer = factory(\App\Customer::class)->create();
        $expected_details = [
            "Test primary line item" => 3600,
            "Test secondary line item" => 150
        ];
        $this->mock_invoice_service->shouldReceive("createInvoice")->once()->with($customer->email, $expected_details);

        $request = [
            "billing_date" => date("Y-m-d"),
            "status" => "new",
            "total" => 3750,
            "line_items" => [
                ["line_number" => 1, "amount" => 3600, "description" => "Test primary line item"],
                ["line_number" => 2, "amount" => 150, "description" => "Test secondary line item"]
            ]
        ];
        $this->json("POST", "/customers/{$customer->id}/billings", $request);
        $this->assertResponseOk();
        $this->seeInDatabase("billings", ["customer_id" => $customer->id, "total" => 3750]);
        $this->seeInDatabase("billing_line_items", ["line_number" => 1, "amount" => 3600]);
        $this->seeInDatabase("billing_line_items", ["line_number" => 2, "amount" => 150]);
    }

    public function testCreateMissingDate(): void
    {
        $request = [
            "total" => 800,
        ];

        $customer = factory(\App\Customer::class)->create();
        $this->json("POST", "/customers/{$customer->id}/billings", $request);
        $this->assertResponseStatus(422);
    }

    public function testCreateDuplicateLineNumber(): void
    {
        $request = [
            "billing_date" => date("Y-m-d"),
            "status" => "new",
            "total" => 5000,
            "line_items" => [
                ["line_number" => 1, "amount" => 4500, "description" => "Test primary line item"],
                ["line_number" => 1, "amount" => 500, "description" => "Test secondary line item"]
            ]
        ];

        $customer = factory(\App\Customer::class)->create();
        $this->json("POST", "/customers/{$customer->id}/billings", $request);
        $this->assertResponseStatus(422);
    }
}
