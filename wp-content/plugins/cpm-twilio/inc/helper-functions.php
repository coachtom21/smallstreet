<?php
function ct_get_user_country_code()
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $ch = curl_init();
    $curlConfig = array(
        CURLOPT_URL            => "http://www.geoplugin.net/json.gp?ip=" . $ip,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => false
    );
    curl_setopt_array($ch, $curlConfig);
    $result = curl_exec($ch);
    curl_close($ch);
    $json_a = json_decode($result, true);
    $countrycode = $json_a['geoplugin_countryCode'];
    
    return $countrycode;
}
