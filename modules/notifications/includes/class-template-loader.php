<?php
/**
 * Template Loader
 * Loads and renders email templates
 *
 * @package    Organization_Core
 * @subpackage Notifications
 * @version    1.0.0
 */

if (!defined('WPINC')) {
    die;
}

class OC_Notification_Template_Loader {
    
    /**
     * Load template with data
     * 
     * @param string $template_path Path to template
     * @param array $data Template variables
     * @return string Rendered HTML
     */
    public function load($template_path, $data = array()) {
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include header
        include $this->get_header_template();
        
        // Include main template
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>Email template not found.</p>';
        }
        
        // Include footer
        include $this->get_footer_template();
        
        // Get content
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Get header template
     */
    private function get_header_template() {
        return plugin_dir_path(dirname(__FILE__)) . 'templates/emails/base/header.php';
    }
    
    /**
     * Get footer template
     */
    private function get_footer_template() {
        return plugin_dir_path(dirname(__FILE__)) . 'templates/emails/base/footer.php';
    }
}
