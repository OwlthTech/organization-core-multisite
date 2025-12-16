---
description: Complete workflow for creating a new module in organization-core-new plugin
---

# Module Creation Workflow

This workflow provides a step-by-step guide to create a new module in the `organization-core-new` WordPress plugin. Follow this guide to ensure your module follows the established architecture and patterns.

## Prerequisites

- Module name (e.g., `events`, `testimonials`, `galleries`)
- Module ID (lowercase, hyphenated version of name)
- Understanding of module requirements (database tables, admin UI, public UI, AJAX operations)

---

## Step 1: Create Module Directory Structure

Create the following folder structure in `modules/[module-name]/`:

```
modules/[module-name]/
├── admin/
│   └── class-[module-name]-admin.php
├── public/
│   └── class-[module-name]-public.php
├── assets/
│   ├── css/
│   └── js/
├── templates/
│   ├── admin/
│   └── public/
├── class-[module-name].php (Main orchestrator)
├── config.php
├── activator.php
├── deactivator.php
├── crud.php
├── ajax.php
└── metaboxes.php (if needed)
```

**Example for "events" module:**
```
modules/events/
├── admin/
│   └── class-events-admin.php
├── public/
│   └── class-events-public.php
├── assets/
│   ├── css/
│   └── js/
├── templates/
│   ├── admin/
│   └── public/
├── class-events.php
├── config.php
├── activator.php
├── deactivator.php
├── crud.php
└── ajax.php
```

---

## Step 2: Create Module Configuration (`config.php`)

Create `modules/[module-name]/config.php`:

```php
<?php
/**
 * [Module Name] Module Configuration
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/[module-name]
 */

if (!defined('WPINC')) {
    die;
}

return array(
    'id' => '[module-id]',  // e.g., 'events'
    'name' => __('[Module Display Name]', 'organization-core'),  // e.g., 'Events Management'
    'description' => __('[Module description]', 'organization-core'),
    'version' => '1.0.0',
    'author' => 'OwlthTech',

    // Module behavior settings
    'default_enabled' => false,  // Whether enabled by default on new sites
    'network_only' => false,     // Can be enabled per site (false) or network-wide only (true)
    'required' => false,         // Required for core functionality

    // Module dependencies (other modules this module needs)
    'dependencies' => array(),   // e.g., array('bookings', 'schools')

    // Features this module supports
    'supports' => array(
        'templates',     // Has overridable templates
        'ajax',         // Uses AJAX functionality
        'cpt',          // Registers custom post types (optional)
        'shortcodes'    // Provides shortcodes (optional)
    ),

    // Template directories
    'template_paths' => array(
        'admin' => 'templates/admin/',
        'public' => 'templates/public/',
    ),

    // Assets (optional)
    'assets' => array(
        // 'admin_js' => array('assets/js/[module-name]-admin.js'),
        // 'admin_css' => array('assets/css/[module-name]-admin.css'),
        // 'public_js' => array('assets/js/[module-name]-public.js'),
        // 'public_css' => array('assets/css/[module-name]-public.css'),
    ),

    // Main module class file
    'class' => 'class-[module-name].php'
);
```

---

## Step 3: Create Main Module Class (`class-[module-name].php`)

Create `modules/[module-name]/class-[module-name].php`:

