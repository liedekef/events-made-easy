<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Payment button
try {
    //Minimal data set, all other required params will generated automatically
    $data = [
        'currency' => 'USD',
        'amount' => 1000 // convert to 10.00$
    ];
    $dataBig = [
        'order_desc' => 'tests SDK',
        'currency' => 'USD',
        'amount' => 1000,
        'default_payment_system' => 'card',
        'response_url' => 'http://site.com/responseurl',
        'server_callback_url' => 'http://site.com/callbackurl',
        'merchant_data' => array(
            'fields' => [
                [
                    'label' => 'Account Id',
                    'name' => 'account_id',
                    'value' => '127318273',
                    'readonly' => true,
                    'required' => true,
                    'valid' => [
                        'pattern' => '[a-z]+'
                    ]
                ],
                [
                    'label' => 'Comment',
                    'name' => 'comment',
                    'value' => '',
                    'readonly' => false,
                    'required' => false,
                    'valid' => [
                        'pattern' => '[a-z]+'
                    ]
                ]
            ]
        )
    ];
    //Call method to generate button
    $url = Cloudipsp\Checkout::button($data);
    $urlBig = Cloudipsp\Checkout::button($dataBig);
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Generate Payment Button</title>
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
            <th style="text-align: center" colspan="2">Request data</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $data], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Response status</td>
            <td>There is no response_status</td>
        </tr>
        <tr>
            <td>Response button url</td>
            <td><a href="<?= $url ?>"><?= $url ?></a></td>
        </tr>
        </tbody>
    </table>
    <table style="margin: auto;" border="1">
        <thead>
        <tr>
            <th style="text-align: center" colspan="2">Request Data</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $dataBig], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Response status</td>
            <td>There is no response_status</td>
        </tr>
        <tr>
            <td>Response url</td>
            <td><a href="<?= $urlBig ?>"><?= $urlBig ?></a></td>
        </tr>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}