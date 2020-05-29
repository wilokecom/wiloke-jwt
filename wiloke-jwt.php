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

require_once plugin_dir_path(__FILE__).'vendor/autoload.php';

define('WILOKE_JWT_VIEWS', plugin_dir_path(__FILE__).'app/Views/');
define('INVALID_REFRESH_TOKEN', 'INVALID_REFRESH_TOKEN');

if (is_admin()) {
    new \WilokeJWT\Controllers\AdminMenuController();
}

/**
 * @var $oGenerateTokenController WilokeJWT\Controllers\GenerateTokenController
 */
global $oGenerateTokenController;

$oGenerateTokenController = new \WilokeJWT\Controllers\GenerateTokenController();
$oVerifyTokenController   = new \WilokeJWT\Controllers\VerifyTokenController();

register_activation_hook(__FILE__, 'wilokeJWTSetupDefault');
function wilokeJWTSetupDefault()
{
    /**
     * @var $oGenerateTokenController \WilokeJWT\Controllers\GenerateTokenController
     */
    global $oGenerateTokenController, $current_user;
    
    $aOptions = \WilokeJWT\Helpers\Option::getJWTSettings();
    if (isset($aOptions['isDefault'])) {
        \WilokeJWT\Helpers\Option::saveJWTSettings(
            [
                'token_expiry'       => 10,
                'test_token_expired' => '',
                'key'                => uniqid('wiloke_jwt_'),
                'is_test_mode'       => 'no'
            ]
        );
        
        try {
            $oGenerateTokenController->createRefreshTokenAfterUserRegisteredAccount($current_user->ID);
        } catch (Exception $e) {
        }
    }
}
