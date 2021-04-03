<?php


namespace WilokeJWT\Controllers;


use WilokeJWT\Helpers\Option;

class UserProfileController
{
	public function __construct()
	{
		add_action('edit_user_profile', [$this, 'showTokenSettings']);
		add_action('personal_options_update', [$this, 'showTokenSettings']);
		add_action('show_user_profile', [$this, 'showTokenSettings']);
		add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
		add_action('rest_api_init', [$this, 'registerRouter']);
		add_action('wp_ajax_fetch_my_tokens', [$this, 'fetchMyTokens']);
		add_action('wp_ajax_revoke_my_token', [$this, 'revokeMyToken']);
		add_action('wp_ajax_create_token', [$this, 'createToken']);
	}

	private function getUserId()
	{
		if (!current_user_can('administrator')) {
			if (isset($_GET['userId']) && (int)$_GET['userId'] !== (int)get_current_user_id()) {
				return 0;
			}
		}

		$userId = !isset($_GET['userId']) || empty($_GET['userId']) ? get_current_user_id() : $_GET['userId'];
		return abs($userId);
	}

	public function revokeMyToken()
	{
		$userId = $this->getUserId();
		if (empty($userId)) {
			wp_send_json_error(['msg' => esc_html__('Invalid user', 'wiloke-jwt')]);
		}

		apply_filters('wiloke/filter/revoke-access-token', false, Option::getUserAccessToken($userId));
		apply_filters('wiloke/filter/revoke-refresh-access-token', false, Option::getUserRefreshToken($userId));

		wp_send_json_success([
			'items' => $this->getMyTokenSkeleton($userId)
		]);
	}

	public function createToken()
	{
		$userId = $this->getUserId();
		if (empty($userId)) {
			wp_send_json_error(['msg' => esc_html__('Invalid user', 'wiloke-jwt')]);
		}

		$aResponse = apply_filters(
			'wiloke/filter/create-access-token-and-refresh-token',
			new \WP_User($userId)
		);

		if (isset($aResponse['error'])) {
			wp_send_json_error(['msg' => $aResponse['error']['msg']]);
        }

		wp_send_json_success([
			'items' => $this->getMyTokenSkeleton($userId, true)
		]);
	}

	public function registerRouter()
	{
		register_rest_route(
			WILOKE_JWT_API,
			'roles',
			[
				[
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => [$this, 'fetchRoles']
				]
			]
		);
	}

	private function getMyTokenSkeleton($userId, $isFullToken = true): array
	{
		if (empty($userId)) {
			return [];
		}

		$oUser = new \WP_User($userId);

		$accessToken = Option::getUserAccessToken($userId);
		if (empty($accessToken)) {
			return [];
		}

		$refreshToken = Option::getUserRefreshToken($userId);

		if (!$isFullToken) {
			$accessToken = substr($accessToken, 0, 5) . '***';
			$refreshToken = substr($refreshToken, 0, 5) . '***';
		}

		return [
			[
				'app_id'        => uniqid('app_'),
				'app_name'      => 'My App',
				'access_token'  => $accessToken,
				'refresh_token' => $refreshToken,
				'roles'         => $oUser->roles
			]
		];
	}

	public function fetchMyTokens()
	{
		header('content-type: application/json');

		$userId = $this->getUserId();

		echo json_encode([
			'items' => $this->getMyTokenSkeleton($userId)
		]);
		die;
	}

	public function fetchRoles(\WP_REST_Request $oRequest)
	{
		global $wp_roles;
		$aRawRoles = $wp_roles->roles;

		$aRoles = [];

		foreach ($aRawRoles as $role => $aRole) {
			$aRoles[] = [
				'label' => $aRole['name'],
				'id'    => $role
			];
		}

		header('content-type: application/json');
		echo json_encode([
			'items' => $aRoles
		]);
		die;
	}

	protected function isProfilePage($hook): bool
	{
		return in_array($hook, ['profile.php', 'user-edit.php']);
	}

	public function enqueueScripts($hook)
	{
		if ($this->isProfilePage($hook)) {
			wp_enqueue_script('UserToken', WILOKE_JWT_URL . 'dist/UserToken.build.js', ['jquery'], WILOKE_JWT_VERSION,
				true);
			wp_localize_script('UserToken', 'WILOKE_JWT', [
				'restAPI'       => rest_url(WILOKE_JWT_API),
				'ajaxurl'       => admin_url('admin-ajax.php'),
				'currentUserId' => get_current_blog_id()
			]);
		}
	}

	public function showTokenSettings()
	{
		?>
        <div id="wiloke-jwt-user-token"></div>
		<?php
	}
}
