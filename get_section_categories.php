<?php

add_action('rest_api_init', 'register_rest_section_categories');

function register_rest_section_categories() {
    register_rest_route('api/v1', 'section-categories', [
        'methods'  => WP_REST_SERVER::READABLE,
        'callback' => 'rest_section_categories_callback',
    ]);
}

function rest_section_categories_callback($data) {
    $town_category = isset($data['t']) ? absint($data['t']) : '';
    $page_number   = isset($data['p']) ? absint($data['p']) : 1;

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
        $args = array(
            'category__and' => array($category->term_id, $town_category),
            'post_type'     => 'post',
            // 'posts_per_page' => 10,
            'post_status'   => 'publish',
            'paged'         => 1,
        );

        $posts_in_category = new WP_Query($args);

        if ($posts_in_category->have_posts()) {
            $child_categories_with_posts[] = array(
                'id'   => $category->term_id,
                'name' => $category->name,
                'parent' => $category->parent,
                'description' => $category->description,
                'count' => count($posts_in_category->posts),
            );
        }
    }

    return rest_ensure_response($child_categories_with_posts);    
}

