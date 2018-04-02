<?php

namespace App\Http\Controllers;

use App\Billing;
use App\BillingLineItem;
use App\Customer;
use App\Providers\Freshbooks\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    private $accounting_invoice_service;

    public function __construct(InvoiceService $invoice_service)
    {
        $this->accounting_invoice_service = $invoice_service;
    }

    public function show($id): Billing
    {
        return Billing::whereKey($id)->with("line_items")->firstOrFail();
    }

    public function indexByCustomer($customer_id): array
    {
        return Customer::findOrFail($customer_id)->billings->toArray();
    }

    /**
     * @param Request $request
     * @param $id
     * @return Billing
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request, $id): Billing
    {
        $this->validate($request, [
            "billing_date" => "required|date|before_or_equal:now",
            "status" => "nullable|" . Rule::in(Billing::STATES),
            "total" => "required|integer|min:0",
            "paid_date" => "nullable|date|after_or_equal:billing_date",
            "paid_amount" => "nullable|integer|min:0",
            "line_items.*.line_number" => "required|integer|min:0",
            "line_items.*.description" => "required",
            "line_items.*.amount" => "required|integer|min:0"
        ]);
        app("db")->transaction(function () use ($request, $id, &$billing) {
            $customer = Customer::findOrFail($id);
            $billing = $customer->billings()->create($request->all());
            $billing->populateLineItems($request->input("line_items"));
            $this->createInvoice($billing);
        });
        return $billing;
    }

    /**
     * @param Billing $billing
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    private function createInvoice(Billing $billing): void
    {
        $invoice_items = $billing->line_items()->orderBy("line_number")->get()->mapWithKeys(function ($item) {
            return [$item->description => $item->amount];
        });
        $this->accounting_invoice_service->createInvoice($billing->customer->email, $invoice_items->toArray());
    }
}
