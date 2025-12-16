<?php

/**
 * Quotes Module Deactivator
 * Cleans up on deactivation
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Quotes_Deactivator
{

    /**
     * Run on plugin deactivation
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules(false);

        // Optional: Clean up transients
        delete_transient('quotes_module_cache');
    }
}
