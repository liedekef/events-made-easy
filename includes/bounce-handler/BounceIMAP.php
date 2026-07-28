<?php

declare(strict_types=1);

namespace BounceMailHandler;

/**
 * BounceIMAP
 *
 * A deliberately small, read-only IMAP4rev1 client, written to remove the
 * ext-imap (UW-IMAP) dependency from BounceMailHandler.
 *
 * It implements only what bounce checking needs:
 *   - LOGIN
 *   - EXAMINE (read-only mailbox open)
 *   - SEARCH (SINCE / HEADER / UNSEEN / ALL, combinable)
 *   - FETCH ... BODY.PEEK[section] (never sets \Seen, never touches flags)
 *   - FETCH ... BODYSTRUCTURE (parsed into a flat part-number map - see
 *     fetchStructure())
 *   - LOGOUT
 *
 * It deliberately does NOT implement STORE, EXPUNGE, COPY, MOVE, APPEND,
 * or IDLE. If you need those later, this is not the class to extend blindly -
 * reach for a real IMAP library instead.
 *
 * Supports implicit TLS (port 993) and STARTTLS (port 143).
 */
class BounceIMAP
{
    /** @var resource|null */
    private $stream = null;

    private int $tagCounter = 0;

    private string $lastError = '';

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * @param string $host
     * @param int    $port
     * @param string $encryption 'ssl' (implicit TLS), 'tls' (STARTTLS), or '' (none)
     * @param int    $timeout    seconds
     */
    public function connect(string $host, int $port, string $encryption = 'ssl', int $timeout = 15): bool
    {
        $transport = ($encryption === 'ssl') ? 'ssl' : 'tcp';
        $context   = stream_context_create([
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $errno  = 0;
        $errstr = '';
        $this->stream = @stream_socket_client(
            "{$transport}://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($this->stream === false) {
            $this->lastError = "Connection failed: {$errstr} ({$errno})";
            $this->stream = null;
            return false;
        }

        stream_set_timeout($this->stream, $timeout);

        // consume the server greeting line, e.g. "* OK IMAP4rev1 Service Ready"
        $greeting = $this->readLine();
        if ($greeting === null || !str_starts_with($greeting, '* OK')) {
            $this->lastError = 'Unexpected greeting: ' . ($greeting ?? '(none)');
            $this->disconnect();
            return false;
        }

        if ($encryption === 'tls') {
            if (!$this->startTls()) {
                $this->disconnect();
                return false;
            }
        }

        return true;
    }

    private function startTls(): bool
    {
        [$ok, , , $response] = $this->command('STARTTLS');
        if (!$ok) {
            $this->lastError = 'STARTTLS rejected: ' . trim($response);
            return false;
        }

        $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT
            | (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT : 0)
            | (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0);

        if (!stream_socket_enable_crypto($this->stream, true, $cryptoMethod)) {
            $this->lastError = 'TLS negotiation failed';
            return false;
        }

        return true;
    }

    public function login(string $username, string $password): bool
    {
        [$ok, , , $response] = $this->command('LOGIN ' . $this->quote($username) . ' ' . $this->quote($password));
        if (!$ok) {
            $this->lastError = 'Login failed: ' . trim($response);
        }
        return $ok;
    }

    /**
     * Opens a mailbox read-only (EXAMINE, not SELECT - belt and braces
     * against ever accidentally flipping \Seen even if PEEK is forgotten
     * somewhere down the line).
     *
     * @return int|false number of existing messages, or false on failure
     */
    public function openMailboxReadOnly(string $mailbox = 'INBOX')
    {
        [$ok, $lines, , $response] = $this->command('EXAMINE ' . $this->quote($mailbox));
        if (!$ok) {
            $this->lastError = "Could not open mailbox '{$mailbox}': " . trim($response);
            return false;
        }

        foreach ($lines as $line) {
            if (preg_match('/^\* (\d+) EXISTS/i', $line, $m)) {
                return (int) $m[1];
            }
        }

        return 0;
    }

    /**
     * @param string[] $criteria e.g. ['SINCE "10-Jul-2026"', 'HEADER "X-Bounce-Id" "abc"']
     * @return int[] message sequence numbers
     */
    public function search(array $criteria = ['ALL']): array
    {
        $query = implode(' ', $criteria);
        [$ok, $lines, , $response] = $this->command('SEARCH ' . $query);
        if (!$ok) {
            $this->lastError = 'SEARCH failed: ' . trim($response);
            return [];
        }

        foreach ($lines as $line) {
            if (str_starts_with($line, '* SEARCH')) {
                $nums = trim(substr($line, strlen('* SEARCH')));
                if ($nums === '') {
                    return [];
                }
                return array_map('intval', preg_split('/\s+/', $nums));
            }
        }

        return [];
    }

    /**
     * Fetches headers only, without ever marking the message \Seen.
     * Shorthand for fetchSection($msgNum, 'HEADER').
     */
    public function fetchHeader(int $msgNum, ?int $maxBytes = null): ?string
    {
        return $this->fetchSection($msgNum, 'HEADER', $maxBytes);
    }

    /**
     * Fetches an arbitrary IMAP body section without marking \Seen.
     *
     * Examples of valid $section values:
     *   ''          - the entire raw message (BODY.PEEK[])
     *   'HEADER'    - the top-level header block
     *   'TEXT'      - the body, excluding the top-level header (like imap_body())
     *   '1'         - MIME part 1 (works for both single-part and multipart
     *                 messages - IMAP treats an unstructured message's whole
     *                 body as part "1")
     *   '2'         - MIME part 2 (e.g. the delivery-status part of a DSN)
     *   '1.MIME'    - the MIME sub-header of part 1 (Content-Type,
     *                 Content-Transfer-Encoding, etc. for that part)
     *   '3.HEADER'  - the header block of part 3, when it is itself a
     *                 message/rfc822 part (e.g. the original message
     *                 embedded in a bounce)
     *
     * $maxBytes, when given, caps how much of the section is fetched using
     * IMAP's partial-fetch octet-range syntax (BODY.PEEK[section]<0.N>) -
     * the server itself only sends the first N bytes, so this genuinely
     * limits network/memory use rather than just truncating locally. Useful
     * to avoid pulling a multi-MB attachment over the wire just to check
     * whether a bounce message contains a particular header or phrase.
     *
     * Returns null if the fetch failed or the section doesn't exist.
     */
    public function fetchSection(int $msgNum, string $section = '', ?int $maxBytes = null): ?string
    {
        $spec = $section === '' ? 'BODY.PEEK[]' : 'BODY.PEEK[' . $section . ']';
        if ($maxBytes !== null) {
            $spec .= '<0.' . $maxBytes . '>';
        }

        [$ok, , $literal, $response] = $this->command("FETCH {$msgNum} {$spec}", true);
        if (!$ok || $literal === null) {
            $this->lastError = "FETCH {$msgNum} {$spec} failed: " . trim($response);
            return null;
        }
        return $literal;
    }

    /**
     * Fetches and parses BODYSTRUCTURE for a message.
     *
     * Returns a flat map of IMAP part numbers (e.g. '1', '2', '2.1', as used
     * in BODY.PEEK[section]) to ['type' => ..., 'subtype' => ..., 'mimetype'
     * => 'type/subtype'] (all lowercased), covering every top-level part and
     * any nested multiparts.
     *
     * Deliberately does NOT recurse into embedded message/rfc822 parts (e.g.
     * the original message inside a bounce) - their own internal structure
     * isn't needed for bounce classification, and staying out of it keeps
     * this parser's scope to exactly what's used. Fetch that part's
     * HEADER/TEXT directly if you need to look inside it.
     *
     * This is a minimal, purpose-built parser, not a general BODYSTRUCTURE
     * implementation: it handles quoted strings, NIL, atoms, and nested
     * lists, but not IMAP literals ({N}-prefixed data) appearing inside the
     * structure itself - vanishingly rare in practice (it would take an
     * unusually long parameter value, e.g. a huge Content-Type boundary or
     * filename, for a server to switch to a literal there), but possible.
     * Returns null rather than guessing if that happens.
     *
     * Returns null on failure: the FETCH itself failing, or a response this
     * parser doesn't understand.
     *
     * @return array<string, array{type: string, subtype: string, mimetype: string}>|null
     */
    public function fetchStructure(int $msgNum): ?array
    {
        [$ok, $lines, , $response] = $this->command("FETCH {$msgNum} BODYSTRUCTURE");
        if (!$ok) {
            $this->lastError = "FETCH {$msgNum} BODYSTRUCTURE failed: " . trim($response);
            return null;
        }

        foreach ($lines as $line) {
            $fetchPos = stripos($line, 'FETCH (');
            if ($fetchPos === false) {
                continue;
            }

            $pos = $fetchPos + strlen('FETCH ');

            try {
                $items = $this->parseImapList($line, $pos);
            } catch (\RuntimeException $e) {
                $this->lastError = "FETCH {$msgNum} BODYSTRUCTURE: " . $e->getMessage();
                return null;
            }

            foreach ($items as $i => $item) {
                if (is_string($item) && strcasecmp($item, 'BODYSTRUCTURE') === 0
                    && isset($items[$i + 1]) && is_array($items[$i + 1])
                ) {
                    return $this->flattenBodyStructure($items[$i + 1]);
                }
            }
        }

        $this->lastError = "FETCH {$msgNum} BODYSTRUCTURE: no parseable response";
        return null;
    }

    /**
     * Turns one parsed BODYSTRUCTURE list into IMAP part numbers.
     *
     * A multipart body-structure list starts with a run of child
     * body-structure lists followed by the subtype string (and multipart
     * extension data); a single-part list starts with the type/subtype
     * strings directly. That distinction - is the first element a list or a
     * string - is exactly how this tells the two apart, same as the IMAP
     * spec does.
     *
     * @return array<string, array{type: string, subtype: string, mimetype: string}>
     */
    private function flattenBodyStructure(array $structure, string $prefix = ''): array
    {
        $parts = [];

        if (isset($structure[0]) && is_array($structure[0])) {
            $index = 1;
            foreach ($structure as $item) {
                if (!is_array($item)) {
                    // Reached the subtype string / extension data that
                    // follows the child parts - nothing more to descend into.
                    break;
                }
                $childPrefix = $prefix === '' ? (string) $index : $prefix . '.' . $index;
                $parts += $this->flattenBodyStructure($item, $childPrefix);
                ++$index;
            }
            return $parts;
        }

        $type = is_string($structure[0] ?? null) ? strtolower($structure[0]) : '';
        $subtype = is_string($structure[1] ?? null) ? strtolower($structure[1]) : '';
        $partNum = $prefix === '' ? '1' : $prefix;

        $parts[$partNum] = [
            'type' => $type,
            'subtype' => $subtype,
            'mimetype' => $type . '/' . $subtype,
        ];

        return $parts;
    }

    /**
     * Parses one IMAP parenthesized list starting at $s[$pos] (which must be
     * '('), advancing $pos past the matching ')'.
     *
     * @return array<int, string|array<mixed>|null>
     */
    private function parseImapList(string $s, int &$pos): array
    {
        $len = strlen($s);
        if ($pos >= $len || $s[$pos] !== '(') {
            throw new \RuntimeException('expected "(" at offset ' . $pos);
        }
        ++$pos;

        $items = [];
        while (true) {
            while ($pos < $len && $s[$pos] === ' ') {
                ++$pos;
            }
            if ($pos >= $len) {
                throw new \RuntimeException('unterminated list');
            }
            if ($s[$pos] === ')') {
                ++$pos;
                return $items;
            }
            $items[] = $this->parseImapToken($s, $pos);
        }
    }

    /**
     * Parses a single IMAP token at $s[$pos]: a parenthesized list, a quoted
     * string, NIL, or a bare atom (e.g. an unquoted number).
     *
     * @return string|array<mixed>|null
     */
    private function parseImapToken(string $s, int &$pos)
    {
        $len = strlen($s);
        while ($pos < $len && $s[$pos] === ' ') {
            ++$pos;
        }
        if ($pos >= $len) {
            throw new \RuntimeException('unexpected end of data at offset ' . $pos);
        }

        $ch = $s[$pos];

        if ($ch === '(') {
            return $this->parseImapList($s, $pos);
        }

        if ($ch === '"') {
            return $this->parseImapQuotedString($s, $pos);
        }

        if ($ch === '{') {
            throw new \RuntimeException('IMAP literals inside BODYSTRUCTURE are not supported by this parser');
        }

        $start = $pos;
        while ($pos < $len && $s[$pos] !== ' ' && $s[$pos] !== '(' && $s[$pos] !== ')') {
            ++$pos;
        }
        $atom = substr($s, $start, $pos - $start);

        return strcasecmp($atom, 'NIL') === 0 ? null : $atom;
    }

    private function parseImapQuotedString(string $s, int &$pos): string
    {
        $len = strlen($s);
        if ($pos >= $len || $s[$pos] !== '"') {
            throw new \RuntimeException('expected quoted string at offset ' . $pos);
        }
        ++$pos;

        $out = '';
        while (true) {
            if ($pos >= $len) {
                throw new \RuntimeException('unterminated quoted string');
            }
            $ch = $s[$pos];
            if ($ch === '\\') {
                ++$pos;
                if ($pos >= $len) {
                    throw new \RuntimeException('unterminated escape in quoted string');
                }
                $out .= $s[$pos];
                ++$pos;
                continue;
            }
            if ($ch === '"') {
                ++$pos;
                return $out;
            }
            $out .= $ch;
            ++$pos;
        }
    }

    public function logout(): void
    {
        if ($this->stream !== null) {
            $this->command('LOGOUT');
        }
        $this->disconnect();
    }

    private function disconnect(): void
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    // ---- low-level protocol plumbing -------------------------------------

    private function quote(string $value): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }

    /**
     * Sends a tagged command and reads until the tagged completion line.
     * Collects untagged (`* ...`) response lines. If $wantLiteral is true,
     * also captures the first IMAP literal ({N}-prefixed) it encounters,
     * which is how FETCH bodies/headers come back.
     *
     * @return array{0: bool, 1: string[], 2: string|null, 3: string}
     */
    private function command(string $command, bool $wantLiteral = false): array
    {
        if ($this->stream === null) {
            return [false, [], null, 'not connected'];
        }

        $tag = 'A' . (++$this->tagCounter);
        $this->writeLine("{$tag} {$command}");

        $untagged = [];
        $literal  = null;

        while (true) {
            $line = $this->readLine();
            if ($line === null) {
                $this->lastError = 'Connection closed unexpectedly';
                return [false, $untagged, $literal, 'connection closed'];
            }

            // literal announcement: "...{123}" at end of line means
            // 123 bytes of raw data follow before the line is "complete"
            if ($wantLiteral && preg_match('/\{(\d+)\}\s*$/', $line, $m)) {
                $byteCount = (int) $m[1];
                $literal   = $this->readBytes($byteCount);
                // consume the rest of this logical line (closing paren, etc.)
                $line .= $this->readLine();
            }

            if (str_starts_with($line, $tag . ' ')) {
                $rest   = substr($line, strlen($tag) + 1);
                $status = strtoupper(strtok($rest, ' '));
                return [$status === 'OK', $untagged, $literal, $rest];
            }

            $untagged[] = $line;
        }
    }

    private function writeLine(string $line): void
    {
        fwrite($this->stream, $line . "\r\n");
    }

    private function readLine(): ?string
    {
        $line = fgets($this->stream, 8192);
        if ($line === false) {
            return null;
        }
        return rtrim($line, "\r\n");
    }

    private function readBytes(int $count): string
    {
        $data = '';
        while (strlen($data) < $count) {
            $chunk = fread($this->stream, $count - strlen($data));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $data .= $chunk;
        }
        return $data;
    }
}
