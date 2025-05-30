<?php

namespace Braintree;

/**
 * WebhookTestingGateway module
 * Creates and manages test webhooks
 */
class WebhookTestingGateway
{
    private $config;

    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
    public function __construct($gateway)
    {
        $this->config = $gateway->config;
        $this->config->assertHasAccessTokenOrKeys();
    }

    /**
     * Build a sample Webhook
     *
     * @param string $kind             the kind of Webhook you want to generate
     * @param string $id               unique identifier
     * @param string $sourceMerchantId optional
     *
     * @return Webhook
     */
    public function sampleNotification($kind, $id, $sourceMerchantId = null)
    {
        $xml = self::_sampleXml($kind, $id, $sourceMerchantId);
        $payload = base64_encode($xml) . "\n";
        $publicKey = $this->config->getPublicKey();
        $sha = Digest::hexDigestSha1($this->config->getPrivateKey(), $payload);
        $signature = $publicKey . "|" . $sha;

        return [
            'bt_signature' => $signature,
            'bt_payload' => $payload
        ];
    }

    private static function _sampleXml($kind, $id, $sourceMerchantId)
    {
        switch ($kind) {
            case WebhookNotification::TRANSACTION_DISBURSED:
                $subjectXml = self::_transactionDisbursedSampleXml($id);
                break;
            case WebhookNotification::TRANSACTION_REVIEWED:
                $subjectXml = self::_transactionReviewedSampleXml($id);
                break;
            case WebhookNotification::TRANSACTION_SETTLED:
                $subjectXml = self::_transactionSettledSampleXml($id);
                break;
            case WebhookNotification::TRANSACTION_SETTLEMENT_DECLINED:
                $subjectXml = self::_transactionSettlementDeclinedSampleXml($id);
                break;
            case WebhookNotification::DISBURSEMENT_EXCEPTION:
                $subjectXml = self::_disbursementExceptionSampleXml($id);
                break;
            case WebhookNotification::DISBURSEMENT:
                $subjectXml = self::_disbursementSampleXml($id);
                break;
            case WebhookNotification::PARTNER_MERCHANT_CONNECTED:
                $subjectXml = self::_partnerMerchantConnectedSampleXml($id);
                break;
            case WebhookNotification::PARTNER_MERCHANT_DISCONNECTED:
                $subjectXml = self::_partnerMerchantDisconnectedSampleXml($id);
                break;
            case WebhookNotification::PARTNER_MERCHANT_DECLINED:
                $subjectXml = self::_partnerMerchantDeclinedSampleXml($id);
                break;
            case WebhookNotification::OAUTH_ACCESS_REVOKED:
                $subjectXml = self::_oauthAccessRevocationSampleXml($id);
                break;
            case WebhookNotification::CONNECTED_MERCHANT_STATUS_TRANSITIONED:
                $subjectXml = self::_connectedMerchantStatusTransitionedSampleXml($id);
                break;
            case WebhookNotification::CONNECTED_MERCHANT_PAYPAL_STATUS_CHANGED:
                $subjectXml = self::_connectedMerchantPayPalStatusChangedSampleXml($id);
                break;
            case WebhookNotification::DISPUTE_ACCEPTED:
                $subjectXml = self::_disputeAcceptedSampleXml($id);
                break;
            case WebhookNotification::DISPUTE_AUTO_ACCEPTED:
                $subjectXml = self::_disputeAutoAcceptedSampleXml($id);
                break;
            case WebhookNotification::DISPUTE_DISPUTED:
                $subjectXml = self::_disputeDisputedSampleXml($id);
                break;
            case WebhookNotification::DISPUTE_EXPIRED:
                $subjectXml = self::_disputeExpiredSampleXml($id);
                break;
            case WebhookNotification::DISPUTE_LOST:
                $subjectXml = self::_disputeLostSampleXml($id);
                break;
            case WebhookNotification::DISPUTE_OPENED:
                $subjectXml = self::_disputeOpenedSampleXml($id);
                break;
            case WebhookNotification::DISPUTE_UNDER_REVIEW:
                $subjectXml = self::_disputeUnderReviewSampleXml($id);
                break;
            case WebhookNotification::DISPUTE_WON:
                $subjectXml = self::_disputeWonSampleXml($id);
                break;
            case WebhookNotification::REFUND_FAILED:
                $subjectXml = self::_refundFailedSampleXml($id);
                break;
            case WebhookNotification::SUBSCRIPTION_BILLING_SKIPPED:
                $subjectXml = self::_subscriptionBillingSkippedSampleXml($id);
                break;
            case WebhookNotification::SUBSCRIPTION_CHARGED_SUCCESSFULLY:
                $subjectXml = self::_subscriptionChargedSuccessfullySampleXml($id);
                break;
            case WebhookNotification::SUBSCRIPTION_CHARGED_UNSUCCESSFULLY:
                $subjectXml = self::_subscriptionChargedUnsuccessfullySampleXml($id);
                break;
            case WebhookNotification::SUBSCRIPTION_EXPIRED:
                $subjectXml = self::_subscriptionExpiredSampleXml($id);
                break;
            case WebhookNotification::SUBSCRIPTION_CANCELED:
                $subjectXml = self::_subscriptionCanceledSampleXml($id);
                break;
            case WebhookNotification::SUBSCRIPTION_WENT_PAST_DUE:
                $subjectXml = self::_subscriptionWentPastDueSampleXml($id);
                break;
            case WebhookNotification::CHECK:
                $subjectXml = self::_checkSampleXml();
                break;
            case WebhookNotification::ACCOUNT_UPDATER_DAILY_REPORT:
                $subjectXml = self::_accountUpdaterDailyReportSampleXml($id);
                break;
            case WebhookNotification::GRANTOR_UPDATED_GRANTED_PAYMENT_METHOD:
                $subjectXml = self::_grantedPaymentInstrumentUpdateSampleXml();
                break;
            case WebhookNotification::RECIPIENT_UPDATED_GRANTED_PAYMENT_METHOD:
                $subjectXml = self::_grantedPaymentInstrumentUpdateSampleXml();
                break;
            case WebhookNotification::GRANTED_PAYMENT_METHOD_REVOKED:
                $subjectXml = self::_venmoAccountXml($id);
                break;
            case WebhookNotification::PAYMENT_METHOD_REVOKED_BY_CUSTOMER:
                $subjectXml = self::_paymentMethodRevokedByCustomerSampleXml($id);
                break;
            case WebhookNotification::LOCAL_PAYMENT_COMPLETED:
                $subjectXml = self::_localPaymentCompletedSampleXml($id);
                break;
            case WebhookNotification::LOCAL_PAYMENT_EXPIRED:
                $subjectXml = self::_localPaymentExpiredSampleXml();
                break;
            case WebhookNotification::LOCAL_PAYMENT_FUNDED:
                $subjectXml = self::_localPaymentFundedSampleXml();
                break;
            case WebhookNotification::LOCAL_PAYMENT_REVERSED:
                $subjectXml = self::_localPaymentReversedSampleXml();
                break;
            case WebhookNotification::PAYMENT_METHOD_CUSTOMER_DATA_UPDATED:
                $subjectXml = self::_paymentMethodCustomerDataUpdatedSampleXml($id);
                break;
            default:
                $subjectXml = self::_subscriptionSampleXml($id);
                break;
        }
        $timestamp = self::_timestamp();

        $sourceMerchantIdXml = '';
        if (!is_null($sourceMerchantId)) {
            $sourceMerchantIdXml = "<source-merchant-id>{$sourceMerchantId}</source-merchant-id>";
        }

        return "
        <notification>
            <timestamp type=\"datetime\">{$timestamp}</timestamp>
            <kind>{$kind}</kind>
            {$sourceMerchantIdXml}
            <subject>{$subjectXml}</subject>
        </notification>
        ";
    }

