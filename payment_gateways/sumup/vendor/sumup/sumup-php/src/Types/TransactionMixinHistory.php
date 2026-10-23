<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Additional transaction fields used by history and detailed views.
 */
class TransactionMixinHistory
{
    /**
     * Short description of the payment. The value is taken from the `description` property of the related checkout resource.
     *
     * @var string|null
     */
    public ?string $productSummary = null;

    /**
     * Total number of payouts to the registered user specified in the `user` property.
     *
     * @var int|null
     */
    public ?int $payoutsTotal = null;

    /**
     * Number of payouts that are made to the registered user specified in the `user` property.
     *
     * @var int|null
     */
    public ?int $payoutsReceived = null;

    /**
     * Payout plan of the registered user at the time when the transaction was made.
     *
     * @var TransactionMixinHistoryPayoutPlan|null
     */
    public ?TransactionMixinHistoryPayoutPlan $payoutPlan = null;

}
