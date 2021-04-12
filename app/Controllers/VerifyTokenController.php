<?php

namespace WilokeJWT\Controllers;

use Exception;
use Firebase\JWT\JWT;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\Option;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class VerifyTokenController
 * @package WilokeJWT\Controllers
 */
final class VerifyTokenController extends Core {
	/**
	 * VerifyTokenController constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'registerRestRouter' ] );
		add_filter( 'wiloke-jwt/filter/verify-token', [ $this, 'filterVerifyToken' ], 10, 2 );
	}

	/**
	 * @param $aStatus
	 * @param $token
	 *
	 * @return array
	 */
	public function filterVerifyToken( $aStatus, $token ): array {
		try {
			$aInfo = $this->verifyToken( $token );

			return [
				'userID' => $aInfo->userID,
                'code' => 200
			];
		}
		catch ( Exception $e ) {
			return [
				'msg'  => $e->getMessage(),
				'code' => 401
			];
		}
	}

	public function registerRestRouter() {
		register_rest_route( 'wilokejwt/v1', '/signin', [
			'methods'             => 'POST',
			'args'                => [
				'username' => [
					'required'    => true,
					'type'        => 'string',
					'description' => esc_html__( 'The username is required', 'wiloke-jwt' )
				],
				'password' => [
					'required'    => true,
					'type'        => 'string',
					'description' => esc_html__( 'The password is required', 'wiloke-jwt' )
				]
			],
			'callback'            => [ $this, 'signIn' ],
			'permission_callback' => '__return_true'
		] );
	}

	/**
	 * @param WP_REST_Request $oRequest
	 *
	 * @return WP_REST_Response
	 */
	public function signIn( WP_REST_Request $oRequest ) {
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
