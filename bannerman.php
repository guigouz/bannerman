<?php
/*
Plugin Name: Bannerman
Plugin URI: https://github.com/guigouz/bannerman
Description: Banner manager
Version: 0.2
Author: Guilherme Barile
Author URI: https://guigouz.github.com
License: GPLv2 or later
*/

add_action('init', 'bannerman_init');
add_action('add_meta_boxes_banner', 'bannerman_metaboxes');

add_filter('manage_banner_posts_columns', 'bannerman_posts_columns');
add_action('manage_posts_custom_column', 'bannerman_custom_column');

add_image_size('home-topo', 600);
add_image_size('home-centro', 380);
add_image_size('home-rodape', 450);
add_image_size('banners-list-thumbnail', 500, 50, true);


function bannerman_init() {
    $labels = array(
        'name' => 'Banners',
        'singular_name' => 'Banner',
        'add_new_item' => 'Incluir Banner',
        'edit_item' => 'Editar Banner',
        'new_item' => 'Novo Banner',
        'view_item' => 'Ver Banner',
        'search_items' => 'Pesquisar Banners',
        'not_found' => 'Nenhum banner encontrado',
        'not_found_in_trash' => 'Nenhum banner na lixeira'
    );

    register_post_type('banner', array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'supports' => array('thumbnail')
    ));

    register_taxonomy('banner_area', 'banner', array(
        'label' => 'Áreas',
        'public' => false,
        'show_ui' => true,
        'hierarchical' => true,
        'show_tagcloud' => false,
        'capabilities' => array(
            'manage_terms' => 'manage_options',
            'edit_terms' => 'manage_options',
            'delete_terms' => 'manage_options',
            'assign_terms' => 'edit_posts'
        )
    ));
}

/**
 * Make the "Featured Image" metabox front and center when editing a header-image post.
 */
function bannerman_metaboxes($post) {
    global $wp_meta_boxes;

    remove_meta_box('postimagediv', 'banner', 'side');
    add_meta_box('postimagediv', __('Featured Image'), 'post_thumbnail_meta_box', 'banner', 'normal', 'high');
    add_meta_box('bannerlinkbox', 'Link', 'banners_link_box', 'banner', 'normal', 'high');
}

function bannerman_link_box() {
    global $post;
    echo "<input type=\"text\" style=\"width: 98%;\" name=\"post_content\" value=\"" . esc_attr($post->post_content) . "\"/>";

    //echo '<style type="text/css">#message a, #minor-publishing { display: none }</style>';
}


/**
 * Modify which columns display when the admin views a list of header-image posts.
 */
function bannerman_posts_columns($posts_columns) {
    $tmp = array();

    foreach ($posts_columns as $key => $value) {
        if ($key == 'title') {
            $tmp['banner'] = 'Banner';
        } else {
            $tmp[$key] = $value;
        }
    }

    return $tmp;
}


/**
 * Custom column output when admin is view the header-image post list.
 */
function bannerman_custom_column($column_name) {
    global $post;

    if ($column_name == 'banner') {
        echo "<a href='", get_edit_post_link($post->ID), "'>", get_the_post_thumbnail($post->ID, 'banners-list-thumbnail'), "</a>";
    }
}

/*
 * Utility functions
 */

function get_banner($area, $count = 1, $orderby = 'rand') {

    $q = new WP_Query(array(
        'post_type' => 'banner',
        'posts_per_page' => $count,
        'tax_query' => array(
            array(
                'taxonomy' => 'banner_area',
                'field' => is_numeric($area) ? 'id' : 'slug',
                'terms' => $area
            )
        ),
        'orderby' => $orderby
    ));

    // TODO configurar o tamanho de cada área (metadata)

    $arr = $q->get_posts();

    //print_r($arr);
    return sprintf('<a href="%s">%s</a>',
        empty($arr[0]->post_content) ? '#' : $arr[0]->post_content,
        get_the_post_thumbnail($arr[0]->ID, $area)
    );

}
