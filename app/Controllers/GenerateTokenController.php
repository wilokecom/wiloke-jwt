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
        add_action('set_logged_in_cookie', [$this, 'afterLoggedInCookieWasSet'], 10, 4);
        add_action('admin_init', [$this, 'fixGenerateTokenIfUserLoggedIntoSiteBeforeInstallingMe']);
        add_action('wiloke-jwt/created-access-token', [$this, 'addAccessTokenToLocalStore'], 10, 3);
        add_action('clear_auth_cookie', [$this, 'deleteAccessTokenFromLocalStore']);
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
     * @param $ignoreSetCookie
     * @param $expiry
     *
     * @return bool
     */
    public function addAccessTokenToLocalStore($token, $expiry, $ignoreSetCookie)
    {
        if ($ignoreSetCookie) {
            return false;
        }
        
        $oSettings                 = Option::getJWTSettings();
        $host                      = parse_url(get_option('siteurl'), PHP_URL_HOST);
        $oSettings['token_expiry'] = empty($oSettings['token_expiry']) ? 1000000000000 : $oSettings['token_expiry'];
        
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
                self::generateToken(get_current_user_id(), 0);
            }
        }
    }
    
    /**
     * @param      $userID
     * @param      $expire
     * @param bool $ignoreSetCookie
     *
     * @return string
     */
    private function generateToken($userID, $expire, $ignoreSetCookie = false)
    {
        $oUser              = new \WP_User($userID);
        $createdAccessToken = Option::getUserToken($oUser->ID);
        
        if (!empty($createdAccessToken)) {
            $aVerifyStatus = apply_filters('wiloke-jwt/filter/verify-token', [
                'error' => [
                    'message' => 'Invalid Token',
                    'code'    => 401
                ]
            ], $createdAccessToken);
        }
        
        if (!isset($aVerifyStatus) || isset($aVerifyStatus['error'])) {
            $aPayload = [
                'message' => $this->generateTokenMsg($oUser)
            ];
            $aOptions = Option::getJWTSettings();
            
            if (!empty($aOptions['exp'])) {
                $aPayload['exp'] = strtotime('+'.$aOptions['exp'].' day');
            }
            
            $encoded = JWT::encode($aPayload, $aOptions['key']);
            Option::saveUserToken($encoded, $oUser->ID);
            
            do_action('wiloke-jwt/created-access-token', $encoded, $expire, $ignoreSetCookie);
            
            return $encoded;
        } else {
            do_action('wiloke-jwt/created-access-token', $createdAccessToken, $expire, $ignoreSetCookie);
        }
    }
    
    /**
     * @param      $loggedInCookie
     * @param      $expire
     * @param      $expiration
     * @param      $userID
     *
     * @return string
     */
    public function afterLoggedInCookieWasSet($loggedInCookie, $expire, $expiration, $userID)
    {
        $this->generateToken($userID, $expire);
    }
    
    /**
     * @param $userID
     *
     * @return string
     */
    public function generateTokenAfterCreatingAccount($userID)
    {
        $oUser = new \WP_User($userID);
        
        return $this->generateToken($oUser->user_login, $oUser, true);
    }
}
