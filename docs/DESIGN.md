# Design Documentation

Two sets of diagrams, same diagram types, different points in time.

- **[`design/`](design/)** — the original design-phase diagrams: use case,
  context, data flow, class diagram, database schema, and five sequence
  diagrams. Made during planning, before any code was written.
- **[`design/shipped/`](design/shipped/)** — the same diagram types, redrawn
  from the actual routes, migrations, and models. What's in the repo today.

This is version 1 of a graduation project, not a production system with a
funded roadmap, so scope was cut where time and complexity demanded it. The
two sets aren't meant to match line for line, comparing them is basically a
changelog of what shipped versus what was originally planned.

## As-built: one diagram per module

The system has five modules (Authentication & Authorization, Project
Management, Collaboration, Communication, Administration). ERD, class
diagram, and use case diagram are each split five ways, one per module,
showing every table, every model field, and every real method. The two
exceptions:

- **Context diagram** stays as one file, it's inherently a whole-system
  external view (who/what talks to the API), there's no natural per-module
  split for that.
- **DFD** is two-level, matching standard DFD practice: one Level 1 diagram
  showing the whole system as 8 process boxes, plus five Level 2 diagrams
  that each drill into one module's internal sub-processes.

Cross-module foreign keys show up as a named stub entity (e.g. `PROJECTS`
appears as a bare stub in the Collaboration ERD) with a pointer to the
module that actually owns it, so each file is self-contained without
duplicating full definitions.

## What changed between planned and shipped

- Chat moved from a MySQL + WebSocket design to Firebase Realtime Database
  (see the `drop_mysql_chat_tables` migration for exactly where that happened)
- Auth moved from JWT to Laravel Sanctum bearer tokens
- Matching moved from an in-process "Matching Engine" + Redis cache to a
  separate ML microservice called over HTTP, with no cache layer
- The proposed payment system was never built
- The schema grew substantially: the original diagrams show roughly 7
  entities, the shipped schema has 32 models across 53 migrations
- Accepting a **collaboration invitation** does *not* auto-create a project
  team membership (separate manual step), but accepting a **project
  application** *does*, both are "someone joins a team" on paper, they
  behave differently in the code. See the Project Management and
  Collaboration DFDs for where this splits.

## Original design (planning phase)

| Diagram                     | File                                                                             |
|-----------------------------|----------------------------------------------------------------------------------|
| Use case diagram            | [`design/use-case-diagram.png`](design/use-case-diagram.png)                     |
| Context diagram             | [`design/context-diagram.png`](design/context-diagram.png)                       |
| Data flow diagram (level 1) | [`design/data-flow-diagram-level1.png`](design/data-flow-diagram-level1.png)     |
| Class diagram               | [`design/class-diagram.png`](design/class-diagram.png)                           |
| Database schema             | [`design/database-schema-erd.png`](design/database-schema-erd.png)               |
| Sequence: user registration | [`design/sequence-user-registration.png`](design/sequence-user-registration.png) |
| Sequence: chat              | [`design/sequence-chat.png`](design/sequence-chat.png)                           |
| Sequence: project creation  | [`design/sequence-project-creation.png`](design/sequence-project-creation.png)   |
| Sequence: smart matching    | [`design/sequence-smart-matching.png`](design/sequence-smart-matching.png)       |
| Sequence: admin moderation  | [`design/sequence-admin-moderation.png`](design/sequence-admin-moderation.png)   |

## As-built (version 1)

Each file is Mermaid source, GitHub renders it automatically when you open
the file.

**ERD** — every column, straight from `database/migrations/`:

