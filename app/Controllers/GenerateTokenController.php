<?php

namespace WilokeJWT\Controllers;

use Exception;
use Firebase\JWT\JWT;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\Option;
use WilokeJWT\Helpers\Session;
use WP_User;

/**
 * Class GenerateTokenController
 * @package WilokeJWT\Controllers
 */
final class GenerateTokenController extends Core
{
	private static bool $handlingUserLogin = false;
	private static bool $clearingToken = false;

	/**
	 * GenerateTokenController constructor.
	 */
	public function __construct()
	{
		add_action('set_logged_in_cookie', [$this, 'handleTokenAfterUserSetLoggedInCookie'], 10, 4);
		add_action('wiloke-jwt/created-access-token', [$this, 'storeAccessTokenToCookie'], 10, 3);
		add_action(
			'wiloke-jwt/created-access-and-refresh-token',
			[$this, 'storeAccessAndRefreshTokenToCookie'],
			10,
			2
		);

		add_action('wiloke/jwt/clear-token', [$this, 'removeAccessTokenAfterLogout']);
		add_action('clear_auth_cookie', [$this, 'removeAccessTokenAfterLogout']);
		add_filter('wiloke/filter/get-refresh-token', [$this, 'getUserRefreshToken']);
		add_filter('wiloke/filter/revoke-access-token', [$this, 'filterRevokeAccessToken'], 10, 2);
		add_filter('wiloke/filter/revoke-refresh-access-token', [$this, 'filterRevokeRefreshAccessToken'], 10, 2);
		add_filter('wiloke/filter/renew-access-token', [$this, 'filterRenewAccessToken'], 10, 3);
		add_filter('wiloke/filter/is-access-token-expired', [$this, 'filterIsTokenExpired'], 10, 2);
		add_action('clean_user_cache', [$this, 'maybeRevokeRefreshPasswordAfterUpdatingUser'], 10, 2);
		add_action('delete_user', [$this, 'deleteTokensBeforeDeletingUser'], 10);
	}

	function autoGenerateTokenAfterActivatingPlugin(): bool
	{
		if (!is_user_logged_in()) {
			return false;
		}
		/**
		 * @var $oGenerateTokenController GenerateTokenController
		 */
		global $current_user;
		$aOptions = Option::getJWTSettings();
		$this->storeAccessAndRefreshTokenToCookie(Option::getUserAccessToken(get_current_user_id()),
			Option::getUserRefreshToken(get_current_user_id()));
		if (isset($aOptions['isDefault'])) {
			Option::saveJWTSettings($aOptions);
			try {
				$this->createRefreshTokenAfterUserRegisteredAccount(
					$current_user->ID,
					true
				);
			}
			catch (Exception $e) {
			}
		}

		return true;
	}

	/**
	 * @param $assetToken
	 * @param $refersToken
	 *
	 * @return bool
	 */
	public function storeAccessAndRefreshTokenToCookie($assetToken, $refersToken): bool
	{
		$host = parse_url(home_url('/'), PHP_URL_HOST);
		setcookie(
			'wiloke_my_jwt',
			$assetToken,
			$this->getTokenExpired(),
			'/',
			$host,
			is_ssl()
		);
		setcookie(
			'wiloke_my_rf_token',
			$refersToken,
			$this->getTokenExpired(),
			'/',
			$host,
			is_ssl()
		);

		return true;
	}

	/**
	 * @param $token
	 * @param $ignoreSetCookie
	 *
	 * @return bool
	 */
	public function storeAccessTokenToCookie($token, $ignoreSetCookie = false)
	{
		if ($ignoreSetCookie) {
			return false;
		}

		$host = parse_url(home_url('/'), PHP_URL_HOST);

		setcookie(
			'wiloke_my_jwt',
			$token,
			$this->getTokenExpired(),
			'/',
			$host,
			is_ssl()
		);
	}

