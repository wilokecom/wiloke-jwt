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

final class LoginController extends Core
{
    use ClientIP;

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerRouters']);
    }

    public function registerRouters()
    {
        register_rest_route(
            WILOKE_JWT_API,
            'register-code',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'generateCode'],
                'permission_callback' => '__return_true'
            ]
        );

        register_rest_route(
            WILOKE_JWT_API,
            'signin-with-wilcity',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'generateCode'],
                'permission_callback' => '__return_true'
            ]
        );
        register_rest_route(
            WILOKE_JWT_API,
            'sign-up',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'handleSignUp'],
                'permission_callback' => '__return_true'
            ]
        );
        register_rest_route(
            WILOKE_JWT_API,
            'sign-in',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'handleSignIn'],
                'permission_callback' => '__return_true'
            ]
        );
        register_rest_route(
            WILOKE_JWT_API,
            'renew-token',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'renewToken'],
                'permission_callback' => '__return_true'
            ]
        );
    }

    public function generateCode(WP_REST_Request $oRequest)
    {
        if (!$oRequest->get_param('client_session')) {
            return MessageFactory::factory('rest')->error(
                esc_html__('Missing client session', 'wiloke-jwt'),
                400
            );
        }

        if (!AppClientModel::isValidApp($oRequest->get_param('app_id'),$oRequest->get_param('app_secret'))) {
            return MessageFactory::factory('rest')->error(
                esc_html__('The app id or app secret not has existed in the database or the page had must enable public',
                    'wiloke-jwt'),
                400
            );
        }
        $ID = PreLoginModel::createCode($oRequest->get_param('client_session'), $this->determineClientIP());
        if (empty($ID)) {
            return MessageFactory::factory('rest')->error(
                esc_html__('Something went wrong and We could not generate code', 'wiloke-jwt'),
                400
            );
        }

        return MessageFactory::factory('rest')->success(
            'The code has been generated',
            [
                'code'           => PreLoginModel::getCode($ID),
                'client_session' => $oRequest->get_param('client_session')
            ]
        );
    }

    public function handleSignUp(WP_REST_Request $oRequest)
    {
        $aData = $oRequest->get_params();
        try {
            if (!isset($aData['code']) && empty($aData['code'])) {
                return MessageFactory::factory('rest')->error(
                    esc_html__('The code is required', 'wiloke-jwt'),
                    400
                );
            }
            if (!isset($aData['email']) && empty($aData['email'])) {
                return MessageFactory::factory('rest')->error(
                    esc_html__('The email  is required', 'wiloke-jwt'),
                    400
                );
            }
            if (PreLoginModel::isMatchedCode($aData['code'], $this->determineClientIP())) {
                if (email_exists($aData['email'])) {
                    return MessageFactory::factory('rest')->error(
                        esc_html__('The email has existed in database', 'wiloke-jwt'),
                        400
                    );
                } else {
                    $userId = wp_insert_user([
                        'user_login' => Users::generateUsername($aData['email']),
                        'user_email' => $aData['email'],
                        'user_pass'  => uniqid('wiloke_')
                    ]);
                    if (is_wp_error($userId)) {
                        return MessageFactory::factory('rest')->error($userId->get_error_message(), 401);
                    } else {
                        Option::saveUserToken($this->generateToken(get_user_by('ID', $userId)), $userId);
                        return MessageFactory::factory('rest')
                            ->success(esc_html__('Congrats, You have registered successfully', 'wiloke-jwt'),
                                [
                                    'accessToken'  => Option::getUserToken($userId),
                                    'refreshToken' => Option::getUserRefreshToken($userId)
                                ]);
                    }
                }
            } else {
                return MessageFactory::factory('rest')->error(
                    esc_html__('Matching code was not found or was not exist in database', 'wiloke-jwt'),
                    400
                );
            }
        } catch (Exception $oException) {
            return MessageFactory::factory('rest')->error($oException->getMessage(), $oException->getCode());
        }
    }

    public function handleSignIn(WP_REST_Request $oRequest)
    {
        $accessToken = $oRequest->get_param('accessToken');
        try {
            $aResponse = $this->getResponseData($accessToken);
            if (isset($aResponse['error']) && $aResponse['error']['message'] == 'Expired token') {
                return MessageFactory::factory('rest')
                    ->error('Assess token expired',400);
            }
            return MessageFactory::factory('rest')
                ->success(esc_html__('Congrats, You have logged in successfully', 'wiloke-jwt'));
        } catch (Exception $oException) {
            return MessageFactory::factory('rest')->error($oException->getMessage(), $oException->getCode());
        }
    }

    private function getResponseData(string $accessToken)
    {
        return apply_filters(
            'wiloke-jwt/filter/verify-token',
            [
                'error' => [
                    'message' => esc_html__('Wiloke JWT plugin is required', 'wiloke-jwt'),
                    'code'    => 404
                ]
            ],
            $accessToken
        );
    }
    public function renewToken(WP_REST_Request $oRequest){
        $aData = $oRequest->get_params();
        try {
            if (!isset($aData['code']) && empty($aData['refreshToken'])) {
                return MessageFactory::factory('rest')->error(
                    esc_html__('The refresh token is required', 'wiloke-jwt'),
                    400
                );
            }
            $aToken = apply_filters(
                'wiloke/filter/revoke-refresh-access-token',
                [
                    'error' => [
                        'message' => esc_html__('Wiloke JWT plugin is required', 'wiloke-jwt'),
                        'code'    => 404
                    ]
                ],
                $aData['refreshToken']
            );
            if (!empty($aToken) && !isset($aToken['msg'])) {
                return MessageFactory::factory('rest')
                    ->success(esc_html__('Assess token created new successfully', 'wiloke-jwt'),
                        $aToken['data']);
            } else {
                return MessageFactory::factory('rest')->error($aToken['msg'],$aToken['code']);
            }
        } catch (Exception $oException) {
            return MessageFactory::factory('rest')->error($oException->getMessage(), $oException->getCode());
        }
    }
}
