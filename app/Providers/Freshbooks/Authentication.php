<?php

namespace App\Providers\Freshbooks;

use App\Exceptions\FreshbooksServiceException;

class Authentication
{
    private const BEARER_TOKEN_FILE = "./storage/app/freshbooks-token.secret";
    private const BEARER_TOKEN_LIFE = 8 * 3600; //8 hours

    private $client;
    private $auth_url;
    private $redirect_uri;
    private $client_id;
    private $client_secret;
    private $code_url;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
        $this->auth_url = env("FRESHBOOKS_OAUTH_URL");
        $this->redirect_uri = env("FRESHBOOKS_OAUTH_REDIRECT_URI");
        $this->client_id = env("FRESHBOOKS_OAUTH_CLIENT_ID");
        $this->client_secret = env("FRESHBOOKS_OAUTH_CLIENT_SECRET");
        $this->code_url = env("FRESHBOOKS_OAUTH_AUTHORIZATION_URL");
    }

    /**
     * @return string the token
     * @throws FreshbooksServiceException if no token is available
     */
    public function getBearerToken(): string
    {
        if (!file_exists(self::BEARER_TOKEN_FILE)) {
            throw new FreshbooksServiceException("Bearer credentials not found; run `artisan freshbooks:bearer-token`");
        }
        $tokens = self::getStoredTokenData();
        $age = time() - (int)$tokens[2];
        if ($age > self::BEARER_TOKEN_LIFE) {
            $this->refreshBearerToken();
            $tokens = self::getStoredTokenData();
        }
        return $tokens[0];
    }

    /**
     * The URL to visit to get a code
     * @return string
     */
    public function getAuthorizationUrl(): string
    {
        $code_url = $this->code_url;
        return str_replace(["{client_id}", "{redirect_uri}"], [$this->client_id, $this->redirect_uri], $code_url);
    }

    /**
     * Seed the bearer token configuration from the given response code. Get a code by visiting the authorization URL.
     * @see self::getAuthorizationUrl()
     * @param string $code
     * @throws FreshbooksServiceException
     */
    public function initBearerToken(string $code): void
    {
        $req = [
            "grant_type" => "authorization_code",
            "code" => $code,
            "client_id" => $this->client_id,
            "client_secret" => $this->client_secret,
            "redirect_uri" => $this->redirect_uri
        ];
        $output = $this->client->postWithoutAuthentication($this->auth_url, $req);
        $this->persistTokens($output);
    }

    /**
     * @throws FreshbooksServiceException
     */
    public function refreshBearerToken(): void
    {
        $tokens = self::getStoredTokenData();
        $req = [
            "grant_type" => "refresh_token",
            "refresh_token" => $tokens[1],
            "client_id" => $this->client_id,
            "client_secret" => $this->client_secret,
            "redirect_uri" => $this->redirect_uri
        ];
        $output = $this->client->postWithoutAuthentication($this->auth_url, $req);
        $this->persistTokens($output);
    }

    /**
     * @param $output array the JSON response
     * @throws FreshbooksServiceException
     */
    private function persistTokens(array $output): void
    {
        if ($output === null) {
            throw new FreshbooksServiceException("Invalid API response");
        }
        if (array_key_exists("access_token", $output)) {
            $bearer = $output["access_token"];
            $refresh = $output["refresh_token"];
            $timestamp = $output["created_at"];
            file_put_contents(self::BEARER_TOKEN_FILE, "{$bearer}|{$refresh}|{$timestamp}\n");
        } else {
            $as_string = json_encode($output);
            throw new FreshbooksServiceException("Invalid response: {$as_string}");
        }
    }

    private static function getStoredTokenData()
    {
        return explode("|", trim(file_get_contents(self::BEARER_TOKEN_FILE)));
    }
}
