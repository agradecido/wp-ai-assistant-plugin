<?php
declare( strict_types=1 );

namespace WPAIChatbot\Utils\Session;

class Session {
	/**
	 * Returns a UUID v4 stored in the `wp_ai_sid` cookie.
	 * If the cookie is not set or invalid, a new UUID v4 is generated and set in the cookie.
	 *
	 * @return string The session ID (UUID v4).
	 */
	public static function get_session_id(): string {
		$cookie = 'wp_ai_sid';

		if ( isset( $_COOKIE[ $cookie ] ) && preg_match( '/^[0-9a-f-]{36}$/i', $_COOKIE[ $cookie ] ) ) {
			return $_COOKIE[ $cookie ];
		}

		$sid = wp_generate_uuid4();
		setcookie(
			$cookie,
			$sid,
			array(
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ?: '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		return $sid;
	}
}
