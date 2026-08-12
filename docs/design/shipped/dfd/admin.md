# DFD Level 2: Administration (As-Built)

Drills into process 8.0 from the
[system-wide Level 1 DFD](../data-flow-diagram-level1.md). The
`identity_verifications` and `users` data stores are dashed, they're
owned by the Authentication & Authorization module, this process only
reads and updates them.

```mermaid
flowchart TB
    User((User))
    ModAdmin((Moderator / Admin))
    OCR[OCR Service]
    ML[ML Service]

    subgraph Admin["Administration — Level 2"]
        P1[8.1 Reports]
        P2[8.2 Verification Review]
        P3[8.3 Restrictions]
        P4[8.4 Content Moderation]
        P5[8.5 Audit Logs]
        P6[8.6 User Management]
        P7[8.7 System Settings]
        P8[8.8 ML Training Data]
    end

    D1[(reports)]
    D2[(identity_verifications —<br/>owned by Auth module)]
    D3[(user_restrictions)]
    D4[(content_moderation)]
    D5[(admin_actions, system_logs,<br/>analytics_events)]
    D6[(users — owned by Auth module)]
    D7[(system_settings,<br/>configuration_history)]

    User -->|file/view/withdraw own| P1 --> D1
    ModAdmin -->|review/resolve| P1 --> D1
    ModAdmin -->|claim/escalate/decide| P2 --> D2
    OCR -.->|enrichment data, from submission| P2
    ModAdmin -->|issue/lift| P3 --> D3
    ModAdmin -->|log action| P4 --> D4
    P1 & P2 & P3 & P4 & P6 & P7 -->|write| D5
    ModAdmin -->|view| P5 --> D5
    ModAdmin -->|view/update/delete| P6 --> D6
    ModAdmin -->|view/update, view history| P7 --> D7
    ModAdmin -->|stats/generate/export| P8 --> ML
```
