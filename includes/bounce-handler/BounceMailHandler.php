<?php

declare(strict_types=1);

/**
 * Bounce Mail Handler (formerly known as BMH and PHPMailer-BMH)
 *
 * @copyright 2008-2009 Andry Prevost. All Rights Reserved.
 * @copyright 2011-2012 Anthon Pang.
 * @copyright 2015-2019 Lars Moelleken.
 * @license   GPL
 *
 * Rewritten to drop the ext-imap (UW-IMAP) dependency in favour of the
 * bundled BounceIMAP client (see BounceIMAP.php). Since this is now used
 * strictly read-only, all delete/move/expunge handling (disableDelete,
 * moveHard, moveSoft, moveUnprocessed, purgeUnprocessed, testMode,
 * globalDelete, mailboxExist, openLocal) has been dropped along with it.
 * If you need those again later, the ext-imap-based version in version
 * control history is the reference - don't try to bolt them onto this
 * class without adding STORE/EXPUNGE/COPY support to BounceIMAP first.
 */
namespace BounceMailHandler;

use function bmhBodyRules;
use function bmhDSNRules;

/**
 * BounceMailHandler class
 *
 * Checks an IMAP inbox for bounced emails and reports them through a
 * callback function, so you can match your own database records against
 * the bounce results (e.g. mark an address inactive).
 */
class BounceMailHandler
{
    const SECONDS_TIMEOUT = 6000;

    const VERBOSE_DEBUG = 3; // detailed report plus debug info

    const VERBOSE_QUIET = 0; // suppress output

    const VERBOSE_REPORT = 2; // detailed report

    const VERBOSE_SIMPLE = 1; // simple report

    /**
     * mail-server
     *
     * @var string
     */
    public $mailhost = 'localhost';

    /**
     * IMAP port, default is '143', other common choices are '993' (ssl)
     *
     * @var int
     */
    public $port = 143;

    /**
     * connection encryption: 'notls' (none), 'tls' (STARTTLS), or 'ssl' (implicit TLS)
     *
     * @var string
     */
    public $serviceOption = 'notls';

    /**
     * mailbox to check, default is 'INBOX'
     *
     * @var string
     */
    public $boxname = 'INBOX';

    /**
     * the username of mailbox
     *
     * @var string
     */
    public $mailboxUserName = '';

    /**
     * the password needed to access mailbox
     *
     * @var string
     */
    public $mailboxPassword = '';

    /**
     * the last error msg
     *
     * @var string
     */
    public $errorMessage = '';

    /**
     * maximum limit messages processed in one batch
     *
     * @var int
     */
    public $maxMessages = 3000;

    /**
     * callback Action function name, the function that handles the bounce mail. Parameters:
     *
     * int     $msgnum          the message number returned by Bounce Mail Handler
     * string  $bounce_type     the bounce type:
     *       'antispam',
     *       'autoreply',
     *       'concurrent',
     *       'content_reject',
     *       'command_reject',
     *       'internal_error',
     *       'defer',
     *       'delayed',
     *       'dns_loop',
     *       'dns_unknown',
     *       'full',
     *       'inactive',
     *       'latin_only',
     *       'other',
     *       'oversize',
     *       'outofoffice',
     *       'unknown',
     *       'unrecognized',
     *       'user_reject',
     *       'warning'
     * string  $email           the target email address
     * string  $subject         the subject
     * mixed   $xheader         on matched rules: the value of the $requiredXHeader header if configured, false otherwise; on unrecognized: the full raw header block
     * int     $remove          the rule's own remove flag (informational only - nothing is actually deleted)
     * string  $rule_reason     descriptive reason for the matched rule, empty string if unrecognized
     * string  $rule_cat        bounce mail detect rule category
     * int     $totalFetched    total number of messages in the mailbox
     * string  $body            the message body used for rule matching
     * string  $headerFull      the full email header
     * string  $bodyFull        the full email text body (excludes headers)
     * string  $status_code     DSN status code (if available)
     * string  $action          DSN action (if available)
     * string  $diagnostic_code DSN diagnostic code (if available)
     *
     * @var mixed
     */
    public $actionFunction = 'callbackAction';

    /**
     * Indicates wether the callback function is called on all messages or just the bounces, default false (just bounces)
     */
    public $actionFunctionOnAllMessages = false;

    /**
     * Callback custom body rules
     * ```
     * function customBodyRulesCallback( $result, $body, $debug )
     * {
     *    return $result;
     * }
     * ```
     *
     * @var callable|null
     */
    public $customBodyRulesCallback;

