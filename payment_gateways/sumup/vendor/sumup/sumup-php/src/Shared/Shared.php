<?php

declare(strict_types=1);

namespace SumUp\Shared;

/**
 * Three-letter [ISO4217](https://en.wikipedia.org/wiki/ISO_4217) code of the currency for the amount. Currently supported currency values are enumerated above.
 */
enum TransactionBaseCurrency: string
{
    case BGN = 'BGN';
    case BRL = 'BRL';
    case CHF = 'CHF';
    case CLP = 'CLP';
    case CZK = 'CZK';
    case DKK = 'DKK';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case HRK = 'HRK';
    case HUF = 'HUF';
    case NOK = 'NOK';
    case PLN = 'PLN';
    case RON = 'RON';
    case SEK = 'SEK';
    case USD = 'USD';
}

/**
 * Payment type used for the transaction.
 */
enum TransactionBasePaymentType: string
{
    case CASH = 'CASH';
    case POS = 'POS';
    case ECOM = 'ECOM';
    case RECURRING = 'RECURRING';
    case BITCOIN = 'BITCOIN';
    case BALANCE = 'BALANCE';
    case MOTO = 'MOTO';
    case BOLETO = 'BOLETO';
    case DIRECT_DEBIT = 'DIRECT_DEBIT';
    case APM = 'APM';
    case UNKNOWN = 'UNKNOWN';
}

/**
 * Current status of the transaction.
 */
enum TransactionBaseStatus: string
{
    case SUCCESSFUL = 'SUCCESSFUL';
    case CANCELLED = 'CANCELLED';
    case FAILED = 'FAILED';
    case PENDING = 'PENDING';
}

/**
 * Entry mode of the payment details.
 */
enum TransactionCheckoutInfoEntryMode: string
{
    case BOLETO = 'BOLETO';
    case SOFORT = 'SOFORT';
    case IDEAL = 'IDEAL';
    case BANCONTACT = 'BANCONTACT';
    case EPS = 'EPS';
    case MYBANK = 'MYBANK';
    case SATISPAY = 'SATISPAY';
    case BLIK = 'BLIK';
    case P_24 = 'P24';
    case GIROPAY = 'GIROPAY';
    case PIX = 'PIX';
    case QR_CODE_PIX = 'QR_CODE_PIX';
    case APPLE_PAY = 'APPLE_PAY';
    case GOOGLE_PAY = 'GOOGLE_PAY';
    case PAYPAL = 'PAYPAL';
    case NONE = 'NONE';
    case CHIP = 'CHIP';
    case MANUAL_ENTRY = 'MANUAL_ENTRY';
    case CUSTOMER_ENTRY = 'CUSTOMER_ENTRY';
    case MAGSTRIPE_FALLBACK = 'MAGSTRIPE_FALLBACK';
    case MAGSTRIPE = 'MAGSTRIPE';
    case DIRECT_DEBIT = 'DIRECT_DEBIT';
    case CONTACTLESS = 'CONTACTLESS';
    case MOTO = 'MOTO';
    case CONTACTLESS_MAGSTRIPE = 'CONTACTLESS_MAGSTRIPE';
    case N_A = 'N/A';
}

/**
 * Profile's personal address information.
 */
class AddressLegacy
{
    /**
     * City name from the address.
     *
     * @var string|null
     */
    public ?string $city = null;

    /**
     * Two letter country code formatted according to [ISO3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2).
     *
     * @var string|null
     */
    public ?string $country = null;

    /**
     * First line of the address with details of the street name and number.
     *
     * @var string|null
     */
    public ?string $line1 = null;

    /**
     * Second line of the address with details of the building, unit, apartment, and floor numbers.
     *
     * @var string|null
     */
    public ?string $line2 = null;

    /**
     * Postal code from the address.
     *
     * @var string|null
     */
    public ?string $postalCode = null;

    /**
     * State name or abbreviation from the address.
     *
     * @var string|null
     */
    public ?string $state = null;

}

/**
 * Error message structure.
 */
class Error
{
    /**
     * Short description of the error.
     *
     * @var string|null
     */
    public ?string $message = null;

    /**
     * Platform code for the error.
     *
     * @var string|null
     */
    public ?string $errorCode = null;

}

/**
 * Error message for forbidden requests.
 */
class ErrorForbidden
{
    /**
     * Short description of the error.
     *
     * @var string|null
     */
    public ?string $errorMessage = null;

    /**
     * Platform code for the error.
     *
     * @var string|null
     */
    public ?string $errorCode = null;

    /**
     * HTTP status code for the error.
     *
     * @var string|null
     */
    public ?string $statusCode = null;

}

