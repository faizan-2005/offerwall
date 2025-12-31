<?php
// Basic configuration - copy to .env or set environment vars in production
function DB_CONFIG() {
    return [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'dbname' => getenv('DB_NAME') ?: 'offerwall',
    ];
}

// Security settings
function APP_CONFIG() {
    return [
        'postback_secret' => getenv('POSTBACK_SECRET') ?: getenv('POSTBACK_SECRET') ?: 'CHANGE_ME',
        'token_ttl' => 60*60*24*14, // 14 days
    ];
}
