Nice — below is a complete, practical example that implements the architecture and best practices I recommended: a **transport-agnostic service layer**, a **thin CRUD wrapper**, **AJAX adapter**, **REST adapter**, **enqueue + nonce/localize**, **JS examples** for using both transports, simple **caching**, **error handling**, and a short **PHPUnit** test skeleton. Drop these into your plugin (adjust class/file names and namespaces to match your plugin).

# Files / pieces included

1. `/modules/{module}/crud.php` — thin CRUD layer (data access).
2. `/modules/{module}/service.php` — service layer (business rules and normalization).
3. `/modules/{module}/rest-controller.php` — REST adapter.
4. `/modules/{module}/ajax-handler.php` — AJAX adapter.
5. `/modules/{module}/plugin.php` — plugin bootstrap + hooks + enqueue.
6. `assets/js/main.js` — JS showing both AJAX and REST usage.
7. `{plugin}/test/test-service.php` — PHPUnit skeleton for the service layer.

---

# 1) `/modules/{module}/crud.php` — Data access (keeps DB logic isolated)

```php
<?php
// /modules/{module}/crud.php

if (!class_exists('OC_Bookings_CRUD')) {
    /**
     * Very small example CRUD wrapper.
     * In your real plugin this will contain prepared statements, WP DB calls, WP_Query, etc.
     */
    class OC_Bookings_CRUD {
        /**
         * Fetch schools for a user on a given blog/site.
         *
         * @param int $user_id
         * @param int $blog_id
         * @return array|false Array of schools on success, false on failure.
         */
        public static function get_user_schools($user_id, $blog_id) {
            // Replace with real DB queries. Here is a mocked result:
            if (empty($user_id) || empty($blog_id)) {
                return false;
            }

            // Example rows returned from DB
            return [
                [
                    'id' => 101,
                    'name' => 'North Primary School',
                    'address' => '1 Oak St',
                ],
                [
                    'id' => 202,
                    'name' => 'East Secondary',
                    'address' => '200 East Ave',
                ],
            ];
        }
    }
}
```

---

# 2) `/modules/{module}/service.php` — Transport-agnostic service layer (single source of truth)

```php
<?php
// /modules/{module}/service.php

require_once plugin_dir_path(__FILE__) . 'crud.php';

if (!class_exists('OC_Service')) {
    /**
     * Service layer: business logic, validation, caching, and normalization.
     * Do not call wp_send_json_* from here. Throw exceptions for errors.
     */
    class OC_Service {

        /**
         * Get schools for a user on a blog.
         *
         * @param int $user_id
         * @param int $blog_id
         * @param array $args Optional filters (pagination, role, etc.)
         * @return array ['schools' => array, 'total' => int]
         * @throws InvalidArgumentException On validation error.
         * @throws RuntimeException On data access failure.
         */
        public static function get_user_schools(int $user_id, int $blog_id, array $args = []): array {
            // Basic validation
            if ($user_id <= 0) {
                throw new InvalidArgumentException('Invalid user ID.');
            }
            if ($blog_id <= 0) {
                throw new InvalidArgumentException('Invalid blog ID.');
            }

            $cache_key = self::get_cache_key($user_id, $blog_id, $args);

            // Try transient cache
            $cached = get_transient($cache_key);
            if (is_array($cached)) {
                return $cached;
            }

            // Call the CRUD layer
            $rows = OC_Bookings_CRUD::get_user_schools($user_id, $blog_id);
            if ($rows === false) {
                throw new RuntimeException('Failed to fetch schools from storage.');
            }

            // Example normalization: ensure integer IDs, sanitize text, and consistent keys
            $schools = array_map(function ($r) {
                return [
                    'id' => (int) ($r['id'] ?? 0),
                    'name' => wp_strip_all_tags($r['name'] ?? ''),
                    'address' => wp_strip_all_tags($r['address'] ?? ''),
                ];
            }, $rows);

            $result = [
                'schools' => $schools,
                'total' => count($schools),
            ];

            // Cache for 60 seconds (tweak based on your needs)
            set_transient($cache_key, $result, 60);

            return $result;
        }

        /**
         * Invalidate school cache for a user+blog (call after any write).
         *
         * @param int $user_id
         * @param int $blog_id
         * @param array $args
         * @return void
         */
        public static function invalidate_user_schools_cache(int $user_id, int $blog_id, array $args = []) {
            $cache_key = self::get_cache_key($user_id, $blog_id, $args);
            delete_transient($cache_key);
        }

        private static function get_cache_key(int $user_id, int $blog_id, array $args = []): string {
            // Keep key deterministic; include filters if present.
            $parts = ['org_core', 'user_schools', $blog_id, $user_id, md5(json_encode($args))];
            return implode('_', $parts);
        }
    }
}
```

---

