<?php

/**
 * Define constant for enabled town IDs.
 */
define('PUEBLOS_HABILITADOS', [2312]); // ID del post

/**
 * Register REST API endpoint for towns.
 */
add_action('rest_api_init', 'register_rest_towns');

/**
 * Register REST route for towns.
 */
function register_rest_towns() {
	register_rest_route('api/v2', 'towns', [
		'methods'  => WP_REST_SERVER::READABLE,
		'callback' => 'rest_towns_callback',
	]);
}

/**
 * Callback function for the towns REST API endpoint.
 *
 * @param array $data Request data.
 * @return WP_REST_Response Response data.
 */
function rest_towns_callback($data) {
	// Get the page number from the request, default to 1.
	$page_number = isset($data['p']) ? absint(sanitize_text_field($data['p'])) : 1;

	// Query posts with specific parameters.
	$args  = [
		'category'      => 51,
		'post_type'     => 'post',
		'posts_per_page' => 10,
		'paged'         => $page_number,
		'post_status'   => 'publish',
	];
	$posts = get_posts($args);

	$response = [];
	foreach ($posts as $post) {
		// Get category information for the post.
		$post_category = get_post_category($post->ID);

		// Build the response array for each post.
		$response[] = [
			'post_id'          => $post->ID,
			'name'             => $post->post_title,
			'featured_img_url' => get_the_post_thumbnail_url($post->ID),
			'description'      => get_post_description($post->post_content),
			'category_id'      => $post_category['id'],
			'enabled'          => in_array($post->ID, PUEBLOS_HABILITADOS),
		];
	}

	// Ensure the response is properly formatted.
	return rest_ensure_response($response);
}

/**
 * Get category information for a post.
 *
 * @param int $post_id Post ID.
 * @return array Category information.
 */
function get_post_category($post_id) {
	$categories = wp_get_post_categories($post_id);
	$post_category = [
		'id'   => 0,
		'name' => '',
	];
	foreach ($categories as $category_id) {
		$category = get_category($category_id);
		if ($category->name !== 'Pueblo') {
			$post_category = [
				'id'   => $category->term_id,
				'name' => $category->name,
			];
			break;
		}
	}

	return $post_category;
}

/**
 * Get post description by stripping tags.
 *
 * @param string $post_content Post content.
 * @return string Stripped post description.
 */
function get_post_description($post_content) {
	return wp_strip_all_tags(html_entity_decode(apply_filters('the_content', $post_content)));
}