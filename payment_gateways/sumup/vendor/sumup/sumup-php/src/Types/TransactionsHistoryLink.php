<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Hypermedia link used for transaction history pagination.
 */
class TransactionsHistoryLink
{
    /**
     * Pagination relation indicating which page the link retrieves, for example `next`.
     *
     * @var string
     */
    public string $rel;

    /**
     * Query string to use with the transaction history endpoint when requesting the linked page. Preserve the returned pagination references and query parameters.
     *
     * @var string
     */
    public string $href;

}
