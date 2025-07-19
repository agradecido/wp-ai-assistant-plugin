<?php
declare(strict_types=1);

namespace WPAIS\Utils;

class Session
{
    /**
     * Cookie name for anonymous session IDs.
     */
    private const COOKIE_NAME = 'wp_ai_sid';

    /**
     * Cookie expiration time (1 year).
     */
    private const COOKIE_EXPIRES = YEAR_IN_SECONDS;

    /**
     * UUID pattern for validation.
     */
    private const UUID_PATTERN = '/^[0-9a-f-]{36}$/i';

    /**
     * Prefix for logged-in user session IDs.
     */
    private const USER_PREFIX = 'user_';

    /**
     * Cached session ID to avoid multiple calculations.
     */
    private static ?string $cached_session_id = null;

    /**
     * Cached WordPress functions availability status.
     */
    private static ?bool $wp_functions_available = null;

    /**
     * Get the session ID for the current user or anonymous session.
     *
     * @return string The session ID.
     * @throws \RuntimeException If WordPress functions are not available.
     */
    public static function get_session_id(): string
    {
        // Return cached session ID if available (for performance)
        if ( self::$cached_session_id !== null ) {
            error_log( "Session Debug: Using cached sessionId=" . self::$cached_session_id );
            return self::$cached_session_id;
        }

        // Validate WordPress dependencies
        self::validate_wordpress_dependencies();

        // Return user-based session ID for logged-in users
        if ( is_user_logged_in() ) {
            self::$cached_session_id = self::USER_PREFIX . get_current_user_id();
            error_log( "Session Debug: User logged in, sessionId=" . self::$cached_session_id );
            return self::$cached_session_id;
        }

        // Debug cookie information
        error_log( "Session Debug: Cookie name=" . self::COOKIE_NAME );
        error_log( "Session Debug: Cookie exists=" . (isset($_COOKIE[self::COOKIE_NAME]) ? 'YES' : 'NO') );
        
        if ( isset($_COOKIE[self::COOKIE_NAME]) ) {
            error_log( "Session Debug: Cookie value=" . $_COOKIE[self::COOKIE_NAME] );
            error_log( "Session Debug: Cookie valid=" . (preg_match(self::UUID_PATTERN, $_COOKIE[self::COOKIE_NAME]) ? 'YES' : 'NO') );
        }

        // Check for existing valid cookie.
        if ( isset($_COOKIE[self::COOKIE_NAME]) && preg_match(self::UUID_PATTERN, $_COOKIE[self::COOKIE_NAME]) ) {
            self::$cached_session_id = $_COOKIE[self::COOKIE_NAME];
            error_log( "Session Debug: Using existing cookie sessionId=" . self::$cached_session_id );
            return self::$cached_session_id;
        }

        // Generate new session ID and set cookie.
        error_log( "Session Debug: Creating new session" );
        self::$cached_session_id = self::create_anonymous_session();
        error_log( "Session Debug: New sessionId=" . self::$cached_session_id );
        return self::$cached_session_id;
    }

    /**
     * Validate that required WordPress functions are available.
     *
     * @throws \RuntimeException If WordPress functions are not available.
     */
    private static function validate_wordpress_dependencies(): void
    {
        // Use cached result if available.
        if ( self::$wp_functions_available === true ) {
            return;
        }

        if ( self::$wp_functions_available === false ) {
            throw new \RuntimeException( 'WordPress functions are not available.' );
        }

        // Check and cache the result.
        if ( ! function_exists( 'is_user_logged_in' ) || ! function_exists( 'get_current_user_id' ) ) {
            self::$wp_functions_available = false;
            throw new \RuntimeException( 'WordPress user functions are not available.' );
        }

        if ( ! function_exists( 'wp_generate_uuid4' ) ) {
            self::$wp_functions_available = false;
            throw new \RuntimeException( 'WordPress UUID generation function is not available.' );
        }

        self::$wp_functions_available = true;
    }

