# Class Diagram: Authentication & Authorization (As-Built)

Attributes match the schema in [`erd/auth.md`](../erd/auth.md), minus
`created_at`/`updated_at`/`deleted_at` (present on every model, already
covered by the ERD). Enum-typed columns use the real PHP enum class from
`app/Enums/` where one exists (`UserRole`, `AccountStatus`, etc.), plain
`string` otherwise. Methods are the actual non-relationship public
methods on each model, taken from `app/Models/*.php`.

```mermaid
classDiagram
    class User {
        +string id
        +string email
        +string username
        +string password
        +string full_name
        +string profile_picture_url
        +string bio
        +string location
        +string website_url
        +string linkedin_url
        +string github_url
        +UserRole role
        +AccountStatus account_status
        +bool email_verified
        +bool identity_verified
        +IdentityVerificationLevel identity_verification_level
        +string email_verification_token
        +datetime email_verification_expires
        +datetime last_login_at
        +string last_login_ip
        +int login_attempts
        +datetime locked_until
        +isAdmin() bool
        +isModerator() bool
        +isGuest() bool
        +isRegularUser() bool
        +isActive() bool
        +isPending() bool
        +isSuspended() bool
        +isBanned() bool
        +isBlocked() bool
        +canAuthenticate() bool
        +isEmailVerified() bool
        +isIdentityVerified() bool
        +isFullyVerified() bool
        +isLocked() bool
    }
    class IdentityVerification {
        +string id
        +string user_id
        +string id_card_image_front
        +string id_card_image_back
        +string id_card_number
        +string id_card_number_hash
        +string full_name_on_card
        +date date_of_birth
        +string nationality
        +date expiry_date
        +string submission_method
        +string ip_address
        +string user_agent
        +string device_info
        +bool liveness_check_passed
        +array liveness_check_data
        +float face_match_score
        +IdentityVerificationStatus verification_status
        +string rejection_reason
        +isPending() bool
        +isVerified() bool
        +isRejected() bool
        +isUnderReview() bool
        +isEscalated() bool
    }
    class VerificationReview {
        +string id
        +string verification_id
        +string reviewer_id
        +ReviewAction review_action
        +string review_notes
        +RejectionReasonCategory rejection_reason_category
        +datetime reviewed_at
        +bool automated_checks_passed
        +array automated_checks_data
    }
    class VerificationAttempt {
        +string id
        +string user_id
        +int attempt_number
        +array submission_data
        +string result
        +string failure_reason
        +string ip_address
    }
    class UserSkill {
        +string id
        +string user_id
        +string skill_name
        +int proficiency_level
        +float years_experience
        +bool is_approved
    }
    class SkillEndorsement {
        +string id
        +string user_skill_id
        +string endorsed_by_user_id
    }
    class PortfolioItem {
        +string id
        +string user_id
        +string title
        +string description
        +string file_url
        +string thumbnail_url
        +string item_type
        +string external_url
        +string visibility
        +bool is_featured
    }
    class PortfolioSkill {
        +string id
        +string portfolio_item_id
        +string skill_name
    }

    User "1" --> "0..1" IdentityVerification : submits
    User "1" --> "*" VerificationReview : performs
    IdentityVerification "1" --> "*" VerificationReview : reviewed in
    User "1" --> "*" VerificationAttempt : makes
    User "1" --> "*" UserSkill : has
    UserSkill "1" --> "*" SkillEndorsement : receives
    User "1" --> "*" SkillEndorsement : gives
    User "1" --> "*" PortfolioItem : owns
    PortfolioItem "1" --> "*" PortfolioSkill : tags
```
