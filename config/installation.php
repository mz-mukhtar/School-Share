<?php

return [
    'enabled' => (bool) env('INSTALL_ENABLED', false),
    'token_hash' => env('INSTALL_TOKEN_HASH', ''),
    'require_https' => true,
    'root' => base_path(),
    'public_path' => public_path(),
];
