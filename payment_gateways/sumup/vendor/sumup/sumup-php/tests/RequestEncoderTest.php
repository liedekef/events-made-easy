<?php

namespace SumUp\Tests;

use PHPUnit\Framework\TestCase;
use SumUp\RequestEncoder;
use SumUp\Types\CheckoutCreateRequest;
use SumUp\Types\CheckoutCreateRequestCurrency;

class RequestEncoderTest extends TestCase
{
    public function testEncodeLeavesArrayPayloadUntouched()
    {
        $payload = ['merchant_code' => 'MC123', 'amount' => 10.0];

        $this->assertSame($payload, RequestEncoder::encode($payload));
    }

    public function testEncodeConvertsDtoToSnakeCaseAndBackedEnumValues()
    {
        $request = new CheckoutCreateRequest(
            checkoutReference: 'order-123',
            amount: 10.0,
            currency: CheckoutCreateRequestCurrency::EUR,
            merchantCode: 'MC123',
        );

        $encoded = RequestEncoder::encode($request);

        $this->assertSame('order-123', $encoded['checkout_reference']);
        $this->assertSame(10.0, $encoded['amount']);
        $this->assertSame('EUR', $encoded['currency']);
        $this->assertSame('MC123', $encoded['merchant_code']);
    }

    public function testEncodeRecursivelyNormalizesNestedObjects()
    {
        $encoded = RequestEncoder::encode(new RequestEncoderFixture());

        $this->assertSame('abc123', $encoded['client_transaction_id']);
        $this->assertSame('nested-value', $encoded['nested']['inner_value']);
        $this->assertArrayHasKey('items', $encoded);
        $this->assertSame('item-1', $encoded['items'][0]['item_id']);
        $this->assertSame('item-2', $encoded['items'][1]['item_id']);
    }
}

class RequestEncoderFixture
{
    public string $clientTransactionId = 'abc123';
    public RequestEncoderNestedFixture $nested;

    /**
     * @var RequestEncoderItemFixture[]
     */
    public array $items;

    public function __construct()
    {
        $this->nested = new RequestEncoderNestedFixture();
        $this->items = [new RequestEncoderItemFixture('item-1'), new RequestEncoderItemFixture('item-2')];
    }
}

class RequestEncoderNestedFixture
{
    public string $innerValue = 'nested-value';
}

class RequestEncoderItemFixture
{
    public string $itemId;

    public function __construct(string $itemId)
    {
        $this->itemId = $itemId;
    }
}
