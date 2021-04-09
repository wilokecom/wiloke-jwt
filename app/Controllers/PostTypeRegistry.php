<?php


namespace WilokeJWT\Controllers;


use WilokeJWT\Helpers\Option;

class PostTypeRegistry
{
	private string $appIdKey     = 'app_id';
	private string $appSecretKey = 'app_secret';

	public function __construct()
	{
		add_action('init', [$this, 'registerPostTypes']);
		add_action('cmb2_admin_init', [$this, 'registerMetaBoxes'], 10);
		add_action('save_post_'.$this->getPostType(), [$this, 'generateTokens'], 10, 4);
	}

	private function getPostType()
	{
		$aConfig = include WILOKE_JWT_PATH . 'app/configs/client-apps/post-type.php';
		return $aConfig['post_type'];
	}

	protected function generateAppIdAndSecret(int $postId)
	{
		$appId = get_post_meta($postId, $this->appIdKey, true);
		if (empty($appId)) {
			update_post_meta($postId, $this->appIdKey, uniqid('app_id_'));
			update_post_meta($postId, $this->appSecretKey, md5(uniqid('app_secret_')));
		}
	}

	public function generateTokens($postId): bool
	{
		$this->generateAppIdAndSecret($postId);

		return true;
	}

	public function registerPostTypes()
	{
		$aConfig = include WILOKE_JWT_PATH . 'app/configs/client-apps/post-type.php';
		$postType = $aConfig['post_type'];
		unset($aConfig['post_type']);

		register_post_type($postType, $aConfig);
	}

	public function registerMetaBoxes()
	{
		$aConfig = include WILOKE_JWT_PATH . 'app/configs/client-apps/metaboxes.php';
		$aFields = $aConfig['fields'];
		unset($aConfig['fields']);

		$oCmb2 = new_cmb2_box($aConfig);
		foreach ($aFields as $aField) {
			$oCmb2->add_field($aField);
		}
	}
}
