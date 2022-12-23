<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Payment scheme A(form)
try {
    //Minimal data set, all other required params will generated automatically
    $data = [
        'verification_type' => 'code', //default - amount
        'currency' => 'USD',
        'amount' => 100 // convert to 1.00$
    ];
    //Call method to generate form
    $form_string = \Cloudipsp\Verification::form($data);
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Generate verification form string</title>
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
            <td>There is no response_status</td>
        </tr>
        <tr>
            <td>Rendered form:</td>
            <td><?= $form_string ?></td>
        </tr>
        <tr>
            <td>Rendered form string:</td>
            <td><?php var_dump($form_string) ?></td>
        </tr>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    echo "Fail: " . $e->getMessage();
}