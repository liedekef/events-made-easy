<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * A membership associates a user with a resource, memberships is defined by user, resource, resource type, and associated roles.
 */
class Membership
{
    /**
     * ID of the membership.
     *
     * @var string
     */
    public string $id;

    /**
     * ID of the resource the membership is in.
     *
     * @var string
     */
    public string $resourceId;

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
     * User's roles.
     *
     * @var string[]
     */
    public array $roles;

    /**
     * User's permissions.
     *
     * @var string[]
     */
    public array $permissions;

    /**
     * The timestamp of when the membership was created.
     *
     * @var string
     */
    public string $createdAt;

    /**
     * The timestamp of when the membership was last updated.
     *
     * @var string
     */
    public string $updatedAt;

    /**
     * Pending invitation for membership.
     *
     * @var Invite|null
     */
    public ?Invite $invite = null;

    /**
     * The status of the membership.
     *
     * @var MembershipStatus
     */
    public MembershipStatus $status;

    /**
     * Set of user-defined key-value pairs attached to the object. Partial updates are not supported. When updating, always submit whole metadata. Maximum of 64 parameters are allowed in the object.
     *
     * @var array<string, mixed>|null
     */
    public ?array $metadata = null;

    /**
     * Object attributes that are modifiable only by SumUp applications.
     *
     * @var array<string, mixed>|null
     */
    public ?array $attributes = null;

    /**
     * Information about the resource the membership is in.
     *
     * @var MembershipResource
     */
    public MembershipResource $resource;

}
