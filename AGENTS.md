# WordPress AI Assistant Plugin - Agent Architecture Documentation

## Overview

This document describes the agent architecture pattern implementation within the WordPress AI Assistant Plugin. The plugin follows clean code principles, SOLID design patterns, and implements a modular agent-based architecture for handling AI-powered chat interactions.

## Core Design Principles

### Clean Code Implementation
- **Single Responsibility Principle**: Each class handles one specific concern.
- **Open/Closed Principle**: Classes are open for extension, closed for modification.
- **Dependency Inversion**: High-level modules don't depend on low-level modules.
- **Guard Clauses**: Early exits prevent nested conditionals.
- **PHPDoc Comments**: All files, classes, properties, and methods are documented.

### Agent Architecture Pattern

The plugin implements an agent-based architecture where different components act as specialized agents:

## Primary Agents

### 1. Assistant Agent (`WPAIS\Api\Assistant`)

**Purpose**: Core communication agent with OpenAI Assistant API.

**Responsibilities**:
- Manages OpenAI API communication.
- Handles thread creation and message processing.
- Processes assistant responses and formats output.
- Maintains conversation context and state.

**Key Methods**:
```php
/**
 * Initialize the Assistant settings.
 */
public static function init(): void

/**
 * Query the AI assistant with a user message.
 *
 * @param string $query The user's query text.
 * @param string $thread_id Optional thread ID for continuing conversations.
 * @return array Response from the assistant or error.
 */
public static function query_assistant(string $query, string $thread_id = ''): array

/**
 * Get assistant information including the model being used.
 *
 * @return array Information about the assistant or error.
 */
public static function get_assistant_info(): array
```

**Guard Clause Implementation**:
```php
public static function get_assistant_info(): array {
    self::init();

    // Guard clause: Early exit if required configuration is missing.
    if (empty(self::$api_key) || empty(self::$assistant_id)) {
        Logger::error('Error: API key or Assistant ID is missing.');
        return [
            'error'   => true,
            'message' => 'Error: API key or Assistant ID is missing.',
        ];
    }

    // Main processing logic continues here.
    // ...
}
```

### 2. Thread Management Agent (`WPAIS\Domain\Thread\ThreadRepository`)

**Purpose**: Manages conversation threads and message persistence.

**Responsibilities**:
- Creates and manages conversation threads.
- Persists messages to WordPress database.
- Retrieves conversation history.
- Maintains thread state and metadata.

**Interface Contract**:
```php
/**
 * Thread repository interface defining conversation management operations.
 */
interface ThreadRepository {
    /**
     * Add a message to the specified thread.
     *
     * @param string $thread_id The thread identifier.
     * @param string $message The message content.
     * @param string $role The message role (user|assistant).
     * @return bool Success status.
     */
    public function addMessage(string $thread_id, string $message, string $role): bool;

    /**
     * Retrieve all messages for a specific thread.
     *
     * @param string $thread_id The thread identifier.
     * @return array Array of thread messages.
     */
    public function getThreadMessages(string $thread_id): array;
}
```

### 3. Quota Management Agent (`WPAIS\Domain\Quota\QuotaManager`)

**Purpose**: Controls and monitors API usage limits.

**Responsibilities**:
- Tracks daily API usage limits.
- Prevents quota overruns.
- Manages rate limiting policies.
- Provides usage analytics.

**Implementation Pattern**:
```php
/**
 * Quota management agent for controlling API usage.
 */
class QuotaManager {
    /**
     * Daily usage limit.
     *
     * @var int
     */
    private int $dailyLimit;

    /**
     * Quota repository instance.
     *
     * @var QuotaRepository
     */
    private QuotaRepository $repository;

    /**
     * Initialize quota manager with repository and limits.
     *
     * @param QuotaRepository $repository The quota data repository.
     * @param int $dailyLimit The daily usage limit.
     */
    public function __construct(QuotaRepository $repository, int $dailyLimit) {
        $this->repository = $repository;
        $this->dailyLimit = $dailyLimit;
    }

    /**
     * Check if quota allows new request.
     *
     * @return bool True if request is allowed, false otherwise.
     */
    public function canMakeRequest(): bool {
        // Guard clause: Early exit if usage exceeds limit.
        if ($this->getCurrentUsage() >= $this->dailyLimit) {
            Logger::log('Daily quota limit reached.');
            return false;
        }

        return true;
    }
}
```

