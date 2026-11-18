<?php
// phpcs:ignoreFile WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
namespace EventsMadeEasy;

defined( 'ABSPATH' ) || exit;

/**
 * Small IMAP4rev1 client
 *
 * It uses PHP streams instead of ext-imap, which is deprecated and no longer
 * bundled with newer PHP versions. Only the commands needed by the mailbox
 * integrations are implemented:
 * LOGIN, EXAMINE/SELECT, SEARCH, FETCH BODY.PEEK/BODYSTRUCTURE,
 * INTERNALDATE, STORE, CAPABILITY and EXPUNGE.
 *
 * All message commands use UIDs (UID SEARCH / UID FETCH / UID STORE), so the
 * numbers returned by search() stay valid for the whole session even when
 * another client deletes or expunges messages in the meantime.
 */
class ImapClient {

    /** @var resource|null */
    private $stream = null;

    private int $tag_counter = 0;

    private string $last_error = '';

    private int $message_count = 0;

    public function get_last_error(): string {
        return $this->last_error;
    }

    public function message_count(): int {
        return $this->message_count;
    }

    /**
     * @param string $encryption 'ssl' (implicit TLS), 'tls' (STARTTLS), or '' (none)
     */
    public function connect( string $host, int $port, string $encryption = 'ssl', int $timeout = 15 ): bool {
        $transport = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $context   = stream_context_create( [
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ] );

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

        if ( $this->stream === false ) {
            $this->last_error = "Connection failed: {$errstr} ({$errno})";
            $this->stream = null;
            return false;
        }

        stream_set_timeout( $this->stream, $timeout );

        $greeting = $this->read_line();
        if ( $greeting === null || ! str_starts_with( $greeting, '* OK' ) ) {
            $this->last_error = 'Unexpected greeting: ' . ( $greeting ?? '(none)' );
            $this->disconnect();
            return false;
        }

        if ( $encryption === 'tls' ) {
            if ( ! $this->start_tls() ) {
                $this->disconnect();
                return false;
            }
        }

        return true;
    }

    private function start_tls(): bool {
        [ $ok, , , $response ] = $this->command( 'STARTTLS' );
        if ( ! $ok ) {
            $this->last_error = 'STARTTLS rejected: ' . trim( $response );
            return false;
        }

        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT
            | ( defined( 'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT' ) ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT : 0 )
            | ( defined( 'STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT' ) ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0 );

        if ( ! stream_socket_enable_crypto( $this->stream, true, $crypto_method ) ) {
            $this->last_error = 'TLS negotiation failed';
            return false;
        }

        return true;
    }

    public function login( string $username, string $password ): bool {
        [ $ok, , , $response ] = $this->command( 'LOGIN ' . $this->quote( $username ) . ' ' . $this->quote( $password ) );
        if ( ! $ok ) {
            $this->last_error = 'Login failed: ' . trim( $response );
        }
        return $ok;
    }

    /**
     * @return int|false number of existing messages, or false on failure
     */
    public function open_mailbox( string $mailbox = 'INBOX', bool $read_only = true ) {
        $command = ( $read_only ? 'EXAMINE ' : 'SELECT ' ) . $this->quote( $mailbox );
        [ $ok, $lines, , $response ] = $this->command( $command );
        if ( ! $ok ) {
            $this->last_error = "Could not open mailbox '{$mailbox}': " . trim( $response );
            return false;
        }

        foreach ( $lines as $line ) {
            if ( preg_match( '/^\* (\d+) EXISTS/i', $line, $m ) ) {
                $this->message_count = (int) $m[1];
                return $this->message_count;
            }
        }

        $this->message_count = 0;
        return 0;
    }

    /**
     * @return int|false
     */
    public function open_mailbox_read_only( string $mailbox = 'INBOX' ) {
        return $this->open_mailbox( $mailbox, true );
    }

