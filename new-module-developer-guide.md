# Developer Guide: Creating a New Organization Core Module

## 1. Introduction

This guide provides a comprehensive, step-by-step walkthrough for creating a new module for the Organization Core plugin. It is designed to be followed by developers to ensure new modules adhere to the existing architecture, coding standards, and design patterns.

A module is a self-contained package of functionality that can be enabled or disabled on a per-site basis within the WordPress multisite network.

## 2. Module File Structure

A well-structured module is key to maintainability. Below is the recommended file and directory structure for a new module called `my-module`.

```
organization-core/
└── modules/
    └── my-module/
        ├── activator.php                   # Optional: Runs on plugin activation.
        ├── deactivator.php                 # Optional: Runs on plugin deactivation.
        │
        ├── config.php                      # Required: Module metadata and configuration.
        ├── class-my-module.php             # Required: The main module orchestrator class.
        │
        ├── cpt.php                         # Optional: For Custom Post Type and Taxonomy registration.
        ├── crud.php                        # Optional: For database Create, Read, Update, Delete operations.
        ├── ajax.php                        # Optional: For handling AJAX requests.
        │
        ├── admin/                          # Optional: Admin-facing files.
        │   ├── class-my-module-admin.php   # Admin-specific hooks and logic.
        │   ├── assets/
        │   │   ├── css/my-module-admin.css
        │   │   └── js/my-module-admin.js
        │   └── templates/
        │       └── admin-settings-page.php
        │
        └── public/                         # Optional: Public-facing files.
            ├── class-my-module-public.php  # Public-specific hooks and logic.
            ├── assets/
            │   ├── css/my-module-public.css
            │   └── js/my-module-public.js
            └── templates/
                └── public-display.php
```

## 3. Core Module Files & Their Purpose

Every module requires at least two core files: `config.php` and `class-my-module.php`.

### `config.php` (Required)

This file returns a PHP array containing metadata about your module. The core plugin uses this for registration and for display in the Network Admin dashboard.

**Example `config.php`:**

```php
<?php
// /modules/my-module/config.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

return [
    'id'          => 'my-module', // Unique, slug-formatted ID.
    'name'        => __( 'My Awesome Module', 'organization-core' ),
    'version'     => '1.0.0',
    'description' => __( 'This is a description of what my module does.', 'organization-core' ),
    'author'      => 'Your Name',
    'dependencies' => [ // Optional: other module IDs this module depends on.
        'bookings',
    ],
    'supports' => [ // Optional: features this module uses.
        'cpt',
        'ajax',
        'templates',
    ],
];
```

### `class-my-module.php` (Required)

This is the main entry point and orchestrator for your module. It must extend `Organization_Core_Abstract_Module`. Its primary job is to load dependencies and initialize the admin, public, and AJAX components.

**Key Responsibilities:**

1.  Extend `Organization_Core_Abstract_Module`.
2.  Implement the three abstract methods: `init()`, `get_module_id()`, and `get_config()`.
3.  Load all other module files (`crud.php`, `admin/class-my-module-admin.php`, etc.).
4.  Instantiate the admin, public, and AJAX classes based on the current context (`is_admin()`, `DOING_AJAX`).
5.  Register the module with the `Organization_Core_Module_Registry`.

**Example `class-my-module.php`:**

