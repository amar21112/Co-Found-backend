<?php

namespace App\DTOs\Call;

/**
 * Data parsed from Prosody's mod_reservations POST /conference request.
 *
 * mod_reservations sends form-encoded body (not JSON):
 *   name       = room name (e.g. "cofound-abc123")
 *   start_time = ISO datetime string
 *   mail_owner = JID of the room creator
 */
final readonly class JitsiReservationDTO
{
    public function __construct(
        public string $roomName,
        public string $startTime,
        public string $mailOwner,
    ) {}
}
