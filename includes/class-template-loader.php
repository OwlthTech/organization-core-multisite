<?php

/**
 * Template Loader with Theme-based Override Support
 * SCENARIO 1: Different themes per site = automatic different templates
 */
class OC_Template_Loader
{

    /**
     * Load template with theme override support
     */
    public function get_template($template_name, $args = array(), $module_id = '', $default_path = '')
    {
        $template = $this->locate_template($template_name, $module_id, $default_path);

        if (! $template) {
            return;
        }

        // Extract variables for use in template
        if ($args && is_array($args)) {
            extract($args);
        }

        // Hook before template loads
        do_action('organization_core_before_template', $template_name, $template, $args, $module_id);

        include $template;

        // Hook after template loads
        do_action('organization_core_after_template', $template_name, $template, $args, $module_id);
    }

    /**
     * Locate template with priority order
     * 
     * Priority:
     * 1. Child theme: themes/child-theme/organization-core/{module}/{template}.php
     * 2. Parent theme: themes/parent-theme/organization-core/{module}/{template}.php
     * 3. Plugin default: plugins/organization-core/modules/{module}/templates/{template}.php
     */
    public function locate_template($template_name, $module_id = '', $default_path = '')
    {
        $template = false;

        // PRIORITY 1 & 2: Check theme directory (child theme gets priority automatically)
        $template = $this->check_theme_template($template_name, $module_id);

        // PRIORITY 3: Plugin default template
        if (! $template) {
            $template = $this->get_default_template($template_name, $module_id, $default_path);
        }

        // Allow developers to filter the final template path
        return apply_filters(
            'organization_core_locate_template',
            $template,
            $template_name,
            $module_id
        );
    }

    /**
     * Check theme directory for template override
     * WordPress locate_template() automatically checks child theme first
     */
    private function check_theme_template($template_name, $module_id)
    {
        $paths = array(
            trailingslashit('organization-core') . $module_id . '/' . $template_name,
            trailingslashit('organization-core') . $template_name,
        );

        return locate_template($paths);
    }

    /**
     * Get plugin default template
     */
    private function get_default_template($template_name, $module_id, $default_path)
    {
        if (empty($default_path)) {
            $default_path = ORGANIZATION_CORE_PLUGIN_DIR . 'modules/' . $module_id . '/templates/';
        }

        $template = $default_path . $template_name;

        return file_exists($template) ? $template : false;
    }

    /**
     * Get template part (like WordPress get_template_part)
     */
    public function get_template_part($slug, $name = '', $module_id = '', $args = array())
    {
        $templates = array();

        if ($name) {
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";

        $template = $this->locate_template($templates[0], $module_id);

        if ($template) {
            if ($args && is_array($args)) {
                extract($args);
            }
            load_template($template, false);
        }
    }
}
