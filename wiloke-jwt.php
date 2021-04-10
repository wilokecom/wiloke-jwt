<?php
/*
Plugin Name: Wiloke JSW
Description: A useful plugin for WordPress Rest API
Version: 1.0
Text Domain: wiloke-jwt
Domain Path: /lang
Author: Wiloke
Author URI: https://wiloke.com
Plugin URI: https://wiloke.com/
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

use WilokeJWT\Controllers\LoginController;
use WilokeJWT\Controllers\PostTypeRegistry;
use WilokeJWT\Controllers\UserProfileController;
use WilokeJWT\DB\BlackListTokensTbl;

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

define( 'WILOKE_JWT_API_NAMESPACE', 'wiloke-jwt' );
define( 'WILOKE_JWT_API_VERSION', 'v1' );
define( 'WILOKE_JWT_API', WILOKE_JWT_API_NAMESPACE . '/' . WILOKE_JWT_API_VERSION );
define( 'WILOKE_JWT_VIEWS', plugin_dir_path( __FILE__ ) . 'app/Views/' );
define( 'INVALID_REFRESH_TOKEN', 'INVALID_REFRESH_TOKEN' );
define( 'WILOKE_JWT_VERSION', 1.0 );
define( 'WILOKE_JWT_URL', plugin_dir_url( __FILE__ ) );
define( 'WILOKE_JWT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WILOKE_JWT_PREFIX', 'wiloke-jwt_' );

if ( is_admin() ) {
	new \WilokeJWT\Controllers\AdminMenuController();
}

/**
 * @var $oGenerateTokenController WilokeJWT\Controllers\GenerateTokenController
 */
global $oGenerateTokenController;

$oGenerateTokenController = new \WilokeJWT\Controllers\GenerateTokenController();
$oVerifyTokenController   = new \WilokeJWT\Controllers\VerifyTokenController();
//new UserProfileController;
new PostTypeRegistry;
new LoginController;

register_activation_hook( __FILE__, function () {
	do_action( 'wiloke_jwt_plugin_activated' );
	new BlackListTokensTbl;
	new \WilokeJWT\DB\PreLoginTbl();
} );