    private static function _merchantAccountApprovedSampleXml($id)
    {
        return "
        <merchant_account>
            <id>{$id}</id>
            <master_merchant_account>
                <id>master_ma_for_{$id}</id>
                <status>active</status>
            </master_merchant_account>
            <status>active</status>
        </merchant_account>
        ";
    }

    private static function _merchantAccountDeclinedSampleXml($id)
    {
        return "
        <api-error-response>
            <message>Credit score is too low</message>
            <errors>
                <errors type=\"array\"/>
                    <merchant-account>
                        <errors type=\"array\">
                            <error>
                                <code>82621</code>
                                <message>Credit score is too low</message>
                                <attribute type=\"symbol\">base</attribute>
                            </error>
                        </errors>
                    </merchant-account>
                </errors>
                <merchant-account>
                    <id>{$id}</id>
                    <status>suspended</status>
                    <master-merchant-account>
                        <id>master_ma_for_{$id}</id>
                        <status>suspended</status>
                    </master-merchant-account>
                </merchant-account>
        </api-error-response>
        ";
    }

    private static function _transactionDisbursedSampleXml($id)
    {
        return "
        <transaction>
            <id>{$id}</id>
            <amount>100</amount>
            <disbursement-details>
                <disbursement-date type=\"date\">2013-07-09</disbursement-date>
            </disbursement-details>
        </transaction>
        ";
    }

