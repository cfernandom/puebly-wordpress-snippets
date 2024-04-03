<?php

function redirect_if_desktop() {
    if (preg_match("#^/app#", $_SERVER['REQUEST_URI']) && !wp_is_mobile()) {
        wp_redirect('https://puebly.com');
        exit;
    }
}
add_action('init', 'redirect_if_desktop');