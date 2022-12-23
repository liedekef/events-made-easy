<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Payment status
try {
    //Generating pcidss order to get status below see more https://docs.fondy.eu/docs/page/4/
    $TestOrderData = [
        'order_id' => time(),
        'card_number' => '4444555511116666',
        'cvv2' => '333',
        'expiry_date' => '1232',
        'currency' => 'USD',
        'amount' => 1000,
        'client_ip' => '127.2.2.1'
    ];
    //Call method to generate order
    $status_order_data = Cloudipsp\Pcidss::start($TestOrderData);
    if ($status_order_data->isApproved()) {// Checking if prev payment valid(signature)
        $dataToGetStatus = [
            'order_id' => $TestOrderData['order_id']
        ];
        $s_order = Cloudipsp\Order::status($dataToGetStatus);
    }
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Order Status</title>
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
            <th style="text-align: center" colspan="2">Request data</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $dataToGetStatus], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Response status:</td>
            <td><?= $s_order->getData()['response_status'] ?></td>
        </tr>
        <tr>
            <td>Normal response:</td>
            <td>
                <pre><?php print_r($s_order->getData()); ?></pre>
            </td>
        </tr>
        <tr>
            <td>Check order is valid:</td>
            <td><?php var_dump($s_order->isValid()); ?></td>
        </tr>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}