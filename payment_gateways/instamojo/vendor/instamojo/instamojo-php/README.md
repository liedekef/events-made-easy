# Instamojo PHP API [![Latest Stable Version](https://poser.pugx.org/instamojo/instamojo-php/v/stable)](https://packagist.org/packages/instamojo/instamojo-php) [![License](https://poser.pugx.org/instamojo/instamojo-php/license)](https://opensource.org/licenses/MIT)

Assists you to programmatically create, edit and delete Links on Instamojo in PHP.

**Note**: If you're using this wrapper with our sandbox environment `https://test.instamojo.com/` then you should pass `true` as third argument to the `Instamojo` class while initializing it. `client_id` and `client_secret` token for the same can be obtained from <https://test.instamojo.com/developers/> (Details: [Test Or Sandbox Account](https://instamojo.zendesk.com/hc/en-us/articles/208485675-Test-or-Sandbox-Account)).

```php
$authType = "app/user" /**Depend on app or user based authentication**/

$api = Instamojo\Instamojo::init($authType,[
        "client_id" =>  'XXXXXQAZ',
        "client_secret" => 'XXXXQWE',
        "username" => 'FOO', /** In case of user based authentication**/
        "password" => 'XXXXXXXX' /** In case of user based authentication**/

    ],true); /** true for sandbox enviorment**/

```

## Installing via [Composer](https://getcomposer.org/)

```bash
php composer.phar require instamojo/instamojo-php
```

**Note**: If you're not using Composer then directly include the contents of `src` directory in your project.

## Usage

```php
$api = Instamojo\Instamojo::init($authType,[
        "client_id" =>  'XXXXXQAZ',
        "client_secret" => 'XXXXQWE',
        "username" => 'FOO', /** In case of user based authentication**/
        "password" => 'XXXXXXXX' /** In case of user based authentication**/

    ]);
```

## Documentation

See the Documentation for datailed instructions

 * <a href="https://docs.instamojo.com/v1.1"> v1.1 Documentation </a>
 * <a href="https://docs.instamojo.com/v2"> v2 Documentation </a>

