<?php

namespace App\Providers\Freshbooks;

use App\Exceptions\FreshbooksServiceException;

class CustomerService
{
    private $client;
    private $identity;

    public function __construct(ApiClient $client, Identity $identity)
    {
        $this->client = $client;
        $this->identity = $identity;
    }

    /**
     * @param string $email
     * @param null|string $name
     * @return array
     * @throws FreshbooksServiceException
     */
    public function createCustomer(string $email, ?string $name = null): array
    {
        if ($this->getCustomerDetails($email) !== null) {
            throw new \InvalidArgumentException("Customer with email address {$email} already exists");
        }

        $details = ["email" => $email];
        if (\strlen($name) > 0) {
            $details["organization"] = $name;
        }

        $url = "{$this->identity->getAccountUrl()}/users/clients";
        $result = $this->client->post($url, ["client" => $details]);
        if ($this->client->isError($result)) {
            $error_message = $this->client->getErrorMessage($result);
            throw new FreshbooksServiceException("Failed to create customer: {$error_message}");
        }
        return $result["response"]["result"]["client"];
    }

    /**
     * @param string $email
     * @return array|null
     * @throws FreshbooksServiceException
     */
    public function getCustomerDetails(string $email): ?array
    {
        $search = ["email" => $email];

        $url = "{$this->identity->getAccountUrl()}/users/clients";
        $result = $this->client->get($url, ["search" => $search, "per_page" => 1]);
        if ($this->client->isError($result)) {
            $error_message = $this->client->getErrorMessage($result);
            throw new FreshbooksServiceException("Customer search failure: {$error_message}");
        }
        if ($result["response"]["result"]["total"] === 0) {
            return null;
        }
        return $result["response"]["result"]["clients"][0];
    }
}
