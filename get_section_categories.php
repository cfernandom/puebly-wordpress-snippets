<?php

add_action('rest_api_init', 'register_rest_section_categories');

function register_rest_section_categories() {
    register_rest_route('api/v1', 'section-categories', [
        'methods'  => WP_REST_SERVER::READABLE,
        'callback' => 'rest_section_categories_callback',
    ]);
}

function rest_section_categories_callback($data) {
    $town_category = isset($data['t']) ? sanitize_text_field($data['t']) : '';
    $page_number   = isset($data['p']) ? absint(sanitize_text_field($data['p'])) : 1;

    if (empty($town_category)) {
        return array();
    }

    $section_ids   = array(35, 3, 36, 5, 7, 37, 4); // Parent categories

    $section_child_categories = array();

    foreach ($section_ids as $section_id) {
        $child_categories = get_categories(array(
            'child_of' => $section_id,
        ));

        $section_child_categories = array_merge($section_child_categories, $child_categories);
    }

    $child_categories_with_posts = array();

    foreach ($section_child_categories as $category) {
        // Verificar si hay al menos un post en la categoría actual con la categoría del pueblo p
        $posts_in_category = get_posts(array(
            'category__and'      => array($category->term_id, $town_category),
            'numberposts'   => 1, // Obtener al menos un post
        ));

        if (!empty($posts_in_category)) {
            $child_categories_with_posts[] = $category;
        }
    }

    return rest_ensure_response($child_categories_with_posts);    
}

