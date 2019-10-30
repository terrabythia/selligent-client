<?php

namespace Digitaalbedrijf\Selligent\Requests {
    
    require_once __DIR__ . '/authentication.php';

    use function Functional\partial_any;
    use function Functional\placeholder;
    use GuzzleHttp\Client;
    use GuzzleHttp\Psr7\Request;
    use function Functional\compose;
    use function Functional\memoize;
    use function Functional\partial_left;
    use function Functional\partial_right;
    use function Digitaalbedrijf\Selligent\Authentication\selligent_get_authorization_hash;

    /**
     * @param array $data
     * @return string
     */
    function selligent_transform_stream_request_data(array $data): string {
        if (empty($data)) {
            return "";
        }

        $bodyHeader = compose(
            partial_right('array_slice', 0, 1),
            partial_left('Functional\\first'),
            partial_left('array_keys'),
            partial_left('implode', '|')
        )($data);

        $bodyContent = compose(
            partial_left('array_map', 'json_encode'),
            partial_left('implode', "\n")
        )($data);

        return "$bodyHeader\n$bodyContent";
    }

    /**
     * @param string $baseUrl Api Base URL
     * @return Client
     */
    function selligent_guzzle_client($baseUrl) {
        // memoize the client: when this function is called for the second time, the memoize function
        // will make sure to always return the already existing (cached) client instance
        $memoizeFn = function($baseUrl) {
            return new Client([
                'base_uri' => $baseUrl,
            ]);
        };
        return memoize($memoizeFn, [$baseUrl]);
    }

    /**
     * @param $username
     * @param $password
     * @param Request $request
     * @return Request|callable (when callable it will return a partially applied function)
     */
    function selligent_authenticated_request(string $username, string $password, ?Request $request = null) {
        if (!isset($request)) {
            // return a partial fn
            return partial_any(
                'Digitaalbedrijf\\Selligent\\Requests\\selligent_authenticated_request',
                $username,
                $password,
                placeholder()
            );
        }
        $authHash = selligent_get_authorization_hash($username, $password, $request->getUri(), $request->getMethod());
        return $request->withAddedHeader('Authorization', $authHash);
    }

    /**
     * @param string $apiPath
     * @param array|string|null $body
     * @param string $method
     * @return Request
     */
    function selligent_request(string $apiPath, $body = null, string $method = 'GET'): Request {
        $request = new Request($method, $apiPath, [
            "Content-Type" => "application/json",
            "Accept" => "*/*",
            "Connection" => "keep-alive"
        ], $body && !is_string($body) ? json_encode($body) : $body);
        return $request;
    }

    /**
     * @param $listId
     * @param array $fields
     * @return Request
     */
    function selligent_fetch_list_profiles_request($listId, $fields = []): Request {
        $apiPath = "/restapi/api/async/lists/$listId/profiles";
        if (!empty($fields)) {
            $apiPath = "$apiPath?fields=" . implode(",", $fields);
        }
        return selligent_request(
            $apiPath,
            null,
            'GET'
        );
    }

    /**
     * @return Request
     */
    function selligent_fetch_lists_request(): Request {
        $apiPath = '/restapi/api/async/lists';
        return selligent_request(
            $apiPath,
            null,
            'GET'
        );
    }

    /**
     * @param int $listId
     * @return Request
     */
    function selligent_fetch_list_request(int $listId): Request {
        $apiPath = "/restapi/api/async/lists/$listId";
        return selligent_request(
            $apiPath,
            null,
            'GET'
        );
    }

    /**
     * @param int $listId
     * @param array $fields
     * @return Request
     */
    function selligent_fetch_list_data_request(int $listId, array $fields = []): Request {
        $apiPath = "/restapi/api/sync/lists/$listId/data";
        if (!empty($fields)) {
            $apiPath = "$apiPath?fields=" . implode(",", $fields);
        }
        return selligent_request(
            $apiPath,
            null,
            'GET'
        );
    }

    /**
     * @param int $listId
     * @param int $profileId
     * @return Request
     */
    function selligent_fetch_profile_request(int $listId, int $profileId): Request {
        $apiPath = "/restapi/api/async/lists/$listId/profiles/$profileId";
        return selligent_request(
            $apiPath,
            null,
            'GET'
        );
    }

