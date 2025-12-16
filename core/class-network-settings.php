<?php
/**
 * Manages network-wide settings storage and retrieval
 */
class OC_Network_Settings {
    
    const SETTINGS_KEY = 'organization_core_network_settings';

    /**
     * Get settings for specific module
     */
    public static function get_module_settings( $module_id ) {
        $all_settings = get_site_option( self::SETTINGS_KEY, array() );
        
        if ( isset( $all_settings['modules'][ $module_id ] ) ) {
            return $all_settings['modules'][ $module_id ];
        }

        // Return default settings if not found
        return array(
            'scope' => 'disabled',
            'sites' => array(),
            'settings' => array()
        );
    }

    /**
     * Update settings for specific module
     */
    public static function update_module_settings( $module_id, $settings ) {
        $all_settings = get_site_option( self::SETTINGS_KEY, array(
            'modules' => array(),
            'version' => ORGANIZATION_CORE_VERSION
        ) );

        $all_settings['modules'][ $module_id ] = $settings;
        $all_settings['last_updated'] = current_time( 'mysql' );

        return update_site_option( self::SETTINGS_KEY, $all_settings );
    }

    /**
     * Get all settings
     */
    public static function get_all_settings() {
        return get_site_option( self::SETTINGS_KEY, array() );
    }

    /**
     * Get all network sites formatted
     */
    public static function get_all_network_sites() {
        $sites = get_sites( array(
            'number' => 10000,
            'orderby' => 'path'
        ) );

        $formatted = array();
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            $formatted[] = array(
                'blog_id' => $site->blog_id,
                'blogname' => get_bloginfo( 'name' ),
                'siteurl' => get_bloginfo( 'url' ),
                'path' => $site->path
            );
            restore_current_blog();
        }

        return $formatted;
    }

    /**
     * Delete module settings
     */
    public static function delete_module_settings( $module_id ) {
        $all_settings = get_site_option( self::SETTINGS_KEY, array() );
        
        if ( isset( $all_settings['modules'][ $module_id ] ) ) {
            unset( $all_settings['modules'][ $module_id ] );
            $all_settings['last_updated'] = current_time( 'mysql' );
            return update_site_option( self::SETTINGS_KEY, $all_settings );
        }
        
        return false;
    }
}