    private static function _transactionReviewedSampleXml($id)
    {
        return "
        <transaction-review>
            <transaction-id>my_id</transaction-id>
            <decision>smart_decision</decision>
            <reviewer-email>hey@girl.com</reviewer-email>
            <reviewer-note>I reviewed this</reviewer-note>
            <reviewed-time type='dateTime'>2018-10-11T21:28:37Z</reviewed-time>
        </transaction-review>
        ";
    }

    private static function _transactionSettledSampleXml($id)
    {
        return "
        <transaction>
          <id>{$id}</id>
          <status>settled</status>
          <type>sale</type>
          <currency-iso-code>USD</currency-iso-code>
          <amount>100.00</amount>
          <merchant-account-id>ogaotkivejpfayqfeaimuktty</merchant-account-id>
          <payment-instrument-type>us_bank_account</payment-instrument-type>
          <us-bank-account>
            <routing-number>123456789</routing-number>
            <last-4>1234</last-4>
            <account-type>checking</account-type>
            <account-holder-name>Dan Schulman</account-holder-name>
          </us-bank-account>
        </transaction>
        ";
    }

    private static function _transactionSettlementDeclinedSampleXml($id)
    {
        return "
        <transaction>
          <id>{$id}</id>
          <status>settlement_declined</status>
          <type>sale</type>
          <currency-iso-code>USD</currency-iso-code>
          <amount>100.00</amount>
          <merchant-account-id>ogaotkivejpfayqfeaimuktty</merchant-account-id>
          <payment-instrument-type>us_bank_account</payment-instrument-type>
          <us-bank-account>
            <routing-number>123456789</routing-number>
            <last-4>1234</last-4>
            <account-type>checking</account-type>
            <account-holder-name>Dan Schulman</account-holder-name>
          </us-bank-account>
        </transaction>
        ";
    }

    private static function _disbursementSampleXml($id)
    {
        return "
        <disbursement>
          <id>{$id}</id>
          <transaction-ids type=\"array\">
            <item>asdfg</item>
            <item>qwert</item>
          </transaction-ids>
          <success type=\"boolean\">true</success>
          <retry type=\"boolean\">false</retry>
          <merchant-account>
            <id>merchant_account_token</id>
            <currency-iso-code>USD</currency-iso-code>
            <sub-merchant-account type=\"boolean\">false</sub-merchant-account>
            <status>active</status>
          </merchant-account>
          <amount>100.00</amount>
          <disbursement-date type=\"date\">2014-02-10</disbursement-date>
          <exception-message nil=\"true\"/>
          <follow-up-action nil=\"true\"/>
        </disbursement>
        ";
    }

    private static function _disputeUnderReviewSampleXml($id)
    {
        return "
        <dispute>
          <amount>250.00</amount>
          <amount-disputed>250.0</amount-disputed>
          <amount-won>245.00</amount-won>
          <currency-iso-code>USD</currency-iso-code>
          <received-date type=\"date\">2014-03-01</received-date>
          <reply-by-date type=\"date\">2014-03-21</reply-by-date>
          <kind>chargeback</kind>
          <status>under_review</status>
          <reason>fraud</reason>
          <id>{$id}</id>
          <transaction>
            <id>{$id}</id>
            <amount>250.00</amount>
          </transaction>
          <date-opened type=\"date\">2014-03-21</date-opened>
        </dispute>
        ";
    }

    private static function _disputeOpenedSampleXml($id)
    {
        return "
        <dispute>
          <amount>250.00</amount>
          <amount-disputed>250.0</amount-disputed>
          <amount-won>245.00</amount-won>
          <currency-iso-code>USD</currency-iso-code>
          <received-date type=\"date\">2014-03-01</received-date>
          <reply-by-date type=\"date\">2014-03-21</reply-by-date>
          <kind>chargeback</kind>
          <status>open</status>
          <reason>fraud</reason>
          <id>{$id}</id>
          <transaction>
            <id>{$id}</id>
            <amount>250.00</amount>
          </transaction>
          <date-opened type=\"date\">2014-03-21</date-opened>
        </dispute>
        ";
    }

