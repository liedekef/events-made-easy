<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Payment url scheme B(host-to-host)
try {
    //Minimal data set, all other required params will generated automatically
    $data = [
        'currency' => 'USD',
        'amount' => 1000 // convert to 10.00$
    ];
    //some other params
    $dataBig = [
        'order_desc' => 'tests SDK',
        'currency' => 'USD',
        'amount' => 1000,
        'default_payment_system' => 'card',
        'response_url' => 'http://site.com/responseurl',
        'server_callback_url' => 'http://site.com/callbackurl',
        'payment_systems' => 'qiwi,yandex,webmoney,card,p24',
        'preauth' => 'N',
        'sender_email' => 'tests@fondy.eu',
        'delayed' => 'Y',
        'lang' => 'ru',
        'product_id' => 'some_product_id',
        'required_rectoken' => 'N',
        'lifetime' => 36000,
        'verification' => 'N',
        'subscription' => 'N',
        'merchant_data' => array(
            'custom_data1' => 'Some string',
            'custom_data2' => '2222',
            'custom_data3' => '3!@#$%^&(()_+?"}',
            'custom_data4' => ['custom_data4_test', 'custom_data4_test2', 'custom_data4_test3' => ['custom_data4_test3_33' => 'custom_data4_test3_33_string']]
        )
    ];
    //Call method to generate url
    $url = Cloudipsp\Checkout::url($data);
    $urlBig = Cloudipsp\Checkout::url($dataBig);
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Generate payment url</title>
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
            <th style="text-align: center" colspan="2">Request Data</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $data], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Response status:</td>
            <td><?= $url->getData()['response_status'] ?></td>
        </tr>
        <tr>
            <td>Normal Response:</td>
            <td>
                <pre><?php print_r($url->getData()) ?></pre>
            </td>
        </tr>
        <tr>
            <td>Response url:</td>
            <td><a href="<?= $url->getUrl() ?>"><?= $url->getUrl() ?></a></td>
        </tr>
        </tbody>
    </table>
    <table style="margin: auto;" border="1">
        <thead>
        <tr>
            <th style="text-align: center" colspan="2">Request Data:</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $dataBig], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Response status:</td>
            <td><?= $urlBig->getData()['response_status'] ?></td>
        </tr>
        <tr>
            <td>Normal Response:</td>
            <td>
                <pre><?php print_r($urlBig->getData()) ?></pre>
            </td>
        </tr>
        <tr>
            <td>Response url:</td>
            <td><a href="<?= $urlBig->getUrl() ?>"><?= $urlBig->getUrl() ?></a></td>
        </tr>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}