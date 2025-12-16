<?php
return array(
    'id' => 'packages',
    'name' => __( 'Packages System', 'organization-core' ),
    'description' => __( 'Manage and display packages', 'organization-core' ),
    'version' => '1.0.0',
    'author' => 'OwlthTech',
    'default_enabled' => true,
    'network_only' => true,
    'required' => true,
    'dependencies' => array(),
    'supports' => array( 'templates' ),
    'class' => 'class-packages.php'
);
