<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Payment card verification url scheme B(host-to-host) https://docs.fondy.eu/docs/page/11/
try {
    //Minimal data set, all other required params will generated automatically
    $data = [
        'verification_type' => 'code', //default - amount
        'currency' => 'USD',
        'amount' => 100 // convert to 1.00$
    ];
    //Call method to generate url
    $url = \Cloudipsp\Verification::url($data);
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Generate verification url</title>
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
            <th style="text-align: center" colspan="2">Request data:</th>
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
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}