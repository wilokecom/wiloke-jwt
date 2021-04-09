<?php
$aLabels = [
	'name'               => _x('Client Apps', 'Post type general name', 'wiloke-jwt'),
	'singular_name'      => _x('Client App', 'Post type singular name', 'wiloke-jwt'),
	'menu_name'          => _x('Client Apps', 'Admin Menu text', 'wiloke-jwt'),
	'add_new'            => __('Add New', 'wiloke-jwt'),
	'add_new_item'       => __('Add New App', 'wiloke-jwt'),
	'new_item'           => __('New App', 'wiloke-jwt'),
	'edit_item'          => __('Edit App', 'wiloke-jwt'),
	'view_item'          => __('View App', 'wiloke-jwt'),
	'all_items'          => __('All Apps', 'wiloke-jwt'),
	'search_items'       => __('Search Apps', 'wiloke-jwt'),
	'parent_item_colon'  => __('Parent Apps:', 'wiloke-jwt'),
	'not_found'          => __('No Apps found.', 'wiloke-jwt'),
	'not_found_in_trash' => __('No Apps found in Trash.', 'wiloke-jwt')
];

return [
	'name'               => esc_html__('Client Apps', 'wiloke-jwt'),
	'labels'             => $aLabels,
	'description'        => '',
	'public'             => true,
	'publicly_queryable' => false,
	'show_ui'            => true,
	'show_in_menu'       => true,
	'query_var'          => true,
	'rewrite'            => [
		'slug' => 'client-apps'
	],
	'capability_type'    => 'post',
	'has_archive'        => true,
	'hierarchical'       => true,
	'menu_position'      => 20,
	'supports'           => ['title', 'author', 'thumbnail', 'editor'],
	'show_in_rest'       => true,
	'post_type'          => 'client_app'
];
