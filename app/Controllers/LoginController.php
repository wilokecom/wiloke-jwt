<?php


namespace WilokeJWT\Controllers;


use Exception;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\ClientIP;
use WilokeJWT\Helpers\Option;
use WilokeJWT\Helpers\Users;
use WilokeJWT\Illuminate\Message\MessageFactory;
use WilokeJWT\Models\AppClientModel;
use WilokeJWT\Models\PreLoginModel;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

final class LoginController extends Core {
	use ClientIP;

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'registerRouters' ] );
	}

	public function registerRouters() {
		register_rest_route(
			WILOKE_JWT_API,
			'auth-code',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'generateCode' ],
				'permission_callback' => '__return_true'
			]
		);

		register_rest_route(
			WILOKE_JWT_API,
			'register-code',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'generateCode' ],
				'permission_callback' => '__return_true'
			]
		);

		register_rest_route(
			WILOKE_JWT_API,
			'token-validation',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'validateToken' ],
				'permission_callback' => '__return_true'
			]
		);

		register_rest_route(
			WILOKE_JWT_API,
			'sign-up',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handleSignUp' ],
				'permission_callback' => '__return_true'
			]
		);

		register_rest_route(
			WILOKE_JWT_API,
			'wilcity/sign-in',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'signinWithAccessToken' ],
				'permission_callback' => '__return_true'
			]
		);

		register_rest_route(
			WILOKE_JWT_API,
			'sign-in-with-token',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'signinWithAccessToken' ],
				'permission_callback' => '__return_true'
			]
		);

		register_rest_route(
			WILOKE_JWT_API,
			'renew-token',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'renewToken' ],
				'permission_callback' => '__return_true'
			]
		);

		register_rest_route( WILOKE_JWT_API, 'sign-in', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'signIn' ],
			'permission_callback' => '__return_true'
		] );
	}

	public function validateToken( WP_REST_Request $oRequest ) {
		if ( empty( $accessToken = $oRequest->get_param( 'accessToken' ) ) ) {
			return MessageFactory::factory( 'rest' )->error(
				esc_html__( 'Missing access token', 'wiloke-jwt' ),
				400
			);
		}

		$aResponse = apply_filters(
			'wiloke/filter/verify-access-token',
			[
				'status'  => 'error',
				'code'    => 404,
				'message' => esc_html__( 'We could not find any filter', 'wiloke-jwt' )
			],
			$accessToken
		);

		if ( $aResponse['status'] === 'error' ) {
			return MessageFactory::factory( 'rest' )->error(
				$aResponse['message'],
				422 // Invalid status code
			);
		}

		return MessageFactory::factory( 'rest' )->success( 'The token is correct' );
	}

	public function generateCode( WP_REST_Request $oRequest ) {
		if ( ! $oRequest->get_param( 'client_session' ) ) {
			return MessageFactory::factory( 'rest' )->error(
				esc_html__( 'Missing client session', 'wiloke-jwt' ),
				400
			);
		}

		if ( ! AppClientModel::isValidApp( $oRequest->get_param( 'app_id' ), $oRequest->get_param( 'app_secret' ) ) ) {
			return MessageFactory::factory( 'rest' )->error(
				esc_html__( 'Invalid App ID', 'wiloke-jwt' ),
				400
			);
		}
		$ID = PreLoginModel::createCode( $oRequest->get_param( 'client_session' ), $this->determineClientIP() );
		if ( empty( $ID ) ) {
			return MessageFactory::factory( 'rest' )->error(
				esc_html__( 'Something went wrong and We could not generate code', 'wiloke-jwt' ),
				400
			);
		}

		return MessageFactory::factory( 'rest' )->success(
			'The code has been generated',
			[
				'code'           => PreLoginModel::getCode( $ID ),
				'client_session' => $oRequest->get_param( 'client_session' )
			]
		);
	}

	public function handleSignUp( WP_REST_Request $oRequest ) {
		$aData = $oRequest->get_params();
		try {
			if ( ! isset( $aData['code'] ) || empty( $aData['code'] ) ) {
				return MessageFactory::factory( 'rest' )->error(
					esc_html__( 'The code is required', 'wiloke-jwt' ),
					400
				);
			}
			if ( ! isset( $aData['email'] ) || empty( $aData['email'] ) ) {
				return MessageFactory::factory( 'rest' )->error(
					esc_html__( 'The email  is required', 'wiloke-jwt' ),
					400
				);
			}
			if ( PreLoginModel::isMatchedCode( $aData['code'], $this->determineClientIP() ) ) {
				if ( email_exists( $aData['email'] ) ) {
					return MessageFactory::factory( 'rest' )->error(
						esc_html__( 'The email has existed in database', 'wiloke-jwt' ),
						400
					);
				} else {
					$userId = wp_insert_user( [
						'user_login' => Users::generateUsername( $aData['email'] ),
						'user_email' => $aData['email'],
						'user_pass'  => uniqid( 'wiloke_' )
					] );
					if ( is_wp_error( $userId ) ) {
						return MessageFactory::factory( 'rest' )->error( $userId->get_error_message(), 401 );
					} else {
						$aResponse = apply_filters(
							'wiloke/filter/create-access-token-and-refresh-token',
							[
								'status'  => 'error',
								'message' => ''
							],
							new WP_User( $userId )
						);

						if ( $aResponse['status'] == 'error' ) {
							return MessageFactory::factory( 'rest' )
							                     ->error( $aResponse['message'], $aResponse['code'] );
						}

						return MessageFactory::factory( 'rest' )
						                     ->success( esc_html__( 'Congrats, You have registered successfully',
							                     'wiloke-jwt' ),
							                     [
								                     'accessToken'  => Option::getUserToken( $userId ),
								                     'refreshToken' => Option::getUserRefreshToken( $userId )
							                     ] );
					}
				}
			} else {
				return MessageFactory::factory( 'rest' )->error(
					esc_html__( 'Matching code was not found or was not exist in database', 'wiloke-jwt' ),
					400
				);
			}
		}
		catch ( Exception $oException ) {
			return MessageFactory::factory( 'rest' )->error( $oException->getMessage(), $oException->getCode() );
		}
	}

	public function signinWithAccessToken( WP_REST_Request $oRequest ) {
		$accessToken = $oRequest->get_param( 'accessToken' );
		try {
			$aResponse = apply_filters(
				'wiloke-jwt/filter/verify-token',
				[
					'message' => esc_html__( 'Wiloke JWT plugin is required', 'wiloke-jwt' ),
					'code'    => 404
				],
				$accessToken
			);
			if ( $aResponse['code'] === 200 ) {
				return MessageFactory::factory( 'rest' )
				                     ->success( esc_html__( 'Congrats, You have logged in successfully',
					                     'wiloke-jwt' ) );
			} else {
				return MessageFactory::factory( 'rest' )->error( $aResponse['message'], $aResponse['code'] );
			}
		}
		catch ( Exception $oException ) {
			return MessageFactory::factory( 'rest' )->error( $oException->getMessage(), $oException->getCode() );
		}
	}

	/**
	 * @param WP_REST_Request $oRequest
	 *
	 * @return array|string|void|\WP_REST_Response
	 */
	public function renewToken( WP_REST_Request $oRequest ) {
		$aData = $oRequest->get_params();
		try {
			if ( ! isset( $aData['refreshToken'] ) || empty( $aData['refreshToken'] ) ) {
				return MessageFactory::factory( 'rest' )->error(
					esc_html__( 'The refresh token is required', 'wiloke-jwt' ),
					400
				);
			}
			$aToken = apply_filters(
				'wiloke/filter/renew-access-token',
				[
					'message' => esc_html__( 'Wiloke JWT plugin is required', 'wiloke-jwt' ),
					'code'    => 404
				],
				$aData['refreshToken'],
				$aData['accessToken']
			);

			if ( $aToken['status'] === 'success' ) {
				return MessageFactory::factory( 'rest' )
				                     ->success(
					                     esc_html__( 'The access token has been created successfully', 'wiloke-jwt' ),
					                     [
						                     'accessToken' => $aToken['data']['accessToken']
					                     ]
				                     );
			} else {
				return MessageFactory::factory( 'rest' )->error( $aToken['message'], $aToken['code'] );
			}
		}
		catch ( Exception $oException ) {
			return MessageFactory::factory( 'rest' )->error( $oException->getMessage(), $oException->getCode() );
		}
	}

	/**
	 * @param WP_REST_Request $oRequest
	 *
	 * @return WP_REST_Response
	 */
	public function signIn( WP_REST_Request $oRequest ): WP_REST_Response {
		if ( ! isset( $aData['username'] ) || empty( $aData['username'] ) ) {
			return MessageFactory::factory( 'rest' )->error(
				esc_html__( 'The username is required', 'wiloke-jwt' ),
				400
			);
		}
		if ( ! isset( $aData['password'] ) || empty( $aData['password'] ) ) {
			return MessageFactory::factory( 'rest' )->error(
				esc_html__( 'The password is required', 'wiloke-jwt' ),
				400
			);
		}
		$oUser = wp_signon( [
			'user_login'    => $oRequest->get_param( 'username' ),
			'user_password' => $oRequest->get_param( 'password' ),
			'remember'      => true
		], is_ssl() );

		if ( is_wp_error( $oUser ) ) {
			return new WP_REST_Response( [
				'error' => $oUser->get_error_message()
			], 401 );
		}

		return new WP_REST_Response(
			apply_filters(
				'wiloke-jwt/app/general-token-controller/signed-in-msg',
				[
					'token' => Option::getUserToken( $oUser->ID )
				]
			),
			200
		);
	}
}