```php
<?php
/**
 * [Module Name] Module - Main Orchestrator
 * This class loads all module components and coordinates them
 */

if (!defined('WPINC')) {
    die;
}

class OC_[ModuleName]_Module extends OC_Abstract_Module
{
    private $admin;
    private $public;
    private $ajax;

    public function __construct()
    {
        parent::__construct();
        $config = $this->get_config();
        $this->module_id = $config['id'];
        $this->module_name = $config['name'];
        $this->version = $config['version'];
    }

    /**
     * Initialize module - Load all components
     */
    public function init()
    {
        // Validate if module should run
        $validator = new OC_Module_Validator();

        if (!$validator->is_module_enabled_for_site($this->get_module_id())) {
            return;
        }

        if (!$validator->validate_dependencies($this->get_module_id(), get_current_blog_id())) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }

        // Load module components
        $this->load_dependencies();

        // Initialize components based on context
        if (is_admin()) {
            $this->init_admin();
        }

        if (!is_admin()) {
            $this->init_public();
        }

        // Load AJAX handlers (works for both admin and public)
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $this->init_ajax();
        }
    }

    /**
     * Load all module dependencies
     */
    private function load_dependencies()
    {
        $module_path = plugin_dir_path(__FILE__);

        // Core components (always loaded)
        require_once $module_path . 'crud.php';
        
        // Admin components
        if (is_admin()) {
            require_once $module_path . 'activator.php';
            require_once $module_path . 'deactivator.php';
            
            // Load metaboxes if needed
            if (file_exists($module_path . 'metaboxes.php')) {
                require_once $module_path . 'metaboxes.php';
            }
            
            require_once $module_path . 'admin/class-[module-name]-admin.php';
        }

        // Public components
        if (!is_admin()) {
            require_once $module_path . 'public/class-[module-name]-public.php';
        }

        // AJAX handlers
        if (defined('DOING_AJAX') && DOING_AJAX) {
            require_once $module_path . 'ajax.php';
        }
    }

    /**
     * Initialize admin-specific functionality
     */
    private function init_admin()
    {
        $this->admin = new OC_[ModuleName]_Admin(
            $this->module_id,
            $this->version,
            $this->template_loader
        );
        $this->admin->init();
    }

    /**
     * Initialize public-facing functionality
     */
    private function init_public()
    {
        $this->public = new OC_[ModuleName]_Public(
            $this->module_id,
            $this->version,
            $this->template_loader
        );
        $this->public->init();
    }

    /**
     * Initialize AJAX handlers
     */
    private function init_ajax()
    {
        $this->ajax = new OC_[ModuleName]_Ajax(
            $this->module_id
        );
    }

    public function get_module_id()
    {
        return '[module-id]';
    }

    public function get_config()
    {
        return require plugin_dir_path(__FILE__) . 'config.php';
    }

    public function dependency_notice()
    {
?>
        <div class="notice notice-error">
            <p><?php _e('The [Module Name] module requires other modules to be enabled.', 'organization-core'); ?></p>
        </div>
<?php
    }
}

// Register module
add_action('organization_core_register_modules', function () {
    $config = require plugin_dir_path(__FILE__) . 'config.php';
    OC_Module_Registry::register_module($config['id'], $config);
});
```

---

## Step 4: Create Database Activator (`activator.php`)

Create `modules/[module-name]/activator.php`:

```php
<?php
/**
 * [Module Name] Activator
 * Handles database table creation and rewrite rules
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_[ModuleName]_Activator
{
    /**
     * Run on plugin activation
     */
    public static function activate()
    {
        self::create_[module_name]_table();
        self::add_rewrite_rules();
        flush_rewrite_rules(false);
    }

    /**
     * Create module database table
     */
    public static function create_[module_name]_table()
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . '[table_name]';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            blog_id BIGINT(20) NOT NULL,
            user_id BIGINT(20) NOT NULL DEFAULT 0,
            
            -- Add your custom fields here
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            data JSON DEFAULT NULL,
            
            -- Standard fields
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            PRIMARY KEY (id),
            KEY blog_id (blog_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add rewrite rules (if needed for public URLs)
     */
    public static function add_rewrite_rules()
    {
        // Example: Custom URL structure
        // add_rewrite_rule(
        //     '^[module-name]/([0-9]+)/?$',
        //     'index.php?[module-name]_page=view&[module-name]_id=$matches[1]',
        //     'top'
        // );
    }
}

// Register rewrite rules on every page load (if needed)
// add_action('init', array('OC_[ModuleName]_Activator', 'add_rewrite_rules'), 10);
```

---

## Step 5: Create Deactivator (`deactivator.php`)

Create `modules/[module-name]/deactivator.php`:

