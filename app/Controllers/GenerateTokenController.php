<?php

namespace WilokeJWT\Controllers;

use Exception;
use Firebase\JWT\JWT;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\Option;
use WilokeJWT\Illuminate\Message\MessageFactory;
use WP_User;

/**
 * Class GenerateTokenController
 * @package WilokeJWT\Controllers
 */
final class GenerateTokenController extends Core {
	/**
	 * GenerateTokenController constructor.
	 */
	public function __construct() {
		add_filter( 'wiloke/filter/get-refresh-token', [ $this, 'getUserRefreshToken' ] );
		add_filter( 'wiloke/filter/revoke-access-token', [ $this, 'filterRevokeAccessToken' ], 10, 2 );
		add_filter( 'wiloke/filter/revoke-refresh-access-token', [ $this, 'filterRevokeRefreshToken' ], 10, 2 );
		add_filter( 'wiloke/filter/renew-access-token', [ $this, 'filterRenewAccessToken' ], 10, 3 );
		add_filter(
			'wiloke/filter/create-access-token-and-refresh-token',
			[ $this, 'filterGetAccessTokenAndRefreshToken' ]
		);
		add_filter( 'wiloke/filter/is-access-token-expired', [ $this, 'filterIsTokenExpired' ], 10, 2 );
		add_filter( 'wiloke/filter/verify-access-token', [ $this, 'filterVerifyToken' ], 10, 2 );
		add_filter( 'wiloke/filter/set-black-list-access-token', [ $this, 'filterSetBackListToken' ], 10, 3 );
		add_filter( 'wiloke/filter/is-black-list-access-token', [ $this, 'filterIsBackListToken' ], 10, 3 );
		add_action( 'wiloke-jwt/filter/after/logged-in', [ $this, 'filterGetTokenAndAccessTokenAfterLoggedIn' ], 10,
			2 );
		add_action( 'wiloke-jwt/after/logged-in', [ $this, 'generateTokenAndAccessTokenAfterLoggedIn' ], 10 );
	}

	public function generateTokenAndAccessTokenAfterLoggedIn( $userID ) {
		$this->filterGetTokenAndAccessTokenAfterLoggedIn( [], $userID );
	}

	public function filterGetTokenAndAccessTokenAfterLoggedIn( $aResponse, $userID ) {
		try {
			$accessToken  = Option::getUserAccessToken( $userID );
			$refreshToken = Option::getUserRefreshToken( $userID );

			$oUser = new WP_User( $userID );
			if ( empty( $refreshToken ) ) {
				$refreshToken = $this->generateRefreshToken( $oUser );
				$accessToken  = $this->generateToken( $oUser );
			} else {
				if ( ! $this->isAccessTokenExpired( $accessToken ) ) {
					$accessToken = $this->renewAccessToken( $refreshToken );
				}
			}

			return MessageFactory::factory()->success(
				esc_html__( 'The token has been generated successfully', 'wiloke-jwt' ),
				[
					'accessToken'  => $accessToken,
					'refreshToken' => $refreshToken
				]
			);
		}
		catch ( \Exception $oException ) {
			return MessageFactory::factory()->error( $oException->getMessage(), $oException->getCode() );
		}
	}

	/**
	 * @param $aResponse
	 * @param $userId
	 * @param $accessToken
	 *
	 * @return array
	 */
	function filterSetBackListToken( $aResponse, $userId, $accessToken ): array {
		## A list of black list token
		$aData = $this->setBlackListAccessToken( $userId, $accessToken );
		if ( ! empty( $aData ) ) {
			return MessageFactory::factory()->success(
				esc_html__( 'The data has been generated', 'wiloke-jwt' ),
				[
					'items' => $aData
				]
			);
		}

		return $aResponse;
	}

	function filterIsBackListToken( $aResponse, $userId, $accessToken ): array {
		$status = $this->isBlackListAccessToken( $userId, $accessToken );
		if ( $status ) {
			return MessageFactory::factory()->success(
				'The access token is exist back list token',
				null
			);
		} else {
			return MessageFactory::factory()->error(
				'The access token is not exist back list token',
				404
			);
		}
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

	public function filterVerifyToken( $status, $accessToken ) {
		try {
			$oUser = $this->verifyToken( $accessToken );

			return MessageFactory::factory()->success(
				'The token is correct',
				[
					'userID' => $oUser->userID
				]
			);
		}
		catch ( \Exception $oException ) {
			return MessageFactory::factory()->error(
				$oException->getMessage(),
				$oException->getCode()
			);
		}
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
	 * @param $status
	 * @param $token
	 *
	 * @return array|bool
	 */
	public function filterRevokeRefreshToken( $status, $token ) {
		try {
			$oUserInfo = $this->verifyToken( $token, 'refresh_token' );
			$this->revokeRefreshAccessToken( $oUserInfo->userID );

			return MessageFactory::factory()->success(
				'The Refresh Token has been revoked.'
			);
		}
		catch ( Exception $oException ) {
			return MessageFactory::factory()->error(
				$oException->getMessage(),
				$oException->getCode()
			);
		}
	}

	public function filterGetAccessTokenAndRefreshToken( WP_User $oUser ): array {
		return $this->getRefreshTokenAndAccessToken( $oUser );
	}

	protected function getRefreshTokenAndAccessToken( WP_User $oUser ): array {
		try {
			return MessageFactory::factory()->success(
				'The Tokens have been created.',
				[
					'accessToken'  => $this->generateToken( $oUser ),
					'refreshToken' => $this->generateRefreshToken( $oUser )
				]
			);
		}
		catch ( Exception $oException ) {
			return MessageFactory::factory()->error(
				$oException->getMessage(),
				$oException->getCode()
			);
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

	public function isTestMode( $test = '' ): bool {
		if ( ! empty( $test ) ) {
			return true;
		}

		return Option::isTestMode();
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
		catch ( Exception $oException ) {
			return MessageFactory::factory()->error(
				$oException->getMessage(),
				[
					'statusCode' => 'INVALID_REFRESH_TOKEN'
				]
			);
		}

		try {
			$accessToken = ! empty( $oldAccessToken ) ? $oldAccessToken : Option::getUserToken( $oUserInfo->userID );
			if ( $this->isAccessTokenExpired( $accessToken ) || $accessToken == $oldAccessToken ) {
				return MessageFactory::factory()->success(
					'The Access Token has been generated',
					[
						'accessToken' => $this->renewAccessToken( $refreshToken ),
						'userID'      => $oUserInfo->userID
					]
				);
			} else {
				return MessageFactory::factory()->error(
					esc_html__( 'Sorry, We could not renew the token', 'wiloke-jwt' ),
					403
				);
			}
		}
		catch ( Exception $oException ) {
			return MessageFactory::factory()->error(
				$oException->getMessage(),
				403,
				[
					'statusCode' => 'TOKEN_EXPIRED'
				]
			);
		}
	}
}