    private static function _disputeLostSampleXml($id)
    {
        return "
        <dispute>
          <amount>250.00</amount>
          <amount-disputed>250.0</amount-disputed>
          <amount-won>245.00</amount-won>
          <currency-iso-code>USD</currency-iso-code>
          <received-date type=\"date\">2014-03-01</received-date>
          <reply-by-date type=\"date\">2014-03-21</reply-by-date>
          <kind>chargeback</kind>
          <status>lost</status>
          <reason>fraud</reason>
          <id>{$id}</id>
          <transaction>
            <id>{$id}</id>
            <amount>250.00</amount>
            <next_billing-date type=\"date\">2020-02-10</next_billing-date>
          </transaction>
          <date-opened type=\"date\">2014-03-21</date-opened>
        </dispute>
        ";
    }

    private static function _disputeWonSampleXml($id)
    {
        return "
        <dispute>
          <amount>250.00</amount>
          <amount-disputed>250.0</amount-disputed>
          <amount-won>245.00</amount-won>
          <currency-iso-code>USD</currency-iso-code>
          <received-date type=\"date\">2014-03-01</received-date>
          <reply-by-date type=\"date\">2014-03-21</reply-by-date>
          <kind>chargeback</kind>
          <status>won</status>
          <reason>fraud</reason>
          <id>{$id}</id>
          <transaction>
            <id>{$id}</id>
            <amount>250.00</amount>
          </transaction>
          <date-opened type=\"date\">2014-03-21</date-opened>
          <date-won type=\"date\">2014-03-22</date-won>
        </dispute>
        ";
    }

    private static function _refundFailedSampleXml($id)
    {
        return "
        <transaction>
            <id>{$id}</id>
            <amount>250.00</amount>
            <us-bank-account>
                <routing-number>123456789</routing-number>
                <last-4>1234</last-4>
                <account-type>checking</account-type>
                <account-holder-name>Dan Schulman</account-holder-name>
            </us-bank-account>
            <status>processor_declined</status>
            <refunded-transaction-fk>1</refunded-transaction-fk>
        </transaction>
        ";
    }

    private static function _disputeAcceptedSampleXml($id)
    {
        return "
        <dispute>
          <amount>250.00</amount>
          <amount-disputed>250.0</amount-disputed>
          <amount-won>245.00</amount-won>
          <currency-iso-code>USD</currency-iso-code>
          <received-date type=\"date\">2014-03-01</received-date>
          <reply-by-date type=\"date\">2014-03-21</reply-by-date>
          <kind>chargeback</kind>
          <status>accepted</status>
          <reason>fraud</reason>
          <id>{$id}</id>
          <transaction>
            <id>{$id}</id>
            <amount>250.00</amount>
          </transaction>
          <date-opened type=\"date\">2014-03-21</date-opened>
        </dispute>
        ";
    }

    private static function _disputeAutoAcceptedSampleXml($id)
    {
        return "
        <dispute>
          <amount>250.00</amount>
          <amount-disputed>250.0</amount-disputed>
          <amount-won>245.00</amount-won>
          <currency-iso-code>USD</currency-iso-code>
          <received-date type=\"date\">2014-03-01</received-date>
          <reply-by-date type=\"date\">2014-03-21</reply-by-date>
          <kind>chargeback</kind>
          <status>auto_accepted</status>
          <reason>fraud</reason>
          <id>{$id}</id>
          <transaction>
            <id>{$id}</id>
            <amount>250.00</amount>
          </transaction>
          <date-opened type=\"date\">2014-03-21</date-opened>
        </dispute>
        ";
    }

    private static function _disputeDisputedSampleXml($id)
    {
        return "
        <dispute>
          <amount>250.00</amount>
          <amount-disputed>250.0</amount-disputed>
          <amount-won>245.00</amount-won>
          <currency-iso-code>USD</currency-iso-code>
          <received-date type=\"date\">2014-03-01</received-date>
          <reply-by-date type=\"date\">2014-03-21</reply-by-date>
          <kind>chargeback</kind>
          <status>disputed</status>
          <reason>fraud</reason>
          <id>{$id}</id>
          <transaction>
            <id>{$id}</id>
            <amount>250.00</amount>
          </transaction>
          <date-opened type=\"date\">2014-03-21</date-opened>
        </dispute>
        ";
    }

