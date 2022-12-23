<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Psidss result 3ds
try {
    if (!empty($_POST)) {
        session_start();
        $dataTo3dsSubmit = $_POST;
        $dataTo3dsSubmit['order_id'] = isset($_SESSION['order_id']) ? $_SESSION['order_id'] : null; // adding order id from prev step
        $orderData = \Cloudipsp\Pcidss::submit($dataTo3dsSubmit);
        session_destroy();
    }
    //getting returned data
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
            <th style="text-align: center" colspan="2">Submit request 3ds card</th>
        </tr>
        <tr>
            <th style="max-width: 600px;text-align: left"
                colspan="2"><?php printf("<pre style='    word-break: break-all;
    white-space: normal;'>%s</pre>", json_encode(['request' => $dataTo3dsSubmit], JSON_PRETTY_PRINT)) ?></th>
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
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}