<?php

namespace Mollie\Api\Resources;

use Mollie\Api\Types\InvoiceStatus;

/**
 * @property \Mollie\Api\MollieApiClient $connector
 */
class Invoice extends BaseResource
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $reference;

    /**
     * @var string
     */
    public $vatNumber;

    /**
     * @var string
     */
    public $status;

    /**
     * Date the invoice was issued, e.g. 2018-01-01
     *
     * @var string
     */
    public $issuedAt;

    /**
     * Date the invoice was paid, e.g. 2018-01-01
     *
     * @var string|null
     */
    public $paidAt;

    /**
     * Date the invoice is due, e.g. 2018-01-01
     *
     * @var string|null
     */
    public $dueAt;

    /**
     * Amount object containing the total amount of the invoice excluding VAT.
     *
     * @var \stdClass
     */
    public $netAmount;

    /**
     * Amount object containing the VAT amount of the invoice. Only for merchants registered in the Netherlands.
     *
     * @var \stdClass
     */
    public $vatAmount;

    /**
     * Total amount of the invoice including VAT.
     *
     * @var \stdClass
     */
    public $grossAmount;

    /**
     * Array containing the invoice lines.
     *
     * @see https://docs.mollie.com/reference/v2/invoices-api/get-invoice
     *
     * @var array
     */
    public $lines;

    /**
     * Contains a PDF to the Invoice
     *
     * @var \stdClass
     */
    public $_links;

    public function isPaid(): bool
    {
        return $this->status == InvoiceStatus::PAID;
    }

    public function isOpen(): bool
    {
        return $this->status == InvoiceStatus::OPEN;
    }

    public function isOverdue(): bool
    {
        return $this->status == InvoiceStatus::OVERDUE;
    }
}
