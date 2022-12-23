<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Psidss step one
try {
    //Generating pcidss order with 3ds card see more https://docs.fondy.eu/docs/page/4/
    $TestOrderData = [
        'order_id' => time(),
        'card_number' => '4444555566661111', //test card with 3ds
        'cvv2' => '333',
        'expiry_date' => '1232',
        'currency' => 'USD',
        'amount' => 1000,
        'client_ip' => '127.2.2.1'
    ];
    //Call method to generate order
    $orderData = Cloudipsp\Pcidss::start($TestOrderData);
    //getting returned data

    //getting response url
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'] . '/';
    $response_url = $protocol . $domainName;
    //starting session to write order_id
    session_start();
    $_SESSION['order_id'] = $TestOrderData['order_id'];
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Pcidss 3ds</title>
        <style>
            table tr td, table tr th {
                padding: 10px;
            }
        </style>
    </head>
    <body>
    <table style="margin: auto" border="1">
        <thead>
        <tr>
            <th style="text-align: center" colspan="2">Request 3ds card</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $TestOrderData], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Response status:</td>
            <td><?= $orderData->getData()['response_status'] ?></td>
        </tr>
        <tr>
            <td>Normal response:</td>
            <td>
                <pre><?php print_r($orderData->getData()); ?></pre>
            </td>
        </tr>
        <tr>
            <td>Check if card has 3ds:</td>
            <td><?php var_dump($orderData->is3ds()); ?></td>
        </tr>
        <tr>
            <td>Build an HTML form and using it submit customer to acs_url:</td>
            <td><?php print(\Cloudipsp\Pcidss::get3dsFrom($orderData->getData(), $response_url . '/examples/Pcidss/3dsresult.php')); ?></td>
        </tr>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}