    private static function _disputeExpiredSampleXml($id)
    {
        return "
        <dispute>
          <amount>250.00</amount>
          <amount-disputed>250.0</amount-disputed>
          <amount-won>245.00</amount-won>
          <currency-iso-code>USD</currency-iso-code>
          <received-date type=\"date\">2014-03-01</received-date>
          <reply-by-date type=\"date\">2014-03-21</reply-by-date>
          <kind>chargeback</kind>
          <status>expired</status>
          <reason>fraud</reason>
          <id>{$id}</id>
          <transaction>
            <id>{$id}</id>
            <amount>250.00</amount>
          </transaction>
          <date-opened type=\"date\">2014-03-21</date-opened>
        </dispute>
        ";
    }

    private static function _subscriptionSampleXml($id)
    {
        return "
        <subscription>
            <id>{$id}</id>
            <status>Active</status>
            <transactions type=\"array\">
            </transactions>
            <add_ons type=\"array\">
            </add_ons>
            <discounts type=\"array\">
            </discounts>
        </subscription>
        ";
    }

    private static function _subscriptionBillingSkippedSampleXml($id)
    {
        return "
        <subscription>
            <id>{$id}</id>
            <status>Active</status>
            <transactions type=\"array\">
            </transactions>
            <add_ons type=\"array\">
            </add_ons>
            <discounts type=\"array\">
            </discounts>
        </subscription>
        ";
    }

    private static function _subscriptionChargedSuccessfullySampleXml($id)
    {
        return "
        <subscription>
            <id>{$id}</id>
            <status>Active</status>
            <billing-period-start-date type=\"date\">2016-03-21</billing-period-start-date>
            <billing-period-end-date type=\"date\">2017-03-31</billing-period-end-date>
            <transactions type=\"array\">
                <transaction>
                    <id>{$id}</id>
                    <status>submitted_for_settlement</status>
                    <amount>49.99</amount>
                </transaction>
            </transactions>
            <add_ons type=\"array\">
            </add_ons>
            <discounts type=\"array\">
            </discounts>
        </subscription>
        ";
    }

    private static function _subscriptionChargedUnsuccessfullySampleXml($id)
    {
        return "
        <subscription>
            <id>{$id}</id>
            <status>Active</status>
            <billing-period-start-date type=\"date\">2016-03-21</billing-period-start-date>
            <billing-period-end-date type=\"date\">2017-03-31</billing-period-end-date>
            <transactions type=\"array\">
                <transaction>
                    <id>{$id}</id>
                    <status>failed</status>
                    <amount>49.99</amount>
                </transaction>
            </transactions>
            <add_ons type=\"array\">
            </add_ons>
            <discounts type=\"array\">
            </discounts>
        </subscription>
        ";
    }

    private static function _subscriptionExpiredSampleXml($id)
    {
        return "
        <subscription>
            <id>{$id}</id>
            <status>Expired</status>
            <transactions type=\"array\">
            </transactions>
            <add_ons type=\"array\">
            </add_ons>
            <discounts type=\"array\">
            </discounts>
        </subscription>
        ";
    }

    private static function _subscriptionCanceledSampleXml($id)
    {
        return "
        <subscription>
            <id>{$id}</id>
            <status>Canceled</status>
            <transactions type=\"array\">
            </transactions>
            <add_ons type=\"array\">
            </add_ons>
            <discounts type=\"array\">
            </discounts>
        </subscription>
        ";
    }

    private static function _subscriptionWentPastDueSampleXml($id)
    {
        return "
        <subscription>
            <id>{$id}</id>
            <status>Past Due</status>
            <transactions type=\"array\">
            </transactions>
            <add_ons type=\"array\">
            </add_ons>
            <discounts type=\"array\">
            </discounts>
        </subscription>
        ";
    }

    private static function _checkSampleXml()
    {
        return "
            <check type=\"boolean\">true</check>
        ";
    }

    private static function _partnerMerchantConnectedSampleXml($id)
    {
        return "
        <partner-merchant>
          <merchant-public-id>public_id</merchant-public-id>
          <public-key>public_key</public-key>
          <private-key>private_key</private-key>
          <partner-merchant-id>abc123</partner-merchant-id>
          <client-side-encryption-key>cse_key</client-side-encryption-key>
        </partner-merchant>
        ";
    }

