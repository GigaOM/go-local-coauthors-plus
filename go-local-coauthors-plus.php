<?php
/**
 * Plugin Name: Co-Authors Plus - Gigaom Local Customizations
 * Plugin URI: http://gigaom.com
 * Description: Integrates Co-Authors-Plus with other components
 * Author: Gigaom
 * Version: 1.0
 * Author URI: http://gigaom.com/
 */

require_once __DIR__ . '/components/class-go-local-coauthors-plus.php';
go_coauthors();

require_once __DIR__ . '/components/class-go-local-coauthors-plus-query.php';
go_coauthors_query();
