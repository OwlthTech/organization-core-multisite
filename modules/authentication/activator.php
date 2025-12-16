<?php

/**
 * Authentication Module Activator
 *
 * @package Organization_Core
 * @subpackage Modules/Authentication
 * @since 1.0.0
 */

class OC_Authentication_Activator
{

    /**
     * Run activation tasks
     *
     * @since 1.0.0
     */
    public static function activate()
    {
        self::register_custom_roles();
    }

    /**
     * Register custom user roles
     *
     * @since 1.0.0
     */
    private static function register_custom_roles()
    {
        // Subscriber role
        add_role(
            'subscriber',
            __('Subscriber', 'organization-core'),
            array(
                'read' => true,
            )
        );

        // Customer role
        add_role(
            'customer',
            __('Customer', 'organization-core'),
            array(
                'read' => true,
            )
        );
    }
}
