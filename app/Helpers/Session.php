<?php

namespace WilokeJWT\Helpers;

/**
 * Class Session
 * @package HSBlogCore\Helpers
 */
class Session
{
	/**
	 * @param $name
	 *
	 * @return string
	 */
	protected static function generatePrefix($name): string
	{
		return WILOKE_JWT_PREFIX.$name;
	}

	public static function sessionStart()
	{
		if (!headers_sent() && session_status() == PHP_SESSION_NONE) {
			session_start();
		}
	}

	/**
	 * @param null $name
	 */
	public static function destroySession($name = null)
	{
		if (!empty(self::generatePrefix($name))) {
			unset($_SESSION[self::generatePrefix($name)]);
		} else {
			session_destroy();
		}
	}

	/**
	 * @param      $name
	 * @param      $value
	 * @param null $sessionID
	 */
	public static function setSession($name, $value, $sessionID = null)
	{
		$value = maybe_serialize($value);
		if (empty(session_id())) {
			self::sessionStart();
		}
		$_SESSION[self::generatePrefix($name)] = $value;
	}

	/**
	 * @param      $name
	 * @param bool $thenDestroy
	 *
	 * @return bool|mixed
	 */
	public static function getSession($name, $thenDestroy = false): bool
	{
		if (defined('WILOKE_STORE_WITH_DB') && WILOKE_STORE_WITH_DB) {
			$value = get_transient(self::generatePrefix($name));
		} else {
			if (empty(session_id())) {
				self::sessionStart();
			}

			$value = isset($_SESSION[self::generatePrefix($name)]) ? $_SESSION[self::generatePrefix($name)] : '';
		}

		if (empty($value)) {
			return false;
		}

		if ($thenDestroy) {
			self::destroySession($name);
		}

		return maybe_unserialize($value);
	}
}