```php
<?php
// /modules/my-module/class-my-module.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Organization_Core_My_Module extends Organization_Core_Abstract_Module {

    protected $admin;
    protected $public;
    protected $ajax;

    public function __construct() {
        parent::__construct(); // This initializes the template loader.
        $config = $this->get_config();
        $this->module_id   = $config['id'];
        $this->module_name = $config['name'];
        $this->version     = $config['version'];
    }

    /**
     * Get the unique module ID.
     */
    public function get_module_id() {
        return 'my-module';
    }

    /**
     * Get the module's configuration array.
     */
    public function get_config() {
        return require plugin_dir_path( __FILE__ ) . 'config.php';
    }

    /**
     * Initialize the module.
     * This is where we load dependencies and instantiate our components.
     */
    public function init() {
        // The core plugin already validates if the module is enabled for the site.
        // You can add extra validation if needed.

        $this->load_dependencies();

        if ( is_admin() ) {
            $this->init_admin();
        }

        if ( ! is_admin() ) {
            $this->init_public();
        }

        // AJAX handlers need to be loaded for both front-end and back-end.
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $this->init_ajax();
        }
    }

    /**
     * Load all files required by the module.
     */
    private function load_dependencies() {
        $module_path = plugin_dir_path( __FILE__ );

        // Load functional files
        if ( file_exists( $module_path . 'crud.php' ) ) {
            require_once $module_path . 'crud.php';
        }
        if ( file_exists( $module_path . 'cpt.php' ) ) {
            require_once $module_path . 'cpt.php';
        }

        // Load class files
        if ( is_admin() && file_exists( $module_path . 'admin/class-my-module-admin.php' ) ) {
            require_once $module_path . 'admin/class-my-module-admin.php';
        }
        if ( ! is_admin() && file_exists( $module_path . 'public/class-my-module-public.php' ) ) {
            require_once $module_path . 'public/class-my-module-public.php';
        }
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX && file_exists( $module_path . 'ajax.php' ) ) {
            require_once $module_path . 'ajax.php';
        }
    }

    /**
     * Initialize admin-facing functionality.
     */
    private function init_admin() {
        $this->admin = new Organization_Core_My_Module_Admin( $this->get_module_id(), $this->version, $this->template_loader );
        $this->admin->init();
    }

    /**
     * Initialize public-facing functionality.
     */
    private function init_public() {
        $this->public = new Organization_Core_My_Module_Public( $this->get_module_id(), $this->version, $this->template_loader );
        $this->public->init();
    }

    /**
     * Initialize AJAX handlers.
     */
    private function init_ajax() {
        $this->ajax = new Organization_Core_My_Module_Ajax( $this->get_module_id() );
        $this->ajax->init();
    }
}

/**
 * Register the module with the core plugin.
 * This MUST be done for the plugin to recognize the module.
 */
add_action( 'organization_core_register_modules', function() {
    $config = require plugin_dir_path( __FILE__ ) . 'config.php';
    Organization_Core_Module_Registry::register_module( $config['id'], $config );
});
```

## 4. Activation & Deactivation Hooks

If your module needs to perform actions on plugin activation (e.g., create a database table) or deactivation (e.g., flush rewrite rules), you can use `activator.php` and `deactivator.php`.

The core plugin automatically finds and runs these files.

### `activator.php`

Create a class named `Organization_Core_{ModuleName}_Activator` with a static `activate()` method. The module name should be the PascalCase version of the module ID (e.g., `my-module` becomes `My_Module`).

**Example `activator.php`:**

```php
<?php
// /modules/my-module/activator.php

class Organization_Core_My_Module_Activator {
    public static function activate() {
        // Example: Create a custom database table.
        global $wpdb;
        $table_name = $wpdb->prefix . 'my_module_items';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            name text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Always flush rewrite rules on activation if you register CPTs.
        flush_rewrite_rules();
    }
}
```

### `deactivator.php`

Create a class named `Organization_Core_{ModuleName}_Deactivator` with a static `deactivate()` method.

**Example `deactivator.php`:**

```php
<?php
// /modules/my-module/deactivator.php

class Organization_Core_My_Module_Deactivator {
    public static function deactivate() {
        // Example: Flush rewrite rules to remove CPT rules.
        flush_rewrite_rules();
    }
}
```

## 5. Handling Admin, Public, and AJAX Logic

Separating your logic into dedicated classes keeps the module organized.

### Admin Logic (`admin/class-my-module-admin.php`)

This class handles all admin-area functionality, like adding menu pages, enqueuing admin scripts, and processing form submissions.