/**
 * Pending invitation for membership.
 */
class Invite
{
    /**
     * Email address of the invited user.
     *
     * @var string
     */
    public string $email;

    /**
     *
     * @var string
     */
    public string $expiresAt;

}

/**
 * Created mandate
 */
class MandateResponse
{
    /**
     * Indicates the mandate type
     *
     * @var string|null
     */
    public ?string $type = null;

    /**
     * Mandate status
     *
     * @var string|null
     */
    public ?string $status = null;

    /**
     * Merchant code which has the mandate
     *
     * @var string|null
     */
    public ?string $merchantCode = null;

}

/**
 * Personal details for the customer.
 */
class PersonalDetails
{
    /**
     * First name of the customer.
     *
     * @var string|null
     */
    public ?string $firstName = null;

    /**
     * Last name of the customer.
     *
     * @var string|null
     */
    public ?string $lastName = null;

    /**
     * Email address of the customer.
     *
     * @var string|null
     */
    public ?string $email = null;

    /**
     * Phone number of the customer.
     *
     * @var string|null
     */
    public ?string $phone = null;

    /**
     * Date of birth of the customer.
     *
     * @var string|null
     */
    public ?string $birthDate = null;

    /**
     * An identification number user for tax purposes (e.g. CPF)
     *
     * @var string|null
     */
    public ?string $taxId = null;

    /**
     * Profile's personal address information.
     *
     * @var AddressLegacy|null
     */
    public ?AddressLegacy $address = null;

}

/**
 * A RFC 9457 problem details object.
 *
 * Additional properties specific to the problem type may be present.
 *
 */
class Problem
{
    /**
     * A URI reference that identifies the problem type.
     *
     * @var string
     */
    public string $type;

    /**
     * A short, human-readable summary of the problem type.
     *
     * @var string|null
     */
    public ?string $title = null;

    /**
     * The HTTP status code generated by the origin server for this occurrence of the problem.
     *
     * @var int|null
     */
    public ?int $status = null;

    /**
     * A human-readable explanation specific to this occurrence of the problem.
     *
     * @var string|null
     */
    public ?string $detail = null;

    /**
     * A URI reference that identifies the specific occurrence of the problem.
     *
     * @var string|null
     */
    public ?string $instance = null;

}

/**
 * Details of the transaction.
 */
class TransactionBase
{
    /**
     * Unique ID of the transaction.
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * Transaction code returned by the acquirer/processing entity after processing the transaction.
     *
     * @var string|null
     */
    public ?string $transactionCode = null;

    /**
     * Total amount of the transaction.
     *
     * @var float|null
     */
    public ?float $amount = null;

    /**
     * Three-letter [ISO4217](https://en.wikipedia.org/wiki/ISO_4217) code of the currency for the amount. Currently supported currency values are enumerated above.
     *
     * @var TransactionBaseCurrency|null
     */
    public ?TransactionBaseCurrency $currency = null;

    /**
     * Date and time of the creation of the transaction. Response format expressed according to [ISO8601](https://en.wikipedia.org/wiki/ISO_8601) code.
     *
     * @var string|null
     */
    public ?string $timestamp = null;

    /**
     * Current status of the transaction.
     *
     * @var TransactionBaseStatus|null
     */
    public ?TransactionBaseStatus $status = null;

    /**
     * Payment type used for the transaction.
     *
     * @var TransactionBasePaymentType|null
     */
    public ?TransactionBasePaymentType $paymentType = null;

    /**
     * Current number of the installment for deferred payments.
     *
     * @var int|null
     */
    public ?int $installmentsCount = null;

}

class TransactionCheckoutInfo
{
    /**
     * Unique code of the registered merchant to whom the payment is made.
     *
     * @var string|null
     */
    public ?string $merchantCode = null;

    /**
     * Amount of the applicable VAT (out of the total transaction amount).
     *
     * @var float|null
     */
    public ?float $vatAmount = null;

    /**
     * Amount of the tip (out of the total transaction amount).
     *
     * @var float|null
     */
    public ?float $tipAmount = null;

    /**
     * Entry mode of the payment details.
     *
     * @var TransactionCheckoutInfoEntryMode|null
     */
    public ?TransactionCheckoutInfoEntryMode $entryMode = null;

    /**
     * Authorization code for the transaction sent by the payment card issuer or bank. Applicable only to card payments.
     *
     * @var string|null
     */
    public ?string $authCode = null;

    /**
     * Internal unique ID of the transaction on the SumUp platform.
     *
     * @var int|null
     */
    public ?int $internalId = null;

}
