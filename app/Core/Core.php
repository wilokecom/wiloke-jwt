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
class Core {
	/**
	 * @return false|int
	 */
	protected function getTokenExpired() {
		return Option::getAccessTokenExp();
	}

	/**
	 * @param $userId
	 *
	 * @return array|mixed
	 */
	private function getBlackListAccessToken( $userId ) {
		$blackListAT = get_user_meta( $userId, 'black_list_access_token', true );

		return empty( $blackListAT ) ? [] : $blackListAT;
	}

	/**
	 * @param $userId
	 * @param $accessToken
	 *
	 * @return bool
	 */
	protected function isBlackListAccessToken( $userId, $accessToken ) {
		$aBlackList = $this->getBlackListAccessToken( $userId );

		return in_array( $accessToken, $aBlackList );
	}

	/**
	 * @param $userId
	 * @param $accessToken
	 *
	 * @return array
	 */
	private function setBlackListAccessToken( $userId, $accessToken ): array {
		$aBlackLists = $this->getBlackListAccessToken( $userId );
		$aBlackLists = array_splice( $aBlackLists, 0, 49 ); // maximum 50 items only
		array_unshift( $aBlackLists, $accessToken );

		update_user_meta( $userId, 'black_list_access_token', $aBlackLists );

		return $aBlackLists;
	}

	protected function clearTokens(): bool {
		$this->clearAccessTokenCookie();
		$this->clearRefreshTokenCookie();

		return true;
	}

	protected function setTokens(): bool {
		$this->setAccessTokenCookie();
		$this->setRefreshAccessTokenToCookie();

		return true;
	}

	protected function clearAccessTokenCookie(): bool {
		$host = parse_url( home_url( '/' ), PHP_URL_HOST );
		setcookie(
			'wiloke_my_jwt',
			'',
			current_time( 'timestamp' ) - 10000000,
			'/',
			$host,
			is_ssl()
		);

		return true;
	}

	protected function clearRefreshTokenCookie(): bool {
		$host = parse_url( home_url( '/' ), PHP_URL_HOST );
		setcookie(
			'wiloke_my_rf_token',
			'',
			current_time( 'timestamp' ) - 10000000,
			'/',
			$host,
			is_ssl()
		);

		return true;
	}

	/**
	 * @param $token
	 *
	 * @return bool
	 */
	protected function setAccessTokenCookie( $token = '' ): bool {
		$token = empty( $token ) ? Option::getUserAccessToken() : $token;
		$host  = parse_url( home_url( '/' ), PHP_URL_HOST );
		setcookie(
			'wiloke_my_jwt',
			$token,
			$this->getTokenExpired(),
			'/',
			$host,
			is_ssl()
		);

		return true;
	}

	protected function setRefreshAccessTokenToCookie( $refreshToken = '' ) {
		$refreshToken = empty( $refreshToken ) ? Option::getUserRefreshToken() : $refreshToken;
		$host         = parse_url( home_url( '/' ), PHP_URL_HOST );
		setcookie(
			'wiloke_my_rf_token',
			$refreshToken,
			$this->getTokenExpired(),
			'/',
			$host,
			is_ssl()
		);
	}

	/**
	 * @param $userId
	 *
	 * @return bool
	 */
	protected function revokeAccessToken( $userId ): bool {
		$host = parse_url( home_url( '/' ), PHP_URL_HOST );
		setcookie(
			'wiloke_my_jwt',
			'',
			current_time( 'timestamp' ) - 10000000,
			'/',
			$host,
			is_ssl()
		);

		$accessToken = Option::getUserToken( $userId );
		$this->setBlackListAccessToken( $userId, $accessToken );

		return Option::revokeAccessToken( $userId );
	}

	/**
	 * @param $userId
	 *
	 * @return bool
	 */
	protected function revokeRefreshAccessToken( $userId ) {
		self::revokeAccessToken( $userId );

		return Option::revokeRefreshToken( $userId );
	}

	/**
	 * @param $token
	 *
	 * @return bool|mixed
	 */
	protected function isValidAccessTokenEvenExpired( $token ) {
		try {
			$key    = Option::getAccessTokenKey();
			$oParse = JWT::decode( $token, $key, [ 'HS256' ] );
			$oUser  = json_decode( $oParse->message );

			return isset( $oUser->userID ) && ! empty( $oUser->userID );
		}
		catch ( Exception $oE ) {
			return strtoupper( $oE->getMessage() ) === 'EXPIRED TOKEN';
		}
	}