```php
<?php
// /modules/my-module/admin/class-my-module-admin.php

class Organization_Core_My_Module_Admin {
    private $module_id;
    private $version;
    private $template_loader;

    public function __construct( $module_id, $version, $template_loader ) {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;
    }

    public function init() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function add_admin_menu_page() {
        add_menu_page(
            'My Module',
            'My Module',
            'manage_options',
            'my-module-admin',
            [ $this, 'render_admin_page' ],
            'dashicons-admin-generic',
            25
        );
    }

    public function render_admin_page() {
        // Use the template loader to render the page
        $this->template_loader->get_template( 'admin/admin-settings-page.php', [], $this->module_id, plugin_dir_path( __FILE__ ) . 'templates/' );
    }

    public function enqueue_assets( $hook ) {
        // Only load assets on your module's admin page
        if ( 'toplevel_page_my-module-admin' !== $hook ) {
            return;
        }
        wp_enqueue_style(
            $this->module_id . '-admin',
            plugin_dir_url( __FILE__ ) . 'assets/css/my-module-admin.css',
            [],
            $this->version
        );
        wp_enqueue_script(
            $this->module_id . '-admin',
            plugin_dir_url( __FILE__ ) . 'assets/js/my-module-admin.js',
            ['jquery'],
            $this->version,
            true
        );
    }
}
```

### Public Logic (`public/class-my-module-public.php`)

This class handles all front-end functionality, like registering shortcodes or enqueuing public scripts.

```php
<?php
// /modules/my-module/public/class-my-module-public.php

class Organization_Core_My_Module_Public {
    // ... constructor similar to Admin class ...

    public function init() {
        add_shortcode( 'my_module_display', [ $this, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function render_shortcode( $atts ) {
        ob_start();
        $this->template_loader->get_template( 'public/public-display.php', [], $this->module_id, plugin_dir_path( __FILE__ ) . 'templates/' );
        return ob_get_clean();
    }

    public function enqueue_assets() {
        // Only load assets if the shortcode is present or on a specific page
        if ( is_singular() && has_shortcode( get_post()->post_content, 'my_module_display' ) ) {
            wp_enqueue_style( $this->module_id . '-public', /* ... */ );
            wp_enqueue_script( $this->module_id . '-public', /* ... */ );
        }
    }
}
```

### AJAX Logic (`ajax.php`)

This file should contain a class that registers all AJAX action hooks.

```php
<?php
// /modules/my-module/ajax.php

class Organization_Core_My_Module_Ajax {
    private $module_id;

    public function __construct( $module_id ) {
        $this->module_id = $module_id;
    }

    public function init() {
        // AJAX action for logged-in users
        add_action( 'wp_ajax_my_module_do_stuff', [ $this, 'handle_do_stuff' ] );
        // AJAX action for non-logged-in users
        add_action( 'wp_ajax_nopriv_my_module_do_stuff', [ $this, 'handle_do_stuff' ] );
    }

    public function handle_do_stuff() {
        check_ajax_referer( 'my_module_nonce', 'security' );

        // Sanitize and process $_POST data
        $some_data = sanitize_text_field( $_POST['data'] );

        // Use CRUD functions
        $result = my_module_update_item( 1, $some_data );

        if ( $result ) {
            wp_send_json_success( [ 'message' => 'It worked!' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Something went wrong.' ] );
        }
    }
}
```

## 6. Template Loading and Overriding

The `Organization_Core_Abstract_Module` provides access to the template loader via `$this->template_loader`. You can also use the helper methods `get_template()` and `get_template_part()`.

**How to load a template:**

```php
// In your Admin or Public class methods:
$args = [ 'title' => 'My Page Title' ];
$this->template_loader->get_template(
    'my-template-file.php',      // Template file name
    $args,                       // Data to pass to the template
    $this->module_id,            // Your module's ID
    plugin_dir_path( __FILE__ ) . 'templates/' // The default path inside your module
);
```

**Template Override Priority:**
The loader will look for the template in this order:

1.  `themes/child-theme/organization-core/my-module/my-template-file.php`
2.  `themes/parent-theme/organization-core/my-module/my-template-file.php`
3.  `plugins/organization-core/modules/my-module/admin/templates/my-template-file.php` (based on the default path provided)

This system allows theme developers to customize your module's output without editing the plugin files directly.
