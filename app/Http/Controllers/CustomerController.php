<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Providers\Freshbooks\CustomerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private $accounting_customer_service;

    public function __construct(CustomerService $customer_service)
    {
        $this->accounting_customer_service = $customer_service;
    }

    public function show($id): Customer
    {
        return Customer::findOrFail($id);
    }

    public function index(): Collection
    {
        return Customer::orderBy("email")->get();
    }

    /**
     * @param Request $request
     * @return Customer
     * @throws \App\Exceptions\FreshbooksServiceException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request): Customer
    {
        $this->validate($request, [
            "name" => "required",
            "email" => "required|email|unique:customers"
        ]);
        app("db")->transaction(function () use ($request, &$c) {
            $c = Customer::create($request->all());
            $this->accounting_customer_service->createCustomer($c->email, $c->name);
        });
        return $c;
    }
}
