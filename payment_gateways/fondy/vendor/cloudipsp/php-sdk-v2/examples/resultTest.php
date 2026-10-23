<?php
//Emulating payment result api 1.0 format json
//To check it not use php-build in server
$curl = curl_init();
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$response_url = $protocol . $domainName;
curl_setopt_array($curl, array(
    CURLOPT_PORT => $_SERVER['SERVER_PORT'],
    CURLOPT_URL => $response_url . "/examples/result.php",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\"rrn\": \"\", \"masked_card\": \"444455XXXXXX6666\", \"sender_cell_phone\": \"\", \"response_signature_string\": \"**********|111|USD|111|123456|444455|VISA|USD|7|444455XXXXXX6666|1396424|1396424_1da4a65b81fdaf934c535e352a776744|approved|21.05.2018 13:50:55|93088761|card|success|0|0|purchase\", \"response_status\": \"success\", \"sender_account\": \"\", \"fee\": \"\", \"rectoken_lifetime\": \"\", \"reversal_amount\": \"0\", \"settlement_amount\": \"0\", \"actual_amount\": \"111\", \"order_status\": \"approved\", \"response_description\": \"\", \"verification_status\": \"\", \"order_time\": \"21.05.2018 13:50:55\", \"actual_currency\": \"USD\", \"order_id\": \"1396424_1da4a65b81fdaf934c535e352a776744\", \"parent_order_id\": \"\", \"merchant_data\": \"\", \"tran_type\": \"purchase\", \"eci\": \"7\", \"settlement_date\": \"\", \"payment_system\": \"card\", \"rectoken\": \"\", \"approval_code\": \"123456\", \"merchant_id\": 1396424, \"settlement_currency\": \"\", \"payment_id\": 93088761, \"product_id\": \"\", \"currency\": \"USD\", \"card_bin\": 444455, \"response_code\": \"\", \"card_type\": \"VISA\", \"amount\": \"111\", \"sender_email\": \"\", \"signature\": \"7725ca95944de78550c3ca132c1e6602707afa90\"}",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/json"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    print ($response);
}