```php
<?php
/**
 * [Module Name] Deactivator
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_[ModuleName]_Deactivator
{
    /**
     * Run on plugin deactivation
     */
    public static function deactivate()
    {
        // Clean up rewrite rules
        flush_rewrite_rules();
        
        // Note: We don't delete database tables on deactivation
        // Tables should only be deleted on uninstall
    }
}
```

---

## Step 6: Create CRUD Operations (`crud.php`)

Create `modules/[module-name]/crud.php`:

```php
<?php
/**
 * [Module Name] CRUD - Database Operations Layer
 * @package    Organization_Core
 * @subpackage [ModuleName]
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_[ModuleName]_CRUD
{
    /**
     * Get the table name
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->base_prefix . '[table_name]';
    }

    /**
     * Create a new record
     * 
     * @param int   $blog_id Blog ID
     * @param array $data Record data
     * @return int|false Record ID or false on failure
     */
    public static function create($blog_id, $data)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $insert_data = array(
            'blog_id' => $blog_id,
            'user_id' => isset($data['user_id']) ? intval($data['user_id']) : get_current_user_id(),
            'title' => isset($data['title']) ? sanitize_text_field($data['title']) : '',
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'data' => isset($data['data']) ? wp_json_encode($data['data']) : wp_json_encode(array()),
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active',
            'created_at' => current_time('mysql'),
        );

        $format = array('%d', '%d', '%s', '%s', '%s', '%s', '%s');

        $result = $wpdb->insert($table_name, $insert_data, $format);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get a single record by ID
     */
    public static function get($id, $blog_id)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND blog_id = %d",
            $id,
            $blog_id
        ), ARRAY_A);

        if ($record && !empty($record['data'])) {
            $record['data'] = json_decode($record['data'], true);
        }

        return $record;
    }

    /**
     * Get records with filters
     */
    public static function get_all($blog_id, $args = array())
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $defaults = array(
            'number'  => 20,
            'offset'  => 0,
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'status'  => '',
            'user_id' => 0,
            'search'  => '',
        );
        $args = wp_parse_args($args, $defaults);

        $where_clauses = array();
        $where_clauses[] = $wpdb->prepare("blog_id = %d", $blog_id);

        if (!empty($args['status'])) {
            $where_clauses[] = $wpdb->prepare("status = %s", $args['status']);
        }

        if (!empty($args['user_id'])) {
            $where_clauses[] = $wpdb->prepare("user_id = %d", $args['user_id']);
        }

        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = $wpdb->prepare(
                "(title LIKE %s OR description LIKE %s)",
                $search_term,
                $search_term
            );
        }

        $where = implode(" AND ", $where_clauses);

        $allowed_orderby = array('id', 'title', 'status', 'created_at');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $offset = intval($args['offset']);
        $limit = intval($args['number']);

        $sql = "SELECT * FROM {$table_name} WHERE {$where} ORDER BY {$orderby} {$order}";

        if ($limit > 0) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        } elseif ($limit === -1) {
            // Get all results
        } else {
            $sql .= " LIMIT 20 OFFSET {$offset}";
        }

        $results = $wpdb->get_results($sql, ARRAY_A);

        // Decode JSON fields
        foreach ($results as &$result) {
            if (!empty($result['data'])) {
                $result['data'] = json_decode($result['data'], true);
            }
        }

        return $results;
    }

    /**
     * Update a record
     */
    public static function update($id, $blog_id, $data)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $update_data = array();
        $format = array();

        if (isset($data['title'])) {
            $update_data['title'] = sanitize_text_field($data['title']);
            $format[] = '%s';
        }

        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }

        if (isset($data['data'])) {
            $update_data['data'] = wp_json_encode($data['data']);
            $format[] = '%s';
        }

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }

        if (empty($update_data)) {
            return false;
        }

        $update_data['modified_at'] = current_time('mysql');
        $format[] = '%s';

        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id, 'blog_id' => $blog_id),
            $format,
            array('%d', '%d')
        );

        return $result !== false;
    }

    /**
     * Delete a record
     */
    public static function delete($id, $blog_id)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $result = $wpdb->delete(
            $table_name,
            array('id' => $id, 'blog_id' => $blog_id),
            array('%d', '%d')
        );

        return $result !== false;
    }

    /**
     * Count total records
     */
    public static function count($blog_id = null)
    {
        global $wpdb;

        if (empty($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        $table = self::get_table_name();

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE blog_id = %d",
            $blog_id
        ));
    }
}
```