    /**
     * @param string[] $criteria e.g. ['UNSEEN'], ['SINCE "10-Jul-2026"']
     * @return int[] message UIDs
     */
    public function search( array $criteria = [ 'ALL' ] ): array {
        $query = implode( ' ', $criteria );
        [ $ok, $lines, , $response ] = $this->command( 'UID SEARCH ' . $query );
        if ( ! $ok ) {
            $this->last_error = 'SEARCH failed: ' . trim( $response );
            return [];
        }

        foreach ( $lines as $line ) {
            if ( str_starts_with( $line, '* SEARCH' ) ) {
                $nums = trim( substr( $line, strlen( '* SEARCH' ) ) );
                if ( $nums === '' ) {
                    return [];
                }
                return array_map( 'intval', preg_split( '/\s+/', $nums ) );
            }
        }

        return [];
    }

    public function fetch_header( int $uid, ?int $max_bytes = null ): ?string {
        return $this->fetch_section( $uid, 'HEADER', $max_bytes );
    }

    public function fetch_section( int $uid, string $section = '', ?int $max_bytes = null ): ?string {
        $spec = $section === '' ? 'BODY.PEEK[]' : 'BODY.PEEK[' . $section . ']';
        if ( $max_bytes !== null ) {
            $spec .= '<0.' . $max_bytes . '>';
        }

        [ $ok, , $literal, $response ] = $this->command( "UID FETCH {$uid} {$spec}", true );
        if ( ! $ok || $literal === null ) {
            $this->last_error = "FETCH {$uid} {$spec} failed: " . trim( $response );
            return null;
        }

        return $literal;
    }

    /**
     * @return array<string,array{type:string,subtype:string,mimetype:string}>|null
     */
    public function fetch_structure( int $uid ): ?array {
        [ $ok, $lines, , $response ] = $this->command( "UID FETCH {$uid} BODYSTRUCTURE" );
        if ( ! $ok ) {
            $this->last_error = "FETCH {$uid} BODYSTRUCTURE failed: " . trim( $response );
            return null;
        }

        foreach ( $lines as $line ) {
            $fetch_pos = stripos( $line, 'FETCH (' );
            if ( $fetch_pos === false ) {
                continue;
            }

            $pos = $fetch_pos + strlen( 'FETCH ' );

            try {
                $items = $this->parse_imap_list( $line, $pos );
            } catch ( \RuntimeException $e ) {
                $this->last_error = "FETCH {$uid} BODYSTRUCTURE: " . $e->getMessage();
                return null;
            }

            foreach ( $items as $i => $item ) {
                if ( is_string( $item ) && strcasecmp( $item, 'BODYSTRUCTURE' ) === 0
                    && isset( $items[ $i + 1 ] ) && is_array( $items[ $i + 1 ] )
                ) {
                    return $this->flatten_body_structure( $items[ $i + 1 ] );
                }
            }
        }

        $this->last_error = "FETCH {$uid} BODYSTRUCTURE: no parseable response";
        return null;
    }

    /**
     * Arrival time (INTERNALDATE) of a message as a unix timestamp, or null
     * when the server does not report one.
     */
    public function fetch_internal_date( int $uid ): ?int {
        [ $ok, $lines, , $response ] = $this->command( "UID FETCH {$uid} INTERNALDATE" );
        if ( ! $ok ) {
            $this->last_error = "FETCH {$uid} INTERNALDATE failed: " . trim( $response );
            return null;
        }
        foreach ( $lines as $line ) {
            if ( preg_match( '/INTERNALDATE "([^"]+)"/i', $line, $m ) ) {
                $ts = strtotime( $m[1] );
                return $ts === false ? null : $ts;
            }
        }
        return null;
    }

    public function mark_seen( int $uid ): bool {
        [ $ok, , , $response ] = $this->command( "UID STORE {$uid} +FLAGS (\\Seen)" );
        if ( ! $ok ) {
            $this->last_error = "STORE {$uid} failed: " . trim( $response );
        }
        return $ok;
    }

    public function delete_message( int $uid ): bool {
        return $this->delete_messages( [ $uid ] );
    }

