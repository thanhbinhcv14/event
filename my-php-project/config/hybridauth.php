<?php
return [
    'callback' => 'http://localhost/event/my-php-project/public/social-callback.php',
    'providers' => [
        'Google' => [
            'enabled' => true,
            'keys'    => [
                'id'     => 'GOOGLE_CLIENT_ID',
                'secret' => 'GOOGLE_CLIENT_SECRET'
            ],
            'scope'   => 'email profile'
        ],
        'Facebook' => [
            'enabled' => true,
            'keys'    => [
                'id'     => 'FACEBOOK_APP_ID',
                'secret' => 'FACEBOOK_APP_SECRET'
            ],
            'scope'   => 'email'
        ]
    ]
];