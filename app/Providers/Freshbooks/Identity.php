<?php

namespace App\Providers\Freshbooks;

class Identity
{
    private $client;
    private $identifiers;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return int
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    public function getUserId(): int
    {
        return (int) $this->getIdentifiers()["user_id"];
    }

    /**
     * @return int
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    public function getBusinessId(): int
    {
        return (int) $this->getIdentifiers()["business_id"];
    }

    /**
     * @return string
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    public function getAccountId(): string
    {
        return $this->getIdentifiers()["account_id"];
    }

    /**
     * @return string
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    public function getAccountUrl(): string
    {
        return "https://api.freshbooks.com/accounting/account/{$this->getAccountId()}";
    }

    /**
     * @return array
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    private function getIdentifiers(): array
    {
        if ($this->identifiers === null) {
            $result = $this->client->get("https://api.freshbooks.com/auth/api/v1/users/me", []);
            $admin = array_filter($result["response"]["roles"], function ($role) {
                return $role["role"] === "admin";
            });
            $this->identifiers = [
                "user_id" => $result["response"]["id"],
                "business_id" => $result["response"]["profession"]["business_id"],
                "account_id" => $admin[0]["accountid"]
            ];
        }
        return $this->identifiers;
    }
}