	/**
	 * @param      $userId
	 * @param bool $isDirectly
	 *
	 * @return array
	 */
	public function createRefreshTokenAfterUserRegisteredAccount($userId, bool $isDirectly = false): array
	{
		if (isset($_GET['import']) && $_GET['import'] == 'wordpress') {
			return [
				'error' => [
					'messages' => 'Importing data',
					'code'     => 400
				]
			];
		}

		if (empty($userId)) {
			return [
				'error' => [
					'messages' => 'Invalid User Id',
					'code'     => 400
				]
			];
		}

		$oUser = new WP_User($userId);

		if (empty($oUser) || is_wp_error($oUser)) {
			return [
				'error' => [
					'message' => 'Invalid User',
					'code'    => 400
				]
			];
		}

		$refreshToken = $this->generateRefreshToken($oUser);
		$aResponse = [];

		if (!empty($refreshToken)) {
			try {
				$accessToken = $this->renewAccessToken($refreshToken);
				$aResponse = [
					'accessToken'  => $accessToken,
					'refreshToken' => $refreshToken,
					'userId'       => $userId,
					'oUser'        => $oUser,
					'isDirectly'   => $isDirectly
				];
				do_action('wiloke-jwt/created-refresh-token', $aResponse);
			}
			catch (Exception $exception) {
				$aResponse = [
					'error' => [
						'message' => $exception->getMessage(),
						'code'    => 400
					]
				];
			}
		}

		return $aResponse;
	}

	/**
	 * @param $status
	 * @param $accessToken
	 *
	 * @return bool
	 */
	public function filterIsTokenExpired($status, $accessToken)
	{
		return $this->isAccessTokenExpired($accessToken);
	}

	/**
	 *
	 * @param $userId
	 *
	 * @return bool
	 */
	public function removeAccessTokenAfterLogout(): bool
	{
		if (self::$clearingToken) {
			return false;
		}

		self::$clearingToken = true;
		do_action(
			'wiloke-jwt/Controllers/GenerateTokenController/removedAccessTokenAfterLogout',
			get_current_user_id()
		);
		\WilokeShopifyLogin\Helpers\Session::destroySession('handleAuth');
		return $this->clearCookie();
	}

	/**
	 * @param $status
	 * @param $userId
	 *
	 * @return bool
	 */
	public function filterRevokeAccessToken($status, $userId): bool
	{
		return $this->revokeAccessToken($userId);
	}

	/**
	 * @param $userID
	 */
	public function deleteTokensBeforeDeletingUser($userID)
	{
		$this->revokeRefreshAccessToken($userID);
	}

	/**
	 * @param         $userID
	 * @param WP_User $oUser
	 *
	 * @return array|bool
	 */
	public function maybeRevokeRefreshPasswordAfterUpdatingUser($userID, WP_User $oUser)
	{
		if (self::$handlingUserLogin) {
			return false;
		}

		$token = Option::getUserRefreshToken($oUser->ID);
		if (!empty($token)) {
			return $this->filterRevokeRefreshAccessToken(null, $token);
		}
	}

	/**
	 * @param $status
	 * @param $token
	 *
	 * @return array|bool
	 */
	public function filterRevokeRefreshAccessToken($status, $token)
	{
		try {
			$oUserInfo = $this->verifyToken($token, 'refresh_token');
			$this->revokeRefreshAccessToken($oUserInfo->userID);
			$oUser = new WP_User($oUserInfo->userID);
			$refreshToken = $this->generateRefreshToken($oUser);
			$accessToken = $this->generateToken($oUser);

			return [
				'data' => [
					'refreshToken' => $refreshToken,
					'accessToken'  => $accessToken
				]
			];
		}
		catch (Exception $exception) {
			return [
				'error' => [
					'message' => $exception->getMessage(),
					'code'    => 401
				]
			];
		}
	}

	public function fixGenerateTokenIfUserLoggedIntoSiteBeforeInstallingMe()
	{
		if (current_user_can('administrator')) {
			if (empty(Option::getUserToken())) {
				$oUser = new WP_User(get_current_user_id());
				$this->generateToken($oUser);
			}
		}
	}

