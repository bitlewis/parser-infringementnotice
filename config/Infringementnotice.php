<?php

return [
    'parser' => [
        'name'          => 'InfringementNotice',
        'enabled'       => true,
        'sender_map'    => [
            '/notice@infringementnotice.ca/',
        ],
        'body_map'      => [
            //
        ],
    ],

    'feeds' => [
        'default' => [
            'class'     => 'COPYRIGHT_INFRINGEMENT',
            'type'      => 'ABUSE',
            'enabled'   => true,
            'fields'    => [
                'Type',
                'Port',
                'IP_Address',
                'TimeStamp'
            ],
        ],
    ],
];
