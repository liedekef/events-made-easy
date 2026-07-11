<?php

/*
 * phpmailer-bmh_rules.php — Bounce Mail Handler rule engine.
 * Based on PHPMailer-BMH (c) 2002-2009 Andy Prevost, GPL.
 *
 * Rules are matched in order; the first match sets rule_cat/rule_reason.
 * Refactored to a data-driven rule table instead of a long if/elseif chain:
 * each rule is [pattern, category, reason, emailGroup?, target?], and a
 * small "engine" (bmhFindRule / bmhApplyRule) walks the list. Behaviour is
 * unchanged from the original — only the structure was simplified.
 */

global $rule_categories;
$rule_categories = [
    'antispam'       => ['remove' => 0, 'bounce_type' => 'blocked'],
    'autoreply'      => ['remove' => 0, 'bounce_type' => 'autoreply'],
    'concurrent'     => ['remove' => 0, 'bounce_type' => 'soft'],
    'content_reject' => ['remove' => 0, 'bounce_type' => 'soft'],
    'command_reject' => ['remove' => 1, 'bounce_type' => 'hard'],
    'internal_error' => ['remove' => 0, 'bounce_type' => 'temporary'],
    'defer'          => ['remove' => 0, 'bounce_type' => 'soft'],
    'delayed'        => ['remove' => 0, 'bounce_type' => 'temporary'],
    'dns_loop'       => ['remove' => 1, 'bounce_type' => 'hard'],
    'dns_unknown'    => ['remove' => 1, 'bounce_type' => 'hard'],
    'full'           => ['remove' => 0, 'bounce_type' => 'soft'],
    'inactive'       => ['remove' => 1, 'bounce_type' => 'hard'],
    'latin_only'     => ['remove' => 0, 'bounce_type' => 'soft'],
    'other'          => ['remove' => 1, 'bounce_type' => 'generic'],
    'oversize'       => ['remove' => 0, 'bounce_type' => 'soft'],
    'outofoffice'    => ['remove' => 0, 'bounce_type' => 'soft'],
    'unknown'        => ['remove' => 1, 'bounce_type' => 'hard'],
    'unrecognized'   => ['remove' => 0, 'bounce_type' => false],
    'user_reject'    => ['remove' => 1, 'bounce_type' => 'hard'],
    'warning'        => ['remove' => 0, 'bounce_type' => 'soft'],
];

global $bmh_newline;
$bmh_newline = "\n";

/* =====================================================================
 * RULE ENGINE
 * ===================================================================== */

/**
 * Returns an empty result skeleton shared by bmhBodyRules() and bmhDSNRules().
 */
function bmhEmptyResult(): array
{
    return [
        'email'           => '',
        'bounce_type'     => false,
        'remove'          => 0,
        'rule_cat'        => 'unrecognized',
        'rule_reason'     => '',
        'status_code'     => '',
        'action'          => '',
        'diagnostic_code' => '',
    ];
}

/**
 * Walks a rule table and returns the first matching rule + its preg_match
 * result, or null if nothing matched.
 *
 * Each rule is either a plain tuple [pattern, cat, reason, emailGroup?, target?],
 * or an associative array for the few special cases that need a guard
 * condition or a dynamically built pattern.
 *
 * $subjects maps target name ('body', 'diag', 'status', 'dsn_msg') to the
 * string it should be matched against. $context is passed through to
 * 'dynamic' pattern builders (needed by the one rule that reuses an
 * already-detected email address).
 */
