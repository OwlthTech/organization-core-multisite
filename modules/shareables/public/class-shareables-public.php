<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/shareables/public
 */

class OC_Shareables_Public {

    private $module_id;
    private $version;
    private $template_loader;

    public function __construct($module_id, $version, $template_loader) {
        $this->module_id = $module_id;
        $this->version = $version;
        $this->template_loader = $template_loader;
    }

    public function init() {
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_filter('template_include', array($this, 'load_template'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_head', array($this, 'add_meta_tags'));
    }

    public function add_query_vars($vars) {
        $vars[] = 'shareables_uuid';
        return $vars;
    }

    public function load_template($template) {
        $uuid = get_query_var('shareables_uuid');
        if ($uuid) {
            $shareable = OC_Shareables_CRUD::get_item_by_uuid($uuid);
            if ($shareable) {
                // Locate the template
                $new_template = $this->template_loader->locate_template(
                    'share-view.php',
                    $this->module_id,
                    plugin_dir_path(__DIR__) . 'templates/public/'
                );
                
                if ($new_template && file_exists($new_template)) {
                    // Pass data to template
                    $args = array('item' => $shareable);
                    
                    // Extract args for template
                    extract($args);
                    
                    // Include template
                    include $new_template;
                    
                    // Return empty string to prevent WordPress from loading default template
                    return '';
                }
            } else {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
            }
        }
        return $template;
    }

    public function enqueue_assets() {
        if (get_query_var('shareables_uuid')) {
            wp_enqueue_style($this->module_id . '-public-css', plugin_dir_url(__DIR__) . 'assets/public/css/shareables.css', array(), $this->version, 'all');
            wp_enqueue_script($this->module_id . '-public-js', plugin_dir_url(__DIR__) . 'assets/public/js/shareables.js', array('jquery'), $this->version, false);
        }
    }

    public function add_meta_tags() {
        if (get_query_var('shareables_uuid')) {
            echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
        }
    }
}