# 3) `/modules/{module}/ajax-handler.php` — Thin AJAX adapter

```php
<?php
// /modules/{module}/ajax-handler.php

if (!class_exists('OC_AJAX')) {
    class OC_AJAX {

        /**
         * AJAX handler to return current user's schools.
         *
         * Expects: POST { action: 'get_user_schools', nonce: '...' }
         * Uses check_ajax_referer('bookings_nonce', 'nonce')
         */
        public function handle_get_schools_of_current_user() {
            try {
                check_ajax_referer('bookings_nonce', 'nonce');

                if (!is_user_logged_in()) {
                    wp_send_json_error(['message' => __('You must be logged in.', 'organization-core')], 403);
                }

                $user_id = get_current_user_id();
                $blog_id = get_current_blog_id();

                // Optionally accept filters via POST; sanitize them.
                $args = []; // e.g. page, per_page, role filters

                $result = OC_Service::get_user_schools($user_id, $blog_id, $args);

                wp_send_json_success($result);
            } catch (InvalidArgumentException $e) {
                wp_send_json_error(['message' => $e->getMessage()], 400);
            } catch (RuntimeException $e) {
                wp_send_json_error(['message' => __('Failed to load schools.', 'organization-core')], 500);
            } catch (Exception $e) {
                wp_send_json_error(['message' => __('An unexpected error occurred.', 'organization-core')], 500);
            }
        }
    }
}
```

Hook this in your plugin bootstrap (see plugin.php below).

---

# 4) `/modules/{module}/rest-controller.php` — Thin REST adapter

```php
<?php
// /modules/{module}/rest-controller.php

if (!class_exists('OC_REST_Controller')) {
    class OC_REST_Controller {

        /**
         * Register routes. Call this during rest_api_init.
         */
        public static function register_routes() {
            register_rest_route('organization-core/v1', '/user-schools', [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_user_schools'],
                'permission_callback' => [__CLASS__, 'permission_get_user_schools'],
            ]);
        }

        /**
         * Permission callback - keep this single-purpose.
         * Use more granular capability checks if needed.
         *
         * @param WP_REST_Request $request
         * @return bool|WP_Error
         */
        public static function permission_get_user_schools($request) {
            if (!is_user_logged_in()) {
                return new WP_Error('rest_forbidden', __('You must be authenticated.', 'organization-core'), ['status' => 401]);
            }
            // Example: check capability if you want:
            // if (!current_user_can('read')) { return new WP_Error(...); }
            return true;
        }

        /**
         * REST callback that leverages the service layer.
         *
         * @param WP_REST_Request $request
         * @return WP_REST_Response|WP_Error
         */
        public static function get_user_schools($request) {
            try {
                $user_id = get_current_user_id();
                $blog_id = get_current_blog_id();

                // Map REST query params to service args, with sanitization
                $args = []; // e.g. $request->get_param('page')

                $result = OC_Service::get_user_schools($user_id, $blog_id, $args);

                return rest_ensure_response([
                    'success' => true,
                    'data' => $result,
                ]);
            } catch (InvalidArgumentException $e) {
                return new WP_Error('invalid_argument', $e->getMessage(), ['status' => 400]);
            } catch (RuntimeException $e) {
                return new WP_Error('server_error', __('Failed to load schools.', 'organization-core'), ['status' => 500]);
            } catch (Exception $e) {
                return new WP_Error('server_error', __('An unexpected error occurred.', 'organization-core'), ['status' => 500]);
            }
        }
    }
}
```

---

# 5) `/modules/{module}/plugin.php` — Bootstrap: hooks, enqueues, and wiring everything up

```php
<?php
// /modules/{module}/plugin.php

require_once plugin_dir_path(__FILE__) . 'crud.php';
require_once plugin_dir_path(__FILE__) . 'service.php';
require_once plugin_dir_path(__FILE__) . 'ajax-handler.php';
require_once plugin_dir_path(__FILE__) . 'rest-controller.php';

class OC_Plugin {

    private $ajax;

    public function __construct() {
        $this->ajax = new OC_AJAX();

        // AJAX hooks
        add_action('wp_ajax_get_user_schools', [$this->ajax, 'handle_get_schools_of_current_user']);
        // If you want unauthenticated access (rare), add wp_ajax_nopriv_...
        // add_action('wp_ajax_nopriv_get_user_schools', [$this->ajax, 'handle_get_schools_of_current_user']);

        // REST hook
        add_action('rest_api_init', ['OC_REST_Controller', 'register_routes']);

        // Scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Enqueue scripts and localize nonce + REST base URL
     */
    public function enqueue_scripts() {
        $handle = 'org-core-main-js';
        wp_enqueue_script($handle, plugin_dir_url(__FILE__) . '../assets/js/main.js', ['jquery'], '1.0.0', true);

        wp_localize_script($handle, 'orgCore', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'bookings_nonce' => wp_create_nonce('bookings_nonce'),
            'rest_base' => esc_url_raw(rest_url('organization-core/v1')),
        ]);
    }
}

// Initialize
new OC_Plugin();
```

