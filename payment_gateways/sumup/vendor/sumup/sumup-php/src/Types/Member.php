<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * A member is user within specific resource identified by resource id, resource type, and associated roles.
 */
class Member
{
    /**
     * ID of the member.
     *
     * @var string
     */
    public string $id;

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
     * The timestamp of when the member was created.
     *
     * @var string
     */
    public string $createdAt;

    /**
     * The timestamp of when the member was last updated.
     *
     * @var string
     */
    public string $updatedAt;

    /**
     * Information about the user associated with the membership.
     *
     * @var MembershipUser|null
     */
    public ?MembershipUser $user = null;

    /**
     * Pending invitation for membership.
     *
     * @var Invite|null
     */
    public ?Invite $invite = null;

    /**
     * The status of the membership.
     *
     * @var MemberStatus
     */
    public MemberStatus $status;

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

}
