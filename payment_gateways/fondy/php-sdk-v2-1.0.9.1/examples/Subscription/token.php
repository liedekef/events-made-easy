<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Payment subscription token scheme B(host-to-host)
try {
    //Minimal data set, all other required params will generated automatically
    $data = [
        'currency' => 'USD',
        'amount' => 1000, // convert to 10.00$
        'recurring_data' => [
            'start_time' => '2021-12-24',
            'amount' => 1000,
            'every' => 30,
            'period' => 'day',
            'state' => 'y',
            'readonly' => 'y'
        ]

    ];
    //Call method to generate token
    \Cloudipsp\Configuration::setApiVersion('2.0'); //allow only json, api protocol 2.0
    $url = Cloudipsp\Subscription::token($data);
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
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $data], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Normal Response:</td>
            <td>
                <pre><?php print_r($url->getData()) ?></pre>
            </td>
        </tr>
        <tr>
            <td>Response subscription token:</td>
            <td><?php print_r($url->getData()['token']) ?></td>
        </tr>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}