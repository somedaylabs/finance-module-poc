<?php

namespace App\Providers\Freshbooks;

use App\Exceptions\FreshbooksServiceException;

class InvoiceService
{
    private $client;
    private $identity;
    private $customers;

    public function __construct(ApiClient $client, Identity $identity, CustomerService $customers)
    {
        $this->client = $client;
        $this->identity = $identity;
        $this->customers = $customers;
    }

    /**
     * @param string $email
     * @param array $line_items
     * @return array
     * @throws FreshbooksServiceException
     */
    public function createInvoice(string $email, array $line_items): array
    {
        $customer = $this->getCustomer($email);
        $lines = array_map(function ($item, $value) {
            return [
                "name" => $item,
                "qty" => 1,
                "unit_cost" => [
                    "amount" => $value,
                    "code" => "USD"
                ]
            ];
        }, array_keys($line_items), $line_items);
        $invoice = [
            "customerid" => $customer["id"],
            "email" => $email,
            "lines" => $lines,
            "create_date" => date("Y-m-d")
        ];

        $url = "{$this->identity->getAccountUrl()}/invoices/invoices";
        $result = $this->client->post($url, ["invoice" => $invoice]);
        if ($this->client->isError($result)) {
            $error_message = $this->client->getErrorMessage($result);
            throw new FreshbooksServiceException("Failed to create invoice: {$error_message}");
        }
        return $result["response"]["result"]["invoice"];
    }

    /**
     * @param string $email
     * @return array
     * @throws FreshbooksServiceException
     */
    public function getInvoices(string $email): array
    {
        $customer = $this->getCustomer($email);
        $search = [
            "customerid" => $customer["id"]
        ];
        $url = "{$this->identity->getAccountUrl()}/invoices/invoices";
        $result = $this->client->get($url, ["search" => $search, "per_page" => 25, "include[]" => "lines"]);
        if ($this->client->isError($result)) {
            $error_message = $this->client->getErrorMessage($result);
            throw new FreshbooksServiceException("Invoice search failure: {$error_message}");
        }
        if ($result["response"]["result"]["total"] === 0) {
            return null;
        }
        return $result["response"]["result"]["invoices"];
    }

    /**
     * @param string $email
     * @return array
     * @throws \InvalidArgumentException
     * @throws FreshbooksServiceException
     */
    private function getCustomer(string $email): array
    {
        $customer = $this->customers->getCustomerDetails($email);
        if ($customer === null) {
            throw new \InvalidArgumentException("Customer {$email} does not exist in invoicing system");
        }
        return $customer;
    }
}
