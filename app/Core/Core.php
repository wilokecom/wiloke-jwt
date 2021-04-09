<?php

namespace WilokeJWT\Core;

use Exception;
use Firebase\JWT\JWT;
use WilokeJWT\Helpers\Option;
use WP_User;

/**
 * Class Core
 * @package WilokeJWT\Core
 */
class Core
{
	/**
	 * @return false|int
	 */
	protected function getTokenExpired()
	{
		return Option::getAccessTokenExp();
	}

	/**
	 * @param $userId
	 *
	 * @return array|mixed
	 */
	private function getBlackListAccessToken($userId)
	{
		$blackListAT = get_user_meta($userId, 'black_list_access_token', true);

		return empty($blackListAT) ? [] : $blackListAT;
	}

	/**
	 * @param $userId
	 * @param $accessToken
	 *
	 * @return bool
	 */
	protected function isBlackListAccessToken($userId, $accessToken)
	{
		$aBlackList = $this->getBlackListAccessToken($userId);

		return in_array($accessToken, $aBlackList);
	}

	/**
	 * @param $userId
	 * @param $accessToken
	 *
	 * @return array|mixed
	 */
	private function setBlackListAccessToken($userId, $accessToken): array
	{
		$aBlackLists = $this->getBlackListAccessToken($userId);
		$aBlackLists = array_splice($aBlackLists, 0, 49); // maximum 50 items only
		array_unshift($aBlackLists, $accessToken);

		update_user_meta($userId, 'black_list_access_token', $aBlackLists);

		return $aBlackLists;
	}

	/**
	 * @param $userId
	 *
	 * @return bool
	 */
	protected function revokeUserAccessToken($userId): bool
	{
		$host = parse_url(home_url('/'), PHP_URL_HOST);
		setcookie(
			'wiloke_my_jwt',
			'',
			current_time('timestamp') - 10000000,
			'/',
			$host,
			is_ssl()
		);

		$accessToken = Option::getUserToken($userId);
		$this->setBlackListAccessToken($userId, $accessToken);

		return Option::revokeAccessToken($userId);
	}

	protected function revokeClientAppAccessToken($appId): bool
	{
		$host = parse_url(home_url('/'), PHP_URL_HOST);
		$this->setBlackListAccessToken($userId, $accessToken);

		return Option::revokeAccessToken($userId);
	}


	/**
	 * @param $userId
	 *
	 * @return bool
	 */
	protected function revokeRefreshAccessToken($userId): bool
	{
		self::revokeUserAccessToken($userId);

		return Option::revokeRefreshToken($userId);
	}

	/**
	 * @param $token
	 *
	 * @return bool|mixed
	 */
	protected function isValidAccessTokenEvenExpired($token)
	{
		try {
			$key = Option::getAccessTokenKey();
			$oParse = JWT::decode($token, $key, ['HS256']);
			$oUser = json_decode($oParse->message);

			return isset($oUser->userID) && !empty($oUser->userID);
		}
		catch (Exception $oE) {
			return strtoupper($oE->getMessage()) === 'EXPIRED TOKEN';
		}
	}

	/**
	 * @param        $token
	 * @param string $type
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function verifyToken($token, $type = 'access_token')
	{
		$errMsg = '';
		$oUser = (object)[];

		try {
			if ($type === 'refresh_token') {
				$key = Option::getRefreshTokenKey();
			} else {
				$key = Option::getAccessTokenKey();
			}

			$oParse = JWT::decode($token, $key, ['HS256']);
			$oUser = json_decode($oParse->message);
			if (!isset($oUser->userID) || empty($oUser->userID)) {
				$errMsg = esc_html__('The user has been removed or does not exist', 'wiloke-jwt');
			} else {
				if ($type === 'refresh_token') {
					$currentToken = Option::getUserRefreshToken($oUser->userID);
					if ($token !== $currentToken) {
						$errMsg = esc_html__('The token has been expired', 'wiloke-jwt');
					}
				}
			}

		}
		catch (Exception $oE) {
			$errMsg = $oE->getMessage();
		}

		if (!empty($errMsg)) {
			throw new Exception($errMsg);
		}

		return $oUser;
	}

	/**
	 * @param $refreshToken
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function renewAccessToken($refreshToken)
	{
		$oInfo = $this->verifyToken($refreshToken, 'refresh_token');
		$oUser = new WP_User($oInfo->userID);

		if (empty($oUser) || is_wp_error($oUser)) {
			throw new Exception(esc_html__('The user has been removed', 'wiloke-jwt'));
		}

		$this->revokeUserAccessToken($oUser->ID);

		return $this->generateToken($oUser);
	}

	/**
	 * @param array $aInfo
	 * @return false|string
	 */
	private function generateTokenMsg(array $aInfo)
	{
		return json_encode($aInfo);
	}

