# DFD Level 2: Collaboration (As-Built)

Drills into process 4.0 from the
[system-wide Level 1 DFD](../data-flow-diagram-level1.md).
Accepting a **collaboration invitation** only updates its status and
notifies the sender, it does not touch `project_team_members`. Compare
with the Project Management DFD, where accepting an application does.

```mermaid
flowchart TB
    User((User))
    ML[ML Service]

    subgraph Collab["Collaboration — Level 2"]
        P1[4.1 Connections]
        P2[4.2 Invitations]
        P3[4.3 Matching]
        P4[4.4 Ratings]
    end

    D1[(user_connections)]
    D2[(collaboration_invitations)]
    D3[(matches, match_feedback)]
    D4[(collaboration_ratings)]

    User -->|request/accept/reject/block| P1 --> D1
    User -->|send/respond/withdraw| P2 --> D2
    ML -->|scored pairs| P3 --> D3
    User -->|view/save/feedback| P3
    User -->|rate collaborator| P4 --> D4
```
