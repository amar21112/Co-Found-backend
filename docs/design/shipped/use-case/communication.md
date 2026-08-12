# Use Case Diagram: Communication (As-Built)

"Send / Receive Messages" is shown dashed on purpose, it's a real thing
users do, but it doesn't go through this API at all, the client talks to
Firebase directly. "Reserve Conference Room" and "Verify Participant"
are actual endpoints (`/jitsi/conference`, `/jitsi/participant/verify`),
but the caller is Prosody, not a person, hence the separate system actor.

```mermaid
flowchart LR
    User([Regular User])
    Sys([Prosody / Jicofo<br/>system actor, not human])

    subgraph Notif["Notifications"]
        UC1((List Notifications))
        UC2((Mark Notification Read))
        UC3((Mark All Read))
        UC4((View / Update Preferences))
    end

    subgraph Chat["Chat"]
        UC5((Send / Receive Messages<br/>direct Firebase, not this API))
    end

    subgraph Calls["Video Calls"]
        UC6((List Calls))
        UC7((Initiate Call))
        UC8((Join Call))
        UC9((Leave Call))
        UC10((End Call))
        UC11((Cancel Call))
    end

    subgraph Reserve["Call Room Approval — system only"]
        UC12((Reserve Conference Room))
        UC13((Verify Participant))
    end

    User --> UC1
    User --> UC2
    User --> UC3
    User --> UC4
    User -.->|not via this API| UC5
    User --> UC6
    User --> UC7
    User --> UC8
    User --> UC9
    User --> UC10
    User --> UC11
    Sys --> UC12
    Sys --> UC13
```