    private static function _partnerMerchantDisconnectedSampleXml($id)
    {
        return "
        <partner-merchant>
          <partner-merchant-id>abc123</partner-merchant-id>
        </partner-merchant>
        ";
    }

    private static function _partnerMerchantDeclinedSampleXml($id)
    {
        return "
        <partner-merchant>
          <partner-merchant-id>abc123</partner-merchant-id>
        </partner-merchant>
        ";
    }

    private static function _oauthAccessRevocationSampleXml($id)
    {
        return "
        <oauth-application-revocation>
          <merchant-id>{$id}</merchant-id>
          <oauth-application-client-id>oauth_application_client_id</oauth-application-client-id>
        </oauth-application-revocation>
        ";
    }

    private static function _accountUpdaterDailyReportSampleXml($id)
    {
        return "
        <account-updater-daily-report>
            <report-date type=\"date\">2016-01-14</report-date>
            <report-url>link-to-csv-report</report-url>
        </account-updater-daily-report>
        ";
    }

    private static function _connectedMerchantStatusTransitionedSampleXml($id)
    {
        return "
        <connected-merchant-status-transitioned>
          <merchant-public-id>{$id}</merchant-public-id>
          <status>new_status</status>
          <oauth-application-client-id>oauth_application_client_id</oauth-application-client-id>
        </connected-merchant-status-transitioned>
        ";
    }

    private static function _connectedMerchantPayPalStatusChangedSampleXml($id)
    {
        return "
        <connected-merchant-paypal-status-changed>
          <merchant-public-id>{$id}</merchant-public-id>
          <action>link</action>
          <oauth-application-client-id>oauth_application_client_id</oauth-application-client-id>
        </connected-merchant-paypal-status-changed>
        ";
    }

    private static function _grantedPaymentInstrumentUpdateSampleXml()
    {
        return "
		<granted-payment-instrument-update>
		  <grant-owner-merchant-id>vczo7jqrpwrsi2px</grant-owner-merchant-id>
		  <grant-recipient-merchant-id>cf0i8wgarszuy6hc</grant-recipient-merchant-id>
		  <payment-method-nonce>
			<nonce>ee257d98-de40-47e8-96b3-a6954ea7a9a4</nonce>
			<consumed type='boolean'>false</consumed>
			<locked type='boolean'>false</locked>
		  </payment-method-nonce>
		  <token>abc123z</token>
		  <updated-fields type='array'>
			<item>expiration-month</item>
			<item>expiration-year</item>
		  </updated-fields>
		</granted-payment-instrument-update>
        ";
    }

    private static function _paymentMethodRevokedByCustomerSampleXml($id)
    {
        return "
        <paypal-account>
            <billing-agreement-id>a-billing-agreement-id</billing-agreement-id>
            <created-at type='datetime'>2019-01-01T12:00:00Z</created-at>
            <customer-id>a-customer-id</customer-id>
            <default type='boolean'>true</default>
            <email>name@email.com</email>
            <global-id>cGF5bWVudG1ldGhvZF9jaDZieXNz</global-id>
            <image-url>https://assets.braintreegateway.com/payment_method_logo/paypal.png?environment=test</image-url>
            <subscriptions type='array'/>
            <token>{$id}</token>
            <updated-at type='datetime'>2019-01-02T12:00:00Z</updated-at>
            <is-channel-initiated nil='true'/>
            <payer-id>a-payer-id</payer-id>
            <payer-info nil='true'/>
            <limited-use-order-id nil='true'/>
            <revoked-at type='datetime'>2019-01-02T12:00:00Z</revoked-at>
        </paypal-account>
        ";
    }

    private static function _localPaymentCompletedSampleXml($id)
    {
        if ($id == "blik_one_click_id") {
            return self::_blikOneClickLocalPaymentCompletedSampleXml();
        } else {
            return self::_defaultLocalPaymentCompletedSampleXml();
        }
    }
    private static function _blikOneClickLocalPaymentCompletedSampleXml()
    {
        return "
		<local-payment>
            <bic>a-bic</bic>
            <blik-aliases type='array'>
                <blik-alias>
                    <key>unique-key-1</key>
                    <label>unique-label-1</label>
                </blik-alias>
            </blik-aliases>
            <iban-last-chars>1234</iban-last-chars>
            <payer-id>a-payer-id</payer-id>
            <payer-name>a-payer-name</payer-name>
            <payment-id>a-payment-id</payment-id>
            <payment-method-nonce>ee257d98-de40-47e8-96b3-a6954ea7a9a4</payment-method-nonce>
            <transaction>
                <id>1</id>
                <status>authorizing</status>
                <amount>10.00</amount>
                <order-id>order1234</order-id>
            </transaction>
		</local-payment>
        ";
    }

