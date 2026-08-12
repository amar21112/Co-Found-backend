# ERD: Communication (As-Built)

Chat itself has no tables here; conversations and messages moved to
Firebase Realtime Database (see the `drop_mysql_chat_tables` migration).
`video_calls.conversation_id` is a plain string column, not a foreign
key; it holds a Firebase key, not a row in this database. `call_type`
is constrained at the database level (a CHECK constraint) so that
exactly one of `conversation_id` / `project_id` is set, never both.

```mermaid
erDiagram
    USERS ||--o{ VIDEO_CALLS : "initiates"
    PROJECTS |o--o{ VIDEO_CALLS : "hosts"
    VIDEO_CALLS ||--o{ CALL_PARTICIPANTS : "has"
    USERS ||--o{ CALL_PARTICIPANTS : "joins"
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o| NOTIFICATION_PREFERENCES : "sets"

    VIDEO_CALLS {
        uuid id PK
        enum call_type "conversation|project"
        string conversation_id "Firebase key, no FK"
        uuid project_id FK
        uuid initiated_by FK
        string room_name UK
        string room_url
        timestamp start_time
        timestamp end_time
        int duration_seconds
        enum status
        string recording_url
        timestamp created_at
        timestamp updated_at
    }
    CALL_PARTICIPANTS {
        uuid id PK
        uuid call_id FK
        uuid user_id FK
        timestamp joined_at
        timestamp left_at
        int duration_seconds
        enum role
        uuid active_token_jti
    }
    NOTIFICATIONS {
        uuid id PK
        uuid user_id FK
        string type
        string title
        text body
        json data
        enum priority
        bool read
        timestamp read_at
        timestamp delivered_at
        timestamp created_at
        timestamp updated_at
    }
    NOTIFICATION_PREFERENCES {
        uuid id PK
        uuid user_id FK,UK
        bool platform_notifications
        bool email_notifications
        bool push_notifications
        enum notification_digest
        time quiet_hours_start
        time quiet_hours_end
        string quiet_hours_timezone
        json preferences
        timestamp updated_at
    }
    USERS {
        uuid id PK "defined fully in Authentication & Authorization ERD"
    }
    PROJECTS {
        uuid id PK "defined fully in Project Management ERD"
    }
```
