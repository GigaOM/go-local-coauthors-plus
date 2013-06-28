<?php

/**
 * Plugin Name: GigaOM Local Customizations for Co-Authors Plus
 * Plugin URI: http://gigaom.com
 * Description: Integrates Co-Authors-Plus with other components
 * Author: GigaOM
 * Version: 1.0
 * Author URI: http://gigaom.com/
 */
 
require __DIR__ . '/components/class-go-local-coauthors-plus.php';

go_coauthors();

require_once __DIR__ . '/components/class-go-local-coauthors-plus-query.php';

go_coauthors_query();
