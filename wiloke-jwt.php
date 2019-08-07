<?php
/*
Plugin Name: Wiloke JSW
Description: An useful plugin for WordPress Rest API
Version: 1.0
Text Domain: wiloke-jwt
Domain Path: /lang
Author: Wiloke
Author URI: https://wiloke.com
Plugin URI: https://wiloke.com/
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

require_once plugin_dir_path(__FILE__).'vendor/autoload.php';

define('WILOKE_JWT_VIEWS', plugin_dir_path(__FILE__) . 'app/Views/');

if (is_admin()) {
    new \WilokeJWT\Controllers\AdminMenuController();
}

new \WilokeJWT\Controllers\GenerateTokenController();
new \WilokeJWT\Controllers\VerifyTokenController();