### 4. Frontend Interface Agents

#### Chat Shortcode Agent (`WPAIS\Frontend\ChatShortcode`)

**Purpose**: Renders and manages frontend chat interface.

**Responsibilities**:
- Registers WordPress shortcode for chat interface.
- Enqueues required CSS and JavaScript assets.
- Handles shortcode attributes and configuration.
- Generates secure nonces for AJAX requests.

**Guard Clause Example**:
```php
/**
 * Render the chatbot shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered chatbot HTML.
 */
public static function render(array $atts): string {
    // Guard clause: Early exit if chatbot is disabled.
    if (!self::is_enabled()) {
        return self::get_disabled_message();
    }

    // Guard clause: Early exit if required configuration is missing.
    if (!self::has_valid_configuration()) {
        return self::get_configuration_error_message();
    }

    // Proceed with rendering chatbot interface.
    return self::load_chatbot_template($atts);
}
```

#### History Shortcode Agent (`WPAIS\Frontend\HistoryShortcode`)

**Purpose**: Displays conversation history interface.

**Responsibilities**:
- Renders conversation history listings.
- Provides thread continuation functionality.
- Manages pagination and filtering.
- Handles thread metadata display.

### 5. Administrative Agents

#### Settings Agent (`WPAIS\Admin\Settings`)

**Purpose**: Manages plugin configuration and admin interface.

**Responsibilities**:
- Registers admin menu pages and settings.
- Handles configuration validation and sanitization.
- Provides testing interface for admin users.
- Manages plugin activation and deactivation hooks.

#### Conversation Meta Box Agent (`WPAIS\Admin\ConversationMetaBox`)

**Purpose**: Provides WordPress admin interface for thread management.

**Responsibilities**:
- Displays conversation threads in admin panel.
- Provides thread management tools.
- Shows conversation analytics and metrics.
- Handles bulk operations on threads.

## Agent Communication Patterns

### 1. Dependency Injection Pattern

Agents receive their dependencies through constructor injection, promoting loose coupling:

```php
/**
 * Main plugin class coordinating all agents.
 */
class Plugin {
    /**
     * Quota management agent.
     *
     * @var QuotaManager
     */
    private QuotaManager $quotaManager;

    /**
     * Initialize plugin with dependency injection.
     */
    public function init(): void {
        // Guard clause: Early exit if already initialized.
        if ($this->isInitialized()) {
            return;
        }

        // Inject dependencies into agents.
        global $wpdb;
        $repo = new WPDBQuotaRepository($wpdb);
        $dailyLimit = (int) get_option('wp_ai_assistant_daily_limit', self::DEFAULT_DAILY_LIMIT);
        $this->quotaManager = new QuotaManager($repo, $dailyLimit);

        // Initialize thread repository and connect with Assistant.
        $threadRepository = new WPThreadRepository();
        Assistant::set_thread_repository($threadRepository);
    }
}
```

### 2. Repository Pattern

Data access is abstracted through repository interfaces:

```php
/**
 * WordPress database implementation of quota repository.
 */
class WPDBQuotaRepository implements QuotaRepository {
    /**
     * WordPress database instance.
     *
     * @var wpdb
     */
    private wpdb $wpdb;

    /**
     * Initialize repository with database instance.
     *
     * @param wpdb $wpdb WordPress database instance.
     */
    public function __construct(wpdb $wpdb) {
        $this->wpdb = $wpdb;
    }

    /**
     * Get current usage count for today.
     *
     * @return int Current usage count.
     */
    public function getCurrentUsage(): int {
        // Guard clause: Early exit if database is not available.
        if (!$this->isDatabaseAvailable()) {
            Logger::error('Database not available for quota check.');
            return 0;
        }

        // Execute database query with proper sanitization.
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}ai_assistant_quota WHERE date = %s",
            gmdate('Y-m-d')
        );

        return (int) $this->wpdb->get_var($query);
    }
}
```

