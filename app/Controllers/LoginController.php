<?php


namespace WilokeJWT\Controllers;


use Exception;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\ClientIP;
use WilokeJWT\Helpers\Option;
use WilokeJWT\Illuminate\Message\MessageFactory;
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
                'args'                => [
                    'code'  => [
                        'required'    => true,
                        'type'        => 'string',
                        'description' => esc_html__('The code is required', 'wiloke-jwt')
                    ],
                    'email' => [
                        'required'    => true,
                        'type'        => 'string',
                        'description' => esc_html__('The email is required', 'wiloke-jwt')
                    ]
                ],
                'callback'            => [$this, 'handleSignUp'],
                'permission_callback' => '__return_true'
            ]
        );
        register_rest_route(
            WILOKE_JWT_API,
            'sign-in',
            [
                'methods' => 'POST',
                'args'    => [
                    'token' => [
                        'required'    => true,
                        'type'        => 'string',
                        'description' => esc_html__('The token is required', 'wiloke-jwt')
                    ]
                ],
                'callback'            => [$this, 'handleSignIn'],
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
            if (PreLoginModel::isMatchedCode($aData['code'], $this->determineClientIP())) {
                if (email_exists($aData['email'])) {
                    return MessageFactory::factory('rest')->error(
                        esc_html__('The email has existed in database', 'wiloke-jwt'),
                        400
                    );
                } else {
                    $userId = wp_insert_user([
                        'user_login' => $aData['email'],
                        'user_email' => $aData['email'],
                        'user_pass'  => $aData['email']
                    ]);
                    if (is_wp_error($userId)) {
                        return MessageFactory::factory('rest')->error($userId->get_error_message(), 401);
                    } else {
                        Option::saveUserToken($this->generateToken(get_user_by('ID', $userId)), $userId);
                        return MessageFactory::factory('rest')
                            ->success(esc_html__('Congrats, You have registered successfully', 'wiloke-jwt'),
                                [
                                    'token' => Option::getUserToken($userId)
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
        $token = $oRequest->get_param('token');
        try {
            var_dump((new VerifyTokenController())->verifyToken($token));die();
            if (PreLoginModel::isMatchedCode($aData['code'], $this->determineClientIP())) {
                if (email_exists($aData['email'])) {
                    return MessageFactory::factory('rest')->error(
                        esc_html__('The email has existed in database', 'wiloke-jwt'),
                        400
                    );
                } else {
                    $userId = wp_insert_user([
                        'user_login' => $aData['email'],
                        'user_email' => $aData['email'],
                        'user_pass'  => $aData['email']
                    ]);
                    if (is_wp_error($userId)) {
                        return MessageFactory::factory('rest')->error($userId->get_error_message(), 401);
                    } else {
                        Option::saveUserToken($this->generateToken(get_user_by('ID', $userId)), $userId);
                        return MessageFactory::factory('rest')
                            ->success(esc_html__('Congrats, You have registered successfully', 'wiloke-jwt'),
                                [
                                    'token' => Option::getUserToken($userId)
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
}
