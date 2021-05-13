<?php

namespace WilokeJWT\Controllers;

use Exception;
use Firebase\JWT\JWT;
use HSBlogCore\Helpers\Cookie;
use HSBlogCore\Helpers\Session;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\Option;
use WP_User;

/**
 * Class GenerateTokenController
 * @package WilokeJWT\Controllers
 */
final class GenerateTokenController extends Core {
	private bool $handlingUserLogin = false;

	/**
	 * GenerateTokenController constructor.
	 */
	public function __construct() {
		add_action( 'set_logged_in_cookie', [ $this, 'handleTokenAfterUserSetLoggedInCookie' ], 10, 4 );
		add_action( 'wp_login', [ $this, 'handleTokenAfterUserSignedIn' ], 10, 2 );
		add_action( 'wiloke-jwt/created-access-token', [ $this, 'storeAccessTokenToCookie' ], 10, 3 );
		add_action( 'clear_auth_cookie', [ $this, 'removeAccessTokenAfterLogout' ] );
		add_action( 'user_register', [ $this, 'createRefreshTokenAfterUserRegisteredAccount' ] );
		add_filter( 'wiloke/filter/get-refresh-token', [ $this, 'getUserRefreshToken' ] );
		add_filter( 'wiloke/filter/revoke-access-token', [ $this, 'filterRevokeAccessToken' ], 10, 2 );
		add_filter( 'wiloke/filter/revoke-refresh-access-token', [ $this, 'filterRevokeRefreshAccessToken' ], 10, 2 );
		add_filter( 'wiloke/filter/renew-access-token', [ $this, 'filterRenewAccessToken' ], 10, 3 );
		add_filter(
			'wiloke/filter/create-access-token-and-refresh-token',
			[ $this, 'filterCreateAccessTokenAndRefreshToken' ]
		);
		add_filter( 'wiloke/filter/is-access-token-expired', [ $this, 'filterIsTokenExpired' ], 10, 2 );
		add_action( 'clean_user_cache', [ $this, 'maybeRevokeRefreshPasswordAfterUpdatingUser' ], 10, 2 );
		add_action( 'delete_user', [ $this, 'deleteTokensBeforeDeletingUser' ], 10 );
		add_action( 'init', [ $this, 'autoGenerateTokenAfterActivatingPlugin' ], 1 );
	}

	function autoGenerateTokenAfterActivatingPlugin() {
		/**
		 * @var $oGenerateTokenController GenerateTokenController
		 */
		global $current_user;
		$aOptions = Option::getJWTSettings();

		if ( isset( $aOptions['isDefault'] ) ) {
			Option::saveJWTSettings( $aOptions );

			try {
				$this->createRefreshTokenAfterUserRegisteredAccount(
					$current_user->ID,
					true
				);
			}
			catch ( Exception $e ) {
			}
		}
	}

	/**
	 * @param      $userId
	 * @param bool $isDirectly
	 *
	 * @return array
	 */
	public function createRefreshTokenAfterUserRegisteredAccount( $userId, $isDirectly = false ): array {
		if ( isset( $_GET['import'] ) && $_GET['import'] == 'wordpress' ) {
			return [
				'msg'  => 'Ignore create token',
				'code' => 200
			];
		}

		if ( empty( $userId ) ) {
			return [
				'msg'  => 'Invalid User Id',
				'code' => 400
			];
		}

		$oUser = new WP_User( $userId );

		if ( empty( $oUser ) || is_wp_error( $oUser ) ) {
			return [
				'msg'  => 'Invalid User',
				'code' => 400
			];
		}

		$refreshToken = $this->generateRefreshToken( $oUser );
		if ( ! empty( $refreshToken ) ) {
			try {
				$accessToken = $this->renewAccessToken( $refreshToken );
				$aResponse   = [
					'accessToken'  => $accessToken,
					'refreshToken' => $refreshToken,
					'userId'       => $userId,
					'oUser'        => $oUser,
					'isDirectly'   => $isDirectly,
					'code'         => 200
				];
				do_action( 'wiloke-jwt/created-refresh-token', $aResponse );
			}
			catch ( Exception $exception ) {
				$aResponse = [
					'msg'  => $exception->getMessage(),
					'code' => 400
				];
			}
		} else {
			$aResponse = [
				'msg'  => 'We could not create token',
				'code' => 400
			];
		}

		return $aResponse;
	}

	/**
	 * @param $status
	 * @param $accessToken
	 *
	 * @return bool
	 */
	public function filterIsTokenExpired( $status, $accessToken ): bool {
		return $this->isAccessTokenExpired( $accessToken );
	}

	/**
	 * @return bool
	 */
	public function removeAccessTokenAfterLogout(): bool {
		return $this->clearTokens();
	}

	/**
	 * @param $status
	 * @param $userId
	 *
	 * @return bool
	 */
	public function filterRevokeAccessToken( $status, $userId ): bool {
		return $this->revokeAccessToken( $userId );
	}

	/**
	 * @param $userID
	 */
	public function deleteTokensBeforeDeletingUser( $userID ) {
		$this->revokeRefreshAccessToken( $userID );
	}

	/**
	 * @param         $userID
	 * @param WP_User $oUser
	 *
	 * @return array|bool
	 */
	public function maybeRevokeRefreshPasswordAfterUpdatingUser( $userID, WP_User $oUser ) {
		$token = Option::getUserRefreshToken( $oUser->ID );
		if ( ! empty( $token ) ) {
			return $this->filterRevokeRefreshAccessToken( null, $token );
		}
	}