## Table of Content
* [Create a payment request](#create_payment)
* [Get status of Payment request](#get_status_of_payment_request)
* [Get list of all Payment requests](#get_payment_request_list)
* [Get list of all Payments](#get_list_of_all_payments)
* [Get Details of a payment](#get_details_of_a_payment)
* [Create Gateway order](#create_gateway_order)
* [Create Gateway order for Payment request](#create_gateway_order_for_payment_request)
* [Get Gateway order details](#get_gateway_order_details)
* [Get list of Gateway orders](#get_list_of_gateway_orders)
* [Create refund for payments](#create_refund_for_payment)
* [Get details of a refund](#get_refund_details)
* [Get list of refunds](#get_list_of_refunds)
* [Common FAQ's](#faqs)


### <a name="create_payment"></a> Create a new Payment Request

```php
try {
    $response = $api->createPaymentRequest(array(
        "purpose" => "FIFA 16",
        "amount" => "3499",
        "send_email" => true,
        "email" => "foo@example.com",
        "redirect_url" => "http://www.example.com/handle_redirect.php"
        ));
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you JSON object containing details of the Payment Request that was just created with `longurl` key provide you instamojo-payment-url.

### Recommended seamless checkout Option

 You can render your Instamojo checkout form and collect payments on your webpage with just the instamojo-payment-url obtained in `createPaymentRequest()` using JS based seamless checkout library. To know more how its work <a href = "https://docs.instamojo.com/page/seamless-checkout">Click here</a>.

### Payment Request Creation Parameters

 ### Required

 * `purpose`: Purpose of the payment request.
 * `amount`: The amount for the request. The minimum amount is 9. And the maximum is 200000.

 ### Optional

 * `buyer_name`: Name of the payer.
 * `email`: Email of the payer.
 * `phone`: Phone number of the payer.
 * `send_email`: Set this to `true` if you want to send email to the payer if email is specified. If email is not specified then an error is raised. (default 
 value: `false`)
 * `send_sms`: Set this to `true` if you want to send SMS to the payer if phone is specified. If phone is not specified then an error is raised. (default value:
 `false`)
 * `redirect_url`: set this to a thank-you page on your site. Buyers will be redirected here after successful payment.
 * `webhook`: set this to a URL that can accept POST requests made by Instamojo server after successful payment.
 * `allow_repeated_payments`: To disallow multiple successful payments on a Payment Request pass `false` for this field. If this is set to `false` then the link is
 not accessible publicly after first successful payment, though you can still access it using API(default value: `true`).
 * `partner_fee_type` : Allows you to receive a cut from from payments you facilitate. For fixed fee set this to `fixed`, or for percentage fee set it to
 `percent`.
 * `partner_fee` : This describes the fee that you would collect. It can be either a fixed amount, or a percentage of the original amount, depending on the value
 of `partner_fee_type`.
 * `mark_fulfilled` : Flag to determine if you want to put the payment on hold until you explicitly fulfil it. If `mark_fulfilled` is `True` the payment will be
 paid out to the merchant. If `mark_fulfilled` is `False`, then the payment will be put on hold until you explicitly fulfil the payment. See Fulfil a Payment below
 on how to fulfil a payment.
 * `expires_at` : Time after which the payment request will be expired in UTC timestamp. Max value is 600 seconds. Default is Null.


## <a name="get_status_of_payment_request"></a> Get the status or details of a Payment Request

```php
try {
    $response = $api->getPaymentRequestDetails(['PAYMENT REQUEST ID']);
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you JSON object containing details of the Payment Request and the payments related to it.
Key for payments is `'payments'`.

Here `['PAYMENT REQUEST ID']` is the value of `'id'` key returned by the `createPaymentRequest()` query.

## <a name="get_payment_request_list"></a> Get a list of all Payment Requests

```php
try {
    $response = $api->getPaymentRequests();
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you an array containing Payment Requests created so far. Note that the payments related to individual Payment Request are not returned with this query.

getPaymentRequests() also accepts optional parameters for pagination.

```php
getPaymentRequests($limit=null, $page=null)
```

For example:

```php
$response = $api->getPaymentRequests(50, 1);
```

## <a name="get_list_of_all_payments"> Get a list of all Payments

```php
try {
    $response = $api->getPayments();
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you an array containing Payments details so far.

getPayments() also accepts optional parameters for pagination.

```php
getPayments($limit=null, $page=null)
```

For example:

```php
$response = $api->getPayments(50, 1);
```

## <a name="get_details_of_a_payment"> Get the  details of a Payment

```php
try {
    $response = $api->getPaymentDetails(['PAYMENT ID']);
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you JSON object containing details of the Payment.

Here `['PAYMENT ID']` is the value of `'id'` key returned by the `getPayments()` query.

## <a name="create_gateway_order"> Create a Gateway Order

```php
try {
    $response = $api->createGatewayOrder(array(
      "name" => "XYZ",
      "email" => "abc@foo.com",
      "phone" => "99XXXXXXXX",
      "amount" => "200",
      "transaction_id" => 'TXN_ID', /**transaction_id is unique Id**/
      "currency" => "INR"
    ));
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you JSON object containing details of the order in `order` key and payments options in `payment_options` key.

## <a name="create_gateway_order_for_payment_request"> Create a Gateway Order For payment request

```php
try {
    $response = $api->createGatewayOrderForPaymentRequest($payment_request_id, array(
      "name" => "XYZ",
      "email" => "abc@foo.com",
      "phone" => "99XXXXXXXX",
    ));
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

`$payment_request_id` id the `id` key obtained in `createPaymentRequest()` method.

This will give you JSON object containing with created `order_id` key.

## <a name="get_gateway_order_details"> Get the details of a Gateway Order

```php
try {
    $response = $api->getGatewayOrder(['ORDER ID']);
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you JSON object containing details of the  Gateway Order.

Here `['ORDER ID']` is the value of `'id'` key returned by the `createGatewayOrder()` query.

## <a name="get_list_of_gateway_orders"> Get a list of all Gateway Order

```php
try {
    $response = $api->getGatewayOrders();
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you an array containing Gateway Orders details so far.

getGatewayOrders() also accepts optional parameters for pagination.

```php
getGatewayOrders($limit=null, $page=null)
```

For example:

```php
$response = $api->getGatewayOrders(50, 1);
```

## <a name="create_refund_for_payment"> Create a Refund for a payment

```php
try {
    $response = $api->createRefundForPayment($payment_id, array(
      "type" => "RFD",
      "body" => "XYZ reason of refund",
      "refund_amount" => "10",
      "transaction_id" => "TNX_XYZ"
    ));
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you JSON object containing refund details in `refund` key.

 ### Required Parameters

 * `type`: Three letter short-code identifying the reason for refund, string type.
 * `body`: Additonal text explaining the refund, string type.
 * `refund_amount`: This field can be used to specify the refund amount, string type.
 * `transaction_id`: To Prevent duplicate case creations due to replay of APIs, string type.

Valid values for type parameter:

 * RFD: Duplicate/delayed payment.
 * TNR: Product/service no longer available.
 * QFL: Customer not satisfied.
 * QNR: Product lost/damaged.
 * EWN: Digital download issue.
 * TAN: Event was canceled/changed.
 * PTH: Problem not described above.

## <a name="get_refund_details"> Get the details of a Refund

```php
try {
    $response = $api->getRefundDetails(['REFUND ID']);
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you JSON object containing details of the  Refund.

### <a name="get_list_of_refunds"> Get a list of all Refunds

```php
try {
    $response = $api->getRefunds();
    print_r($response);
}
catch (Exception $e) {
    print('Error: ' . $e->getMessage());
}
```

This will give you an array containing Refunds  details so far.

getRefunds() also accepts optional parameters for pagination.

```php
getRefunds($limit=null, $page=null)
```

For example:

```php
$response = $api->getRefunds(50, 1);
```

## <a name="faqs"> Common FAQ's

* <a href="https://support.instamojo.com/hc/en-us/articles/212214265-How-do-I-get-my-Client-ID-and-Client-Secret-"> How to get your client Id and Client secret </a> 

