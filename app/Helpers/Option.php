<?php
namespace WilokeJWT\Helpers;

/**
 * Class Option
 */
class Option
{
    private static $optionKey = 'wilokejwt';
    private static $userTokenKey = 'wilokejwt';
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

        self::$aJWTOptions = wp_parse_args(
            $aOptions,
            [
                'token_expiry' => 30,
                'key'          => uniqid(self::$optionKey.'_')
            ]
        );

        return self::$aJWTOptions;
    }

    /**
     * @param $val
     */
    public static function saveJWTSettings($val)
    {
        update_option(self::$optionKey, $val);
    }

    /**
     * @param $userID
     * @param $token
     */
    public static function saveUserToken($token, $userID = '')
    {
        $userID = empty($userID) ? get_current_user_id() : $userID;
        update_user_meta($userID, self::$userTokenKey, $token);
    }

    /**
     * @param string $userID
     *
     * @return mixed
     */
    public static function getUserToken($userID = '')
    {
        $userID = empty($userID) ? get_current_user_id() : $userID;
        return get_user_meta($userID, self::$userTokenKey, true);
    }
}
