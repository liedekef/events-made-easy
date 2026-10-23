<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Information about the resource the membership is in.
 */
class MembershipResource
{
    /**
     * ID of the resource the membership is in.
     *
     * @var string
     */
    public string $id;

    /**
     * The type of the membership resource.
     * Possible values are:
     * * `merchant` - merchant account(s)
     * * `organization` - organization(s)
     *
     * @var string
     */
    public string $type;

    /**
     * Display name of the resource.
     *
     * @var string
     */
    public string $name;

    /**
     * Logo fo the resource.
     *
     * @var string|null
     */
    public ?string $logo = null;

    /**
     * The timestamp of when the membership resource was created.
     *
     * @var string
     */
    public string $createdAt;

    /**
     * The timestamp of when the membership resource was last updated.
     *
     * @var string
     */
    public string $updatedAt;

    /**
     * Object attributes that are modifiable only by SumUp applications.
     *
     * @var array<string, mixed>|null
     */
    public ?array $attributes = null;

}
