<?php

namespace App\Providers\Freshbooks;

class ApiClient
{
    private $authentication;

    public function __construct(?Authentication $authentication)
    {
        $this->authentication = $authentication;
    }

    /**
     * @param string $url
     * @param array $params
     * @return array
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    public function post(string $url, array $params): array
    {
        return $this->invoke("POST", $url, $params, true);
    }

    /**
     * @param string $url
     * @param array $params
     * @return array
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    public function get(string $url, array $params): array
    {
        return $this->invoke("GET", $url, $params, true);
    }

    /**
     * @param $url string the API endpoint
     * @param $req array as associative array of data fields; will be converted to JSON
     * @return array the response JSON as an associative array
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    public function postWithoutAuthentication(string $url, array $req): array
    {
        return $this->invoke("POST", $url, $req, false);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @param bool $authenticate
     * @return array
     * @throws \App\Exceptions\FreshbooksServiceException
     */
    private function invoke(string $method, string $url, array $params, bool $authenticate): array
    {
        $headers = [
            "Api-Version: alpha",
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ];
        if ($authenticate) {
            $headers[] = "Authorization: Bearer {$this->authentication->getBearerToken()}";
        }

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECT_TO => ["api.freshbooks.com:443:proxy:28000"],
            CURLOPT_SSL_VERIFYHOST => 0
        ];
        if ($method === "POST") {
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            $opts[CURLOPT_POSTFIELDS] = json_encode($params);
        } else {
            $query = http_build_query($params);
            $opts[CURLOPT_URL] = "{$url}?{$query}";
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);
        if ($response === false) {
            return [];
        }
        return json_decode($response, true);
    }

    public function isError(array $result): bool
    {
        return array_key_exists("errors", $result["response"]);
    }

    public function getErrorMessage(array $result): ?string
    {
        return $result["response"]["errors"][0]["message"];
    }
}
