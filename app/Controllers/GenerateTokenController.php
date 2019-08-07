<?php

namespace WilokeJWT\Controllers;

use Firebase\JWT\JWT;
use WilokeJWT\Helpers\Option;

/**
 * Class GenerateTokenController
 * @package WilokeJWT\Controllers
 */
class GenerateTokenController
{
    /**
     * GenerateTokenController constructor.
     */
    public function __construct()
    {
        add_action('wp_login', [$this, 'generateTokenAfterLoggedIn'], 10, 2);
        add_action('user_register', [$this, 'generateTokenAfterCreatingAccount']);
        add_action('admin_init', [$this, 'fixGenerateTokenIfUserLoggedIntoSiteBeforeInstallingMe']);
        add_action('wiloke-jwt/created-access-token', [$this, 'addAccessTokenToLocalStore']);
        add_action('wp_logout', [$this, 'deleteAccessTokenFromLocalStore']);
    }

    public function deleteAccessTokenFromLocalStore()
    {
        $host = parse_url(get_option('siteurl'), PHP_URL_HOST);
        setcookie(
            'wiloke_my_jwt',
            '',
            current_time('timestamp') - 10000000,
            '/',
            $host,
            is_ssl()
        );
    }

    /**
     * @param $token
     */
    public function addAccessTokenToLocalStore($token)
    {
        $oSettings = Option::getJWTSettings();
        $host      = parse_url(get_option('siteurl'), PHP_URL_HOST);

        $expiry = time() + (86400 * $oSettings['token_expiry']);
        setcookie(
            'wiloke_my_jwt',
            $token,
            $expiry,
            '/',
            $host,
            is_ssl()
        );
    }

    /**
     * @param $oUser \WP_User
     *
     * @return array
     */
    private function generateTokenMsg($oUser)
    {
        return json_encode(
            [
                'userID'    => $oUser->ID,
                'userName'  => $oUser->user_login,
                'userEmail' => $oUser->user_email
            ]
        );
    }

    public function fixGenerateTokenIfUserLoggedIntoSiteBeforeInstallingMe()
    {
        if (current_user_can('administrator')) {
            if (empty(Option::getUserToken())) {
                $oUser = new \WP_User(get_current_user_id());
                self::generateTokenAfterLoggedIn('', $oUser);
            }
        }
    }

    /**
     * @param $userLogin
     * @param $oUser
     *
     * @return string
     */
    public function generateTokenAfterLoggedIn($userLogin, $oUser)
    {
        $aPayload = [
            'message' => $this->generateTokenMsg($oUser)
        ];
        $aOptions = Option::getJWTSettings();

        if (!empty($aOptions['exp'])) {
            $aPayload['exp'] = strtotime('+'.$aOptions['exp'].' day');
        }

        $encoded = JWT::encode($aPayload, $aOptions['key']);
        Option::saveUserToken($encoded, $oUser->ID);

        do_action('wiloke-jwt/created-access-token', $encoded);

        return $encoded;
    }

    /**
     * @param $userID
     *
     * @return string
     */
    public function generateTokenAfterCreatingAccount($userID)
    {
        $oUser = new \WP_User($userID);

        return $this->generateTokenAfterLoggedIn($oUser->user_login, $oUser);
    }
}