    /**
     * Create a new anonymous session with cookie.
     *
     * @return string The generated session ID.
     */
    private static function create_anonymous_session(): string
    {
        $sid = wp_generate_uuid4();
        
        // Only set cookie if headers haven't been sent yet
        if ( ! headers_sent() ) {
            $cookie_config = self::get_cookie_config(time() + self::COOKIE_EXPIRES);
            error_log( "Session Debug: Cookie config=" . json_encode( $cookie_config ) );
            
            setcookie(self::COOKIE_NAME, $sid, $cookie_config);
            
            error_log("Session Debug: Cookie set with name=" . self::COOKIE_NAME);
        } else {
            error_log( "Session Debug: Headers already sent, cannot set cookie" );
        }

        return $sid;
    }

    /**
     * Check if the current session belongs to a logged-in user.
     *
     * @return bool True if user is logged in, false otherwise.
     */
    public static function is_user_session(): bool
    {
        return function_exists( 'is_user_logged_in' ) && is_user_logged_in();
    }

    /**
     * Get the user ID if the current session belongs to a logged-in user.
     *
     * @return int|null The user ID or null if anonymous session.
     */
    public static function get_user_id(): ?int
    {
        if ( self::is_user_session() ) {
            return get_current_user_id();
        }
        return null;
    }

    /**
     * Check if a session ID is valid (either user-based or UUID format).
     *
     * @param string $session_id The session ID to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function is_valid_session_id( string $session_id ): bool
    {
        // Check if it's a user session ID.
        if ( str_starts_with( $session_id, self::USER_PREFIX ) ) {
            $user_id = substr( $session_id, strlen( self::USER_PREFIX ) );
            return is_numeric( $user_id ) && (int) $user_id > 0;
        }

        // Check if it's a valid UUID.
        return preg_match( self::UUID_PATTERN, $session_id ) === 1;
    }

    /**
     * Clear the anonymous session cookie.
     *
     * @return bool True if cookie was cleared, false if not set.
     */
    public static function clear_anonymous_session(): bool
    {
        if ( !isset($_COOKIE[self::COOKIE_NAME] ) ) {
            return false;
        }

        // Only clear cookie if headers haven't been sent yet
        if ( ! headers_sent() ) {
            // Clear the cookie by setting it to expire in the past.
            setcookie( self::COOKIE_NAME, '', self::get_cookie_config( time() - 3600 ) );
        }

        unset( $_COOKIE[self::COOKIE_NAME] );
        
        // Clear cache if this was the cached session.
        if ( isset( $_COOKIE[self::COOKIE_NAME] ) 
            && self::$cached_session_id === $_COOKIE[self::COOKIE_NAME]
        ) {
            self::$cached_session_id = null;
        }
 
        return true;
    }

    /**
     * Clear the cached session ID (useful for testing or session changes).
     *
     * @return void
     */
    public static function clear_cache(): void
    {
        self::$cached_session_id = null;
        self::$wp_functions_available = null;
    }

    /**
     * Force regeneration of the current session ID.
     *
     * @return string The new session ID.
     */
    public static function regenerate_session(): string
    {
        // Clear cache first.
        self::clear_cache();
        
        // Clear existing anonymous session if any.
        self::clear_anonymous_session();
        
        // Generate new session
        return self::get_session_id();
    }

    /**
     * Get cookie configuration array for consistent cookie settings.
     *
     * @param int $expires Expiration time (default: current time + COOKIE_EXPIRES).
     * @return array Cookie configuration array.
     */
    private static function get_cookie_config( int $expires ): array
    {
        return [
            'expires'  => $expires,
            'path'     => defined( 'COOKIEPATH' ) ? ( COOKIEPATH ?: '/' ) : '/',
            'secure'   => function_exists( 'is_ssl' ) ? is_ssl() : isset( $_SERVER['HTTPS'] ),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    /**
     * Get comprehensive session information.
     *
     * @return array{
     *     session_id: string,
     *     is_user: bool,
     *     user_id: int|null,
     *     type: string
     * } Session information array.
     */
    public static function get_session_info(): array
    {
        $session_id = self::get_session_id();
        $is_user = self::is_user_session();
        
        return [
            'session_id' => $session_id,
            'is_user'    => $is_user,
            'user_id'    => $is_user ? get_current_user_id() : null,
            'type'       => $is_user ? 'user' : 'anonymous',
        ];
    }
}
