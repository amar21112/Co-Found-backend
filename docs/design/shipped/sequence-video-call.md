# Sequence: Video Call Room Approval (As-Built)

Not in the original 10 diagrams, video calls are a real V1 feature so it's
included here. Laravel doesn't host the call, it just decides whether Jicofo
(Jitsi's conference focus) is allowed to create the room, by implementing the
[Jicofo reservation API](https://github.com/jitsi/jicofo/blob/master/doc/reservation.md).
On join, it mints a short-lived JWT tied to a `jti` stored on the participant
row, so rotating the `jti` (on reconnect) silently invalidates any still-open
token from a previous session.

```mermaid
sequenceDiagram
    participant U as User
    participant L as Laravel API
    participant P as Prosody / Jicofo
    participant M as mod_cofound_access

    U->>L: POST /calls/{id}/join
    L->>L: check call status + participant capacity
    L->>L: mint JWT (jti stored on participant row)
    L-->>U: 200 { join_token }
    U->>P: connect to room with JWT
    P->>L: POST /jitsi/conference (mod_reservations)
    L-->>P: 200 { max_occupants, duration }
    P->>M: participant joining MUC
    M->>L: POST /jitsi/participant/verify (auth.jitsi)
    L-->>M: 200 { allowed: true } or 403
```
