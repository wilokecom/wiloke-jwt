<?php

namespace WilokeJWT\Controllers;

use WilokeJWT\Helpers\Option;

/**
 * Class AdminMenuController
 * @package WilokeJWT\Controllers
 */
class AdminMenuController
{
	public string  $prefix    = 'wilokejwt_';
	public string  $slug      = 'wiloke-jwt';
	public string  $optionKey = 'wilokejwt';
	public array   $aOptions  = [];
	public ?string $parentSlug;

	/**
	 * AdminMenuController constructor.
	 */
	public function __construct()
	{
		add_action('admin_menu', [$this, 'registerMenu']);
		add_action('network_admin_menu', [$this, 'registerMenu']);
	}

	public function setParentSlug(): AdminMenuController
	{
		$aConfig = include WILOKE_JWT_PATH . 'app/configs/client-apps/post-type.php';
		$this->parentSlug = $aConfig['post_type'];

		return $this;
	}

	public function saveOption()
	{
		$aValues = [];
		if (isset($_POST['wiloke-jwt-field']) && !empty($_POST['wiloke-jwt-field'])) {
			if (wp_verify_nonce($_POST['wiloke-jwt-field'], 'wiloke-jwt-action')) {
				if (isset($_POST['wilokejwt']) && !empty($_POST['wilokejwt'])) {
					foreach ($_POST['wilokejwt'] as $key => $val) {
						$aValues[sanitize_text_field($key)] = sanitize_text_field(trim($val));
					}
				}
				Option::saveJWTSettings($aValues);
			}
		}
	}

	public function registerMenu()
	{
		add_submenu_page(
			'edit.php?post_type=' . $this->setParentSlug()->parentSlug,
			'Wiloke JWT',
			'Wiloke JWT',
			'administrator',
			$this->slug,
			[$this, 'settings']
		);
	}

	public function settings()
	{
		$this->saveOption();
		$this->aOptions = Option::getSiteJWTSettings();

		include WILOKE_JWT_VIEWS . 'wiloke-jwt-settings.php';
	}
}