### 3. Observer Pattern

Events are handled through WordPress hooks system:

```php
/**
 * Register WordPress action and filter hooks.
 */
private function registerHooks(): void {
    // AJAX handlers for frontend requests.
    add_action('wp_ajax_wp_ai_assistant_request', [$this, 'handle_chatbot_request']);
    add_action('wp_ajax_nopriv_wp_ai_assistant_request', [$this, 'handle_chatbot_request']);
    
    // Admin-specific AJAX handlers.
    add_action('wp_ajax_wp_ai_assistant_admin_test', [$this, 'handle_admin_test_request']);
}
```

## Error Handling and Logging

### Centralized Logging Agent

```php
/**
 * Centralized logging agent for debugging and monitoring.
 */
class Logger {
    /**
     * Log an informational message.
     *
     * @param string $message The message to log.
     * @param array $context Additional context data.
     */
    public static function log(string $message, array $context = []): void {
        // Guard clause: Early exit if logging is disabled.
        if (!self::isLoggingEnabled()) {
            return;
        }

        // Format message with context and write to log.
        self::writeLogEntry('info', $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message The error message to log.
     * @param array $context Additional context data.
     */
    public static function error(string $message, array $context = []): void {
        // Always log errors regardless of debug settings.
        self::writeLogEntry('error', $message, $context);
        
        // Optionally notify administrators of critical errors.
        if (self::isCriticalError($message)) {
            self::notifyAdministrators($message, $context);
        }
    }
}
```

## Security Implementation

### Nonce Verification Pattern

All AJAX requests implement nonce verification:

```php
/**
 * Handle chatbot AJAX requests with security validation.
 */
public function handle_chatbot_request(): void {
    $nonce = $_POST['_ajax_nonce'] ?? '';

    // Guard clause: Early exit if nonce verification fails.
    if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_ai_assistant_nonce')) {
        Logger::error('Nonce verification failed');
        wp_send_json_error(['message' => 'Nonce verification failed'], 403);
        wp_die();
    }

    // Guard clause: Early exit if chatbot is disabled.
    if (get_option('wp_ai_assistant_enable') !== '1') {
        $disabledMessage = get_option('wp_ai_assistant_disabled_message', 'Chatbot temporarily disabled.');
        wp_send_json_error(['message' => $disabledMessage]);
        wp_die();
    }

    // Process the request securely.
    $this->processSecureRequest();
}
```

### Input Sanitization

All user inputs are sanitized before processing:

```php
/**
 * Sanitize and validate user input.
 *
 * @param array $input Raw input data.
 * @return array Sanitized input data.
 */
private function sanitizeInput(array $input): array {
    return [
        'query' => sanitize_textarea_field($input['query'] ?? ''),
        'thread_id' => sanitize_text_field($input['thread_id'] ?? ''),
        'assistant_id' => sanitize_text_field($input['assistant_id'] ?? ''),
    ];
}
```

## Performance Optimization

### Caching Strategy

Response caching reduces API calls:

```php
/**
 * Cache assistant responses for performance.
 *
 * @param string $query The user query.
 * @param string $response The assistant response.
 */
private function cacheResponse(string $query, string $response): void {
    // Guard clause: Early exit if caching is disabled.
    if (!self::isCachingEnabled()) {
        return;
    }

    $cacheKey = 'wpai_response_' . md5($query);
    set_transient($cacheKey, $response, self::CACHE_DURATION);
}
```

### Asset Optimization

JavaScript and CSS assets are optimized for performance:

```php
/**
 * Enqueue optimized assets for chatbot functionality.
 */
public static function enqueue_assets(): void {
    $pluginUrl = plugin_dir_url(dirname(__DIR__));
    $version = defined('WP_DEBUG') && WP_DEBUG ? time() : '1.0.1';

    // Enqueue minified assets in production.
    $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
    
    wp_enqueue_style(
        'wp-ai-assistant-style',
        $pluginUrl . "assets/dist/css/chatbot{$suffix}.css",
        [],
        $version
    );
    
    wp_enqueue_script(
        'wp-ai-assistant-script',
        $pluginUrl . "assets/dist/js/chatbot{$suffix}.js",
        ['jquery'],
        $version,
        true
    );
}
```

