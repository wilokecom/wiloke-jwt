<?php

namespace WilokeJWT\Controllers;

use Firebase\JWT\JWT;
use WilokeJWT\Helpers\Option;

/**
 * Class VerifyTokenController
 * @package WilokeJWT\Controllers
 */
class VerifyTokenController
{
    /**
     * VerifyTokenController constructor.
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerRestRouter']);
        add_filter('wiloke-jwt/filter/verify-token', [$this, 'filterVerifyToken'], 10, 2);
    }

    /**
     * @param $aStatus
     * @param $token
     *
     * @return mixed
     */
    public function filterVerifyToken($aStatus, $token)
    {
        try {
            $aSettings = Option::getJWTSettings();
            $oParse    = JWT::decode($token, $aSettings['key'], ['HS256']);

            $aStatus = [
                'userInfo' => $oParse->message
            ];
        } catch (\Exception $oE) {
            $aStatus = [
                'error' => [
                    'message' => $oE->getMessage(),
                    'code'    => 401
                ]
            ];
        }

        return $aStatus;
    }

    public function registerRestRouter()
    {
        register_rest_route('wilokejwt/v1', '/signin', [
            'methods'  => 'POST',
            'args'     => [
                'username' => [
                    'required'    => true,
                    'type'        => 'string',
                    'description' => esc_html__('The username is required', 'wiloke-jwt')
                ],
                'password' => [
                    'required'    => true,
                    'type'        => 'string',
                    'description' => esc_html__('The password is required', 'wiloke-jwt')
                ]
            ],
            'callback' => [$this, 'signIn']
        ]);
    }

    /**
     * @param \WP_REST_Request $oRequest
     *
     * @return \WP_REST_Response
     */
    public function signIn(\WP_REST_Request $oRequest)
    {
        $oUser = wp_signon([
            'user_login'    => $oRequest->get_param('username'),
            'user_password' => $oRequest->get_param('password'),
            'remember'      => true
        ], is_ssl());

        if (is_wp_error($oUser)) {
            $response = new \WP_REST_Response([
                'error' => $oUser->get_error_message()
            ], 401);

            return $response;
        }

        $response = new \WP_REST_Response(
            apply_filters(
                'wiloke-jwt/app/general-token-controller/signed-in-msg',
                [
                    'token' => Option::getUserToken($oUser->ID)
                ]
            ),
            200
        );

        return $response;
    }
}
