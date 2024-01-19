<?php
/**
 * Register REST Section Categories.
 */
define('SECTION_CATEGORY_IDS', [35, 3, 36, 5, 7, 37, 4]); // Parent categories
define('DEPARTMENT_CATEGORY_IDS', [39]);

/**
 * Register REST API route for section categories.
 */
add_action('rest_api_init', 'register_rest_section_child_categories');

function register_rest_section_child_categories() {
    register_rest_route('api/v1', 'section-child-categories', [
        'methods'  => WP_REST_SERVER::READABLE,
        'callback' => 'rest_section_child_categories_callback',
    ]);
}

/**
 * Callback function for handling section categories REST API requests.
 *
 * @param array $data The request data.
 * @return WP_REST_Response|array Response data.
 */
function rest_section_child_categories_callback($data) {
    $town_category = isset($data['t']) ? absint($data['t']) : '';
    $page_number   = isset($data['p']) ? absint($data['p']) : 1;

    if (empty($town_category)) {
        return array();
    }

    if (!defined('SECTION_CATEGORY_IDS')) {
        return array();
    }

    $transient_key = 'child_categories_with_posts_' . $town_category;

    if (false !== ($child_categories_with_posts = get_transient($transient_key))) {
        return rest_ensure_response($child_categories_with_posts);
    }

    $section_child_categories = get_section_child_categories();

    $child_categories_with_posts = get_child_categories_with_posts($section_child_categories, $town_category);

    set_transient($transient_key, $child_categories_with_posts, HOUR_IN_SECONDS * 12);

    return rest_ensure_response($child_categories_with_posts);
}

/**
 * Retrieve section child categories.
 *
 * @return array Array of section child categories.
 */
function get_section_child_categories() {
    $section_child_categories = [];

    foreach (SECTION_CATEGORY_IDS as $section_id) {
        $child_categories = get_categories(['child_of' => $section_id]);
        $section_child_categories = array_merge($section_child_categories, $child_categories);
    }

    return $section_child_categories;
}

/**
 * Retrieve child categories with posts.
 *
 * @param array  $section_child_categories Array of section child categories.
 * @param string $town_category           Town category.
 * @return array Array of child categories with posts.
 */
function get_child_categories_with_posts($section_child_categories, $town_category) {
    $child_categories_with_posts = [];

    foreach ($section_child_categories as $category) {
        $args = [
            'category__and' => [$category->term_id, $town_category],
            'post_type'     => 'post',
            'post_status'   => 'publish',
            'paged'         => 1,
        ];

        $posts_in_category = new WP_Query($args);

        if ($posts_in_category->have_posts()) {
            $child_categories_with_posts[] = [
                'id'          => $category->term_id,
                'name'        => $category->name,
                'parent_id'      => $category->parent,
                'description' => $category->description,
                'count'       => count($posts_in_category->posts),
            ];
        }
    }

    return $child_categories_with_posts;
}

/**
 * Hook to delete transient when a post is updated.
 *
 * @param int $post_id Post ID.
 */
add_action('save_post', 'delete_child_categories_with_posts_transient');

/**
 * Delete transient when a post is updated.
 *
 * @param int $post_id Post ID.
 */
function delete_child_categories_with_posts_transient($post_id) {
    if (!defined('DEPARTMENT_CATEGORY_IDS')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $post_categories = wp_get_post_categories($post_id);

    foreach ($post_categories as $category_id) {
        $category = get_category($category_id);

        if (in_array($category->parent, DEPARTMENT_CATEGORY_IDS)) {
            delete_transient('child_categories_with_posts_' . $category_id);
        }
    }
}
