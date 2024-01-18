<? php
define('PUEBLOS_HABILITADOS', [2312]); // ID del post

add_action('rest_api_init', 'register_rest_towns');

function register_rest_towns() {
	register_rest_route('api/v2', 'towns', [
		'methods' => WP_REST_SERVER::READABLE,
		'callback' => 'rest_towns_callback'
	]);
}

function rest_towns_callback($data) {
	// obtener los post de la categoria Pueblos
	$page_number = isset($data['p']) ? absint(sanitize_text_field($data['p'])) : 1;
	
	$args = array(
		'category' => 51,
		'post_type' => 'post',
		'posts_per_page' => 10,
		'paged' => $page_number,
		'post_status' => 'publish',
	);
	$posts = get_posts($args);
			
	$response = array();
	foreach ($posts as $post) {
		
		$categories = wp_get_post_categories($post->ID);
		$post_category = array(
			'id' => 0, 
			'name' => '',
		);
		foreach ($categories as $category_id) {
			$category = get_category($category_id);
			if($category->name != 'Pueblo') {
				$post_category = array(
					'id' => $category->term_id,
					'name' => $category->name,
				);				
				break;
			}
		}
		
		$response[] = array(
			'post_id' => $post->ID,
			'name' => $post->post_title,
			'featured_img_url' => get_the_post_thumbnail_url($post->ID),
			'description' => wp_strip_all_tags(html_entity_decode(apply_filters('the_content', $post->post_content))),
			'category_id' => $post_category['id'],
			'enabled' => in_array($post->ID,PUEBLOS_HABILITADOS),
		);
	}
	
	return rest_ensure_response($response);
}