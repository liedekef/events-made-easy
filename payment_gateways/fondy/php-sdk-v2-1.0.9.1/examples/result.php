<?php
error_reporting(-1);
ini_set('display_errors', 'On');

/**
 * Setting up testing configuration
 * All testing details you can find here https://docs.fondy.eu/docs/page/2/
 */
require_once 'configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';
/**
 * Getting payment result (server_callback_url)
 * Signature validation example server_callback_url
 * you can get params by yourself, or sdk can got it
 */
try {
    $callbackData = json_decode(file_get_contents('php://input'), TRUE); //if request in json
    if ($callbackData)
        $result = new Cloudipsp\Result\Result($callbackData);
    else
        die('No data');
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>
            Checking payment result
        </title>
        <style>
            table tr td, table tr th {
                padding: 10px;
            }
        </style>
    </head>
    <body>
    <table style="max-width:1000px;margin: auto;" border="1">
        <thead>
        <tr>
            <th style="text-align: center" colspan="2">Result data:</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode($callbackData, JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Result status:</td>
            <td><?= $result->getData()['response_status'] ?></td>
        </tr>
        <tr>
            <td>Normal result:</td>
            <td>
                <pre><?php print_r($result->getData()) ?></pre>
            </td>
        </tr>
        <tr>
            <td>Result is valid:</td>
            <td><?php var_dump($result->isValid()); ?></td>
        </tr>
        <tr>
            <td>Payment is approved:</td>
            <td><?php var_dump($result->isApproved()); ?></td>
        </tr>
        <tr>
            <td>Payment is expired:</td>
            <td><?php var_dump($result->isExpired()); ?></td>
        </tr>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}