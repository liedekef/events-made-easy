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