---

## Step 7: Create AJAX Handler (`ajax.php`)

Create `modules/[module-name]/ajax.php`:

```php
<?php
/**
 * [Module Name] AJAX Handler Class
 * 
 * @package    Organization_Core
 * @subpackage [ModuleName]
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_[ModuleName]_Ajax
{
    private $module_id;

    /**
     * Register all AJAX actions
     */
    public function __construct($module_id)
    {
        $this->module_id = $module_id;

        // Example AJAX actions
        add_action('wp_ajax_create_[module_name]', array($this, 'handle_create'));
        add_action('wp_ajax_get_[module_name]', array($this, 'handle_get'));
        add_action('wp_ajax_update_[module_name]', array($this, 'handle_update'));
        add_action('wp_ajax_delete_[module_name]', array($this, 'handle_delete'));
        
        // Add nopriv actions if needed for public access
        // add_action('wp_ajax_nopriv_get_[module_name]', array($this, 'handle_get'));
    }

    /**
     * Handle create request
     */
    public function handle_create()
    {
        try {
            check_ajax_referer('[module_name]_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(
                    array('message' => __('You must be logged in.', 'organization-core')),
                    401
                );
            }

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();

            // Sanitize input
            $input_data = $this->sanitize_input($_POST);

            // Validate input
            $validation = $this->validate_input($input_data);
            if (is_wp_error($validation)) {
                wp_send_json_error(array('message' => $validation->get_error_message()));
            }

            // Create record
            require_once plugin_dir_path(__FILE__) . 'crud.php';
            $record_id = OC_[ModuleName]_CRUD::create($blog_id, $input_data);

            if (!$record_id) {
                wp_send_json_error(
                    array('message' => __('Failed to create record.', 'organization-core'))
                );
            }

            wp_send_json_success(array(
                'message' => __('Record created successfully!', 'organization-core'),
                'record_id' => $record_id,
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('An error occurred.', 'organization-core')));
        }
    }

    /**
     * Handle get request
     */
    public function handle_get()
    {
        try {
            check_ajax_referer('[module_name]_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(
                    array('message' => __('Authentication required', 'organization-core')),
                    401
                );
            }

            $blog_id = get_current_blog_id();
            $record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            if ($record_id) {
                $record = OC_[ModuleName]_CRUD::get($record_id, $blog_id);
                wp_send_json_success(array('record' => $record));
            } else {
                $records = OC_[ModuleName]_CRUD::get_all($blog_id);
                wp_send_json_success(array('records' => $records));
            }

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to retrieve records.', 'organization-core')));
        }
    }

    /**
     * Handle update request
     */
    public function handle_update()
    {
        try {
            check_ajax_referer('[module_name]_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => __('You must be logged in.', 'organization-core')));
            }

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();
            $record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;

            if (!$record_id) {
                wp_send_json_error(array('message' => __('Invalid record ID.', 'organization-core')));
            }

            // Sanitize input
            $input_data = $this->sanitize_input($_POST);

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            $result = OC_[ModuleName]_CRUD::update($record_id, $blog_id, $input_data);

            if ($result) {
                wp_send_json_success(array('message' => __('Record updated successfully!', 'organization-core')));
            } else {
                wp_send_json_error(array('message' => __('Failed to update record.', 'organization-core')));
            }

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('An error occurred.', 'organization-core')));
        }
    }

    /**
     * Handle delete request
     */
    public function handle_delete()
    {
        try {
            check_ajax_referer('[module_name]_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => __('You must be logged in.', 'organization-core')));
            }

            $user_id = get_current_user_id();
            $blog_id = get_current_blog_id();
            $record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;

            if (!$record_id) {
                wp_send_json_error(array('message' => __('Invalid record ID.', 'organization-core')));
            }

            require_once plugin_dir_path(__FILE__) . 'crud.php';

            $result = OC_[ModuleName]_CRUD::delete($record_id, $blog_id);

            if ($result) {
                wp_send_json_success(array('message' => __('Record deleted successfully!', 'organization-core')));
            } else {
                wp_send_json_error(array('message' => __('Failed to delete record.', 'organization-core')));
            }

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('An error occurred.', 'organization-core')));
        }
    }

    /**
     * Sanitize input data
     */
    private function sanitize_input($post_data)
    {
        return array(
            'title' => isset($post_data['title']) ? sanitize_text_field($post_data['title']) : '',
            'description' => isset($post_data['description']) ? sanitize_textarea_field($post_data['description']) : '',
            'status' => isset($post_data['status']) ? sanitize_text_field($post_data['status']) : 'active',
            // Add more fields as needed
        );
    }

    /**
     * Validate input data
     */
    private function validate_input($input)
    {
        if (empty($input['title'])) {
            return new WP_Error('invalid_title', __('Title is required.', 'organization-core'));
        }

        return true;
    }
}
```