| Module                         | File                                                                         |
|--------------------------------|------------------------------------------------------------------------------|
| Authentication & Authorization | [`design/shipped/erd/auth.md`](design/shipped/erd/auth.md)                   |
| Project Management             | [`design/shipped/erd/project.md`](design/shipped/erd/project.md)             |
| Collaboration                  | [`design/shipped/erd/collaboration.md`](design/shipped/erd/collaboration.md) |
| Communication                  | [`design/shipped/erd/communication.md`](design/shipped/erd/communication.md) |
| Administration                 | [`design/shipped/erd/admin.md`](design/shipped/erd/admin.md)                 |

**Class diagram** — real fields, real enums, real methods, from `app/Models/*.php`:

| Module                         | File                                                                                             |
|--------------------------------|--------------------------------------------------------------------------------------------------|
| Authentication & Authorization | [`design/shipped/class-diagram/auth.md`](design/shipped/class-diagram/auth.md)                   |
| Project Management             | [`design/shipped/class-diagram/project.md`](design/shipped/class-diagram/project.md)             |
| Collaboration                  | [`design/shipped/class-diagram/collaboration.md`](design/shipped/class-diagram/collaboration.md) |
| Communication                  | [`design/shipped/class-diagram/communication.md`](design/shipped/class-diagram/communication.md) |
| Administration                 | [`design/shipped/class-diagram/admin.md`](design/shipped/class-diagram/admin.md)                 |

**Use case diagram** — every use case maps to a real route:

| Module                         | File                                                                                   |
|--------------------------------|----------------------------------------------------------------------------------------|
| Authentication & Authorization | [`design/shipped/use-case/auth.md`](design/shipped/use-case/auth.md)                   |
| Project Management             | [`design/shipped/use-case/project.md`](design/shipped/use-case/project.md)             |
| Collaboration                  | [`design/shipped/use-case/collaboration.md`](design/shipped/use-case/collaboration.md) |
| Communication                  | [`design/shipped/use-case/communication.md`](design/shipped/use-case/communication.md) |
| Administration                 | [`design/shipped/use-case/admin.md`](design/shipped/use-case/admin.md)                 |

**Data flow diagram** — Level 1 (whole system) plus Level 2 (per module):

| Scope                                   | File                                                                                       |
|-----------------------------------------|--------------------------------------------------------------------------------------------|
| Level 1: whole system                   | [`design/shipped/data-flow-diagram-level1.md`](design/shipped/data-flow-diagram-level1.md) |
| Level 2: Authentication & Authorization | [`design/shipped/dfd/auth.md`](design/shipped/dfd/auth.md)                                 |
| Level 2: Project Management             | [`design/shipped/dfd/project.md`](design/shipped/dfd/project.md)                           |
| Level 2: Collaboration                  | [`design/shipped/dfd/collaboration.md`](design/shipped/dfd/collaboration.md)               |
| Level 2: Communication                  | [`design/shipped/dfd/communication.md`](design/shipped/dfd/communication.md)               |
| Level 2: Administration                 | [`design/shipped/dfd/admin.md`](design/shipped/dfd/admin.md)                               |

**Context diagram** — one file, whole-system external view:

[`design/shipped/context-diagram.md`](design/shipped/context-diagram.md)

**Sequence diagrams:**

| Flow                                                       | File                                                                                           |
|------------------------------------------------------------|------------------------------------------------------------------------------------------------|
| User registration                                          | [`design/shipped/sequence-user-registration.md`](design/shipped/sequence-user-registration.md) |
| Chat                                                       | [`design/shipped/sequence-chat.md`](design/shipped/sequence-chat.md)                           |
| Project creation & invitation                              | [`design/shipped/sequence-project-creation.md`](design/shipped/sequence-project-creation.md)   |
| Smart matching                                             | [`design/shipped/sequence-smart-matching.md`](design/shipped/sequence-smart-matching.md)       |
| Admin moderation                                           | [`design/shipped/sequence-admin-moderation.md`](design/shipped/sequence-admin-moderation.md)   |
| Video call room approval *(bonus, not in the original 10)* | [`design/shipped/sequence-video-call.md`](design/shipped/sequence-video-call.md)               |
