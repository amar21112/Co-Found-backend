# ERD: Authentication & Authorization (As-Built)

All 8 tables owned by this module, every column, matching
`database/migrations/` including later alter migrations (e.g.
`id_card_number_hash` was added after the original create, and
`date_of_birth` was made nullable later; both reflected here).

```mermaid
erDiagram
    USERS ||--o| IDENTITY_VERIFICATIONS : "submits"
    USERS ||--o{ VERIFICATION_REVIEWS : "performs"
    IDENTITY_VERIFICATIONS ||--o{ VERIFICATION_REVIEWS : "reviewed in"
    USERS ||--o{ VERIFICATION_ATTEMPTS : "makes"
    USERS ||--o{ USER_SKILLS : "has"
    USER_SKILLS ||--o{ SKILL_ENDORSEMENTS : "receives"
    USERS ||--o{ SKILL_ENDORSEMENTS : "gives"
    USERS ||--o{ PORTFOLIO_ITEMS : "owns"
    PORTFOLIO_ITEMS ||--o{ PORTFOLIO_SKILLS : "tags"

    USERS {
        uuid id PK
        string email UK
        string username UK
        string password
        string full_name
        string profile_picture_url
        text bio
        string location
        string website_url
        string linkedin_url
        string github_url
        enum role
        enum account_status
        bool email_verified
        bool identity_verified
        enum identity_verification_level
        string email_verification_token
        timestamp email_verification_expires
        timestamp last_login_at
        ip last_login_ip
        int login_attempts
        timestamp locked_until
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }
    IDENTITY_VERIFICATIONS {
        uuid id PK
        uuid user_id FK,UK
        string id_card_image_front
        string id_card_image_back
        string id_card_number "encrypted"
        string id_card_number_hash UK
        string full_name_on_card
        date date_of_birth
        string nationality
        date expiry_date
        enum submission_method
        ip ip_address
        text user_agent
        text device_info
        bool liveness_check_passed
        json liveness_check_data
        decimal face_match_score
        enum verification_status
        text rejection_reason
        timestamp created_at
        timestamp updated_at
    }
    VERIFICATION_REVIEWS {
        uuid id PK
        uuid verification_id FK
        uuid reviewer_id FK
        enum review_action
        text review_notes
        enum rejection_reason_category
        timestamp reviewed_at
        bool automated_checks_passed
        json automated_checks_data
    }
    VERIFICATION_ATTEMPTS {
        uuid id PK
        uuid user_id FK
        int attempt_number
        json submission_data
        enum result
        string failure_reason
        ip ip_address
        timestamp created_at
        timestamp updated_at
    }
    USER_SKILLS {
        uuid id PK
        uuid user_id FK
        string skill_name UK
        int proficiency_level "1-5"
        decimal years_experience
        bool is_approved
        timestamp created_at
        timestamp updated_at
    }
    SKILL_ENDORSEMENTS {
        uuid id PK
        uuid user_skill_id FK
        uuid endorsed_by_user_id FK
        timestamp created_at
        timestamp updated_at
    }
    PORTFOLIO_ITEMS {
        uuid id PK
        uuid user_id FK
        string title
        text description
        string file_url
        string thumbnail_url
        enum item_type
        string external_url
        enum visibility
        bool is_featured
        timestamp created_at
        timestamp updated_at
    }
    PORTFOLIO_SKILLS {
        uuid id PK
        uuid portfolio_item_id FK
        string skill_name UK
    }
```
