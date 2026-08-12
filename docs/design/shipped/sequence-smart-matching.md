# Sequence: Smart Matching (As-Built)

The backend never scores matches itself, it just builds features and hands
them to the ML service.

```mermaid
sequenceDiagram
    participant S as Scheduler (02:00 daily)
    participant J as GenerateMatchesJob
    participant Svc as MlMatchingService
    participant ML as ML service (FastAPI)
    participant DB as MySQL

    S->>J: dispatch()
    J->>Svc: generateForAllUsers()
    Svc->>DB: load active users + skills
    Svc->>Svc: build MatchPairDTO[] (feature engineering)
    Svc->>ML: POST /predict/batch (shared secret)
    ML-->>Svc: relevant pairs + scores
    Svc->>DB: upsert into matches (MatchService::ingestBatch)
```

The same `generateForUser()` path also runs synchronously, outside the
nightly batch, right after `AdminVerificationService` approves a user's
identity verification.