    /**
     * Callback custom DSN (Delivery Status Notification) rules
     * ```
     * function customDSNRulesCallback( $result, $dsnMsg, $dsnReport, $debug )
     * {
     *    return $result;
     * }
     * ```
     *
     * @var callable|null
     */
    public $customDSNRulesCallback;

    /**
     * control the debug output, default is VERBOSE_SIMPLE
     *
     * @var int
     */
    public $verbose = self::VERBOSE_SIMPLE;

    /**
     * control the failed DSN rules output
     *
     * @var bool
     */
    public $debugDsnRule = false;

    /**
     * control the failed BODY rules output
     *
     * @var bool
     */
    public $debugBodyRule = false;

    /**
     * If set, a message is only processed (DSN or BODY rules) when this
     * header is present - either on the bounce message itself, or (for
     * standard DSN messages only) on the original message embedded as MIME
     * part 3, per RFC 3462/3798. Messages without it are treated as
     * unprocessed.
     *
     * Leave empty (default) to disable and process every candidate bounce.
     *
     * @var string
     */
    public $requiredXHeader = '';

    /**
     * If set, only messages received on or after this date are processed
     * at all - resolved through an IMAP SINCE search, so older messages
     * are never fetched in the first place.
     *
     * NOTE: IMAP's SINCE only has day granularity (no time-of-day), so
     * messages from the same calendar day as this timestamp will still be
     * fetched. Combine with a precise Date-header check in your callback
     * if you need exact time filtering.
     *
     * Leave null (default) to disable and process every message.
     *
     * @var int|null unix timestamp
     */
    public $sinceDate;

    /**
     * (internal) the connected IMAP client
     *
     * @var BounceIMAP|null
     */
    protected $client;

    /**
     * Holds Bounce Mail Handler version.
     *
     * @var string
     */
    private $version = '7.0-dev (imap-extension-free)';

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * output additional msg for debug
     *
     * @param mixed $msg          if not given, output the last error msg
     * @param int   $verboseLevel the output level of this message
     */
    public function output($msg = '', int $verboseLevel = self::VERBOSE_SIMPLE)
    {
        if ($this->verbose >= $verboseLevel) {
            echo ($msg !== '' ? $msg : $this->errorMessage) . "\n";
        }
    }

    /**
     * Connects to and logs into the configured IMAP mailbox.
     *
     * @return bool
     */
    public function openMailbox(): bool
    {
        set_time_limit(self::SECONDS_TIMEOUT);

        // 'notls' means no encryption; anything else is passed straight
        // through to BounceIMAP::connect() ('ssl' or 'tls').
        $encryption = $this->serviceOption === 'notls' ? '' : $this->serviceOption;

        $this->client = new BounceIMAP();

        if (!$this->client->connect($this->mailhost, $this->port, $encryption)) {
            $this->errorMessage = 'Cannot create IMAP connection to ' . $this->mailhost . "\n" . 'Error MSG: ' . $this->client->getLastError();
            $this->output();

            return false;
        }

        if (!$this->client->login($this->mailboxUserName, $this->mailboxPassword)) {
            $this->errorMessage = 'Login failed for ' . $this->mailboxUserName . "\n" . 'Error MSG: ' . $this->client->getLastError();
            $this->output();

            return false;
        }

        $this->output('Connected to: ' . $this->mailhost . ' (' . $this->mailboxUserName . ')');

        return true;
    }

    /**
     * Extracts the (decoded) Subject header from a raw header block.
     */
    private function extractSubject(string $headerFull): string
    {
        // Could be multi-line, if the new line begins with SPACE or HTAB
        if (!preg_match("/^Subject:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/mi", $headerFull, $match)) {
            return '[NO SUBJECT]';
        }

        $subject = trim(preg_replace('/\s+/', ' ', $match[1]));

        return function_exists('iconv_mime_decode')
            ? iconv_mime_decode($subject, 0, 'UTF-8')
            : $subject;
    }

    /**
     * Looks up the value of the configured header in the passed text
     *
     * @return string|false the header value, or false if not present
     */
    private function findRequiredXHeaderValue(string $headerFull)
    {
        $needle = '/^' . preg_quote($this->requiredXHeader, '/') . ':(.*)$/mi';

        if (preg_match($needle, $headerFull, $match)) {
            return trim($match[1]);
        }
        return false;
    }

