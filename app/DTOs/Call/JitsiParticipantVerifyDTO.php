<?php

namespace App\DTOs\Call;

/**
 * Data sent by mod_cofound_access on every MUC occupant join.
 *
 * Fields:
 *   room_name — Jitsi room name (e.g. "cofound-abc123")
 *   user_id   — UUID from session.jitsi_meet_context_user.id (JWT context claim)
 *   jti       — JWT ID from session.jitsi_meet_context_user.jti
 *               Used to distinguish reconnect (same jti) from token sharing
 *               (different jti on an already-active participant row).
 */
final readonly class JitsiParticipantVerifyDTO
{
    public function __construct(
        public string $roomName,
        public string $userId,
        public string $jti,
    ) {}
}
