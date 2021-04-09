<?php


namespace WilokeJWT\Controllers;


use WilokeJWT\Helpers\ClientIP;
use WilokeJWT\Illuminate\Message\MessageFactory;
use WilokeJWT\Models\PreLoginModel;

final class LoginController
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
	}

	public function generateCode(\WP_REST_Request $oRequest)
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
}