---

## Step 8: Create Admin Class

Create `modules/[module-name]/admin/class-[module-name]-admin.php`:

```php
<?php
/**
 * [Module Name] Admin Interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_[ModuleName]_Admin
{
    private $module_id;
    private $version;
    private $template_loader;
    private $crud;
    private $screen_id;

    public function __construct($module_id, $version, $template_loader)
    {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;
        $this->screen_id = 'toplevel_page_organization-[module-name]';
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'crud.php';
        $this->crud = new OC_[ModuleName]_CRUD();
    }

    public function init()
    {
        // Enqueue assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register screen options
        add_action("load-{$this->screen_id}", array($this, 'register_screen_options'));
        add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'organization-[module-name]') === false) {
            return;
        }

        $module_url = plugin_dir_url(dirname(__FILE__));

        // Enqueue CSS
        wp_enqueue_style(
            '[module-name]-admin-css',
            $module_url . 'assets/css/[module-name]-admin.css',
            array(),
            $this->version
        );

        // Enqueue JS
        wp_enqueue_script(
            '[module-name]-admin-js',
            $module_url . 'assets/js/[module-name]-admin.js',
            array('jquery'),
            $this->version,
            true
        );

        // Localize script
        wp_localize_script('[module-name]-admin-js', '[moduleName]Admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('[module_name]_nonce'),
        ));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('[Module Name]', 'organization-core'),
            __('[Module Name]', 'organization-core'),
            'manage_options',
            'organization-[module-name]',
            array($this, 'render_admin_page'),
            'dashicons-[icon-name]',  // Choose appropriate dashicon
            25  // Menu position
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        // Check if viewing single item
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['item_id'])) {
            $this->template_loader->get_template(
                '[module-name]-single.php',
                array('crud' => $this->crud),
                $this->module_id,
                plugin_dir_path(dirname(__FILE__)) . 'templates/admin/'
            );
            return;
        }

        // Otherwise show list table
        $this->template_loader->get_template(
            '[module-name]-table.php',
            array(),
            $this->module_id,
            plugin_dir_path(dirname(__FILE__)) . 'templates/admin/'
        );
    }

    /**
     * Register screen options
     */
    public function register_screen_options()
    {
        $screen = get_current_screen();

        if ($screen->id !== $this->screen_id) {
            return;
        }

        $option = 'per_page';
        $args = array(
            'label' => __('[Module Name] per page', 'organization-core'),
            'default' => 20,
            'option' => '[module_name]_per_page'
        );

        add_screen_option($option, $args);
    }

    /**
     * Save screen option value
     */
    public function set_screen_option($status, $option, $value)
    {
        if ('[module_name]_per_page' === $option) {
            return $value;
        }

        return $status;
    }
}
```

---

## Step 9: Create Public Class

Create `modules/[module-name]/public/class-[module-name]-public.php`:

```php
<?php
/**
 * [Module Name] Public Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_[ModuleName]_Public
{
    protected $module_id;
    protected $version;
    protected $template_loader;
    private $crud;

    public function __construct($module_id, $version, $template_loader)
    {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'crud.php';
        $this->crud = 'OC_[ModuleName]_CRUD';
    }

    public function init()
    {
        // Register query vars
        add_filter('query_vars', array($this, 'add_query_vars'), 10, 1);
        
        // Template include filter
        add_filter('template_include', array($this, 'include_template'), 99, 1);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 10);
        
        // Register shortcodes
        add_shortcode('[module_name]_list', array($this, 'shortcode_list'));
    }

    /**
     * Register custom query variables
     */
    public function add_query_vars($vars)
    {
        if (!is_array($vars)) {
            $vars = array();
        }

        $vars[] = '[module_name]_page';
        $vars[] = '[module_name]_id';

        return $vars;
    }

    /**
     * Include custom templates
     */
    public function include_template($template)
    {
        $page_type = get_query_var('[module_name]_page');

        if (!empty($page_type)) {
            $this->template_loader->get_template(
                'public/page-' . $page_type . '.php',
                array(),
                $this->module_id
            );
            return false;
        }

        return $template;
    }

    /**
     * Enqueue public assets
     */
    public function enqueue_assets()
    {
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        wp_enqueue_style(
            '[module-name]-public',
            $plugin_url . 'assets/css/[module-name]-public.css',
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_script(
            '[module-name]-public',
            $plugin_url . 'assets/js/[module-name]-public.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script('[module-name]-public', '[moduleName]Public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('[module_name]_nonce'),
        ));
    }

    /**
     * Shortcode to display list
     */
    public function shortcode_list($atts)
    {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'status' => 'active',
        ), $atts);

        ob_start();
        
        $records = $this->crud::get_all(get_current_blog_id(), array(
            'number' => intval($atts['limit']),
            'status' => $atts['status'],
        ));

        $this->template_loader->get_template(
            'public/shortcode-list.php',
            array('records' => $records),
            $this->module_id
        );

        return ob_get_clean();
    }
}
```

---

## Step 10: Create Templates

### Admin List Table Template

Create `modules/[module-name]/templates/admin/[module-name]-table.php`:

