<?php

namespace WilokeJWT\Helpers;

/**
 * Class Option
 */
class Option
{
	private static string $clientAppPublicKey    = 'wilokejwt_public_key';
	private static string $clientAppSecretAppKey = 'wilokejwt_secret_key';
	private static string $optionKey             = 'wilokejwt';
	private static string $userTokenKey          = 'wilokejwt';
	private static string $userRefreshTokenKey   = 'wilokerefreshjwt';
	private static array  $aJWTOptions           = [];
	private static array  $aSiteJWTOptions       = [];

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
				'key'                => uniqid(self::$optionKey . '_'),
				'is_test_mode'       => 'no'
			]
		);

		if (empty($aOptions)) {
			self::$aJWTOptions['isDefault'] = true;
		}

		return self::$aJWTOptions;
	}

	/**
	 * @return mixed|void
	 */
	public static function getSiteJWTSettings()
	{
		if (!is_multisite() || !is_network_admin()) {
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

		return 'access_token_' . $key;
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

			return strtotime('+' . $val . ' seconds');
		}

		$val = abs(Option::getOptionField('token_expiry'));
		$val = empty($val) ? 1 : $val;

		return strtotime('+' . $val . ' hour');
	}

	public static function getClientAppTokenExpired(): int
	{
		return strtotime('+3650 days');
	}

	/**
	 * @param int $postId
	 * @return bool|mixed
	 */
	public static function getClientAppPublicToken(int $postId): bool
	{
		return (string)get_post_meta($postId, self::$clientAppPublicKey, true);
	}


	/**
	 * @param int $postId
	 * @param string $token
	 * @return bool|mixed
	 */
	public static function setClientAppPublicToken(int $postId, string $token): bool
	{
		return (string)update_post_meta($postId, self::$clientAppPublicKey, $token);
	}

	/**
	 * @param int $postId
	 * @return bool|mixed
	 */
	public static function getClientAppSecretToken(int $postId): bool
	{
		return (string)get_post_meta($postId, self::$clientAppSecretAppKey, true);
	}

	/**
	 * @param int $postId
	 * @param string $token
	 * @return bool|mixed
	 */
	public static function setClientAppSecretToken(int $postId, string $token): bool
	{
		return (string)update_post_meta($postId, self::$clientAppSecretAppKey, $token);
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
	 *
	 * @return bool|int
	 */
	private static function safeGetUserId($userID)
	{
		if (empty($userID)) {
			global $current_user;
			if (!$current_user instanceof \WP_User || $current_user->ID === 0 ||
				!in_array('administrator', $current_user->roles)) {
				return false;
			}

			$userID = $current_user->ID;
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
