<?php

namespace Digitaalbedrijf\Selligent\Requests {
    
    require_once './src/authentication.php';

    use GuzzleHttp\Client;
    use GuzzleHttp\Psr7\Request;
    use function Functional\compose;
    use function Functional\memoize;
    use function Functional\partial_left;
    use function Functional\partial_right;
    use function Digitaalbedrijf\Selligent\Authentication\selligent_get_authorization_hash;

    function selligent_transform_stream_request_data(array $data) {
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
     * @return Request
     */
    function selligent_authenticated_request($username, $password, Request $request) {
        $authHash = selligent_get_authorization_hash($username, $password, $request->getUri(), $request->getMethod());
        return $request->withAddedHeader('Authorization', $authHash);
    }

    function selligent_request($apiPath, $body = null, $method = 'GET') {
        $request = new Request($method, $apiPath, [
            "Content-Type" => "application/json",
            "Accept" => "*/*",
            "Connection" => "keep-alive"
        ], $body && !is_string($body) ? json_encode($body) : $body);
        return $request;
    }

    function selligent_fetch_list_profiles_request($listId, $fields = []) {
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

    function selligent_fetch_lists_request() {
        $apiPath = '/restapi/api/async/lists';
        $method = 'GET';
        return selligent_request(
            $apiPath,
            null,
            $method
        );
    }


    function selligent_fetch_list_request($listId) {
        $apiPath = "/restapi/api/async/lists/$listId";
        return selligent_request(
            $apiPath,
            null,
            'GET'
        );
    }

    function selligent_fetch_list_data_request($listId, $fields = []) {
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

    function selligent_fetch_profile_request($listId, $profileId) {
        $apiPath = "/restapi/api/async/lists/$listId/profiles/$profileId";
        return selligent_request(
            $apiPath,
            null,
            'GET'
        );
    }

    function selligent_create_profile_request($listId, array $body, $async = true) {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/profiles";
        $method = "POST";
        return selligent_request(
            $apiPath,
            $body,
            $method
        );
    }

    function selligent_create_many_profiles_request($listId, array $profiles) {

        $apiPath = "/restapi/api/stream/lists/{$listId}/profiles/post?mode=append";
        $method = "POST";

        return selligent_request(
            $apiPath,
            selligent_transform_stream_request_data($profiles),
            $method
        );

    }

    function selligent_delete_profile_request($listId, $profileId, $async = true) {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/profiles/{$profileId}";
        $method = "DELETE";
        return selligent_request(
            $apiPath,
            null,
            $method
        );
    }

    function selligent_search_list_request($listType, $listId, array $filter, ?array $fields = [], $limit = 200) {
        $apiPath = "/restapi/api/sync/lists/{$listId}/{$listType}/search";
        $method = "POST";
        $body = [];
        if (!empty($fields)) {
            $body['fields'] = $fields;
        }
        $body['filter'] = $filter;
        return selligent_request(
            $apiPath,
            json_encode($body),
            $method
        );
    }

    function selligent_search_profiles_request($listId, array $filter, ?array $fields = [], $limit = 200) {
        return selligent_search_list_request('profiles', $listId, $filter, $fields, $limit);
    }

    function selligent_search_data_list_request($listId, array $filter, ?array $fields = [], $limit = 200) {
        return selligent_search_list_request('data', $listId, $filter, $fields, $limit);
    }

    function selligent_update_profile_request($listId, $profileId, array $body, $async = true) {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/profiles/{$profileId}";
        $method = "PUT";
        return selligent_request(
            $apiPath,
            $body,
            $method
        );
    }

    function selligent_create_data_list_record_request($listId, array $body, $async = true) {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/data";
        $method = 'POST';
        return selligent_request(
            $apiPath,
            $body,
            $method
        );
    }

    function selligent_create_many_data_list_records_request($listId, array $records) {
        $apiPath = "/restapi/api/stream/lists/{$listId}/data/post?mode=append";
        $method = "POST";
        return selligent_request(
            $apiPath,
            selligent_transform_stream_request_data($records),
            $method
        );
    }

    function selligent_update_data_list_record_request($listId, $recordId, array $body, $async = true) {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/data/{$recordId}";
        $method = 'PUT';
        return selligent_request(
            $apiPath,
            $body,
            $method
        );
    }

    function selligent_delete_data_list_record_request($listId, $recordId, $async = true) {
        $sAsync = $async ? 'async' : 'sync';
        $apiPath = "/restapi/api/{$sAsync}/lists/{$listId}/data/{$recordId}";
        $method = "DELETE";
        return selligent_request(
            $apiPath,
            null,
            $method
        );
    }

    function selligent_trigger_campaign($campaignId, $actionListId, $userId, $userListId, $actionListRecord = [], $gate = 'POC_GATE') {
        $apiPath = "/restapi/api/async/campaigns/$campaignId/trigger";
        $method = 'POST';
        return selligent_request(
            $apiPath,
            [
                'ActionList' => $actionListId,
                'User' => $userId,
                'UserListId' => $userListId,
                'ActionListRecord' => $actionListRecord,
                'Gate' => $gate,
            ],
            $method
        );
    }

}


