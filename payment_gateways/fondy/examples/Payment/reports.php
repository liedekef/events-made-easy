<?php
require_once '../configuration.php';
require_once SDK_ROOTPATH . '/../vendor/autoload.php';


//Get payment reports https://docs.fondy.eu/docs/page/9/
try {
    $data = [
        "date_from" => date('d.m.Y H:i:s', time() - 7200),
        "date_to" => date('d.m.Y H:i:s', time() - 3600),
    ];
    $reports = \Cloudipsp\Payment::reports($data);
    //getting returned data
    ?>
    <!doctype html>
    <html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Payment reports</title>
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
            <th style="text-align: center" colspan="2">Payment reports request</th>
        </tr>
        <tr>
            <th style="text-align: left"
                colspan="2"><?php printf("<pre>%s</pre>", json_encode(['request' => $data], JSON_PRETTY_PRINT)) ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Normal response:</td>
            <td style="max-width: 1000px">
                <pre style="word-wrap: break-word;    white-space: pre-wrap;"><?php print_r($reports->getData()); ?></pre>
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