    /**
     * @param int[] $uids
     */
    public function delete_messages( array $uids ): bool {
        $uids = array_values( array_unique( array_filter( array_map( 'intval', $uids ), static fn( int $uid ): bool => $uid > 0 ) ) );
        if ( $uids === [] ) {
            return false;
        }

        $set = implode( ',', $uids );
        [ $ok, , , $response ] = $this->command( "UID STORE {$set} +FLAGS (\\Deleted)" );
        if ( ! $ok ) {
            $this->last_error = "STORE {$set} failed: " . trim( $response );
            return false;
        }

        [ $capable, $lines ] = $this->command( 'CAPABILITY' );
        $uid_expunge = $capable && stripos( implode( ' ', $lines ), 'UIDPLUS' ) !== false;
        [ $ok, , , $response ] = $this->command( $uid_expunge ? "UID EXPUNGE {$set}" : 'EXPUNGE' );
        if ( ! $ok ) {
            $this->last_error = 'EXPUNGE failed: ' . trim( $response );
        }
        return $ok;
    }

    public function logout(): void {
        if ( $this->stream !== null ) {
            $this->command( 'LOGOUT' );
        }
        $this->disconnect();
    }

    /**
     * @return array<string,array{type:string,subtype:string,mimetype:string}>
     */
    private function flatten_body_structure( array $structure, string $prefix = '' ): array {
        $parts = [];

        if ( isset( $structure[0] ) && is_array( $structure[0] ) ) {
            $index = 1;
            foreach ( $structure as $item ) {
                if ( ! is_array( $item ) ) {
                    break;
                }
                $child_prefix = $prefix === '' ? (string) $index : $prefix . '.' . $index;
                $parts       += $this->flatten_body_structure( $item, $child_prefix );
                ++$index;
            }
            return $parts;
        }

        $type    = is_string( $structure[0] ?? null ) ? strtolower( $structure[0] ) : '';
        $subtype = is_string( $structure[1] ?? null ) ? strtolower( $structure[1] ) : '';
        $part    = $prefix === '' ? '1' : $prefix;

        $parts[ $part ] = [
            'type'     => $type,
            'subtype'  => $subtype,
            'mimetype' => $type . '/' . $subtype,
        ];

        return $parts;
    }

    /**
     * @return array<int,string|array<mixed>|null>
     */
    private function parse_imap_list( string $s, int &$pos ): array {
        $len = strlen( $s );
        if ( $pos >= $len || $s[ $pos ] !== '(' ) {
            throw new \RuntimeException( 'expected "(" at offset ' . $pos );
        }
        ++$pos;

        $items = [];
        while ( true ) {
            while ( $pos < $len && $s[ $pos ] === ' ' ) {
                ++$pos;
            }
            if ( $pos >= $len ) {
                throw new \RuntimeException( 'unterminated list' );
            }
            if ( $s[ $pos ] === ')' ) {
                ++$pos;
                return $items;
            }
            $items[] = $this->parse_imap_token( $s, $pos );
        }
    }

    /**
     * @return string|array<mixed>|null
     */
    private function parse_imap_token( string $s, int &$pos ) {
        $len = strlen( $s );
        while ( $pos < $len && $s[ $pos ] === ' ' ) {
            ++$pos;
        }
        if ( $pos >= $len ) {
            throw new \RuntimeException( 'unexpected end of data at offset ' . $pos );
        }

        $ch = $s[ $pos ];

        if ( $ch === '(' ) {
            return $this->parse_imap_list( $s, $pos );
        }
        if ( $ch === '"' ) {
            return $this->parse_imap_quoted_string( $s, $pos );
        }
        if ( $ch === '{' ) {
            throw new \RuntimeException( 'IMAP literals inside BODYSTRUCTURE are not supported' );
        }

        $start = $pos;
        while ( $pos < $len && $s[ $pos ] !== ' ' && $s[ $pos ] !== '(' && $s[ $pos ] !== ')' ) {
            ++$pos;
        }
        $atom = substr( $s, $start, $pos - $start );

        return strcasecmp( $atom, 'NIL' ) === 0 ? null : $atom;
    }

