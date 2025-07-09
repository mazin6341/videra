<?php

namespace App\Clients;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\DB;

class IgdbClient {
    /**
     * The cache key for storing the Twitch access token.
     */
    private const TOKEN_CACHE_KEY = 'igdb_api_token';

    private string $base_url;
    private string $auth_url;
    private string $client_id;
    private string $client_secret;
    private Client $http_client;
    
    public function __construct() {
        $this->base_url         = config('services.igdb.base_url');
        $this->auth_url         = config('services.igdb.auth_url');
        $this->client_id        = config('services.igdb.client_id');
        $this->client_secret    = config('services.igdb.client_secret');

        if (!$this->base_url || !parse_url($this->base_url)) {
            throw new Exception("IGDB base URL is missing or invalid in your configuration.");
        }
        if (!$this->auth_url || !parse_url($this->auth_url)) {
            throw new Exception("Twitch authentication URL is missing or invalid in your configuration.");
        }
        if (!$this->client_id || !$this->client_secret) {
            throw new Exception("Twitch Client ID or Secret is missing from your configuration.");
        }

        // We use composition here. This class *uses* a Guzzle client,
        // but it isn't a Guzzle client itself. This provides better encapsulation.
        $this->http_client = new Client([
            'base_uri' => $this->base_url,
        ]);
    }

    /**
     * Authenticates with the Twitch API to get an OAuth access token.
     *
     * This method performs a POST request to the Twitch OAuth2 endpoint
     * to get a new access token using the client credentials grant type.
     * The token is then cached for its duration to avoid unnecessary auth calls.
     *
     * @return string The access token.
     * @throws GuzzleException if the request to the Twitch API fails.
     * @throws Exception if the response from Twitch is invalid or lacks a token.
     */
    private function authenticate(): string {
        // Check if a valid token already exists in the cache.
        if (Cache::has(self::TOKEN_CACHE_KEY))
            return Cache::get(self::TOKEN_CACHE_KEY);

        // If not cached, request a new token from Twitch.
        $response = $this->http_client->post($this->auth_url . '/token', [
            RequestOptions::FORM_PARAMS => [
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'client_credentials',
            ],
        ]);
        
        $data = json_decode($response->getBody()->getContents(), true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['access_token']))
            throw new Exception('Failed to retrieve access token from Twitch.');

        $access_token = $data['access_token'];
        $expires_in = $data['expires_in'] ?? 3600; // Default to 1 hour if not provided

        // Cache the token. We subtract a small buffer (5 minutes) from the expiry time to be safe.
        Cache::put(self::TOKEN_CACHE_KEY, $access_token, $expires_in - 300);

        return $access_token;
    }

    /**
     * Sends an HTTP request to the IGDB API.
     *
     * This is the main method for interacting with the IGDB API. It ensures
     * that the client is authenticated, then sends the request with the
     * necessary headers (Client-ID and Authorization).
     *
     * @param string $method The HTTP method (e.g., 'GET', 'POST').
     * @param string|UriInterface $uri The URI to request (e.g., 'games', 'genres').
     * @param array $options Request options to apply (e.g., 'body', 'query').
     * @return ResponseInterface The response from the API.
     * @throws GuzzleException
     * @throws Exception
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface {
        // Get the access token (from cache or by authenticating).
        $access_token = $this->authenticate();

        // Merge the required authentication headers with any custom headers.
        $defaultHeaders = [
            'Client-ID'     => $this->client_id,
            'Authorization' => 'Bearer ' . $access_token,
        ];

        $options['headers'] = array_merge($defaultHeaders, $options['headers'] ?? []);

        // Make the request using the composed Guzzle client.
        return $this->http_client->request($method, "v4/{$uri}", $options);
    }
}