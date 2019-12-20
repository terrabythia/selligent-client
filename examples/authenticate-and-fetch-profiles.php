<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload
require_once __DIR__ . '/../src/requests.php';

use GuzzleHttp\Exception\RequestException;
use function Digitaalbedrijf\Selligent\Requests\selligent_authenticated_request;
use function Digitaalbedrijf\Selligent\Requests\selligent_guzzle_client;
use function Digitaalbedrijf\Selligent\Requests\selligent_search_profiles_request;

$username = "username";
$password = "password";
$baseUrl = "https://baseurl";
$selligentProfilesListId = "profiles_list_id";

$client = selligent_guzzle_client(
    $baseUrl
);

// partially apply the selligent authenticate request (see https://github.com/lstrojny/functional-php/blob/master/docs/functional-php.md#partial-application)
$authenticateRequestFn = selligent_authenticated_request($username, $password);

// as a test: search profiles where ID != 0
// limit parameter not implemented yet
$request = selligent_search_profiles_request(
    $selligentProfilesListId,
    ['ID' => 0, 'op' => '<>'],
    null
);

try {
    $response = $client->send(
        $authenticateRequestFn(
            $request
        )
    );
    var_dump(json_encode($response->getBody()->getContents()));
} catch (RequestException $e) {
    echo "RequestException: \n";
    var_dump($e->getResponse()->getBody()->getContents());
} catch(Exception $e) {
    var_dump($e);
}