function bmhFindRule(array $rules, array $subjects, array $context = [], string $defaultTarget = 'body'): ?array
{
    foreach ($rules as $raw) {
        $rule = isset($raw[0])
            ? [
                'pattern'     => $raw[0],
                'cat'         => $raw[1],
                'reason'      => $raw[2],
                'email_group' => $raw[3] ?? null,
                'target'      => $raw[4] ?? $defaultTarget,
            ]
            : $raw + ['target' => $defaultTarget];

        $subject = $subjects[$rule['target']] ?? '';
        if ($subject === '') {
            continue;
        }

        if (isset($rule['guard']) && !($rule['guard'])($subject)) {
            continue;
        }

        $pattern = isset($rule['dynamic']) ? ($rule['dynamic'])($context) : $rule['pattern'];
        if ($pattern === null || !preg_match($pattern, $subject, $match)) {
            continue;
        }

        return ['rule' => $rule, 'match' => $match];
    }

    return null;
}

/**
 * Applies a rule found by bmhFindRule() to $result: sets category, reason,
 * optionally the email address, and the bounce_type/remove pair (a rule can
 * override these; otherwise they come from $rule_categories).
 */
function bmhApplyRule(array $found, array &$result): void
{
    global $rule_categories;

    $rule  = $found['rule'];
    $match = $found['match'];

    $result['rule_cat']    = $rule['cat'];
    $result['rule_reason'] = $rule['reason'];

    if (!empty($rule['email_group']) && isset($match[$rule['email_group']])) {
        $result['email'] = $match[$rule['email_group']];
    }

    $result['bounce_type'] = $rule['bounce_type'] ?? $rule_categories[$rule['cat']]['bounce_type'];
    $result['remove']      = $rule['remove'] ?? $rule_categories[$rule['cat']]['remove'];
}

/**
 * When a body rule matched but didn't capture an email address itself,
 * the original BMH behaviour is to grab the last "something@something"
 * token appearing before the matched text.
 */
function bmhExtractPrecedingEmail(string $body, string $matchedText): string
{
    if ($matchedText === '') {
        return '';
    }

    $pos = strpos($body, $matchedText);
    if ($pos === false) {
        return '';
    }

    $preBody = substr($body, 0, $pos);
    $count = preg_match_all('/(\S+@\S+)/', $preBody, $matches);
    if (!$count) {
        return '';
    }

    return trim($matches[1][$count - 1], "'\"()<>.:; \t\r\n\0\x0B");
}

/**
 * Small stand-in for imap_rfc822_parse_adrlist(), scoped to exactly what
 * bmhParseDsnRecipient() needs: pull a "mailbox@host" pair out of a string
 * that may still carry a display name, comments, or angle brackets (e.g.
 * "John Doe <user@example.com>" or "user@example.com (comment)").
 *
 * @return array{mailbox: string, host: string} empty strings if no address found
 */
function bmhParseAddress(string $raw): array
{
    if (preg_match('/([^\s<>()"]+@[^\s<>()"]+)/', $raw, $m)) {
        $addr = rtrim($m[1], '.,;:');
        $parts = explode('@', $addr, 2);
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            return ['mailbox' => $parts[0], 'host' => $parts[1]];
        }
    }

    return ['mailbox' => '', 'host' => ''];
}

/**
 * Extracts the recipient's email address from a standard DSN report,
 * preferring Original-Recipient over Final-Recipient.
 */
function bmhParseDsnRecipient(string $dsn_report): string
{
    if (
        preg_match('/Original-Recipient: rfc822;(.*)/i', $dsn_report, $match)
        || preg_match('/Final-Recipient: rfc822;(.*)/i', $dsn_report, $match)
    ) {
        $email = trim($match[1], "<> \t\r\n\0\x0B");
        $parsed = bmhParseAddress($email);
        if ($parsed['host'] !== '') {
            return $parsed['mailbox'] . '@' . $parsed['host'];
        }
    }

    return '';
}

/* =====================================================================
 * RULE TABLES
 * ===================================================================== */

/**
 * Rules for non-standard bounces: matched against the raw message body.
 * Each row: [pattern, category, reason, emailGroup?]
 */
