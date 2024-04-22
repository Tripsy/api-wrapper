<?php

declare(strict_types=1);

namespace Tripsy\ApiWrapper;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;

class ApiWrapper
{
    /**
     * Flag used to enable debug
     */
    private bool $debug = false;

    /**
     * Array which contain all the info
     */
    private array $result;

    public function __construct()
    {
        $this->result = [
            'success' => false,
            'message' => '',
            'errors' => '',
            'data' => [],
            //'meta' => [],
        ];
    }

    /**
     * Utility function which turns debug on
     */
    public function debug(?bool $value = null): bool
    {
        if (is_null($value) === false) {
            $this->debug = $value;
        }

        return $this->debug;
    }

    /**
     * Helper method used to set `key` value in $this->result
     */
    public function set(string $key, mixed $value): void
    {
        Arr::set($this->result, $key, $value);
    }

    /**
     * Helper method used to get `key` value from $this->result
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->result, $key, $default);
    }

    /**
     * Helper method used to get `key` value
     * If value is provided as argument it will set that value than return it
     */
    private function property(string $key, mixed $value = null): mixed
    {
        if (empty($value) === false) {
            $this->set($key, $value);
        }

        return $this->get($key);
    }

    /**
     * Shortcut method used to get `success` value
     * If value is provided as argument it will set that value than return it
     */
    public function success(?bool $value = null): bool
    {
        if (is_null($value) === false) {
            $this->set('success', $value);
        }

        return $this->get('success');
    }

    /**
     * Shortcut method used to get `message` value
     * If value is provided as argument it will set that value than return it
     */
    public function message(?string $value = null): string
    {
        return $this->property('message', $value);
    }

    /**
     * Shortcut method used to get `errors` value
     * If value is provided as argument it will set that value than return it
     */
    public function errors(mixed $value = null): mixed
    {
        return $this->property('errors', $value);
    }

    /**
     * Push message to `errors` key
     */
    public function pushError(string $value, ?string $key = null): void
    {
        $this->result['errors'][$key] = $value;
    }

    /**
     * Shortcut method used to get `data` value
     * If value is provided as argument it will set that value than return it
     */
    public function data(mixed $value = null): mixed
    {
        return $this->property('data', $value);
    }

    /**
     * Push entry to `data` key
     */
    public function pushData(string $key, mixed $value): void
    {
        $this->result['data'][$key] = $value;
    }

    /**
     * Shortcut method used to get `data` OR `data.{subKey}' value
     */
    public function getData(?string $subKey = null): mixed
    {
        $key = 'data';

        if ($subKey) {
            $key .= '.'.$subKey;
        }

        return $this->property($key);
    }

    /**
     * Shortcut method used to get `meta` value
     * If value is provided as argument it will set that value than return it
     */
    public function meta(mixed $value = null): mixed
    {
        return $this->property('meta', $value);
    }

    /**
     * Push entry to `meta` key
     */
    public function pushMeta(string $key, mixed $value): void
    {
        $this->result['meta'][$key] = $value;
    }

    /**
     * Shortcut method used to get `meta` OR `meta.{subKey}' value
     */
    public function getMeta(?string $subKey = null): mixed
    {
        $key = 'meta';

        if ($subKey) {
            $key .= '.'.$subKey;
        }

        return $this->property($key);
    }

    /**
     * Shortcut method used to get `request` value
     * If value is provided as argument it will set that value than return it
     *
     * @param  bool|null  $value
     * @return bool
     */
    public function request(mixed $value = null): mixed
    {
        return $this->property('request', $value);
    }

    /**
     * Shortcut method used to get `request.method` existing value
     * If value is provided as argument it will set that value than return it
     */
    public function requestMethod(?string $value = null): string
    {
        return $this->property('request.method', $value);
    }

    /**
     * Shortcut method used to get `request.url` value
     * If value is provided as argument it will set that value than return it
     */
    public function requestUrl(?string $value = null): string
    {
        return $this->property('request.url', $value);
    }

    /**
     * Shortcut method used to get `request.headers` OR 'request.headers.{subKey}' value
     * If value is provided as argument it will set that value than return it
     */
    public function requestHeaders(?string $subKey = null, mixed $value = null): mixed
    {
        $key = 'request.headers';

        if ($subKey) {
            $key .= '.'.$subKey;
        }

        return $this->property($key, $value);
    }

    /**
     * Shortcut method used to get `request.params` value
     * If value is provided as argument it will set that value than return it
     *
     * @param  bool|null  $value
     * @return bool
     */
    public function requestParams(mixed $value = null): mixed
    {
        return $this->property('request.params', $value);
    }

    /**
     * Shortcut method used to get `response` value
     * If value is provided as argument it will set that value than return it
     *
     * @param  bool|null  $value
     * @return bool
     */
    public function response(mixed $value = null): mixed
    {
        return $this->property('response', $value);
    }

    /**
     * Make an API request and handle response
     */
    public function makeRequest(callable $response): void
    {
        try {
            $this->handleResponse(call_user_func($response));
        } catch (RequestException|ConnectionException $e) {
            $this->success(false);
            $this->message($e->getMessage());
        }
    }

    /**
     * Alter $this->result based on API response
     */
    public function handleResponse(Response $response): void
    {
        $responseBody = $response->body();

        if ($this->debug() === true) {
            dd($responseBody);
        }

        if ($response->successful()) {
            $this->success(true);
            $this->set('response.status_code', $response->status());
            $this->set('response.body', $responseBody);
            $this->data($responseBody);
        } else {
            $this->response([
                'status_code' => $response->status(),
                'client_error' => $response->clientError(),
                'server_error' => $response->serverError(),
                'body' => $responseBody,
            ]);

            $this->message(__('api.generic_error'));
        }

        if ($this->isJson($responseBody)) {
            $responseJson = $response->json();

            if (empty($responseJson['error']) === false) {
                $this->success(false);

                if (is_array($responseJson['error']) === true) {
                    $this->errors($responseJson['error']);
                } elseif (is_string($responseJson['error']) === true) {
                    $this->message($responseJson['error']);
                }
            }

            if (isset($responseJson['success']) === true) {
                $this->success($responseJson['success']);
            }

            if (empty($responseJson['message']) === false) {
                $this->message($responseJson['message']);
            }

            if (empty($responseJson['errors']) === false) {
                $this->errors($responseJson['errors']);
            }

            if (empty($responseJson['data']) === false) {
                $this->data($responseJson['data']);
            }

            if (empty($responseJson['meta']) === false) {
                $this->meta($responseJson['meta']);
            }
        }
    }

    /**
     * Getter method which will return $this->result data
     */
    public function resultArray(): array
    {
        if ($this->success() === false) {
            unset($this->result['data']);
        } else {
            if (is_string($this->result['data']) && $this->isJson($this->result['data']) === true) {
                $this->result['data'] = json_decode($this->result['data'], true);
            }
        }

        return $this->result;
    }

    /**
     * Getter method which will return json encoded string based on $this->result
     */
    public function resultJson(): string
    {
        return json_encode($this->resultArray());
    }

    /**
     * Return true if string has valid JSON format
     */
    private function isJson(string $string): bool
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
