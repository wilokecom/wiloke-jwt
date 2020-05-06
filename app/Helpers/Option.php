<?php

namespace WilokeJWT\Helpers;

/**
 * Class Option
 */
class Option
{
    private static $optionKey = 'wilokejwt';
    private static $userTokenKey = 'wilokejwt';
    private static $userRefreshTokenKey = 'wilokerefreshjwt';
    private static $aJWTOptions = [];
    private static $aSiteJWTOptions = [];
    
    /**
     * @return array
     */
    public static function getJWTSettings()
    {
        if (!empty(self::$aJWTOptions)) {
            return self::$aJWTOptions;
        }
        
        $aOptions = get_option(self::$optionKey);
        if ((!$aOptions || !isset($aOptions['key']) || empty($aOptions['key'])) && is_multisite()) {
            $aOptions = get_site_option(self::$optionKey);
        }
        
        self::$aJWTOptions = wp_parse_args(
            $aOptions,
            [
                'token_expiry'       => 10,
                'test_token_expired' => '',
                'key'                => uniqid(self::$optionKey.'_'),
                'is_test_mode'       => 'no'
            ]
        );
        
        return self::$aJWTOptions;
    }
    
    /**
     * @return mixed|void
     */
    public static function getSiteJWTSettings()
    {
        if (!is_multisite() || !is_main_site()) {
            self::$aSiteJWTOptions = get_option(self::$optionKey);
        } else {
            self::$aSiteJWTOptions = get_site_option(self::$optionKey);
        }
        
        return self::$aSiteJWTOptions;
    }
    
    /**
     * @param $field
     *
     * @return mixed|string
     */
    public static function getOptionField($field)
    {
        self::getJWTSettings();
        
        return isset(self::$aJWTOptions[$field]) ? self::$aJWTOptions[$field] : '';
    }
    
    /**
     * @return string
     */
    public static function getAccessTokenKey()
    {
        $key = self::getRefreshTokenKey();
        
        return 'access_token_'.$key;
    }
    
    /**
     * @return mixed|string
     */
    public static function getRefreshTokenKey()
    {
        $key = self::getOptionField('key');
        
        return empty($key) ? 'wiloke_jwt' : $key;
    }
    
    /**
     * @return false|int
     */
    public static function getRefreshAccessTokenExp()
    {
        return strtotime('+1000 day');
    }
    
    /**
     * @return bool
     */
    public static function isTestMode()
    {
        return Option::getOptionField('is_test_mode') === 'yes';
    }
    
    /**
     * @return false|int
     */
    public static function getAccessTokenExp()
    {
        if (Option::getOptionField('is_test_mode') === 'yes') {
            $val = abs(Option::getOptionField('test_token_expired'));
            $val = empty($val) ? 10 : $val;
            
            return strtotime('+'.$val.' seconds');
        }
        
        $val = abs(Option::getOptionField('token_expiry'));
        $val = empty($val) ? 1 : $val;
        
        return strtotime('+'.$val.' hour');
    }
    
    /**
     * @param $val
     */
    public static function saveJWTSettings($val)
    {
        if (is_main_site()) {
            update_site_option(self::$optionKey, $val);
        } else {
            update_option(self::$optionKey, $val);
        }
    }
    
    /**
     * @param $userID
     *
     * @return bool|int
     */
    private static function safeGetUserId($userID)
    {
        if (empty($userID)) {
            if (!current_user_can('administrator')) {
                return false;
            }
            
            $userID = get_current_user_id();
        }
        
        return $userID;
    }
    
    /**
     * @param        $token
     * @param string $userID
     *
     * @return bool
     */
    public static function saveUserToken($token, $userID = '')
    {
        $userID = self::safeGetUserId($userID);
        if (empty($userID)) {
            return false;
        }
        
        return update_user_meta($userID, self::$userTokenKey, $token);
    }
    
    /**
     * @param        $freshToken
     * @param string $userID
     *
     * @return bool|int
     */
    public static function saveUserRefreshToken($freshToken, $userID = '')
    {
        $userID = self::safeGetUserId($userID);
        if (empty($userID)) {
            return false;
        }
        
        return update_user_meta($userID, self::$userRefreshTokenKey, $freshToken);
    }
    
    /**
     * @param string $userID
     *
     * @return bool|mixed
     */
    public static function getUserRefreshToken($userID = '')
    {
        $userID = self::safeGetUserId($userID);
        if (empty($userID)) {
            return false;
        }
        
        return get_user_meta($userID, self::$userRefreshTokenKey, true);
    }
    
    /**
     * @param string $userID
     *
     * @return bool|mixed
     */
    public static function getUserAccessToken($userID = '')
    {
        $userID = self::safeGetUserId($userID);
        if (empty($userID)) {
            return false;
        }
        
        return get_user_meta($userID, self::$userTokenKey, true);
    }
    
    /**
     * @param string $userID
     *
     * @return bool
     */
    public static function revokeRefreshToken($userID = '')
    {
        $userID = self::safeGetUserId($userID);
        if (empty($userID)) {
            return false;
        }
        
        return delete_user_meta($userID, self::$userRefreshTokenKey);
    }
    
    /**
     * @param string $userID
     *
     * @return mixed
     */
    public static function getUserToken($userID = '')
    {
        $userID = self::safeGetUserId($userID);
        if (empty($userID)) {
            return false;
        }
        
        return get_user_meta($userID, self::$userTokenKey, true);
    }
    
    /**
     * @param string $userID
     *
     * @return bool
     */
    public static function revokeAccessToken($userID = '')
    {
        $userID = self::safeGetUserId($userID);
        if (empty($userID)) {
            return false;
        }
        
        return delete_user_meta($userID, self::$userTokenKey);
    }
}
