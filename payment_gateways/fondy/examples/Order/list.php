<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Transaction List
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
        $dataToGetList = [
            'order_id' => $TestOrderData['order_id']
        ];
        $listData = Cloudipsp\Order::transactionList($dataToGetList);
    }
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Order transactions list</title>
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
            <th style="text-align: center" colspan="2">Request transaction list data</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $dataToGetList], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Normal response:</td>
            <td>
                <pre><?php print_r($listData->getData()); ?></pre>
            </td>
        </tr>
        <tr>
            <td>Is captured transaction: </td>
            <td>
                <pre><?php print_r($listData->isCapturedByList()); ?></pre>
            </td>
        </tr>

        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}