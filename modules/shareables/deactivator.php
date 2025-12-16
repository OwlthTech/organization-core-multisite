<?php

/**
 * Fired during plugin deactivation
 *
 * @package    Organization_Core
 * @subpackage Organization_Core/modules/shareables
 */

class OC_Shareables_Deactivator {

    /**
     * Deactivate the module.
     */
    public static function deactivate() {
        // Flush rewrite rules to remove custom routes
        flush_rewrite_rules();
    }
}
