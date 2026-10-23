<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Payment form scheme A(form)
try {
    //Minimal data set, all other required params will generated automatically
    $data = [
        'currency' => 'USD',
        'amount' => 1000, // convert to 10.00$
        'order_id' => time(),
        'order_desc' => 'test description'
    ];
    //Call method to generate form
    $form_string = Cloudipsp\Checkout::form($data);
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Generate form string</title>
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
            <td>There is no response_status</td>
        </tr>
        <tr>
            <td>Rendered form:</td>
            <td><?= $form_string ?></td>
        </tr>
        <tr>
            <td>Rendered form fields:</td>
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