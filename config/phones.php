<?php

return [
    'LY' => [
        'code' => 'LY',
        'key' => '218',
        'local_key' => '0',
        'regex' => "/^(?<key>(\+|00)?218|0)(?<provider>9[1-5])(?<digits>\d{7})$/",
        'all_keys' => [
            '218', '+218', '00218', '0',
        ],
    ],
    'EG' => [
        'code' => 'EG',
        'key' => '20',
        'local_key' => '0',
        'regex' => "/^(?<key>(\+)?20|0)(?<provider>1[0125])(?<digits>\d{8})$/",
        'all_keys' => [
            '+20', '20', '0', '0020',
        ],
    ],
    'KSA' => [
        'code' => 'KSA',
        'key' => '966',
        'local_key' => '0',
        'regex' => "/^(?<key>(\+|00)?966|0)?(?<provider>5)(?<digits>\d{8})$/",
        'all_keys' => [
            '+966', '00966', '966', '0',
        ],
    ],
    'AE' => [
        'code' => 'AE',
        'key' => '971',
        'local_key' => '0',

        'regex' => "/^(?<key>(\+|00)?971|0)?(?<provider>5[05])(?<digits>\d{7})$/",
        'all_keys' => [
            '+971', '00971', '971', '0',
        ],
    ],
    'KW' => [
        'code' => 'KW',
        'key' => '965',
        'local_key' => '0',

        'regex' => "/^(?<key>(\+|00)?965|0)?(?<provider>[6|9])(?<digits>\d{7})$/",
        'all_keys' => [
            '+965', '00965', '965', '0',
        ],
    ],
    'BH' => [
        'code' => 'BH',
        'key' => '973',
        'local_key' => '0',

        'regex' => "/^(?<key>(\+|00)?973|0)?(?<provider>3[2-9])(?<digits>\d{7})$/",
        'all_keys' => [
            '+973', '00973', '973', '0',
        ],
    ],
    'QA' => [
        'code' => 'QA',
        'key' => '974',
        'local_key' => '0',

        'regex' => "/^(?<key>(\+|00)?974|0)?(?<provider>[5|6]\d{1})(?<digits>\d{6})$/",
        'all_keys' => [
            '+974', '00974', '974', '0',
        ],
    ],
    'OM' => [
        'code' => 'OM',
        'key' => '968',
        'local_key' => '0',
        'regex' => "/^(?<key>(\+|00)?968|0)?(?<provider>[7|9]\d{1})(?<digits>\d{6})$/",
        'all_keys' => [
            '+968', '00968', '968', '0',
        ],
    ],
];
