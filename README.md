
# Description

This wrapper can be used to build requests, handle responses and also
to create a standard response for a REST API.

Response will contain the following keys:

- success
    - return `true` if no errors are encountered
- message
    - return a `string` message which describe the response
- errors 
    - return an `array` containing a detailed list with errors encountered
- data @return mixed
    - return `array` or `string` containing relevant data for the response
- meta @return array
    - optional additional info can be returned as an `array` as key / value pair

# Requirements

This package has been build to be used in Laravel, but it doesn't have strong dependencies.

The package assumes the requests are made with `lluminate\Support\Facades\Http`

Recommendations:
  * php ^7.3
  * laravel/framework ^8 

# Install

Require the package using composer:

      composer require tripsy/api-wrapper

# Examples

### Output response

    use Tripsy\ApiWrapper\ApiWrapper;

    function index(ApiWrapper $apiWrapper): JsonResponse {
        $results = [];

        $apiWrapper->success(true);
        $apiWrapper->message(__('message.success'));
        $apiWrapper->data([
            'results' => $results,
            'count' => count($results),
        ]);

        return response()->json($apiWrapper->resultArray(), Response::HTTP_OK);
    }

### POST request

    use Tripsy\ApiWrapper\ApiWrapper;

    function store(ApiWrapper $apiWrapper): string
    {
        $apiWrapper->requestMethod('POST');
        $apiWrapper->requestUrl('https://request.url');

        $validate = true;

        if ($validate === false) {
            // the next line of code is not mandatory
            // by default value for `success` is false anyway
            $apiWrapper->success(false);

            $apiWrapper->message(__('message.error'));

            $apiWrapper->errors([
                'Sample error',
                'Another sample error',
            ]);

            $apiWrapper->pushMeta('source', 'my_method');
            $apiWrapper->pushMeta('reference', 'my_app');
        } else {
            $apiWrapper->requestParams([
                'name' => 'John',
                'age' => '34',
                'gender' => 'male',
            ]);

            $apiWrapper->requestHeaders('X_FORWARDED_FOR', '127.0.0.1');

            $apiWrapper->makeRequest(function () use ($apiWrapper) {
                return Http::asForm()
                    ->withHeaders($apiWrapper->requestHeaders())
                    ->withOptions([
                        'verify' => true,
                    ])
                    ->timeout(120)
                    ->post($apiWrapper->requestUrl(), $apiWrapper->requestParams());
            });

            // `success` will be set as true within `makeRequest` to reflect if the API response status code was between 200 & 300
            if ($apiWrapper->success() === true) {
                $apiWrapper->data(json_decode($apiWrapper->data(), true));

                //handle `result`
                switch ($apiWrapper->getData('result')) {
                    case 'success':
                        $apiWrapper->pushMeta('happy', 'yes');
                        break;
                    case 'failed':
                        // since the API request was a success but didn't returned the expected result
                        $apiWrapper->success(false);

                        $apiWrapper->pushMeta('happy', 'no');

                        $apiWrapper->message(__('message.failed'));
                        break;
                    default:
                        $apiWrapper->pushMeta('happy', '?');

                        $apiWrapper->success(false);
                        $apiWrapper->message(__('message.result_not_defined'));
                        break;
                }
            }
        }

        return $apiWrapper->resultJson();
    }

### Another POST request

    use Tripsy\ApiWrapper\ApiWrapper;

    function query(ApiWrapper $apiWrapper): array
    {
        $apiWrapper->requestMethod('POST');
        $apiWrapper->requestUrl('https://request.url');
        $apiWrapper->requestHeaders('Content-Type', 'application/json');
        $apiWrapper->requestHeaders('Accept', 'application/json');
        $apiWrapper->requestParams([
            'formData' => [
                'name' => 'John',
                'age' => '34',
                'gender' => 'male',
            ],
            'sourceData' => [
                'website' => config('app.url'),
                'environment' => config('app.env'),
                'ipAddress' => '127.0.0.1',
            ],
        ]);

        //$apiWrapper->debug(true);
        $apiWrapper->makeRequest(function () use ($apiWrapper) {
            return Http::withToken('your_secret_token')
                ->withHeaders($apiWrapper->requestHeaders())
                ->withOptions([
                    'verify' => true,
                ])
                ->post($apiWrapper->requestUrl(), $apiWrapper->requestParams());
        });

        $responseType = $apiWrapper->getMeta('responseType');

        if (config('app.env') == 'production') {
            if ($responseType == 'hidden') {
                return [];
            }
        }

        return $apiWrapper->resultArray();
    }


### GET request

    use Tripsy\ApiWrapper\ApiWrapper;

    function query(ApiWrapper $apiWrapper): array
    {
        $ipAddress = '192.168.0.1';

        $apiWrapper->requestMethod('GET');
        $apiWrapper->requestUrl('https://request.url/location/'.urlencode($ipAddress));
        $apiWrapper->requestHeaders('Accept', 'application/json');

        $apiWrapper->makeRequest(function () use ($apiWrapper) {
            return Http::withToken('your_secret_token')
                ->withHeaders($apiWrapper->requestHeaders())
                ->withOptions([
                    'verify' => true,
                ])
                ->get($apiWrapper->requestUrl(), $apiWrapper->requestParams());
        });

        if ($apiWrapper->success() === false) {
            Log::channel('test')->info($apiWrapper->message());
        }
    }
