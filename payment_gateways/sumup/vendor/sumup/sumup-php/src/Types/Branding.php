<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Settings used to apply the Merchant's branding to email receipts, invoices, checkouts, and other products.
 */
class Branding
{
    /**
     * Footer text rendered on receipts and other customer-facing products.
     *
     * @var string|null
     */
    public ?string $footerText = null;

    /**
     * An icon for the merchant. Must be square.
     *
     * @var string|null
     */
    public ?string $icon = null;

    /**
     * A logo for the merchant that will be used in place of the icon and without the merchant's name next to it if there's sufficient space.
     *
     * @var string|null
     */
    public ?string $logo = null;

    /**
     * Data-URL encoded hero image for the merchant business.
     *
     * @var string|null
     */
    public ?string $hero = null;

    /**
     * A hex color value representing the primary branding color of this merchant (your brand color).
     *
     * @var string|null
     */
    public ?string $primaryColor = null;

    /**
     * A hex color value representing the color of the text displayed on branding color of this merchant.
     *
     * @var string|null
     */
    public ?string $primaryColorFg = null;

    /**
     * A hex color value representing the secondary branding color of this merchant (accent color used for buttons).
     *
     * @var string|null
     */
    public ?string $secondaryColor = null;

    /**
     * A hex color value representing the color of the text displayed on secondary branding color of this merchant.
     *
     * @var string|null
     */
    public ?string $secondaryColorFg = null;

    /**
     * A hex color value representing the preferred background color of this merchant.
     *
     * @var string|null
     */
    public ?string $backgroundColor = null;

}
