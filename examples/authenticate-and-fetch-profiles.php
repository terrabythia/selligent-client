<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload
require_once __DIR__ . '/../src/requests.php';

use Dotenv\Dotenv;
use GuzzleHttp\Exception\RequestException;
use function Digitaalbedrijf\Selligent\Requests\selligent_authenticated_request;
use function Digitaalbedrijf\Selligent\Requests\selligent_guzzle_client;
use function Digitaalbedrijf\Selligent\Requests\selligent_search_profiles_request;

$dotEnv = Dotenv::create(dirname(__DIR__));
$dotEnv->load();

$username = getenv('SELLIGENT_USERNAME');
$password = getenv('SELLIGENT_PASSWORD');
$baseUrl = getenv('SELLIGENT_BASE_URL');
$selligentProfilesListId = getenv('SELLIGENT_PROFILES_LIST_ID');

$client = selligent_guzzle_client(
    $baseUrl
);

// as a test: search profiles where ID != 0
// limit parameter not implemented yet
$request = selligent_search_profiles_request(
    $selligentProfilesListId,
    ['ID' => 0, 'op' => '<>'],
    null
);

try {
    $response = $client->send(
        selligent_authenticated_request($username, $password, $request)
    );
    var_dump(json_encode($response->getBody()->getContents()));
} catch (RequestException $e) {
    echo "RequestException: \n";
    var_dump($e->getResponse()->getBody()->getContents());
} catch(Exception $e) {
    var_dump($e);
}