    private static function _defaultLocalPaymentCompletedSampleXml()
    {
        return "
		<local-payment>
            <bic>a-bic</bic>
            <iban-last-chars>1234</iban-last-chars>
            <payer-id>a-payer-id</payer-id>
            <payer-name>a-payer-name</payer-name>
            <payment-id>a-payment-id</payment-id>
            <payment-method-nonce>ee257d98-de40-47e8-96b3-a6954ea7a9a4</payment-method-nonce>
            <transaction>
                <id>1</id>
                <status>authorizing</status>
                <amount>10.00</amount>
                <order-id>order1234</order-id>
            </transaction>
		</local-payment>
        ";
    }

    private static function _localPaymentExpiredSampleXml()
    {
        return "
        <local-payment-expired>
            <payment-id>a-payment-id</payment-id>
            <payment-context-id>a-payment-context-id</payment-context-id>
        </local-payment-expired>
        ";
    }

    private static function _localPaymentFundedSampleXml()
    {
        return "
        <local-payment-funded>
            <payment-id>a-payment-id</payment-id>
            <payment-context-id>a-payment-context-id</payment-context-id>
            <transaction>
                <id>1</id>
                <status>settled</status>
                <amount>10.00</amount>
                <order-id>order1234</order-id>
            </transaction>
        </local-payment-funded>
        ";
    }

    private static function _localPaymentReversedSampleXml()
    {
        return "
		<local-payment-reversed>
            <payment-id>a-payment-id</payment-id>
		</local-payment-reversed>
        ";
    }

    private static function _paymentMethodCustomerDataUpdatedSampleXml($id)
    {
        $venmoAccountXml = self::_venmoAccountXml($id);
        return "
        <payment-method-customer-data-updated-metadata>
          <token>TOKEN-12345</token>
          <payment-method>
            {$venmoAccountXml}
          </payment-method>
          <datetime-updated type='dateTime'>2022-01-01T21:28:37Z</datetime-updated>
          <enriched-customer-data>
            <fields-updated type='array'>
                <item>firstName</item>
            </fields-updated>
            <profile-data>
              <username>venmo_username</username>
              <first-name>John</first-name>
              <last-name>Doe</last-name>
              <phone-number>1231231234</phone-number>
              <email>john.doe@paypal.com</email>
              <billing-address>
                <street-address>billing-street-address</street-address>
                <extended-address>billing-extended-address</extended-address>
                <locality>billing-locality</locality>
                <region>billing-region</region>
                <postal-code>billing-code</postal-code>
              </billing-address>
              <shipping-address>
                <street-address>shipping-street-address</street-address>
                <extended-address>shipping-extended-address</extended-address>
                <locality>shipping-locality</locality>
                <region>shipping-region</region>
                <postal-code>shipping-code</postal-code>
              </shipping-address>
            </profile-data>
          </enriched-customer-data>
        </payment-method-customer-data-updated-metadata>
        ";
    }

    private static function _venmoAccountXml($id)
    {
        return "
        <venmo-account>
          <created-at type='dateTime'>2018-10-11T21:28:37Z</created-at>
          <updated-at type='dateTime'>2018-10-11T21:28:37Z</updated-at>
          <default type='boolean'>true</default>
          <image-url>https://assets.braintreegateway.com/payment_method_logo/venmo.png?environment=test</image-url>
          <token>{$id}</token>
          <source-description>Venmo Account: venmojoe</source-description>
          <username>venmojoe</username>
          <venmo-user-id>456</venmo-user-id>
          <subscriptions type='array'/>
          <customer-id>venmo_customer_id</customer-id>
          <global-id>cGF5bWVudG1ldGhvZF92ZW5tb2FjY291bnQ</global-id>
        </venmo-account>
        ";
    }

    private static function _timestamp()
    {
        $originalZone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $timestamp = date("Y-m-d\TH:i:s\Z", time());
        date_default_timezone_set($originalZone);

        return $timestamp;
    }
}