global $BMH_BODY_RULES;
$BMH_BODY_RULES = [
    ['/domain\s+name\s+not\s+found/i', 'dns_unknown', 'DNS error: domain name not found'],
    ["/no\s+such\s+address\s+here/i", 'unknown', 'No such address here'],
    [
        // guarded: only applies when there's no "Technical details" section to try first
        'pattern'     => '/Delivery to the following (?:recipient|recipients) failed permanently\X*?(\S+@\S+\w)/ui',
        'cat'         => 'unknown',
        'reason'      => 'Delivery failed permanently without technical details',
        'email_group' => 1,
        'guard'       => function (string $body): bool {
            return strpos($body, 'Technical details of permanent failure') === false;
        },
    ],
    ["/user.+?not\s+exist/i", 'unknown', 'User does not exist'],
    ['/user\s+unknown|unknown\s+user/i', 'unknown', 'User unknown'],
    ["/no\s+mailbox/i", 'unknown', 'No mailbox here by that name'],
    ["/can't\s+find.*mailbox/i", 'unknown', "Can't find user's mailbox"],
    ["/Can't\s+create\s+output.*<(\S+@\S+\w)>/is", 'unknown', "Can't create output for user", 1],
    ['/=D5=CA=BA=C5=B2=BB=B4=E6=D4=DA/i', 'unknown', 'Mailbox does not exist (Chinese error)'],
    ["/Unrouteable\s+address/i", 'unknown', 'Unrouteable address'],
    ["/delivery[^\n\r]+failed\S*\s+(\S+@\S+\w)\s/i", 'unknown', 'Delivery failed for recipient', 1],
    ["/unknown\s+local-part/i", 'unknown', 'Unknown local-part in address'],
    ["/Invalid.*(?:alias|account|recipient|address|email|mailbox|user).*<(\S+@\S+\w)>/is", 'unknown', 'Invalid recipient address', 1],
    ["/No\s+such.*(?:alias|account|recipient|address|email|mailbox|user).*<(\S+@\S+\w)>/is", 'unknown', 'No such user', 1],
    ['/not unique.\s+Several matches found/i', 'unknown', 'Recipient name not unique'],
    ["/quota\s+exceeded.*<(\S+@\S+\w)>/is", 'full', 'Mailbox full or over quota', 1],
    ["/The message to (\S+@\S+\w)\s.*bounce.*Quota exceed/i", 'full', 'Mailbox full or over quota', 1],
    ['/over.*quota|quota\s+exceeded|message\s+size\s+exceeded|mailbox.*full|not\s+enough\s+storage\s+space/i', 'full', 'Mailbox full or over quota'],
    ['/user is inactive/i', 'inactive', 'User is inactive'],
    ["/(\S+@\S+\w).*n? is restricted/i", 'inactive', 'Recipient is restricted', 1],
    ['/inactive account/i', 'inactive', 'Inactive account'],
    ["/<(\S+@\S+\w)>.*\n.*mailbox unavailable/i", 'unknown', 'Mailbox unavailable', 1],
    ["/<(\S+@\S+\w)>.*\n?.*\n?.*account that you tried to reach does not exist/i", 'unknown', 'Account does not exist (Gmail)', 1],
    ['/Technical details of permanent failure:\s+TEMP_FAILURE: Could not initiate SMTP conversation with any hosts/i', 'dns_unknown', 'Could not initiate SMTP conversation (permanent)'],
    ['/Technical details of temporary failure:\s+TEMP_FAILURE: Could not initiate SMTP conversation with any hosts/i', 'delayed', 'Could not initiate SMTP conversation (temporary)'],
    ['/Technical details of temporary failure:\s+TEMP_FAILURE: The recipient server did not accept our requests to connect./i', 'delayed', 'Recipient server refused connection'],
    [
        'pattern'     => "/input\/output error/i",
        'cat'         => 'internal_error',
        'reason'      => 'Input/output error',
        'target'      => 'body',
        'bounce_type' => 'hard',
        'remove'      => 1,
    ],
    [
        'pattern'     => '/can not open new email file/i',
        'cat'         => 'internal_error',
        'reason'      => 'Cannot open new email file',
        'target'      => 'body',
        'bounce_type' => 'hard',
        'remove'      => 1,
    ],
    ['/Resources temporarily unavailable|Insufficient system resources/i', 'defer', 'Resources temporarily unavailable'],
    ["/^AutoReply message from (\S+@\S+\w)/i", 'autoreply', 'Auto-reply or out-of-office message', 1],
    ["/Your message \([^)]+\) was blocked by|message has been blocked/i", 'antispam', 'Message blocked by DNSBL'],
    ["/Messages\s+without\s+\S+\s+fields\s+are\s+not\s+accepted\s+here/i", 'content_reject', 'Message missing required header fields'],
    ["/(?:alias|account|recipient|address|email|mailbox|user).*no\s+longer\s+accepts\s+mail/i", 'inactive', 'Address no longer accepts mail'],
    ["/does not accept[^\r\n]*non-Western/i", 'latin_only', 'User does not accept non-Western character sets'],
    ["/554.*delivery error.*this user.*doesn't have.*account/is", 'unknown', 'User does not have an account (Yahoo)'],
    ['/550.*Requested.*action.*not.*taken:.*mailbox.*unavailable/is', 'unknown', 'Mailbox unavailable (Hotmail)'],
    ["/550 5\.1\.1.*Recipient address rejected/is", 'unknown', 'Recipient address rejected'],
    ['/550.*in reply to end of DATA command/is', 'unknown', 'Mail rejected at DATA command'],
    ['/550.*in reply to RCPT TO command/is', 'unknown', 'Mail rejected at RCPT TO command'],
    ["/unrouteable\s+mail\s+domain/i", 'dns_unknown', 'Unrouteable mail domain'],
    // Custom Massi rule: multi-language out-of-office / autoreply detector, kept last (lowest priority)
    ["/ferie|fuori ufficio|fuori dall'ufficio|ritorno in ufficio|fuori sede|limited access to|no access to|chiuso dal|in vacanza|se urgente|per urgenze|sono assente|sar. assente|assenza|assente dal|momentaneamente assente|sar. reperibile|richieste urgenti|out of office|out of the office|the office|ll be away|able to answer|maternity leave|maternit|sar. assente|rispondo quando|automated response|back on|avermi contattat.|certezza della lettura|al mio rientro|generato automaticamente|automatically generated|dringenden|will be back|able to reply|holiday/i", 'autoreply', 'Auto-reply or out-of-office message'],
];