	/**
	 * @param $status
	 * @param $token
	 *
	 * @return array|bool
	 */
	public function filterRevokeRefreshAccessToken( $status, $token ) {
		try {
			$oUserInfo = $this->verifyToken( $token, 'refresh_token' );
			$this->revokeRefreshAccessToken( $oUserInfo->userID );
			$oUser        = new WP_User( $oUserInfo->userID );
			$refreshToken = $this->generateRefreshToken( $oUser );
			$accessToken  = $this->generateToken( $oUser );

			return [
				'code' => 200,
				'data' => [
					'refreshToken' => $refreshToken,
					'accessToken'  => $accessToken
				]
			];
		}
		catch ( Exception $exception ) {
			return [
				'msg'  => $exception->getMessage(),
				'code' => 401
			];
		}
	}

	public function filterCreateAccessTokenAndRefreshToken( $oUser ): array {
		return $this->createRefreshTokenAndAccessToken( $oUser );
	}

	protected function createRefreshTokenAndAccessToken( WP_User $oUser ): array {
		try {
			return [
				'code'         => 200,
				'msg'          => esc_html__( 'The token has been revoked', 'wiloke-jwt' ),
				'accessToken'  => $this->generateRefreshToken( $oUser ),
				'refreshToken' => $this->generateRefreshToken( $oUser )
			];
		}
		catch ( Exception $exception ) {
			return [
				'msg'  => $exception->getMessage(),
				'code' => 401
			];
		}
	}

	public function fixGenerateTokenIfUserLoggedIntoSiteBeforeInstallingMe() {
		if ( current_user_can( 'administrator' ) ) {
			if ( empty( Option::getUserToken() ) ) {
				$oUser = new WP_User( get_current_user_id() );
				$this->generateToken( $oUser );
			}
		}
	}

	/**
	 * @param int $userId
	 *
	 * @return bool|mixed
	 */
	public function getUserRefreshToken( int $userId = 0 ) {
		$userId = ! empty( $userId ) ? $userId : get_current_user_id();

		return Option::getUserRefreshToken( $userId );
	}

	/**
	 * @param $logged_in_cookie
	 * @param $expire
	 * @param $expiration
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function handleTokenAfterUserSetLoggedInCookie( $logged_in_cookie, $expire, $expiration, $user_id ): bool {
		$oUser = new WP_User( $user_id );

		return $this->handleTokenAfterUserSignedIn( $oUser->user_email, $oUser );
	}

	/**
	 * @param $userEmail
	 * @param $oUser
	 *
	 * @return bool
	 */
	public function handleTokenAfterUserSignedIn( $userEmail, $oUser ): bool {
		if ( $this->handlingUserLogin ) {
			return false;
		}

		$this->handlingUserLogin = true;
		$refreshToken            = Option::getUserRefreshToken( $oUser->ID );

		$accessToken = '';

		if ( empty( $refreshToken ) ) {
			$this->generateRefreshToken( $oUser );
		} else {
			try {
				$this->verifyToken( $refreshToken, 'refresh_token' );
			}
			catch ( Exception $exception ) {
				$this->generateRefreshToken( $oUser );
			}

			$accessToken = Option::getUserToken( $oUser->ID );
		}

		try {
			$this->verifyToken( $accessToken );
			if ( Option::isTestMode() ) {
				$refreshToken = Option::getUserRefreshToken( $oUser->ID );
				$accessToken  = $this->renewAccessToken( $refreshToken );
				$this->storeAccessTokenToCookie( $accessToken );
			}

			return true;
		}
		catch ( Exception $exception ) {
			$refreshToken = Option::getUserRefreshToken( $oUser->ID );

			if ( ! empty( $refreshToken ) ) {
				try {
					$accessToken = $this->renewAccessToken( $refreshToken );
					$this->storeAccessTokenToCookie( $accessToken );
				}
				catch ( Exception $exception ) {
					return false;
				}
			}

			return false;
		}
	}

	/**
	 * @param $token
	 * @param bool $ignoreSetCookie
	 *
	 * @return bool
	 */
	public function storeAccessTokenToCookie( $token, bool $ignoreSetCookie = false ): bool {
		if ( $ignoreSetCookie ) {
			return false;
		}

		return $this->setAccessTokenCookie( $token );
	}

	/**
	 * @param $userID
	 *
	 * @return mixed|string
	 * @throws Exception
	 */
	public function generateTokenAfterCreatingAccount( $userID ) {
		$oUser = new WP_User( $userID );

		return $this->generateToken( $oUser, true );
	}

	/**
	 * @param        $aStatus
	 * @param        $refreshToken
	 * @param string $oldAccessToken
	 *
	 * @return array
	 */
	public function filterRenewAccessToken( $aStatus, $refreshToken, string $oldAccessToken = '' ): array {
		try {
			$oUserInfo = $this->verifyToken( $refreshToken, 'refresh_token' );
		}
		catch ( Exception $e ) {
			return [
				'msg'        => $e->getMessage(),
				'code'       => 400,
				'statusCode' => 'INVALID_REFRESH_TOKEN'
			];
		}

		try {
			$accessToken = ! empty( $oldAccessToken ) ? $oldAccessToken : Option::getUserToken( $oUserInfo->userID );

			if ( $this->isAccessTokenExpired( $accessToken ) ) {
				return [
					'accessToken' => $this->renewAccessToken( $refreshToken ),
					'userID'      => $oUserInfo->userID,
					'code'        => 200
				];
			} else {
				return [
					'msg'  => esc_html__( 'The renew token is freezed', 'hsblog-core' ),
					'code' => 403
				];
			}
		}
		catch ( Exception $e ) {
			return [
				'msg'        => $e->getMessage(),
				'code'       => 401,
				'statusCode' => 'TOKEN_EXPIRED'
			];
		}
	}
}
