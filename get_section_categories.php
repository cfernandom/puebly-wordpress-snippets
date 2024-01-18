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

    $section_categories   = array(35, 3, 36, 5, 7, 37, 4); // Parent categories

    $response = array();

    foreach ($section_categories as $section_categorie) {
        $child_categories = get_categories(array(
            'child_of' => $$section_categorie,
        ));

        // append child categories to response
        $response = array_merge($response, $child_categories);
    }
    
    return rest_ensure_response($response);
}