    /**
     * @param int $listId
     * @param array $body
     * @param bool $async
     * @return Request
     */
    function selligent_create_profile_request(int $listId, array $body, bool $async = true): Request {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/profiles";
        return selligent_request(
            $apiPath,
            $body,
            'POST'
        );
    }

    /**
     * @param int $listId
     * @param array $profiles
     * @return Request
     */
    function selligent_create_many_profiles_request(int $listId, array $profiles): Request {
        $apiPath = "/restapi/api/stream/lists/{$listId}/profiles/post?mode=append";
        return selligent_request(
            $apiPath,
            selligent_transform_stream_request_data($profiles),
            'POST'
        );
    }

    /**
     * @param int $listId
     * @param int $profileId
     * @param bool $async
     * @return Request
     */
    function selligent_delete_profile_request(int $listId, int $profileId, bool $async = true): Request {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/profiles/{$profileId}";
        return selligent_request(
            $apiPath,
            null,
            'DELETE'
        );
    }

    /**
     * @param string $listType
     * @param int $listId
     * @param array $filter
     * @param array|null $fields
     * @param int $limit
     * @return Request
     */
    function selligent_search_list_request(string $listType, int $listId, array $filter, ?array $fields = [], $limit = 200): Request {
        $apiPath = "/restapi/api/sync/lists/{$listId}/{$listType}/search";
        $body = [];
        if (!empty($fields)) {
            $body['fields'] = $fields;
        }
        $body['filter'] = $filter;
        return selligent_request(
            $apiPath,
            json_encode($body),
            'POST'
        );
    }

    /**
     * @param int $listId
     * @param array $filter
     * @param array|null $fields
     * @param int $limit
     * @return Request
     */
    function selligent_search_profiles_request(int $listId, array $filter, ?array $fields = [], $limit = 200): Request {
        return selligent_search_list_request('profiles', $listId, $filter, $fields, $limit);
    }

    /**
     * @param int $listId
     * @param array $filter
     * @param array|null $fields
     * @param int $limit
     * @return Request
     */
    function selligent_search_data_list_request(int $listId, array $filter, ?array $fields = [], int $limit = 200): Request {
        return selligent_search_list_request('data', $listId, $filter, $fields, $limit);
    }

    /**
     * @param int $listId
     * @param int $profileId
     * @param array $body
     * @param bool $async
     * @return Request
     */
    function selligent_update_profile_request(int $listId, int $profileId, array $body, bool $async = true): Request {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/profiles/{$profileId}";
        return selligent_request(
            $apiPath,
            $body,
            'PUT'
        );
    }

    /**
     * @param int $listId
     * @param array $body
     * @param bool $async
     * @return Request
     */
    function selligent_create_data_list_record_request(int $listId, array $body, bool $async = true): Request {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/data";
        return selligent_request(
            $apiPath,
            $body,
            'POST'
        );
    }

    /**
     * @param int $listId
     * @param array $records
     * @return Request
     */
    function selligent_create_many_data_list_records_request(int $listId, array $records): Request {
        $apiPath = "/restapi/api/stream/lists/{$listId}/data/post?mode=append";
        return selligent_request(
            $apiPath,
            selligent_transform_stream_request_data($records),
            'POST'
        );
    }

    /**
     * @param int $listId
     * @param int $recordId
     * @param array $body
     * @param bool $async
     * @return Request
     */
    function selligent_update_data_list_record_request(int $listId, int $recordId, array $body, bool $async = true): Request {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/data/{$recordId}";
        return selligent_request(
            $apiPath,
            $body,
            'PUT'
        );
    }

    /**
     * @param int $listId
     * @param int $recordId
     * @param bool $async
     * @return Request
     */
    function selligent_delete_data_list_record_request(int $listId, int $recordId, bool $async = true): Request {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/data/{$recordId}";
        return selligent_request(
            $apiPath,
            null,
            'DELETE'
        );
    }


    function selligent_trigger_campaign($campaignId, $actionListId, $userId, $userListId, $actionListRecord = [], $gate = 'POC_GATE') {
        $apiPath = "/restapi/api/async/campaigns/$campaignId/trigger";
        return selligent_request(
            $apiPath,
            [
                'ActionList' => $actionListId,
                'User' => $userId,
                'UserListId' => $userListId,
                'ActionListRecord' => $actionListRecord,
                'Gate' => $gate,
            ],
            'POST'
        );
    }

}