    /**
     * Decodes $body according to the Content-Transfer-Encoding found in
     * $mimeHeader (part-level MIME header), falling back to $fallbackHeader
     * (the top-level message header) when the part has no MIME header of
     * its own - which is the case for genuinely single-part messages.
     */
    private function decodeBySection(string $body, ?string $mimeHeader, string $fallbackHeader): string
    {
        $source = ($mimeHeader !== null && $mimeHeader !== '') ? $mimeHeader : $fallbackHeader;

        if (!preg_match('/^Content-Transfer-Encoding:\s*(\S+)/mi', $source, $match)) {
            return $body;
        }

        switch (strtolower(trim($match[1]))) {
            case 'quoted-printable':
                return quoted_printable_decode($body);
            case 'base64':
                $decoded = base64_decode($body, true);
                return $decoded !== false ? $decoded : $body;
            default:
                return $body;
        }
    }

    /**
     * Function to process each individual message.
     *
     * @param int    $pos          message number
     * @param string $type         'DSN' or 'BODY'
     * @param string $headerFull   the message's full raw header block
     * @param int    $totalFetched total number of messages in mailbox
     *
     * @return array|false $result-array or false
     */
    public function processBounce(int $pos, string $type, string $headerFull, int $totalFetched)
    {
        $subject = $this->extractSubject($headerFull);

        $requiredXHeaderValue = false;

        $bodyFull = null;
        if ($this->requiredXHeader !== '') {
            $requiredXHeaderValue = $this->findRequiredXHeaderValue($headerFull);

            if ($requiredXHeaderValue === false) {
                $bodyFull = $this->client->fetchSection($pos, 'TEXT') ?? '';
                $requiredXHeaderValue = $this->findRequiredXHeaderValue($bodyFull);
                if ($requiredXHeaderValue === false) {
                    $this->output('Msg #' . $pos . ' skipped: missing required header "' . $this->requiredXHeader . '"', self::VERBOSE_REPORT);
                    return false;
                }
            }
        }

        if ($bodyFull === null) {
            $bodyFull = $this->client->fetchSection($pos, 'TEXT') ?? '';
        }
        $body = '';

        if ($type === 'DSN') {
            // first part of DSN (Delivery Status Notification), human-readable explanation
            $dsnMsg = $this->client->fetchSection($pos, '1') ?? '';
            $dsnMsg = $this->decodeBySection($dsnMsg, $this->client->fetchSection($pos, '1.MIME'), $headerFull);

            // second part of DSN (Delivery Status Notification), delivery-status
            $dsnReport = $this->client->fetchSection($pos, '2') ?? '';

            $result = bmhDSNRules($dsnMsg, $dsnReport, $this->debugDsnRule);
            $result = is_callable($this->customDSNRulesCallback) ? call_user_func($this->customDSNRulesCallback, $result, $dsnMsg, $dsnReport, $this->debugDsnRule) : $result;
        } elseif ($type === 'BODY') {
            if (preg_match("/^Content-Type:\s*multipart\//mi", $headerFull)) {
                $body = $this->client->fetchSection($pos, '1') ?? $bodyFull;
                $body = $this->decodeBySection($body, $this->client->fetchSection($pos, '1.MIME'), $headerFull);
            } else {
                // section '1' of a non-multipart message is byte-identical to
                // TEXT, and it has no separate '1.MIME' sub-header - we already
                // have everything we need in $bodyFull, no extra round trips.
                $body = $this->decodeBySection($bodyFull, null, $headerFull);
            }

            $result = bmhBodyRules($body, $this->debugBodyRule);
            $result = is_callable($this->customBodyRulesCallback) ? call_user_func($this->customBodyRulesCallback, $result, $body, $this->debugBodyRule) : $result;
        } else {
            $this->errorMessage = 'Internal Error: unknown type';
            return false;
        }

        $email = $result['email'];
        $bounceType = $result['bounce_type'];

        // workaround: I think there is a error in one of the reg-ex in "bmh_rules.php".
        if ($email && strpos($email, 'TO:<') !== false) {
            $email = str_replace('TO:<', '', $email);
        }

        $remove = $result['remove'];
        $ruleReason = $result['rule_reason'];
        $ruleCategory = $result['rule_cat'];
        $status_code = $result['status_code'];
        $action = $result['action'];
        $diagnostic_code = $result['diagnostic_code'];
        $xheader = $requiredXHeaderValue;

        if ($ruleReason === '') {
            // unrecognized
            if (trim($email) === '' && preg_match('/^From:.*<(.+?)>/mi', $headerFull, $m)) {
                $email = $m[1];
            } elseif (trim($email) === '' && preg_match('/^From:\s*(\S+@\S+)/mi', $headerFull, $m)) {
                $email = trim($m[1]);
            }

            $this->output('No match: ' . $ruleCategory . '; ' . $bounceType . '; ' . $email, self::VERBOSE_REPORT);

            if ($this->actionFunctionOnAllMessages) {
                $params = [
                    $pos,
                    $bounceType,
                    $email,
                    $subject,
                    $headerFull,
                    $remove,
                    $ruleReason,
                    $ruleCategory,
                    $totalFetched,
                    $body,
                    $headerFull,
                    $bodyFull,
                    $status_code,
                    $action,
                    $diagnostic_code,
                ];
                call_user_func_array($this->actionFunction, $params);
            }

            return false;
        }

        // match rule, do bounce action
        $params = [
            $pos,
            $bounceType,
            $email,
            $subject,
            $xheader,
            $remove,
            $ruleReason,
            $ruleCategory,
            $totalFetched,
            $body,
            $headerFull,
            $bodyFull,
            $status_code,
            $action,
            $diagnostic_code,
        ];
        call_user_func_array($this->actionFunction, $params);

        return $result;
    }

