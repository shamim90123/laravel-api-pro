<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the bcrypt algorithm is
    | used; however, you may use other drivers supported by Laravel.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => 'bcrypt',

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | These options configure the Bcrypt algorithm when hashing passwords
    | using the Bcrypt driver. You can modify the "rounds" value to adjust
    | the hashing cost for your environment.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | These options configure the Argon2 hashing algorithms. You may change
    | the "memory", "threads", and "time" cost values according to the
    | performance profile of your servers.
    |
    */

    'argon' => [
        'memory'  => 65536,
        'threads' => 1,
        'time'    => 4,
    ],

];
