<?php

/**
 * Hotels Deactivator
 */

if (!defined('ABSPATH')) {
    exit;
}

class OC_Hotels_Deactivator
{

    /**
     * Run on plugin deactivation
     */
    public static function deactivate()
    {
        // Flush rewrite rules if needed, or cleanup
    }
}
