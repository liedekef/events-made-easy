<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Checkout resource returned after a synchronous processing attempt. In addition to the base checkout fields, it can include the resulting transaction identifiers and any newly created payment instrument token.
 */
class CheckoutSuccess
{
    /**
     * Merchant-defined reference for the checkout. Use it to correlate the SumUp checkout with your own order, cart, subscription, or payment attempt in your systems.
     *
     * @var string|null
     */
    public ?string $checkoutReference = null;

    /**
     * Amount to be charged to the payer, expressed in major units.
     *
     * @var float|null
     */
    public ?float $amount = null;

    /**
     * Three-letter [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) currency code of the amount.
     *
     * @var string|null
     */
    public ?string $currency = null;

    /**
     * Short unique identifier for the merchant that receives the payment.
     *
     * @var string|null
     */
    public ?string $merchantCode = null;

    /**
     * Short merchant-defined description shown in SumUp tools and reporting. Use it to make the checkout easier to recognize in dashboards, support workflows, and reconciliation.
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Optional backend callback URL for checkout status notifications. SumUp sends an HTTP POST with `event_type` and the checkout `id`. Retrieve the checkout to verify its current status before updating your order. See the [webhook guide](https://developer.sumup.com/online-payments/webhooks/) for the payload and response requirements.
     *
     * @var string|null
     */
    public ?string $returnUrl = null;

    /**
     * Unique SumUp identifier of the checkout resource.
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * Current high-level state of the checkout. `PENDING` means the checkout exists but is not yet completed, `PAID` means a payment succeeded, `FAILED` means the latest processing attempt failed, and `EXPIRED` means the checkout can no longer be processed.
     *
     * @var string|null
     */
    public ?string $status = null;

    /**
     * The timestamp of when the checkout was created.
     *
     * @var string|null
     */
    public ?string $date = null;

    /**
     * Optional expiration timestamp. The checkout must be processed before this moment, otherwise it becomes unusable. If omitted, the checkout does not have an explicit expiry time.
     *
     * @var string|null
     */
    public ?string $validUntil = null;

    /**
     * Merchant-scoped identifier of the customer associated with the checkout. Use it when storing payment instruments or reusing saved customer context for recurring and returning-payer flows.
     *
     * @var string|null
     */
    public ?string $customerId = null;

    /**
     * Details of the mandate linked to the saved payment instrument.
     *
     * @var MandateResponse|null
     */
    public ?MandateResponse $mandate = null;

    /**
     * URL of the SumUp-hosted payment page that handles the payment flow. Returned when Hosted Checkout is enabled for the checkout.
     *
     * @var string|null
     */
    public ?string $hostedCheckoutUrl = null;

    /**
     * Payment attempts and resulting transaction records linked to this checkout. Use the Transactions endpoints when you need the authoritative payment result and event history.
     *
     * @var mixed[]|null
     */
    public ?array $transactions = null;

    /**
     * Transaction code of the successful transaction with which the payment for the checkout is completed.
     *
     * @var string|null
     */
    public ?string $transactionCode = null;

    /**
     * Unique identifier of the successful transaction that completed payment for the checkout.
     *
     * @var string|null
     */
    public ?string $transactionId = null;

    /**
     * Name of the merchant.
     *
     * @var string|null
     */
    public ?string $merchantName = null;

    /**
     * URL where the payer is redirected after a redirect-based payment or SCA flow completes.
     *
     * @var string|null
     */
    public ?string $redirectUrl = null;

    /**
     * Details of the saved payment instrument created or reused during checkout processing.
     *
     * @var CheckoutSuccessPaymentInstrument|null
     */
    public ?CheckoutSuccessPaymentInstrument $paymentInstrument = null;

}
