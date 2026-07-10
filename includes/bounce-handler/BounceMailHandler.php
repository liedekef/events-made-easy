<?php

declare(strict_types=1);

/**
 * Bounce Mail Handler (formerly known as BMH and PHPMailer-BMH)
 *
 * @copyright 2008-2009 Andry Prevost. All Rights Reserved.
 * @copyright 2011-2012 Anthon Pang.
 * @copyright 2015-2019 Lars Moelleken.
 * @license   GPL
 */
namespace BounceMailHandler;

use function bmhBodyRules;
use function bmhDSNRules;
use const CL_EXPUNGE;
use const OP_HALFOPEN;
use const OP_READONLY;
use const SORTDATE;

/**
 * BounceMailHandler class
 *
 * BounceMailHandler is a PHP program to check your IMAP/POP3 inbox and
 * delete all 'hard' bounced emails. It features a callback function where
 * you can create a custom action. This provides you the ability to write
 * a script to match your database records and either set inactive or
 * delete records with email addresses that match the 'hard' bounce results.
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
     * callback Action function name the function that handles the bounce mail. Parameters:
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
     *       'delayed'
     *       =>
     *       array(
     *           'remove' => 0,
     *           'bounce_type' => 'temporary'
     *       ),
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
     * string  $subject         the subject, ignore now
     * mixed   $xheader         on matched rules: the value of the $requiredXHeader header if configured, false otherwise; on unrecognized: the imap header object
     * 1 or 0  $remove          delete status, 0 is not deleted, 1 is deleted
     * string  $rule_reason     descriptive reason for the matched rule, empty string if unrecognized
     * string  $rule_cat        bounce mail detect rule category
     * int     $totalFetched    total number of messages in the mailbox
     * string  $body            the message body
     * string  $headerFull      the full email header
     * string  $bodyFull        the full email body (may include attachments)
     * string  $status_code     DSN status code (if available)
     * string  $action          DSN action (if available)
     * string  $diagnostic_code DSN diagnostic code (if available)
     *
     * @var mixed
     */
    public $actionFunction = 'callbackAction';

    /**
     * Callback custom body rules
     * ```
     * function customBodyRulesCallback( $result, $body, $structure, $debug )
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
     * test-mode, if true will not delete messages
     *
     * @var bool
     */
    public $testMode = false;

    /**
     * purge the unknown messages (or not)
     *
     * @var bool
     */
    public $purgeUnprocessed = false;

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
     * header is present - either on the bounce message itself, or on the
     * original message embedded in it (e.g. a custom tracking header such
     * as 'X-EME-mailid'). Messages without it are treated as unprocessed.
     *
     * This check is cheap: it looks at the header block already fetched
     * for every message, and at most does one small, targeted IMAP header
     * fetch on the embedded message/rfc822 part - it never fetches or
     * scans the full message body.
     *
     * Leave empty (default) to disable and process every candidate bounce,
     * matching the previous behaviour.
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
     * Leave null (default) to disable and process every message, matching
     * the previous behaviour.
     *
     * @var int|null unix timestamp
     */
    public $sinceDate;

    /**
     * Control the method to process the mail header
     * if set true, uses the imap_fetchstructure function
     * otherwise, detect message type directly from headers,
     * a bit faster than imap_fetchstructure function and take less resources.
     *
     * however - the difference is negligible
     *
     * @var bool
     */
    public $useFetchstructure = true;

    /**
     * If disableDelete is equal to true, it will disable the delete function.
     *
     * @var bool
     */
    public $disableDelete = false;

    /**
     * defines new line ending
     *
     * @var string
     */
    public $bmhNewLine = "\n";

    /**
     * defines port number, default is '143', other common choices are '110' (pop3), '993' (ssl)
     *
     * @var int
     */
    public $port = 143;

    /**
     * defines service, default is 'imap', choice includes 'pop3'
     *
     * @var string
     */
    public $service = 'imap';

    /**
     * defines service option, default is 'notls', other choices are 'tls', 'ssl'
     *
     * @var string
     */
    public $serviceOption = 'notls';

    /**
     * mailbox type, default is 'INBOX', other choices are (Tasks, Spam, Replies, etc.)
     *
     * @var string
     */
    public $boxname = 'INBOX';

    /**
     * determines if soft bounces will be moved to another mailbox folder
     *
     * @var bool
     */
    public $moveSoft = false;

    /**
     * mailbox folder to move soft bounces to, default is 'soft'
     *
     * @var string
     */
    public $softMailbox = 'INBOX.soft';

    /**
     * determines if hard bounces will be moved to another mailbox folder
     *
     * NOTE: If true, this will disable delete and perform a move operation instead
     *
     * @var bool
     */
    public $moveHard = false;

    /**
     * mailbox folder to move hard bounces to, default is 'hard'
     *
     * @var string
     */
    public $hardMailbox = 'INBOX.hard';

    /**
     * determines if unprocessed bounces will be moved to another mailbox folder
     *
     * @var bool
     */
    public $moveUnprocessed = true;

    /*
     * Mailbox folder to move unprocessed mails
     * @var string
     */
    public $unprocessedBox = 'INBOX.unprocessed';

    /**
     * deletes messages globally prior to date in variable
     *
     * NOTE: excludes any message folder that includes 'sent' in mailbox name
     * format is same as MySQL: 'yyyy-mm-dd'
     * if variable is blank, will not process global delete
     *
     * @var string
     */
    public $deleteMsgDate = '';

    /**
     * (internal variable)
     *
     * The resource handler for the opened mailbox (POP3/IMAP/NNTP/etc.)
     *
     * @var resource|\IMAP\Connection|false
     */
    protected $mailboxLink = false;

    /**
     * Holds Bounce Mail Handler version.
     *
     * @var string
     */
    private $version = '6.0-dev';

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Function to delete messages in a mailbox, based on date
     *
     * NOTE: this is global ... will affect all mailboxes except any that have 'sent' in the mailbox name
     */
    public function globalDelete(): bool
    {
        $dateArr = \explode('-', $this->deleteMsgDate); // date format is yyyy-mm-dd
        $delDate = \mktime(0, 0, 0, (int) ($dateArr[1]), (int) ($dateArr[2]), (int) ($dateArr[0]));

        $port = $this->port . '/' . $this->service . '/' . $this->serviceOption;
        $mboxt = \imap_open('{' . $this->mailhost . ':' . $port . '}', $this->mailboxUserName, $this->mailboxPassword, OP_HALFOPEN);

        if ($mboxt === false) {
            return false;
        }

        $list = \imap_getmailboxes($mboxt, '{' . $this->mailhost . ':' . $port . '}', '*');

        if (\is_array($list)) {
            foreach ($list as $key => $val) {
                // get the mailbox name only
                $nameArr = \explode('}', \imap_utf7_decode($val->name));
                $nameRaw = $nameArr[\count($nameArr) - 1];

                if (\stripos($nameRaw, 'sent') === false) {
                    $mboxd = \imap_open('{' . $this->mailhost . ':' . $port . '}' . $nameRaw, $this->mailboxUserName, $this->mailboxPassword, CL_EXPUNGE);
                    $messages = \imap_sort($mboxd, SORTDATE, 0);

                    foreach ($messages as $message) {
                        $header = \imap_headerinfo($mboxd, $message);

                        // purge if prior to global delete date
                        if ($header->udate < $delDate) {
                            \imap_delete($mboxd, $message);
                        }
                    }

                    \imap_expunge($mboxd);
                    /** @noinspection UnusedFunctionResultInspection */
                    \imap_errors();
                    /** @noinspection UnusedFunctionResultInspection */
                    \imap_alerts();
                    \imap_close($mboxd);
                }
            }

            /** @noinspection UnusedFunctionResultInspection */
            \imap_errors();
            /** @noinspection UnusedFunctionResultInspection */
            \imap_alerts();
            \imap_close($mboxt);

            return true;
        }

        /** @noinspection UnusedFunctionResultInspection */
        \imap_errors();
        /** @noinspection UnusedFunctionResultInspection */
        \imap_alerts();
        \imap_close($mboxt);

        return false;
    }

    /**
     * Function to determine if a particular value is found in a imap_fetchstructure key.
     *
     * @param array  $currParameters imap_fetstructure parameters
     * @param string $varKey         imap_fetstructure key
     * @param string $varValue       value to check for
     *
     * @return bool
     */
    public function isParameter(array $currParameters, string $varKey, string $varValue): bool
    {
        foreach ($currParameters as $object) {
            if (
                \strtoupper($object->attribute) == \strtoupper($varKey)
                &&
                \strtoupper($object->value) == \strtoupper($varValue)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively walks a imap_fetchstructure() parts tree looking for an
     * embedded message/rfc822 part (e.g. the original message attached to
     * a bounce or NDR), and returns its IMAP body section identifier
     * (e.g. '3', or '1.2' if nested inside another multipart).
     *
     * @param object[] $parts  the ->parts array from a structure object
     * @param string   $prefix section prefix used while recursing
     *
     * @return string|null the section identifier, or null if not found
     */
    private function findMessagePartSection(array $parts, string $prefix = ''): ?string
    {
        foreach ($parts as $index => $part) {
            $section = $prefix === '' ? (string) ($index + 1) : $prefix . '.' . ($index + 1);

            if ($part->type == TYPEMESSAGE && isset($part->subtype) && \strtoupper($part->subtype) === 'RFC822') {
                return $section;
            }

            if ($part->type == TYPEMULTIPART && !empty($part->parts)) { // recurse
                $found = $this->findMessagePartSection($part->parts, $section);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Cheap pre-check used by $requiredXHeader: looks up the value of the
     * configured header, either on the bounce message itself or on the
     * original message embedded in it, and returns it so it can be handed
     * to the callback (see $xheader in $actionFunction).
     *
     * This does NOT fetch or scan the full message body. It looks at the
     * header block already fetched for every message and, if needed, does
     * at most one small, targeted IMAP fetch for just that header on the
     * embedded message/rfc822 part.
     *
     * @param int    $pos        message number
     * @param string $headerFull the full header block of the bounce message
     *
     * @return string|false the header value, or false if not present anywhere
     */
    private function findRequiredXHeaderValue(int $pos, string $headerFull)
    {
        $needle = '/^' . \preg_quote($this->requiredXHeader, '/') . ':(.*)$/mi';

        if (\preg_match($needle, $headerFull, $match)) {
            return \trim($match[1]);
        }

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $structure = @\imap_fetchstructure($this->mailboxLink, $pos);

        if (!\is_object($structure) || empty($structure->parts)) {
            return false;
        }

        $section = $this->findMessagePartSection($structure->parts);

        if ($section === null) {
            return false;
        }

        $embeddedHeader = \imap_fetchbody($this->mailboxLink, $pos, $section);

        if (\preg_match($needle, $embeddedHeader, $match)) {
            return \trim($match[1]);
        }

        return false;
    }

    /**
     * Function to check if a mailbox exists - if not found, it will create it.
     *
     * @param string $mailbox the mailbox name, must be in 'INBOX.checkmailbox' format
     * @param bool   $create  whether or not to create the checkmailbox if not found, defaults to true
     *
     * @return bool
     */
    public function mailboxExist(string $mailbox, bool $create = true): bool
    {
        if (\trim($mailbox) === '') {
            // this is a critical error with either the mailbox name blank or an invalid mailbox name
            $this->errorMessage = 'Invalid mailbox name for move operation. Cannot continue: ' . $mailbox;
            $this->output();
            exit();
        }

        $port = $this->port . '/' . $this->service . '/' . $this->serviceOption;
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $mbox = @\imap_open('{' . $this->mailhost . ':' . $port . '}', $this->mailboxUserName, $this->mailboxPassword, OP_HALFOPEN);

        if ($mbox === false) {
            return false;
        }

        $list = \imap_getmailboxes($mbox, '{' . $this->mailhost . ':' . $port . '}', '*');
        $mailboxFound = false;

        if (\is_array($list)) {
            foreach ($list as $key => $val) {
                // get the mailbox name only
                $nameArr = \explode('}', \imap_utf7_decode($val->name));
                $nameRaw = $nameArr[\count($nameArr) - 1];
                if ($mailbox == $nameRaw) {
                    $mailboxFound = true;
                }
            }

            if ($mailboxFound === false && $create) {
                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                @\imap_createmailbox($mbox, \imap_utf7_encode('{' . $this->mailhost . ':' . $port . '}' . $mailbox));
                /** @noinspection UnusedFunctionResultInspection */
                \imap_errors();
                /** @noinspection UnusedFunctionResultInspection */
                \imap_alerts();
                \imap_close($mbox);

                return true;
            }

            /** @noinspection UnusedFunctionResultInspection */
            \imap_errors();
            /** @noinspection UnusedFunctionResultInspection */
            \imap_alerts();
            \imap_close($mbox);

            return false;
        }

        /** @noinspection UnusedFunctionResultInspection */
        \imap_errors();
        /** @noinspection UnusedFunctionResultInspection */
        \imap_alerts();
        \imap_close($mbox);

        return false;
    }

    /**
     * open a mail box in local file system
     *
     * @param string $filePath The local mailbox file path
     *
     * @return bool
     */
    public function openLocal(string $filePath): bool
    {
        \set_time_limit(self::SECONDS_TIMEOUT);

        if (!$this->testMode) {
            $this->mailboxLink = \imap_open($filePath, '', '', CL_EXPUNGE);
        } else {
            $this->mailboxLink = \imap_open($filePath, '', '', OP_READONLY);
        }

        if (!$this->mailboxLink) {
            $this->errorMessage = 'Cannot open the mailbox file to ' . $filePath . $this->bmhNewLine . 'Error MSG: ' . \imap_last_error();
            $this->output();

            return false;
        }

        $this->output('Opened ' . $filePath);

        return true;
    }

    /**
     * open a mail box
     *
     * @return bool
     */
    public function openMailbox(): bool
    {
        // before starting the processing, let's check the delete flag and do global deletes if true
        if (\trim($this->deleteMsgDate) !== '') {
            $this->output('processing global delete based on date of ' . $this->deleteMsgDate);
            $this->globalDelete();
        }

        // disable move operations if server is Gmail ... Gmail does not support mailbox creation
        if (\stripos($this->mailhost, 'gmail') !== false) {
            $this->moveSoft = false;
            $this->moveHard = false;
        }

        $port = $this->port . '/' . $this->service . '/' . $this->serviceOption;

        \set_time_limit(self::SECONDS_TIMEOUT);

        if (!$this->testMode) {
            $this->mailboxLink = \imap_open('{' . $this->mailhost . ':' . $port . '}' . $this->boxname, $this->mailboxUserName, $this->mailboxPassword, CL_EXPUNGE);
        } else {
            $this->mailboxLink = \imap_open('{' . $this->mailhost . ':' . $port . '}' . $this->boxname, $this->mailboxUserName, $this->mailboxPassword, OP_READONLY);
        }

        if (!$this->mailboxLink) {
            $this->errorMessage = 'Cannot create ' . $this->service . ' connection to ' . $this->mailhost . $this->bmhNewLine . 'Error MSG: ' . \imap_last_error();
            $this->output();

            return false;
        }

        $this->output('Connected to: ' . $this->mailhost . ' (' . $this->mailboxUserName . ')');

        return true;
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
            if ($msg) {
                echo $msg . $this->bmhNewLine;
            } else {
                echo $this->errorMessage . $this->bmhNewLine;
            }
        }
    }

    /**
     * Function to process each individual message.
     *
     * @param int    $pos          message number
     * @param string $type         DNS or BODY type
     * @param int    $totalFetched total number of messages in mailbox
     *
     * @return array|false $result-array or false
     */
    public function processBounce(int $pos, string $type, int $totalFetched)
    {
        $header = \imap_headerinfo($this->mailboxLink, $pos);
        $subject = isset($header->subject) ? \strip_tags($header->subject) : '[NO SUBJECT]';
        $body = '';
        $headerFull = \imap_fetchheader($this->mailboxLink, $pos);

        $requiredXHeaderValue = false;

        if (!empty($this->requiredXHeader)) {
            $requiredXHeaderValue = $this->findRequiredXHeaderValue($pos, $headerFull);

            if ($requiredXHeaderValue === false) {
                $this->output('Msg #' . $pos . ' skipped: missing required header "' . $this->requiredXHeader . '"', self::VERBOSE_REPORT);
                return false;
            }
        }

        $bodyFull = \imap_body($this->mailboxLink, $pos);

        if ($type == 'DSN') {
            // first part of DSN (Delivery Status Notification), human-readable explanation
            $dsnMsg = \imap_fetchbody($this->mailboxLink, $pos, '1');
            $dsnMsgStructure = \imap_bodystruct($this->mailboxLink, $pos, '1');

            if ($dsnMsgStructure->encoding == 4) {
                $dsnMsg = \quoted_printable_decode($dsnMsg);
            } elseif ($dsnMsgStructure->encoding == 3) {
                $dsnMsg = \base64_decode($dsnMsg, true);
            }

            // second part of DSN (Delivery Status Notification), delivery-status
            $dsnReport = \imap_fetchbody($this->mailboxLink, $pos, '2');

            // process bounces by rules
            $result = bmhDSNRules($dsnMsg, $dsnReport, $this->debugDsnRule);
            $result = \is_callable($this->customDSNRulesCallback) ? \call_user_func($this->customDSNRulesCallback, $result, $dsnMsg, $dsnReport, $this->debugDsnRule) : $result;
        } elseif ($type == 'BODY') {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            $structure = @\imap_fetchstructure($this->mailboxLink, $pos);

            if (!\is_object($structure)) {
                return false;
            }

            switch ($structure->type) {
                case TYPETEXT:
                    $body = \imap_fetchbody($this->mailboxLink, $pos, '1');
                    $result = bmhBodyRules($body, $this->debugBodyRule);
                    $result = \is_callable($this->customBodyRulesCallback) ? \call_user_func($this->customBodyRulesCallback, $result, $body, $structure, $this->debugBodyRule) : $result;
                    break;

                case TYPEMULTIPART:
                    $body = \imap_fetchbody($this->mailboxLink, $pos, '1');

                    // Detect encoding and decode - only base64
                    if ($structure->parts[0]->encoding == 4) {
                        $body = \quoted_printable_decode($body);
                    } elseif ($structure->parts[0]->encoding == 3) {
                        $body = \base64_decode($body, true);
                    }

                    $result = bmhBodyRules($body, $this->debugBodyRule);
                    $result = \is_callable($this->customBodyRulesCallback) ? \call_user_func($this->customBodyRulesCallback, $result, $body, $structure, $this->debugBodyRule) : $result;
                    break;

                case TYPEMESSAGE:
                    $body = \imap_body($this->mailboxLink, $pos);

                    if ($structure->encoding == 4) {
                        $body = \quoted_printable_decode($body);
                    } elseif ($structure->encoding == 3) {
                        $body = \base64_decode($body, true);
                    }

                    $body = \substr($body, 0, 1000);
                    $result = bmhBodyRules($body, $this->debugBodyRule);
                    $result = \is_callable($this->customBodyRulesCallback) ? \call_user_func($this->customBodyRulesCallback, $result, $body, $structure, $this->debugBodyRule) : $result;
                    break;

                default: // unsupported Content-type
                    $this->output('Msg #' . $pos . ' is unsupported Content-Type:' . $structure->type, self::VERBOSE_REPORT);
                    return false;
            }
        } else {
            $this->errorMessage = 'Internal Error: unknown type';
            return false;
        }

        $email = $result['email'];
        $bounceType = $result['bounce_type'];

        // workaround: I think there is a error in one of the reg-ex in "phpmailer-bmh_rules.php".
        if ($email && \strpos($email, 'TO:<') !== false) {
            $email = \str_replace('TO:<', '', $email);
        }

        if ($this->moveHard && $result['bounce_type'] == 'hard') {
            $remove = 'moved (hard)';
        } elseif ($this->moveSoft && $result['bounce_type'] == 'soft') {
            $remove = 'moved (soft)';
        } elseif ($this->disableDelete) {
            $remove = 0;
        } else {
            $remove = $result['remove'];
        }

        $ruleReason = $result['rule_reason'];
        $ruleCategory = $result['rule_cat'];
        $status_code = $result['status_code'];
        $action = $result['action'];
        $diagnostic_code = $result['diagnostic_code'];
        $xheader = $requiredXHeaderValue;

        if ($ruleReason === '') {
            // unrecognized
            if (
                \trim($email) === ''
                &&
                \property_exists($header, 'fromaddress') === true
            ) {
                $email = $header->fromaddress;
            }

            if ($this->testMode) {
                $this->output('No match: ' . $ruleCategory . '; ' . $bounceType . '; ' . $email);
            } else {
                // code below will use the Callback function, but return no value
                $params = [
                    $pos,
                    $bounceType,
                    $email,
                    $subject,
                    $header,
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
                \call_user_func_array($this->actionFunction, $params);
            }
        } else {
            // match rule, do bounce action
            if ($this->testMode) {
                $this->output('Match: ' . $ruleReason . ':' . $ruleCategory . '; ' . $bounceType . '; ' . $email);

                return $result;
            }

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
            \call_user_func_array($this->actionFunction, $params);

            return $result;
        }

        return false;
    }

    /**
     * process the messages in a mailbox
     *
     * @param bool|int $max $max maximum limit messages processed in one batch,
     *                      if not given uses the property $maxMessages
     *
     * @return bool
     */
    public function processMailbox($max = false): bool
    {
        if (
            empty($this->actionFunction)
            ||
            !\is_callable($this->actionFunction)
        ) {
            $this->errorMessage = 'Action function not found!';
            $this->output();

            @\imap_close($this->mailboxLink);
            return false;
        }

        if ($this->moveHard && ($this->disableDelete === false)) {
            $this->disableDelete = true;
        }

        if (!empty($max)) {
            $this->maxMessages = $max;
        }

        // initialize counters
        $totalCount = \imap_num_msg($this->mailboxLink);
        $this->output('Total: ' . $totalCount . ' messages ');

        if ($this->sinceDate !== null) {
            $criteria = 'SINCE "' . \date('d-M-Y', $this->sinceDate) . '"';
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            $messageNumbers = @\imap_search($this->mailboxLink, $criteria) ?: [];
            \sort($messageNumbers);
            $this->output('Matching ' . $criteria . ': ' . \count($messageNumbers) . ' messages ');
        } else {
            $messageNumbers = $totalCount > 0 ? \range(1, $totalCount) : [];
        }

        $fetchedCount = \count($messageNumbers);
        $processedCount = 0;
        $unprocessedCount = 0;
        $deletedCount = 0;
        $movedCount = 0;

        // process maximum number of messages
        if ($fetchedCount > $this->maxMessages) {
            $messageNumbers = \array_slice($messageNumbers, 0, $this->maxMessages);
            $fetchedCount = $this->maxMessages;
            $this->output('Processing first ' . $fetchedCount . ' messages ');
        }

        if ($this->testMode) {
            $this->output('Running in test mode, not deleting messages from mailbox');
        } else {
            if ($this->disableDelete) {
                if ($this->moveHard) {
                    $this->output('Running in move mode');
                } else {
                    $this->output('Running in disableDelete mode, not deleting messages from mailbox');
                }
                if ($this->moveUnprocessed) {
                    $this->output('Unprocessed mails will be moved to unprocessed folder');
		} else {
                    $this->output('Unprocessed mails will not be touched');
		}
            } else {
                $this->output('Processed messages will be deleted from mailbox');
            }
        }

        foreach ($messageNumbers as $x) {
            // fetch the messages one at a time
            if ($this->useFetchstructure) {
                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                $structure = @\imap_fetchstructure($this->mailboxLink, $x);

                if (
                    $structure
                    &&
                    $structure->type == 1
                    &&
                    $structure->ifsubtype
                    &&
                    $structure->ifparameters
                    &&
                    \strtoupper($structure->subtype) == 'REPORT'
                    &&
                    $this->isParameter($structure->parameters, 'REPORT-TYPE', 'delivery-status')
                ) {
                    $processedResult = $this->processBounce($x, 'DSN', $totalCount);
                } else {
                    // not standard DSN msg
                    $this->output('Msg #' . $x . ' is not a standard DSN message', self::VERBOSE_REPORT);

                    if ($this->debugBodyRule) {
                        if ($structure->ifdescription) {
                            $this->output("  Content-Type : {$structure->description}", self::VERBOSE_DEBUG);
                        } else {
                            $this->output('  Content-Type : unsupported', self::VERBOSE_DEBUG);
                        }
                    }

                    $processedResult = $this->processBounce($x, 'BODY', $totalCount);
                }
            } else {
                $header = \imap_fetchheader($this->mailboxLink, $x);

                // Could be multi-line, if the new line begins with SPACE or HTAB
                if ($header && \preg_match("/Content-Type:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/i", $header, $match)) {
                    if (
                        \preg_match("/multipart\/report/i", $match[1])
                        &&
                        \preg_match("/report-type=[\"']?delivery-status[\"']?/i", $match[1])
                    ) {
                        // standard DSN msg
                        $processedResult = $this->processBounce($x, 'DSN', $totalCount);
                    } else {
                        // not standard DSN msg
                        $this->output('Msg #' . $x . ' is not a standard DSN message', self::VERBOSE_REPORT);

                        if ($this->debugBodyRule) {
                            $this->output("  Content-Type : {$match[1]}", self::VERBOSE_DEBUG);
                        }

                        $processedResult = $this->processBounce($x, 'BODY', $totalCount);
                    }
                } else {
                    // didn't get content-type header
                    $this->output('Msg #' . $x . ' is not a well-formatted MIME mail, missing Content-Type', self::VERBOSE_REPORT);

                    if ($this->debugBodyRule) {
                        $this->output('  Headers: ' . $this->bmhNewLine . $header . $this->bmhNewLine, self::VERBOSE_DEBUG);
                    }

                    $processedResult = $this->processBounce($x, 'BODY', $totalCount);
                }
            }

            $deleteFlag[$x] = false;
            $moveFlag[$x] = false;

            if ($processedResult !== false) {
                $this->output("Processed #$x");
                ++$processedCount;

                if (!$this->disableDelete) {
                    // delete the bounce if not in disableDelete mode
                    if (!$this->testMode) {
                        /** @noinspection PhpUsageOfSilenceOperatorInspection */
                        @\imap_delete($this->mailboxLink, (string)$x);
                    }

                    $deleteFlag[$x] = true;
                    ++$deletedCount;
                } elseif ($this->moveHard && $processedResult['bounce_type'] === 'hard') {
                    // check if the move directory exists, if not create it
                    if (!$this->testMode) {
                        $this->mailboxExist($this->hardMailbox);
                    }

                    // move the message
                    if (!$this->testMode) {
                        /** @noinspection PhpUsageOfSilenceOperatorInspection */
                        @\imap_mail_move($this->mailboxLink, (string) $x, $this->hardMailbox);
                    }

                    $moveFlag[$x] = true;
                    ++$movedCount;
                } elseif ($this->moveSoft && $processedResult['bounce_type'] === 'soft') {
                    // check if the move directory exists, if not create it
                    if (!$this->testMode) {
                        $this->mailboxExist($this->softMailbox);
                    }

                    // move the message
                    if (!$this->testMode) {
                        /** @noinspection PhpUsageOfSilenceOperatorInspection */
                        @\imap_mail_move($this->mailboxLink, (string) $x, $this->softMailbox);
                    }

                    $moveFlag[$x] = true;
                    ++$movedCount;
                }
            } else {
                // not processed
                $this->output("Ignored #$x, not a bounce");
                ++$unprocessedCount;
                if (!$this->disableDelete && $this->purgeUnprocessed) {
                    // delete this bounce if not in disableDelete mode, and the flag BOUNCE_PURGE_UNPROCESSED is set
                    if (!$this->testMode) {
                        /** @noinspection PhpUsageOfSilenceOperatorInspection */
                        @\imap_delete($this->mailboxLink, (string)$x);
                    }

                    $deleteFlag[$x] = true;
                    ++$deletedCount;
                }

                if ($this->moveUnprocessed) {
                    // check if the move directory exists, if not create it
                    $this->mailboxExist($this->unprocessedBox);
                    // move the message
                    /** @noinspection PhpUsageOfSilenceOperatorInspection */
                    @\imap_mail_move($this->mailboxLink, (string)$x, $this->unprocessedBox);
                    $moveFlag[$x] = true;
                    ++$movedCount;
                }
            }

            \flush();
        }

        $this->output($this->bmhNewLine . 'Closing mailbox, and purging messages');

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @\imap_expunge($this->mailboxLink);
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @\imap_close($this->mailboxLink);

        $this->output('Read: ' . $fetchedCount . ' messages');
        $this->output($processedCount . ' action taken');
        $this->output($unprocessedCount . ' no action taken');
        $this->output($deletedCount . ' messages deleted');
        $this->output($movedCount . ' messages moved');

        return true;
    }
}
