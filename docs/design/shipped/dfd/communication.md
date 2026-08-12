# DFD Level 2: Communication (As-Built)

Drills into process 5.0 from the
[system-wide Level 1 DFD](../data-flow-diagram-level1.md). The dashed
line is deliberate, direct chat between users bypasses this API
entirely and goes straight to Firebase.

```mermaid
flowchart TB
    User((User))
    OtherProc[[Auth / Project / Collaboration / Admin<br/>processes elsewhere]]
    Jitsi[Jitsi / Prosody]
    FB[(Firebase RTDB)]

    subgraph Comm["Communication — Level 2"]
        P1[5.1 Notifications]
        P2[5.2 Video Call Lifecycle]
        P3[5.3 Call Room Approval]
    end

    D1[(notifications,<br/>notification_preferences)]
    D2[(video_calls, call_participants)]

    OtherProc -->|trigger event| P1 --> D1
    P1 -->|push| FB
    User -->|read/preferences| P1
    User <-->|chat directly, bypasses this API| FB

    User -->|initiate/join/leave/end/cancel| P2 --> D2
    P2 -->|mint JWT| P3
    Jitsi -->|reserve room, verify participant| P3 --> D2
```
