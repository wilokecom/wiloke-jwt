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

    /**
     * @return array
     */
    public static function getJWTSettings()
    {
        if (!empty(self::$aJWTOptions)) {
            return self::$aJWTOptions;
        }

        $aOptions = get_option(self::$optionKey);
        $aOptions = empty($aOptions) ? [] : $aOptions;
        if (empty($aOptions) && is_multisite()) {
            $aOptions = get_site_option(self::$optionKey);
        }

        self::$aJWTOptions = wp_parse_args(
            $aOptions,
            [
                'token_expiry' => 1,
                'key' => uniqid(self::$optionKey . '_')
            ]
        );

        return self::$aJWTOptions;
    }

    /**
     * @param $field
     * @return mixed|string
     */
    public static function getOptionField($field)
    {
        self::getJWTSettings();

        return isset(self::$aJWTOptions[$field]) ? self::$aJWTOptions[$field] : '';
    }

    public static function getAccessTokenKey()
    {
        $key = self::getRefreshTokenKey();
        return 'access_token_' . $key;
    }

    public static function getRefreshTokenKey()
    {
        $key = self::getOptionField('key');
        return empty($key) ? 'wiloke_jwt' : $key;
    }

    public static function getRefreshAccessTokenExp()
    {
        return strtotime('+1000 day');
    }

    public static function getAccessTokenExp() {
        $val = abs(Option::getOptionField('exp'));
        $val = empty($val) ? 1 : $val;

        return strtotime('+'.$val.' day');
    }

    /**
     * @param $val
     */
    public static function saveJWTSettings($val)
    {
        if (is_network_admin()) {
            update_site_option(self::$optionKey, $val);
        } else {
            update_option(self::$optionKey, $val);
        }
    }

    /**
     * @param $userID
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
     * @param $token
     * @param string $userID
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

    public static function saveUserRefreshToken($freshToken, $userID = '')
    {
        $userID = self::safeGetUserId($userID);
        if (empty($userID)) {
            return false;
        }
        return update_user_meta($userID, self::$userRefreshTokenKey, $freshToken);
    }

    public static function getRefreshUserToken($userID = '')
    {
        $userID = self::safeGetUserId($userID);
        if (empty($userID)) {
            return false;
        }

        return get_user_meta($userID, self::$userRefreshTokenKey, true);
    }


    /**
     * @param string $userID
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
