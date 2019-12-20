<?php

namespace Digitaalbedrijf\Selligent\Authentication {

    function selligent_get_authorization_hash($username, $password, $apiPath, $method = 'GET')
    {

        $time = time();

        $pattern = "$time-$method-$apiPath";
        $hash = hash_hmac('sha256', $pattern, $password);

        return "hmac $username:$hash:$time";

    }

}