    private function parse_imap_quoted_string( string $s, int &$pos ): string {
        $len = strlen( $s );
        if ( $pos >= $len || $s[ $pos ] !== '"' ) {
            throw new \RuntimeException( 'expected quoted string at offset ' . $pos );
        }
        ++$pos;

        $out = '';
        while ( true ) {
            if ( $pos >= $len ) {
                throw new \RuntimeException( 'unterminated quoted string' );
            }
            $ch = $s[ $pos ];
            if ( $ch === '\\' ) {
                ++$pos;
                if ( $pos >= $len ) {
                    throw new \RuntimeException( 'unterminated escape in quoted string' );
                }
                $out .= $s[ $pos ];
                ++$pos;
                continue;
            }
            if ( $ch === '"' ) {
                ++$pos;
                return $out;
            }
            $out .= $ch;
            ++$pos;
        }
    }

    private function quote( string $value ): string {
        $value = str_replace( [ "\r", "\n" ], '', $value );
        return '"' . str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $value ) . '"';
    }

    /**
     * Run a command and collect its untagged lines.
     *
     * Literals ("{123}" followed by 123 raw bytes) are handled in two ways:
     * with $want_literal the last one is returned as the literal; otherwise it
     * is folded into the line as a quoted string, so line-based parsers such as
     * BODYSTRUCTURE (where servers may send a filename as a literal) keep working.
     *
     * @return array{0:bool,1:string[],2:string|null,3:string}
     */
    private function command( string $command, bool $want_literal = false ): array {
        if ( $this->stream === null ) {
            return [ false, [], null, 'not connected' ];
        }

        $tag = 'A' . ( ++$this->tag_counter );
        $this->write_line( "{$tag} {$command}" );

        $untagged = [];
        $literal  = null;

        while ( true ) {
            $line = $this->read_line();
            if ( $line === null ) {
                $this->last_error = 'Connection closed unexpectedly';
                return [ false, $untagged, $literal, 'connection closed' ];
            }

            while ( preg_match( '/\{(\d+)\}$/', $line, $m ) ) {
                $byte_count = (int) $m[1];
                $bytes      = $this->read_bytes( $byte_count );
                if ( strlen( $bytes ) !== $byte_count ) {
                    // Timed out mid-literal: the stream is out of sync, so drop
                    // the connection instead of returning a truncated attachment.
                    $this->last_error = 'Truncated server response (timeout?)';
                    $this->disconnect();
                    return [ false, $untagged, null, 'truncated response' ];
                }
                $rest = $this->read_line() ?? '';
                if ( $want_literal ) {
                    $literal = $bytes;
                    $line   .= $rest;
                } else {
                    $line = substr( $line, 0, -strlen( $m[0] ) ) . $this->quote( $bytes ) . $rest;
                }
            }

            if ( str_starts_with( $line, $tag . ' ' ) ) {
                $rest   = substr( $line, strlen( $tag ) + 1 );
                $status = strtoupper( strtok( $rest, ' ' ) );
                return [ $status === 'OK', $untagged, $literal, $rest ];
            }

            $untagged[] = $line;
        }
    }

    private function write_line( string $line ): void {
        // No CR/LF inside a command: it would start a second, injected command.
        fwrite( $this->stream, str_replace( [ "\r", "\n" ], '', $line ) . "\r\n" );
    }

    private function read_line(): ?string {
        // fgets() with a length stops early on very long lines (large
        // SEARCH results, BODYSTRUCTURE), so keep reading until the newline.
        $line = '';
        do {
            $chunk = fgets( $this->stream, 8192 );
            if ( $chunk === false ) {
                return $line === '' ? null : rtrim( $line, "\r\n" );
            }
            $line .= $chunk;
        } while ( ! str_ends_with( $chunk, "\n" ) );

        return rtrim( $line, "\r\n" );
    }

    private function read_bytes( int $count ): string {
        $data = '';
        while ( strlen( $data ) < $count ) {
            $chunk = fread( $this->stream, $count - strlen( $data ) );
            if ( $chunk === false || $chunk === '' ) {
                break;
            }
            $data .= $chunk;
        }
        return $data;
    }

    private function disconnect(): void {
        if ( $this->stream !== null ) {
            fclose( $this->stream );
            $this->stream = null;
        }
    }
}