    /**
     * process the messages in a mailbox
     *
     * @param bool|int $max maximum limit messages processed in one batch,
     *                      if not given uses the property $maxMessages
     *
     * @return bool
     */
    public function processMailbox($max = false): bool
    {
        if (empty($this->actionFunction) || !is_callable($this->actionFunction)) {
            $this->errorMessage = 'Action function not found!';
            $this->output();

            if ($this->client !== null) {
                $this->client->logout();
            }

            return false;
        }

        if (!empty($max)) {
            $this->maxMessages = $max;
        }

        $totalCount = $this->client->openMailboxReadOnly($this->boxname);

        if ($totalCount === false) {
            $this->errorMessage = 'Could not open mailbox "' . $this->boxname . '": ' . $this->client->getLastError();
            $this->output();

            return false;
        }

        $this->output('Total: ' . $totalCount . ' messages ');

        if ($this->sinceDate !== null) {
            $criteria = 'SINCE "' . date('d-M-Y', $this->sinceDate) . '"';
            $messageNumbers = $this->client->search([$criteria]);
            sort($messageNumbers);
            $this->output('Matching ' . $criteria . ': ' . count($messageNumbers) . ' messages ');
        } else {
            $messageNumbers = $totalCount > 0 ? range(1, $totalCount) : [];
        }

        $fetchedCount = count($messageNumbers);
        $processedCount = 0;
        $unprocessedCount = 0;

        // process maximum number of messages
        if ($fetchedCount > $this->maxMessages) {
            $messageNumbers = array_slice($messageNumbers, 0, $this->maxMessages);
            $fetchedCount = $this->maxMessages;
            $this->output('Processing first ' . $fetchedCount . ' messages ');
        }

        $this->output('Running read-only: nothing will be deleted, moved, or marked as read');

        foreach ($messageNumbers as $x) {
            $headerFull = $this->client->fetchHeader($x) ?? '';

            $type = 'BODY';

            // Could be multi-line, if the new line begins with SPACE or HTAB
            if ($headerFull !== '' && preg_match("/^Content-Type:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/mi", $headerFull, $match)) {
                if (preg_match('/multipart\/report/i', $match[1]) && preg_match('/report-type=["\']?delivery-status["\']?/i', $match[1])) {
                    $type = 'DSN';
                } else {
                    $this->output('Msg #' . $x . ' is not a standard DSN message', self::VERBOSE_REPORT);

                    if ($this->debugBodyRule) {
                        $this->output("  Content-Type : {$match[1]}", self::VERBOSE_DEBUG);
                    }
                }
            } else {
                $this->output('Msg #' . $x . ' is not a well-formatted MIME mail, missing Content-Type', self::VERBOSE_REPORT);

                if ($this->debugBodyRule) {
                    $this->output('  Headers: ' . "\n" . $headerFull . "\n", self::VERBOSE_DEBUG);
                }
            }

            $processedResult = $this->processBounce($x, $type, $headerFull, $totalCount);

            if ($processedResult !== false) {
                $this->output("Processed #$x");
                ++$processedCount;
            } else {
                $this->output("Ignored #$x, not a bounce");
                ++$unprocessedCount;
            }

            if (php_sapi_name() == 'cli') {
                flush();
            }
        }

        $this->output("\n" . 'Closing mailbox');
        $this->client->logout();

        $this->output('Read: ' . $fetchedCount . ' messages');
        $this->output($processedCount . ' action taken');
        $this->output($unprocessedCount . ' no action taken');

        return true;
    }
}
