<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//P2P card credit https://docs.fondy.eu/docs/page/24/
try {
    \Cloudipsp\Configuration::setMerchantId(1396424);
    \Cloudipsp\Configuration::setCreditKey('testcredit'); // to generate in you need use credit key
    $TestOrderData = [
        'currency' => 'USD',
        'amount' => 111,
        'receiver_card_number' => '4444555511116666'
    ];
    //Call method to generate order
    $orderData = Cloudipsp\P2pcredit::start($TestOrderData);
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>P2P card credit</title>
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
            <th style="text-align: center" colspan="2">P2P card credit</th>
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
            <td>Check order is approved:</td>
            <td><?php var_dump($orderData->isApproved()); ?></td>
        </tr>
        <tr>
            <td>Check order data is valid:</td>
            <td><?php var_dump($orderData->isValid()); ?></td>
        </tr>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}