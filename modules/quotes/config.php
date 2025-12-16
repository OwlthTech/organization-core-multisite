<?php
return array(
    'id' => 'quotes',
    'name' => __( 'Quotes System', 'organization-core' ),
    'description' => __( 'Manage and display quotes', 'organization-core' ),
    'version' => '1.0.0',
    'author' => 'OwlthTech',
    'default_enabled' => true,
    'network_only' => true,
    'required' => true,
    'dependencies' => array(),
    'supports' => array( 'templates' ),
    'class' => 'class-quotes.php'
);