```php
<?php
/**
 * Admin List Table Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$crud = new OC_[ModuleName]_CRUD();
$blog_id = get_current_blog_id();
$records = $crud::get_all($blog_id);
?>

<div class="wrap">
    <h1><?php _e('[Module Name]', 'organization-core'); ?></h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'organization-core'); ?></th>
                <th><?php _e('Title', 'organization-core'); ?></th>
                <th><?php _e('Status', 'organization-core'); ?></th>
                <th><?php _e('Created', 'organization-core'); ?></th>
                <th><?php _e('Actions', 'organization-core'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?php echo esc_html($record['id']); ?></td>
                    <td><?php echo esc_html($record['title']); ?></td>
                    <td><?php echo esc_html($record['status']); ?></td>
                    <td><?php echo esc_html($record['created_at']); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=organization-[module-name]&action=view&item_id=' . $record['id']); ?>">
                            <?php _e('View', 'organization-core'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

### Public Template

Create `modules/[module-name]/templates/public/shortcode-list.php`:

```php
<?php
/**
 * Public Shortcode List Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="[module-name]-list">
    <?php if (!empty($records)): ?>
        <?php foreach ($records as $record): ?>
            <div class="[module-name]-item">
                <h3><?php echo esc_html($record['title']); ?></h3>
                <p><?php echo esc_html($record['description']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p><?php _e('No items found.', 'organization-core'); ?></p>
    <?php endif; ?>
</div>
```

---

## Step 11: Create Assets

### Admin CSS

Create `modules/[module-name]/assets/css/[module-name]-admin.css`:

```css
/* Admin styles for [Module Name] */
.[module-name]-admin-container {
    padding: 20px;
}
```

### Admin JavaScript

Create `modules/[module-name]/assets/js/[module-name]-admin.js`:

```javascript
(function($) {
    'use strict';

    $(document).ready(function() {
        // Admin JavaScript for [Module Name]
        console.log('[Module Name] Admin JS loaded');
    });

})(jQuery);
```

### Public CSS

Create `modules/[module-name]/assets/css/[module-name]-public.css`:

```css
/* Public styles for [Module Name] */
.[module-name]-list {
    display: grid;
    gap: 20px;
}
```

### Public JavaScript

Create `modules/[module-name]/assets/js/[module-name]-public.js`:

```javascript
(function($) {
    'use strict';

    $(document).ready(function() {
        // Public JavaScript for [Module Name]
        console.log('[Module Name] Public JS loaded');
    });

})(jQuery);
```

---

## Step 12: Enable the Module

1. Go to WordPress Admin → Network Admin → Organization Core Settings
2. Find your new module in the list
3. Enable it for the desired sites
4. The module's database table will be created automatically on first activation

---

## Common Patterns & Best Practices

### 1. **Abstract Module Inheritance**
- All main module classes MUST extend `OC_Abstract_Module`
- Implement required methods: `init()`, `get_module_id()`, `get_config()`
- Use `$this->template_loader` for loading templates

### 2. **Database Operations**
- Always use `$wpdb->base_prefix` for multisite compatibility
- Include `blog_id` in all tables
- Use prepared statements for all queries
- Decode JSON fields after retrieval

### 3. **AJAX Security**
- Always use `check_ajax_referer()` for nonce verification
- Check user capabilities with `is_user_logged_in()` or `current_user_can()`
- Sanitize all input data
- Use `wp_send_json_success()` and `wp_send_json_error()`

### 4. **Template Loading**
- Use `$this->template_loader->get_template()` from abstract class
- Support theme overrides in `wp-content/themes/[theme]/organization-core/[module-id]/`
- Pass data as associative array to templates

### 5. **Asset Enqueuing**
- Hook into `admin_enqueue_scripts` for admin assets
- Hook into `wp_enqueue_scripts` for public assets
- Always localize scripts with AJAX URL and nonce
- Check current page before enqueuing module-specific assets

### 6. **Module Validation**
- Use `OC_Module_Validator` to check if module is enabled
- Validate dependencies before initialization
- Show admin notices for missing dependencies

### 7. **Naming Conventions**
- Class names: `OC_[ModuleName]_[Component]` (e.g., `OC_Events_Admin`)
- Function names: `snake_case`
- File names: `lowercase-with-hyphens.php`
- Database tables: `{$wpdb->base_prefix}[table_name]`

---

## Checklist

Before considering your module complete, verify:

- [ ] Module directory structure is correct
- [ ] `config.php` is properly configured
- [ ] Main module class extends `OC_Abstract_Module`
- [ ] Module is registered with `OC_Module_Registry`
- [ ] Database table is created in `activator.php`
- [ ] CRUD operations are implemented
- [ ] AJAX handlers are secured with nonces
- [ ] Admin interface is functional
- [ ] Public interface works (if needed)
- [ ] Templates support theme overrides
- [ ] Assets are properly enqueued
- [ ] All user input is sanitized
- [ ] All database queries use prepared statements
- [ ] Module can be enabled/disabled per site
- [ ] Dependencies are validated

---

## Troubleshooting

### Module not appearing in settings
- Check that `config.php` returns a proper array
- Verify module registration hook is firing
- Clear WordPress cache

### Database table not created
- Check `activator.php` SQL syntax
- Verify `dbDelta()` is being called
- Check database user permissions

### AJAX not working
- Verify nonce is being passed correctly
- Check AJAX action names match
- Ensure AJAX handlers are registered
- Check browser console for JavaScript errors

### Templates not loading
- Verify template file paths
- Check `template_loader` is initialized
- Ensure module ID matches directory name

---

## Example: Creating an "Events" Module

Replace all instances of:
- `[module-name]` → `events`
- `[ModuleName]` → `Events`
- `[Module Name]` → `Events`
- `[table_name]` → `events`
- `[icon-name]` → `calendar`

This will create a fully functional Events module following the established architecture.