	/**
	 * @param        $token
	 * @param string $type
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function verifyToken( $token, $type = 'access_token' ) {
		$errMsg = '';
		$oUser  = (object) [];

		try {
			if ( $type === 'refresh_token' ) {
				$key = Option::getRefreshTokenKey();
			} else {
				$key = Option::getAccessTokenKey();
			}

			$oParse = JWT::decode( $token, $key, [ 'HS256' ] );
			$oUser  = json_decode( $oParse->message );
			if ( ! isset( $oUser->userID ) || empty( $oUser->userID ) ) {
				$errMsg = esc_html__( 'The user has been removed or does not exist', 'wiloke-jwt' );
			} else {
				if ( $type === 'refresh_token' ) {
					$currentToken = Option::getUserRefreshToken( $oUser->userID );
					if ( $token !== $currentToken ) {
						$errMsg = esc_html__( 'The token has been expired', 'wiloke-jwt' );
					}
				}
			}

		}
		catch ( Exception $oE ) {
			$errMsg = $oE->getMessage();
		}

		if ( ! empty( $errMsg ) ) {
			throw new Exception( $errMsg );
		}

		return $oUser;
	}

	/**
	 * @param $refreshToken
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function renewAccessToken( $refreshToken ) {
		$oInfo = $this->verifyToken( $refreshToken, 'refresh_token' );
		$oUser = new WP_User( $oInfo->userID );

		if ( empty( $oUser ) || is_wp_error( $oUser ) ) {
			throw new Exception( esc_html__( 'The user has been removed', 'wiloke-jwt' ) );
		}

		$this->revokeAccessToken( $oUser->ID );

		return $this->generateToken( $oUser );
	}

	/**
	 * @param $oUser
	 *
	 * @return false|string
	 */
	private function generateTokenMsg( $oUser ) {
		return json_encode(
			[
				'userID'    => $oUser->ID,
				'userName'  => $oUser->user_login,
				'userEmail' => $oUser->user_email
			]
		);
	}

	/**
	 * @param WP_User $oUser
	 * @param bool $ignoreSetCookie
	 *
	 * @return mixed|string
	 */
	protected function generateToken( WP_User $oUser, $ignoreSetCookie = false ) {
		$token = Option::getUserToken( $oUser->ID );

		if ( ! empty( $token ) ) {
			try {
				$oUserInfo = $this->verifyToken( $token );
			}
			catch ( Exception $exception ) {

			}
		}

		if ( ! isset( $oUserInfo ) ) {
			$aPayload = [
				'message' => $this->generateTokenMsg( $oUser ),
				'exp'     => $this->getTokenExpired()
			];

			$token = JWT::encode( $aPayload, Option::getAccessTokenKey() );
			Option::saveUserToken( $token, $oUser->ID );
		}

		$this->setAccessTokenCookie( $token );
		do_action( 'wiloke-jwt/created-access-token', $token, Option::getAccessTokenExp(), $ignoreSetCookie );

		return $token;
	}

	/**
	 * @param WP_User $oUser
	 *
	 * @return string
	 */
	protected function generateRefreshToken( WP_User $oUser ): string {
		$aPayload = [
			'message' => $this->generateTokenMsg( $oUser ),
			'exp'     => Option::getRefreshAccessTokenExp()
		];

		$encoded = JWT::encode( $aPayload, Option::getRefreshTokenKey() );
		Option::saveUserRefreshToken( $encoded, $oUser->ID );

		return $encoded;
	}

	/**
	 * @param $accessToken
	 *
	 * @return bool
	 */
	protected function isAccessTokenExpired( $accessToken ): bool {
		try {
			JWT::decode( $accessToken, Option::getAccessTokenKey(), [ 'HS256' ] );
			//            $oUserInfo = json_decode($oParse->message);
			//
			//            $userAccessToken = Option::getUserToken($oUserInfo->userID);
			//            $this->verifyToken($userAccessToken);
			return false;
		}
		catch ( Exception $exception ) {
			$message = $exception->getMessage();
			if ( is_string( $message ) && strtoupper( $message ) === 'EXPIRED TOKEN' ) {
				return true;
			}

			return false;
		}
	}
}
