<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email subjects & labels
    | Used in every Mailable — wrap in __() to support future locales.
    |--------------------------------------------------------------------------
    */

    'verify' => [
        'subject' => 'Verify your Co-Found email address',
    ],

    'reset' => [
        'subject' => 'Reset your Co-Found password',
    ],

    'application_received' => [
        'subject' => 'New application for ":project"',
    ],

    'application_accepted' => [
        'subject' => 'You\'re in! Your application to ":project" was accepted',
    ],

    'application_rejected' => [
        'subject' => 'Update on your application to ":project"',
    ],

    'invitation_received' => [
        'subject' => ':sender invited you on Co-Found',
    ],

    'connection_request' => [
        'subject' => ':requester wants to connect with you',
    ],

    'invitation_types' => [
        'project_join'          => 'join a project',
        'team_invite'           => 'join their team',
        'collaboration_request' => 'collaborate',
        'mentorship'            => 'a mentorship',
        'co_founder'            => 'co-found together',
        'default'               => 'collaborate',
    ],

];
