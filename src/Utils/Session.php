<?php
declare(strict_types=1);

namespace WPAIS\Utils;

class Session
{
    public static function get_session_id(): string
    {
        $cookie = 'wp_ai_sid';
        if ( isset($_COOKIE[$cookie]) && preg_match('/^[0-9a-f-]{36}$/i', $_COOKIE[$cookie]) ) {
            return $_COOKIE[$cookie];
        }

        $sid = wp_generate_uuid4();
        setcookie(
            $cookie,
            $sid,
            [
                'expires'  => time() + YEAR_IN_SECONDS,
                'path'     => COOKIEPATH ?: '/',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        return $sid;
    }
}