## Testing Strategy

### Unit Testing Approach

Each agent can be tested independently:

```php
/**
 * Unit tests for Assistant Agent.
 */
class AssistantTest extends WP_UnitTestCase {
    /**
     * Test assistant initialization with valid configuration.
     */
    public function test_init_with_valid_configuration(): void {
        // Arrange: Set up test environment.
        update_option('wp_ai_assistant_api_key', 'test-api-key');
        update_option('wp_ai_assistant_assistant_id', 'test-assistant-id');

        // Act: Initialize assistant.
        Assistant::init();

        // Assert: Verify proper initialization.
        $assistantInfo = Assistant::get_assistant_info();
        $this->assertFalse($assistantInfo['error']);
    }

    /**
     * Test assistant initialization with missing configuration.
     */
    public function test_init_with_missing_configuration(): void {
        // Arrange: Clear configuration options.
        delete_option('wp_ai_assistant_api_key');
        delete_option('wp_ai_assistant_assistant_id');

        // Act: Attempt to initialize assistant.
        Assistant::init();

        // Assert: Verify error handling.
        $assistantInfo = Assistant::get_assistant_info();
        $this->assertTrue($assistantInfo['error']);
        $this->assertStringContainsString('missing', $assistantInfo['message']);
    }
}
```

## Deployment and Monitoring

### Health Check Agent

```php
/**
 * System health monitoring agent.
 */
class HealthCheck {
    /**
     * Perform comprehensive system health check.
     *
     * @return array Health check results.
     */
    public static function performHealthCheck(): array {
        $results = [
            'overall_status' => 'healthy',
            'checks' => [],
        ];

        // Check API connectivity.
        $apiCheck = self::checkApiConnectivity();
        $results['checks']['api'] = $apiCheck;

        // Check database connectivity.
        $dbCheck = self::checkDatabaseConnectivity();
        $results['checks']['database'] = $dbCheck;

        // Check quota limits.
        $quotaCheck = self::checkQuotaLimits();
        $results['checks']['quota'] = $quotaCheck;

        // Determine overall status.
        if (!$apiCheck['status'] || !$dbCheck['status']) {
            $results['overall_status'] = 'unhealthy';
        } elseif (!$quotaCheck['status']) {
            $results['overall_status'] = 'warning';
        }

        return $results;
    }

    /**
     * Check OpenAI API connectivity.
     *
     * @return array API connectivity status.
     */
    private static function checkApiConnectivity(): array {
        // Guard clause: Early exit if API credentials are missing.
        if (empty(get_option('wp_ai_assistant_api_key'))) {
            return [
                'status' => false,
                'message' => 'API key not configured.',
            ];
        }

        // Attempt API connection test.
        $assistantInfo = Assistant::get_assistant_info();
        
        return [
            'status' => !$assistantInfo['error'],
            'message' => $assistantInfo['error'] ? $assistantInfo['message'] : 'API connection successful.',
        ];
    }
}
```

## Conclusion

This agent-based architecture provides a robust, maintainable, and extensible foundation for the WordPress AI Assistant Plugin. Each agent has clearly defined responsibilities, proper error handling, and follows clean code principles including guard clauses and comprehensive PHPDoc documentation.

The modular design allows for easy testing, maintenance, and future enhancements while maintaining security and performance standards expected in WordPress plugin development.

## Future Enhancements

### Planned Agent Extensions

1. **Analytics Agent**: Track usage patterns and conversation metrics.
2. **Personalization Agent**: Customize responses based on user preferences.
3. **Integration Agent**: Connect with third-party services and APIs.
4. **Workflow Agent**: Implement complex multi-step conversation flows.
5. **Content Agent**: Generate and manage dynamic content based on conversations.

Each new agent will follow the same clean code principles and architectural patterns established in this documentation.
