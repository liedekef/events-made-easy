<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Detailed information about a transaction event.
 */
class TransactionEvent
{
    /**
     * Numeric identifier of a transaction event. Use it as `tx_event_id` when requesting receipt details for a specific event. This is separate from the transaction ID and the transaction history pagination references.
     *
     * @var int|null
     */
    public ?int $id = null;

    /**
     * Financial event associated with a transaction.
     * - `PAYOUT`: Funds from the transaction being prepared for or included in a merchant payout. Check the event status to determine whether they have been paid out.
     * - `REFUND`: Money returned to the payer.
     * - `CHARGE_BACK`: A reversal of the payment following a chargeback.
     * - `PAYOUT_DEDUCTION`: An amount deducted from a merchant payout, for example to cover a refund or chargeback.
     *
     * @var TransactionEventEventType|null
     */
    public ?TransactionEventEventType $eventType = null;

    /**
     * Status of the transaction event.
     * Not every value is used for every event type.
     * - `PENDING`: The event has been created but is not final yet. Used for events that are still being processed and whose final outcome is not known yet.
     * - `SCHEDULED`: The event is planned for a future payout cycle but has not been executed yet. This applies to payout events before money is actually sent out.
     * - `RECONCILED`: The underlying payment has been matched with settlement data and is ready to continue through payout processing, but the funds have not been paid out yet. This applies to payout events.
     * - `PAID_OUT`: The payout event has been completed and the funds were included in a merchant payout.
     * - `REFUNDED`: A refund event has been accepted and recorded in the refund flow. This is the status returned for refund events once the transaction amount is being or has been returned to the payer.
     * - `SUCCESSFUL`: The event completed successfully. Use this as the generic terminal success status for event types that do not expose a more specific business outcome such as `PAID_OUT` or `REFUNDED`.
     * - `FAILED`: The event could not be completed. Typical examples are a payout that could not be executed or an event that was rejected during processing.
     *
     * @var TransactionEventStatus|null
     */
    public ?TransactionEventStatus $status = null;

    /**
     * Amount of the event in major units of the associated transaction's currency.
     *
     * @var float|null
     */
    public ?float $amount = null;

    /**
     * Date when the transaction event is due to occur.
     *
     * @var string|null
     */
    public ?string $dueDate = null;

    /**
     * Date when the transaction event occurred.
     *
     * @var string|null
     */
    public ?string $date = null;

    /**
     * Consecutive number of the installment that is paid. Applicable only to payout events, i.e. `event_type = PAYOUT`.
     *
     * @var int|null
     */
    public ?int $installmentNumber = null;

    /**
     * Date and time of the transaction event.
     *
     * @var string|null
     */
    public ?string $timestamp = null;

}
