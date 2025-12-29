<?php
function django_pbkdf2_sha256($password, $salt = null, $iterations = 260000) {
    if (!$salt) {
        $salt = bin2hex(random_bytes(12));
    }
    $hash = base64_encode(hash_pbkdf2("sha256", $password, $salt, $iterations, 32, true));
    return "pbkdf2_sha256\$$iterations\$$salt\$$hash";
}
