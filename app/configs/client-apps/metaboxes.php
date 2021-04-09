<?php
return [
	'id'           => 'client_app_settings',
	'title'        => esc_html__('App Settings', 'wiloke-jwt'),
	'object_types' => ['client_app'],
	'fields'       => [
		[
			'name'    => esc_html__('Scopes *', 'wiloke-jwt'),
			'id'      => 'scopes',
			'type'    => 'text',
			'default' => 'basic'
		],
		[
			'name'  => esc_html__('Redirect URI', 'wiloke-jwt'),
			'id'    => 'redirect_url',
			'type'  => 'text',
			'value' => ''
		],
		[
			'name'       => esc_html__('App ID', 'wiloke-jwt'),
			'id'         => 'app_id',
			'type'       => 'text',
			'value'      => '',
			'attributes' => [
				'readonly' => 'readonly',
			],
			'save_field' => false, // Disables the saving of this field.
		],
		[
			'name'       => esc_html__('App Secret', 'wiloke-jwt'),
			'id'         => 'app_secret',
			'type'       => 'text',
			'value'      => '',
			'attributes' => [
				'readonly' => 'readonly',
			],
			'save_field' => false, // Disables the saving of this field.
		]
	]
];
