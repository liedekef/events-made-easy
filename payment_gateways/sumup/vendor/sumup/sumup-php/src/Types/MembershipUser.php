<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Information about the user associated with the membership.
 */
class MembershipUser
{
    /**
     * Identifier for the End-User (also called Subject).
     *
     * @var string
     */
    public string $id;

    /**
     * Type of the user account.
     *
     * @var MembershipUserType
     */
    public MembershipUserType $type;

    /**
     * End-User's preferred e-mail address. Its value MUST conform to the RFC 5322 [RFC5322] addr-spec syntax. The RP MUST NOT rely upon this value being unique, for unique identification use ID instead.
     *
     * @var string
     */
    public string $email;

    /**
     * True if the user has enabled MFA on login.
     *
     * @var bool
     */
    public bool $mfaOnLoginEnabled;

    /**
     * True if the user is a virtual user (operator).
     *
     * @var bool
     */
    public bool $virtualUser;

    /**
     * True if the user is a service account.
     *
     * @var bool
     */
    public bool $serviceAccountUser;

    /**
     * Time when the user has been disabled. Applies only to virtual users (`virtual_user: true`).
     *
     * @var string|null
     */
    public ?string $disabledAt = null;

    /**
     * User's nickname. Used for display purposes only.
     *
     * @var string|null
     */
    public ?string $nickname = null;

    /**
     * URL of the End-User's profile picture. This URL refers to an image file (for example, a PNG, JPEG, or GIF image file), rather than to a Web page containing an image.
     *
     * @var string|null
     */
    public ?string $picture = null;

    /**
     * Classic identifiers of the user.
     *
     * @var MembershipUserClassic|null
     */
    public ?MembershipUserClassic $classic = null;

}