	/**
	 * @param string $userId
	 *
	 * @return bool|mixed
	 */
	public function getUserRefreshToken($userId = '')
	{
		$userId = !empty($userId) ? $userId : get_current_user_id();

		return Option::getUserRefreshToken($userId);
	}

	/**
	 * @param $logged_in_cookie
	 * @param $expire
	 * @param $expiration
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function handleTokenAfterUserSetLoggedInCookie($logged_in_cookie, $expire, $expiration, $user_id): bool
	{
		$oUser = new WP_User($user_id);
		return $this->handleTokenAfterUserSignedIn($oUser->user_email, $oUser);
	}

	/**
	 * @param $userEmail
	 * @param $oUser
	 *
	 * @return bool
	 */
	public function handleTokenAfterUserSignedIn($userEmail, $oUser): bool
	{
		if (self::$handlingUserLogin || empty($oUser) || is_wp_error($oUser)) {
			return false;
		}

		self::$handlingUserLogin = true;

		$refreshToken = Option::getUserRefreshToken($oUser->ID);

		$accessToken = '';
		if (empty($refreshToken)) {
			$this->generateRefreshToken($oUser);
		} else {
			try {
				$this->verifyToken($refreshToken, 'refresh_token');
			}
			catch (Exception $exception) {
				$this->generateRefreshToken($oUser);
			}
			$accessToken = Option::getUserToken($oUser->ID);
		}

		try {
			$this->verifyToken($accessToken);
			if (Option::isTestMode()) {
				$refreshToken = Option::getUserRefreshToken($oUser->ID);
				$accessToken = $this->renewAccessToken($refreshToken);
				$this->storeAccessTokenToCookie($accessToken);
			}
		}
		catch (Exception $exception) {
			$refreshToken = Option::getUserRefreshToken($oUser->ID);

			if (!empty($refreshToken)) {
				try {
					$accessToken = $this->renewAccessToken($refreshToken);
					$this->storeAccessTokenToCookie($accessToken);
				}
				catch (Exception $exception) {
					\WilokeShopifyLogin\Helpers\Session::destroySession('handleAuth');
					return false;
				}
			}

			\WilokeShopifyLogin\Helpers\Session::destroySession('handleAuth');
			return false;
		}

		do_action('wiloke-jwt/created-refresh-token', [
			'accessToken'  => $accessToken,
			'refreshToken' => $refreshToken,
			'isDirectly'   => true,
			'userId'       => $oUser->ID
		]);

		return true;
	}

	/**
	 * @param $userID
	 *
	 * @return mixed|string
	 * @throws Exception
	 */
	public function generateTokenAfterCreatingAccount($userID)
	{
		$oUser = new WP_User($userID);

		return $this->generateToken($oUser, true);
	}

	/**
	 * @param        $aStatus
	 * @param        $refreshToken
	 * @param string $oldAccessToken
	 *
	 * @return array
	 */
	public function filterRenewAccessToken($aStatus, $refreshToken, $oldAccessToken = '')
	{
		try {
			$oUserInfo = $this->verifyToken($refreshToken, 'refresh_token');
		}
		catch (Exception $e) {
			return [
				'error' => [
					'message'    => $e->getMessage(),
					'code'       => 400,
					'statusCode' => 'INVALID_REFRESH_TOKEN'
				]
			];
		}

		try {
			$accessToken = !empty($oldAccessToken) ? $oldAccessToken : Option::getUserToken($oUserInfo->userID);

			if ($this->isAccessTokenExpired($accessToken)) {
				return [
					'accessToken' => $this->renewAccessToken($refreshToken),
					'userID'      => $oUserInfo->userID
				];
			} else {
				return [
					'error' => [
						'message' => esc_html__('The renew token is freezed', 'hsblog-core'),
						'code'    => 403
					]
				];
			}
		}
		catch (Exception $e) {
			return [
				'error' => [
					'message'    => $e->getMessage(),
					'code'       => 401,
					'statusCode' => 'TOKEN_EXPIRED'
				]
			];
		}
	}
}