	private function generateUserTokenMsg($oUser)
	{
		return $this->generateUserTokenMsg(
			[
				'userID'    => $oUser->ID,
				'userName'  => $oUser->user_login,
				'userEmail' => $oUser->user_email
			]
		);
	}

	private function generateClientAppTokenMsg(\WP_Post $post)
	{
		return $this->generateUserTokenMsg(
			[
				'userID' => $post->post_author,
				'postID' => $post->ID
			]
		);
	}

	/**
	 * @param array $aMessage
	 * @param int $expiredUntil
	 * @return mixed|string
	 */
	protected function generateToken(array $aMessage, int $expiredUntil): string
	{
		$aPayload = [
			'message' => $aMessage,
			'exp'     => $expiredUntil
		];

		return JWT::encode($aPayload, Option::getAccessTokenKey());
	}

	protected function generateClientAppToken(WP_User $oUser, $ignoreSetCookie = false)
	{
		$token = Option::getClientAppPublicToken($oUser->ID);

		if (!empty($token)) {
			throw new \Exception('The token has been generated already');
		}

		$aPayload = [
			'message' => $this->generateUserTokenMsg($oUser),
			'exp'     => $this->getTokenExpired()
		];

		$token = JWT::encode($aPayload, Option::getAccessTokenKey());
		Option::saveUserToken($token, $oUser->ID);

		do_action('wiloke-jwt/created-access-token', $token, Option::getAccessTokenExp(), $ignoreSetCookie);

		return $token;
	}

	protected function generateUserToken(WP_User $oUser, $ignoreSetCookie = false)
	{
		$token = Option::getUserToken($oUser->ID);

		if (!empty($token)) {
			try {
				$oUserInfo = $this->verifyToken($token);
			}
			catch (Exception $exception) {

			}
		}

		if (!isset($oUserInfo)) {
			$aPayload = [
				'message' => $this->generateUserTokenMsg($oUser),
				'exp'     => $this->getTokenExpired()
			];

			$token = JWT::encode($aPayload, Option::getAccessTokenKey());
			Option::saveUserToken($token, $oUser->ID);

			do_action('wiloke-jwt/created-access-token', $token, Option::getAccessTokenExp(), $ignoreSetCookie);
		} else {
			do_action('wiloke-jwt/created-access-token', $token, Option::getAccessTokenExp(), $ignoreSetCookie);
		}

		return $token;
	}

	/**
	 * @param array<{userID: int, postId: ?int}> $aMessage
	 * @param $expiredUntil
	 * @return string
	 */
	protected function generateRefreshToken(array $aMessage, int $expiredUntil): string
	{
		$aPayload = [
			'message' => $aMessage,
			'exp'     => $expiredUntil
		];

		return JWT::encode($aPayload, Option::getRefreshTokenKey());
	}

	/**
	 * @param $accessToken
	 *
	 * @return bool
	 */
	protected function isAccessTokenExpired($accessToken)
	{
		try {
			JWT::decode($accessToken, Option::getAccessTokenKey(), ['HS256']);
			//            $oUserInfo = json_decode($oParse->message);
			//
			//            $userAccessToken = Option::getUserToken($oUserInfo->userID);
			//            $this->verifyToken($userAccessToken);
			return false;
		}
		catch (Exception $exception) {
			$message = $exception->getMessage();
			if (is_string($message) && strtoupper($message) === 'EXPIRED TOKEN') {
				return true;
			}

			return false;
		}
	}
}
