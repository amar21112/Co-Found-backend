<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Project Configuration
    |--------------------------------------------------------------------------
    |
    | All values come from your Firebase project settings.
    | Copy them into your .env file — never commit credentials directly.
    |
    | Required .env keys:
    |   FIREBASE_PROJECT_ID=your-project-id
    |   FIREBASE_DATABASE_URL=https://your-project-id-default-rtdb.firebaseio.com
    |   FIREBASE_CREDENTIALS=/path/to/service-account.json   ← absolute path or JSON string
    |   FIREBASE_STORAGE_BUCKET=your-project-id.appspot.com
    |
    */

    'project_id'     => env('FIREBASE_PROJECT_ID'),
    'database_url'   => env('FIREBASE_DATABASE_URL'),
    'credentials'    => env('FIREBASE_CREDENTIALS'),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),

    /*
    |--------------------------------------------------------------------------
    | Realtime Database Structure
    |--------------------------------------------------------------------------
    |
    | The RTDB tree used by this module.  MySQL is always the source of truth;
    | Firebase is a real-time projection of the latest state.
    |
    | conversations/{conversationId}/
    |   meta        → title, type, project_id, last_message_at, participant_ids[]
    |   messages/{messageId}/
    |     sender_id, content, type, created_at, is_edited, is_pinned,
    |     replied_to_id, deleted
    |   typing/{userId}  → true / null
    |   online/{userId}  → true / null
    |
    | notifications/{userId}/{notificationId}/
    |   type, title, body, data{}, priority, read, created_at
    |
    | presence/{userId}
    |   online, last_seen
    |
    */

    'paths' => [
        'conversations'  => 'conversations',
        'notifications'  => 'notifications',
        'presence'       => 'presence',
    ],
];
