# DFD Level 2: Project Management (As-Built)

Drills into process 3.0 from the
[system-wide Level 1 DFD](../data-flow-diagram-level1.md).
Worth noting: accepting a **project application** (3.4) does
automatically create a team membership, a DB transaction in
`ProjectApplicationService::review()` does both in one step. That's the
opposite of what happens when accepting a **collaboration invitation**
(see the Collaboration DFD), which doesn't.

```mermaid
flowchart TB
    User((User))
    Owner((Project Owner))

    subgraph Proj["Project Management — Level 2"]
        P1[3.1 Project CRUD]
        P2[3.2 Skills / Roles / Milestones]
        P3[3.3 Team Management]
        P4[3.4 Applications]
    end

    D1[(projects, project_skills,<br/>project_roles, project_milestones)]
    D2[(project_team_members)]
    D3[(project_applications,<br/>application_skills)]

    User -->|browse| P1 --> D1
    Owner -->|create/update/delete| P1 --> D1
    Owner -->|configure| P2 --> D1
    Owner -->|manage roster| P3 --> D2
    User -->|leave| P3 --> D2
    User -->|apply| P4 --> D3
    Owner -->|review| P4 --> D3
    P4 -->|accepted| P3
```