/**
 * Fallback rule used when a DSN report didn't yield a recipient email at all.
 */
global $BMH_DSN_NO_EMAIL_RULES;
$BMH_DSN_NO_EMAIL_RULES = [
    ["/quota exceed.*<(\S+@\S+\w)>/is", 'full', 'Quota exceeded (DSN message)', 1, 'dsn_msg'],
];

/**
 * Rules matched against the Diagnostic-Code (falls back to $status_code for
 * one legacy rule). Used for DSN reports with Action: failed.
 */
global $BMH_DSN_DIAG_RULES;
$BMH_DSN_DIAG_RULES = [
    ['/over.*quota|exceed.*quota|(?:alias|account|recipient|address|email|mailbox|user).*full|Insufficient system storage|Benutzer hat zuviele Mails auf dem Server|exceeded storage allocation|Mailbox quota usage exceeded|User has exhausted allowed storage space|User mailbox exceeds allowed size|not.*enough\s+space/is', 'full', 'Mailbox full or over quota'],
    ['/File too large/i', 'full', 'File too large'],
    ['/larger than.*limit/is', 'oversize', 'Message larger than system limit'],
    ['/(?:alias|account|recipient|address|email|mailbox|user)(.*)not(.*)list/is', 'unknown', 'User not listed in address book'],
    ['/user path no exist/i', 'unknown', 'User path does not exist'],
    ['/Relay.*(?:denied|prohibited|prohibited the mail that you sent)/is', 'unknown', 'Relay denied'],
    ['/no.*valid.*(?:alias|account|recipient|address|email|mailbox|user)/is', 'unknown', 'No valid recipients'],
    ['/Invalid.*(?:alias|account|recipient|address|email|mailbox|user)/is', 'unknown', 'Invalid recipient'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*(?:disabled|discontinued)/is', 'unknown', 'Account disabled or discontinued'],
    ["/user doesn't have.*account/is", 'unknown', "User doesn't have an account"],
    ['/(?:unknown|illegal).*(?:alias|account|recipient|address|email|mailbox|user)/is', 'unknown', 'Unknown or illegal alias/account'],
    ["/(?:alias|account|recipient|address|email|mailbox|user).*(?:un|not\s+)available/is", 'unknown', 'Mailbox not available'],
    ['/no (?:alias|account|recipient|address|email|mailbox|user)/i', 'unknown', 'No mailbox here by that name'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*unknown/is', 'unknown', 'User unknown'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*disabled/is', 'unknown', 'User disabled'],
    ['/No such (?:alias|account|recipient|address|email|mailbox|user)/i', 'unknown', 'No such user'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*NOT FOUND/is', 'unknown', 'Mailbox not found'],
    ['/deactivated (?:alias|account|recipient|address|email|mailbox|user)/i', 'unknown', 'Deactivated mailbox'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*reject/is', 'unknown', 'Recipient rejected'],
    ['/bounce.*administrator/is', 'unknown', 'Message bounced by administrator'],
    ['/<.*>.*disabled/is', 'unknown', 'User disabled with MTA service'],
    ['/not our customer/i', 'unknown', 'Not our customer'],
    ['/Wrong (?:alias|account|recipient|address|email|mailbox|user)/i', 'unknown', 'Wrong recipients'],
    ['/(?:unknown|bad).*(?:alias|account|recipient|address|email|mailbox|user)/is', 'unknown', 'Unknown or bad address'],
    // legacy rule: same pattern, but tested against the Status: header instead of the Diagnostic-Code
    ['/(?:unknown|bad).*(?:alias|account|recipient|address|email|mailbox|user)/is', 'unknown', 'Bad destination mailbox address (status)', null, 'status'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*not OK/is', 'unknown', 'User not OK'],
    ['/Access.*Denied/is', 'unknown', 'Access denied'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*lookup.*fail/is', 'unknown', 'Address lookup failed'],
    ['/(?:recipient|address|email|mailbox|user).*not.*member of domain/is', 'unknown', 'User not a member of domain'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*cannot be verified/is', 'unknown', 'Recipient cannot be verified'],
    ['/Unable to relay/i', 'unknown', 'Unable to relay'],
    ["/(?:alias|account|recipient|address|email|mailbox|user).*(?:n't|not) exist/is", 'unknown', 'User does not exist'],
    ['/not have an account/i', 'unknown', 'Does not have an account here'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*is not allowed/is', 'unknown', 'Account is not allowed'],
    ['/not unique.\s+Several matches found/i', 'unknown', 'Recipient name not unique (DSN)'],
    ['/inactive.*(?:alias|account|recipient|address|email|mailbox|user)/is', 'inactive', 'Inactive user'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*Inactive/is', 'inactive', 'Account inactive'],
    ['/(?:alias|account|recipient|address|email|mailbox|user) closed due to inactivity/i', 'inactive', 'Account closed due to inactivity'],
    ['/(?:alias|account|recipient|address|email|mailbox|user) not activated/i', 'inactive', 'Account not activated'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*(?:suspend|expire)/is', 'inactive', 'User suspended or account expired'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*no longer exist/is', 'inactive', 'Recipient no longer exists'],
    ['/(?:forgery|abuse)/i', 'inactive', 'Possible forgery or deactivated due to abuse'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*restrict/is', 'inactive', 'Mailbox is restricted'],
    ['/(?:alias|account|recipient|address|email|mailbox|user).*locked/is', 'inactive', 'User status is locked'],
    ['/(?:alias|account|recipient|address|email|mailbox|user) refused/i', 'user_reject', 'User refused to receive mail'],
    ['/sender.*not/is', 'user_reject', 'Sender email not in my domain'],
    ['/Message refused/i', 'command_reject', 'Message refused'],
    ['/No permit/i', 'command_reject', 'No permit'],
    ["/domain isn't in.*allowed rcpthost|prohibited the mail that you sent|possono ricevere posta solo|non rispetta le norme definite|viola i criteri|recapitare il messaggio per regolamenti/is", 'command_reject', 'Domain not in allowed rcpthosts'],
    ['/AUTH FAILED/i', 'command_reject', 'AUTH failed'],
    ['/relay.*not.*(?:permit|allow)/is', 'command_reject', 'Relay not permitted'],
    ['/not local host/i', 'command_reject', 'Not local host'],
    ['/Unauthorized relay/i', 'command_reject', 'Unauthorized relay'],
    ['/Transaction.*fail/is', 'command_reject', 'Transaction failed'],
    ['/Invalid data/i', 'command_reject', 'Invalid data in message'],
    ['/Local user only/i', 'command_reject', 'Local user only'],
    ['/not.*permit.*to/is', 'command_reject', 'Not permitted to relay'],
    ['/Content reject/i', 'content_reject', 'Content reject'],
    ["/MIME\/REJECT/i", 'content_reject', 'MIME reject'],
    ['/MIME error/i', 'content_reject', 'MIME error'],
    ['/Mail data refused.*AISP/is', 'content_reject', 'Mail data refused by AISP'],
    ['/Host unknown/i', 'dns_unknown', 'Host unknown'],
    ['/Specified domain.*not.*allow/is', 'dns_unknown', 'Specified domain not allowed'],
    ['/No route to host/i', 'dns_unknown', 'No route to host'],
    ['/unrouteable address/i', 'dns_unknown', 'Unrouteable address'],
    ['/Host or domain name not found/i', 'dns_unknown', 'Host or domain name not found'],
    ['/loops back to myself/i', 'dns_loop', 'Mail loops back to myself'],
    ['/System.*busy/is', 'defer', 'System busy'],
    ['/Resources temporarily unavailable|was aborted after|problem with the recipient|mail action aborted/i', 'defer', 'Resources temporarily unavailable'],
    ['/sender is rejected|temporarily rate limited/i', 'antispam', 'Sender is rejected'],
    ['/Client host rejected|detected as spam|spam detected/i', 'antispam', 'Client host rejected'],
    ['/MAIL FROM(.*)mismatches client IP/is', 'antispam', 'MAIL FROM mismatches client IP'],
    ['/denyip/i', 'antispam', 'Deny IP (antispam)'],
    ['/client host.*blocked/is', 'antispam', 'Client host blocked'],
    ['/mail.*reject/is', 'antispam', 'Mail rejected (antispam)'],
    ['/spam.*detect/is', 'antispam', 'Spam detected'],
    ['/reject.*spam/is', 'antispam', 'Rejected as spam'],
    ['/SpamTrap/i', 'antispam', 'SpamTrap reject'],
    ['/Verify mailfrom failed/i', 'antispam', 'Verify mailfrom failed'],
    ['/MAIL.*FROM.*mismatch/is', 'antispam', 'MAIL FROM mismatched with header from'],
    ['/spam scale/i', 'antispam', 'Message scored too high on spam scale'],
    ['/Client host bypass/i', 'antispam', 'Client host bypassing relay'],
    ['/junk mail/i', 'antispam', 'Junk mail detected'],
    ['/message filtered/i', 'antispam', 'Message filtered (spam)'],
    ['/subject.*consider.*spam/is', 'antispam', 'Subject matches spam profile'],
    ['/Temporary local problem/i', 'internal_error', 'Temporary local problem'],
    ['/system config error/i', 'internal_error', 'System config error'],
    ['/delivery.*suspend/is', 'delayed', 'Delivery suspended (timeout)'],
];

/**
 * Rules matched against the raw DSN message text. Used as a second pass
 * within Action: failed, after BMH_DSN_DIAG_RULES found nothing.
 */
global $BMH_DSN_MSG_RULES;
$BMH_DSN_MSG_RULES = [
    ['/(?:alias|account|recipient|address|email|mailbox|user)(?:.*)invalid/i', 'unknown', 'All recipients are invalid'],
    ['/Deferred.*No such.*(?:file|directory)/i', 'unknown', 'Deferred - no such file or directory'],
    ['/mail receiving disabled/i', 'unknown', 'Mail receiving disabled'],
    ['/bad.*(?:alias|account|recipient|address|email|mailbox|user)/i', 'unknown', 'Bad destination mailbox (status code)', null, 'status'],
    ['/bad.*(?:alias|account|recipient|address|email|mailbox|user)/i', 'unknown', 'Bad destination mailbox (DSN msg)'],
    ['/over.*quota|quota.*exceeded|exceed.*\n?.*quota|exceed the quota|(?:alias|account|recipient|address|email|mailbox|user).*full|space.*not.*enough/i', 'full', 'Mailbox full or over quota'],
    ['/Deferred.*Connection (?:refused|reset)/i', 'defer', 'Deferred - connection refused'],
    ['/Invalid host name/i', 'dns_unknown', 'Invalid host name'],
    ['/Deferred.*No route to host/i', 'dns_unknown', 'Deferred - no route to host'],
    ['/Host unknown/i', 'dns_unknown', 'Host unknown'],
    ['/Name server timeout/i', 'dns_unknown', 'Name server timeout'],
    ['/Deferred.*Connection.*tim(?:e|ed).*out/i', 'dns_unknown', 'Connection timed out'],
    // custom Massi rule: a bare (non-"Deferred") connection timeout is treated as hard-but-not-remove
    ['/Connection.*tim(?:e|ed).*out/i', 'delayed', 'Connection timed out'],
    ['/Deferred.*host name lookup failure|Temporary\s+lookup\s+failure/i', 'dns_unknown', 'Host name lookup failure'],
    ['/MX list.*point.*back/i', 'dns_loop', 'MX list points back to server'],
    ["/I\/O error/i", 'internal_error', 'I/O error'],
    ['/connection.*broken/i', 'internal_error', 'Connection broken'],
    [
        // reuses the recipient email already extracted from the DSN report
        'cat'    => 'other',
        'reason' => 'Delivery to recipients failed',
        'target' => 'dsn_msg',
        'dynamic' => function (array $result): string {
            return "/Delivery to the following recipients failed.*\n.*\n.*|Message\s+delivery\s+failed"
                . \preg_quote($result['email'], '/')
                . '/i';
        },
    ],
    // wind-up rules: many other messages end with these generic phrases, so they must stay last
    ['/(?:User unknown|Unknown user)/i', 'unknown', 'User unknown'],
    ['/Service unavailable/i', 'unknown', 'Service unavailable'],
];

/* =====================================================================
 * PUBLIC ENTRY POINTS
 * ===================================================================== */

/**
 * Defined bounce parsing rules for non-standard DSN (matched against $body).
 *
 * @param string $body       body of the email
 * @param bool   $debug_mode show debug info or not
 *
 * @return array result array: 'email', 'bounce_type', 'remove', 'rule_cat', 'rule_reason', ...
 *               if the bounce type could NOT be detected, rule_reason stays ''
 */
function bmhBodyRules($body, $debug_mode = false): array
{
    global $bmh_newline, $BMH_BODY_RULES;

    $result = bmhEmptyResult();

    $found = bmhFindRule($BMH_BODY_RULES, ['body' => $body]);
    if ($found !== null) {
        bmhApplyRule($found, $result);
        if ($result['email'] === '') {
            $result['email'] = bmhExtractPrecedingEmail($body, $found['match'][0] ?? '');
        }
    }

    if ($result['rule_reason'] === '' && $debug_mode) {
        echo 'Body:' . $bmh_newline . $body . $bmh_newline;
        echo $bmh_newline;
    }

    return $result;
}

/**
 * Defined bounce parsing rules for standard DSN (Delivery Status Notification).
 *
 * @param string $dsn_msg    human-readable explanation
 * @param string $dsn_report delivery-status report
 * @param bool   $debug_mode show debug info or not
 *
 * @return array result array: 'email', 'bounce_type', 'remove', 'rule_cat', 'rule_reason', ...
 *               if the bounce type could NOT be detected, rule_reason stays ''
 */
function bmhDSNRules($dsn_msg, $dsn_report, $debug_mode = false): array
{
    global $rule_categories, $bmh_newline;
    global $BMH_DSN_NO_EMAIL_RULES, $BMH_DSN_DIAG_RULES, $BMH_DSN_MSG_RULES;

    $result = bmhEmptyResult();
    $result['email'] = bmhParseDsnRecipient($dsn_report);

    $action = null;
    if (preg_match('/Action: (.+)/i', $dsn_report, $match)) {
        $action = strtolower(trim($match[1]));
        $result['action'] = $action;
    }

    $status_code = '';
    if (preg_match("/Status: ([0-9\.]+)/i", $dsn_report, $match)) {
        $status_code = $match[1];
        $result['status_code'] = $status_code;
    }

    // Could be multi-line, if the new line begins with SPACE or HTAB
    $diag_code = '';
    if (preg_match("/Diagnostic-Code:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/i", $dsn_report, $match)) {
        $diag_code = $match[1];
    }
    if ($diag_code === '') {
        // No Diagnostic-Code in email, fall back to the DSN message
        $diag_code = $dsn_msg;
    }
    $result['diagnostic_code'] = $diag_code;

    $subjects = ['diag' => $diag_code, 'status' => $status_code, 'dsn_msg' => $dsn_msg];

    if ($result['email'] === '') {
        $found = bmhFindRule($BMH_DSN_NO_EMAIL_RULES, $subjects);
        if ($found !== null) {
            bmhApplyRule($found, $result);
        }
    } else {
        // Action could be one of "failed" / "delayed" / "delivered" / "relayed" / "expanded" (RFC 1894)
        switch ($action) {
            case 'failed':
                $found = bmhFindRule($BMH_DSN_DIAG_RULES, $subjects, $result, 'diag')
                    ?? bmhFindRule($BMH_DSN_MSG_RULES, $subjects, $result, 'dsn_msg');
                if ($found !== null) {
                    bmhApplyRule($found, $result);
                }

                break;

            case 'delayed':
                $result['rule_cat'] = 'delayed';
                $result['rule_reason'] = 'Delivery delayed';
                $result['bounce_type'] = $rule_categories['delayed']['bounce_type'];
                $result['remove'] = $rule_categories['delayed']['remove'];

                break;

            case 'delivered':
            case 'relayed':
            case 'expanded': // unhandled cases
            default:
                break;
        }
    }

    if ($result['rule_reason'] === '' && $debug_mode) {
        echo 'email: ' . $result['email'] . $bmh_newline;
        echo 'Action: ' . $action . $bmh_newline;
        echo 'Status: ' . $status_code . $bmh_newline;
        echo 'Diagnostic-Code: ' . $diag_code . $bmh_newline;
        echo "DSN Message:" . $bmh_newline . $dsn_msg . $bmh_newline;
        echo $bmh_newline;
    }
    return $result;
}
