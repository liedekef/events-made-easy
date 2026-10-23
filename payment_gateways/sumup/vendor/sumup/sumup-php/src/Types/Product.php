<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Product details associated with a transaction.
 */
class Product
{
    /**
     * Product name.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Human-readable label for the product price.
     *
     * @var string|null
     */
    public ?string $priceLabel = null;

    /**
     * Product price.
     *
     * @var float|null
     */
    public ?float $price = null;

    /**
     * VAT rate as a decimal fraction, for example `0.19` for 19%.
     *
     * @var float|null
     */
    public ?float $vatRate = null;

    /**
     * VAT amount for a single product.
     *
     * @var float|null
     */
    public ?float $singleVatAmount = null;

    /**
     * Product price including VAT.
     *
     * @var float|null
     */
    public ?float $priceWithVat = null;

    /**
     * Total VAT amount for the product quantity.
     *
     * @var float|null
     */
    public ?float $vatAmount = null;

    /**
     * Product quantity.
     *
     * @var int|null
     */
    public ?int $quantity = null;

    /**
     * Total price calculated as the product price multiplied by the quantity.
     *
     * @var float|null
     */
    public ?float $totalPrice = null;

    /**
     * Total product price including VAT.
     *
     * @var float|null
     */
    public ?float $totalWithVat = null;

}