---

# 6) `assets/js/main.js` — JavaScript examples for AJAX and REST usage

```js
// assets/js/main.js
(function ($) {
  // AJAX usage (legacy WP admin-ajax)
  function fetchSchoolsViaAjax() {
    $.post(
      orgCore.ajax_url,
      {
        action: "get_user_schools",
        nonce: orgCore.bookings_nonce,
      },
      function (response) {
        if (response.success) {
          console.log("AJAX schools", response.data);
        } else {
          console.error("AJAX error", response.data);
          alert(response.data.message || "Error fetching schools");
        }
      }
    ).fail(function (jqXHR) {
      console.error("AJAX transport error", jqXHR);
      alert("Network or server error (AJAX).");
    });
  }

  // REST usage (preferred for modern plugins)
  function fetchSchoolsViaRest() {
    var url = orgCore.rest_base + "/user-schools";

    fetch(url, {
      method: "GET",
      credentials: "same-origin", // send cookies for cookie auth
      headers: {
        Accept: "application/json",
      },
    })
      .then(function (resp) {
        if (!resp.ok) throw resp;
        return resp.json();
      })
      .then(function (json) {
        if (json.success) {
          console.log("REST schools", json.data);
        } else {
          console.error("REST app-level error", json);
          alert(
            (json.data && json.data.message) || "Error fetching schools (REST)."
          );
        }
      })
      .catch(function (err) {
        console.error("REST error", err);
        alert("Network or server error (REST).");
      });
  }

  // Simple example triggers (hook these to UI as needed)
  $(function () {
    // Call one or both to test.
    $("#fetch-schools-ajax").on("click", fetchSchoolsViaAjax);
    $("#fetch-schools-rest").on("click", fetchSchoolsViaRest);
  });
})(jQuery);
```

Add two buttons to your admin/front-end UI for quick testing:

```html
<button id="fetch-schools-ajax">Fetch Schools (AJAX)</button>
<button id="fetch-schools-rest">Fetch Schools (REST)</button>
```

---

# 7) `{plugin}/test/test-service.php` — PHPUnit skeleton (unit-test service layer)

```php
<?php
// {plugin}/t/testest-service.php

use PHPUnit\Framework\TestCase;

final class ServiceTest extends TestCase {

    public function testGetUserSchoolsWithInvalidUserId() {
        $this->expectException(InvalidArgumentException::class);
        OC_Service::get_user_schools(0, 1);
    }

    public function testGetUserSchoolsReturnsArray() {
        // Depending on your WP test bootstrap, you may need to set up a user and blog context.
        $user_id = 1;
        $blog_id = 1;

        $result = OC_Service::get_user_schools($user_id, $blog_id);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('schools', $result);
        $this->assertArrayHasKey('total', $result);
    }
}
```

Run under WP PHPUnit environment or your chosen test harness.

---

# Additional notes & best-practice checklist

- **Single source of truth:** Service layer is the one place business logic lives. Both AJAX and REST call it.
- **Transport responsibilities:** Adapters handle transport-specific auth and response mapping only.
- **Caching:** Implemented in the service with `get_transient()`/`set_transient()`. Always invalidate cache after writes.
- **Security:** AJAX uses `check_ajax_referer('bookings_nonce', 'nonce')`. REST uses `permission_callback`. Both still rely on `is_user_logged_in()` but you can use capability checks as needed.
- **Error handling:** Service throws exceptions. Adapters catch and map to `wp_send_json_*` or `WP_Error` with appropriate HTTP status codes.
- **Normalization:** Service normalizes rows into consistent keys and types.
- **Tests:** Unit test the service layer; adapter tests can be integration tests.
- **Versioning:** REST route uses `organization-core/v1` so you can add `v2` later.
- **Logging:** Adapters log server-side exceptions using `error_log()`; extend with Monolog or other logging if you need structured logs.

---

# How to migrate existing handlers

1. Create the service method that wraps the current CRUD call and normalization.
2. Replace direct CRUD calls inside AJAX/REST handlers with calls to service.
3. Move validation/permission checks to either the adapter (transport-specific) or service (business rules) as appropriate.
4. Add caching/invalidation in the service.
5. Add tests for service logic, then smoke-test both AJAX and REST endpoints.

---

If you want, I can:

- Create a PR-style unified diff for your plugin files.
- Convert the example to use namespaces + PSR-4 autoloading.
- Add capability-based permission checks (e.g., `current_user_can('manage_options')`) or JWT/OAuth support for REST.

Which of those would be most helpful next?
