<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Payment calendar (host-to-host)
try {
    //Minimal data set, all other required params will generated automatically
    $TestOrderData = [
        'order_id' => time(),
        'card_number' => '4444555511116666',
        'cvv2' => '333',
        'expiry_date' => '1232',
        'currency' => 'UAH',
        'amount' => 1000,
        'client_ip' => '127.2.2.1',
        'recurring_data' => [
            'start_time' => date("Y-m-d"),
            'amount' => 1000,
            'every' => 30,
            'period' => 'day',
            'state' => 'y',
            'readonly' => 'y'
        ]
    ];
    //Call method to start calendar order subscription
    \Cloudipsp\Configuration::setApiVersion('2.0'); //allow only json, api protocol 2.0
    $orderData = Cloudipsp\Pcidss::start($TestOrderData);
    $cancel = Cloudipsp\Subscription::stop($orderData->getData()['order_id']);
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Generate subscription Payment token</title>
        <style>
            table tr td, table tr th {
                padding: 10px;
            }
        </style>
    </head>
    <body>
    <table style="margin: auto;" border="1">
        <thead>
        <tr>
            <th style="text-align: center" colspan="2">Request Data:</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $TestOrderData], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Normal cancel response:</td>
            <td>
                <pre><?php print_r($cancel->getData()) ?></pre>
            </td>
        </tr>
        <tr>
            <td>Response subscription stop order_id:</td>
            <td><?php print_r($orderData->getData()['order_id']) ?></td>
        </tr>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}
