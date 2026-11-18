<?php

declare(strict_types=1);

namespace BounceMailHandler;

/**
 * Backwards-compatible adapter around the shared EME IMAP client.
 *
 * BounceMailHandler keeps its existing camelCase API, but the message numbers
 * returned by search() and passed to fetch*() are now UIDs. That makes them
 * stable for the whole session and matches the shared client's contract.
 */
class BounceIMAP
{
    private \EventsMadeEasy\ImapClient $client;

    public function __construct()
    {
        $this->client = new \EventsMadeEasy\ImapClient();
    }

    public function getLastError(): string
    {
        return $this->client->get_last_error();
    }

    public function connect(string $host, int $port, string $encryption = 'ssl', int $timeout = 15): bool
    {
        return $this->client->connect($host, $port, $encryption, $timeout);
    }

    public function login(string $username, string $password): bool
    {
        return $this->client->login($username, $password);
    }

    /**
     * Opens the mailbox read-only (EXAMINE, not SELECT).
     *
     * @return int|false number of existing messages, or false on failure
     */
    public function openMailboxReadOnly(string $mailbox = 'INBOX')
    {
        return $this->client->open_mailbox_read_only($mailbox);
    }

    /**
     * @param string[] $criteria e.g. ['SINCE "10-Jul-2026"', 'HEADER "X-Bounce-Id" "abc"']
     * @return int[] message UIDs
     */
    public function search(array $criteria = ['ALL']): array
    {
        return $this->client->search($criteria);
    }

    public function fetchHeader(int $uid, ?int $maxBytes = null): ?string
    {
        return $this->client->fetch_header($uid, $maxBytes);
    }

    public function fetchSection(int $uid, string $section = '', ?int $maxBytes = null): ?string
    {
        return $this->client->fetch_section($uid, $section, $maxBytes);
    }

    /**
     * @return array<string, array{type: string, subtype: string, mimetype: string}>|null
     */
    public function fetchStructure(int $uid): ?array
    {
        return $this->client->fetch_structure($uid);
    }

    public function logout(): void
    {
        $this->client->logout();
    }
}
