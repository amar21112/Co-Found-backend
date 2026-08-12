# Use Case Diagram: Project Management (As-Built)

"Project Owner" isn't a real role, it's shorthand here for "the Regular
User who owns this particular project," enforced by policy checks
(`project.owner_id === user.id`), not by `UserRole`.

```mermaid
flowchart LR
    Guest([Guest])
    User([Regular User])
    Owner([Project Owner<br/>a Regular User who owns the project])

    subgraph Browse["Discovery"]
        UC1((Browse Projects))
        UC2((View Project Details))
        UC3((View Roles / Milestones / Team))
    end

    subgraph Manage["Project Lifecycle"]
        UC4((Create Project))
        UC5((Update Project))
        UC6((Delete Project))
        UC7((View My Projects))
        UC8((Manage Required Skills))
        UC9((Manage Roles))
        UC10((Manage Milestones))
    end

    subgraph Team["Team"]
        UC11((Leave Team))
        UC12((Update / Remove Team Member))
    end

    subgraph App["Applications"]
        UC13((Apply to Project))
        UC14((Review Application))
        UC15((View / Withdraw My Application))
    end

    Guest --> UC1
    Guest --> UC2
    Guest --> UC3
    User --> UC4
    User --> UC7
    User --> UC11
    User --> UC13
    User --> UC15
    Owner --> UC5
    Owner --> UC6
    Owner --> UC8
    Owner --> UC9
    Owner --> UC10
    Owner --> UC12
    Owner --> UC14
```
