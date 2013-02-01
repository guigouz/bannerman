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

add_image_size('banners-list-thumbnail', 500, 50, true);

add_action( 'widgets_init', 'bannerman_load_widgets' );


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


    $labels = array(
        'name'                => _x( 'Áreas', 'taxonomy general name' ),
        'singular_name'       => _x( 'Área', 'taxonomy singular name' ),
        'search_items'        => __( 'Pesquisar Áreas', 'bannerman' ),
        'all_items'           => __( 'Todas as Áreas', 'bannerman' ),
        'parent_item'         => __( 'Área mãe', 'bannerman' ),
        'parent_item_colon'   => __( 'Área mãe:' ),
        'edit_item'           => __( 'Editar Área' ),
        'update_item'         => __( 'Atualizar Área' ),
        'add_new_item'        => __( 'Adicionar nova Área' ),
        'new_item_name'       => __( 'Nome da Área' ),
        'menu_name'           => __( 'Área' )
    );

    register_taxonomy('banner_area', 'banner', array(
        'labels' => $labels,
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


    add_action('create_banner_area', 'banner_area_meta_save');
    add_action('edit_banner_area', 'banner_area_meta_save');
    add_action('delete_banner_area', 'banner_area_meta_delete');
    add_action('banner_area_add_form_fields', 'banner_area_add_meta_form');
    add_action('banner_area_edit_form_fields', 'banner_area_edit_meta_form');

    bannerman_init_areas();
}

function bannerman_init_areas() {
    global $wpdb;
    $results = $wpdb->get_results("select option_name, option_value from $wpdb->options where option_name like 'banner_area-meta:%'", ARRAY_A);

    foreach($results as $result) {
        preg_match('/.*:([^:]+)/', $result['option_name'], $matches);
        $sizeinfo = unserialize($result['option_value']);
        add_image_size('banner_area-'.$matches[1], $sizeinfo['width'], $sizeinfo['height'], @$sizeinfo['crop']);

    }
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

/**
 * Make the "Featured Image" metabox front and center when editing a header-image post.
 */
function bannerman_metaboxes($post) {
    global $wp_meta_boxes;

    remove_meta_box('postimagediv', 'banner', 'side');
    add_meta_box('postimagediv', __('Featured Image'), 'post_thumbnail_meta_box', 'banner', 'normal', 'high');
    add_meta_box('bannerlinkbox', 'Link', 'bannerman_link_box', 'banner', 'normal', 'high');
}

function bannerman_link_box() {
    global $post;
    echo "<input type=\"text\" style=\"width: 98%;\" name=\"post_content\" value=\"" . esc_attr($post->post_content) . "\"/>";

    //echo '<style type="text/css">#message a, #minor-publishing { display: none }</style>';
}

function bannerman_load_widgets() {
    register_widget( 'Bannerman_Widget' );
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



// Banner_area helpers

function banner_area_add_meta_form() {
    echo '
<div class="form-field">
    <label for="bannerman-width">Tamanho</label>
    <input type="text" size="3" value="" style="width: 50px;" id="bannerman-width" name="banner_area_meta[width]">x
    <input type="text" size="3" value="" style="width: 50px;" id="bannerman-height" name="banner_area_meta[height]">
    <input type="checkbox" value="1" name="banner_area_meta[crop]"/> Recortar
    <p>O tamanho do banner</p>
</div>';
}

function banner_area_edit_meta_form() {
    $term = get_term($_GET['tag_ID'], 'banner_area');
    $meta = banner_area_meta($term->slug);

    echo '
<tr class="form-field">
    <th valign="top" scope="row"><label for="bannerman-width">Tamanho</label></th>
    <td><input type="text" size="3" style="width: 50px;" value="'.$meta['width'].'" id="bannerman-width" name="banner_area_meta[width]">x
    <input type="text" size="3" style="width: 50px;" value="'.$meta['height'].'" id="bannerman-height" name="banner_area_meta[height]">
    <input type="checkbox" value="1" name="banner_area_meta[crop]" '.($meta['crop'] == 1 ? 'checked' : '').'/> Recortar
    <p>O tamanho do banner</p>
    </td>

</tr>';
}

function banner_area_meta($slug, $key = null) {
    $meta = get_option("banner_area-meta:$slug", array());

    if ($key) {
        return @$meta[$key];
    }
    else {
        return $meta;
    }
}

function banner_area_meta_delete($tag_ID) {
    $term = get_term($tag_ID, 'banner_area');
    delete_option("banner_area-meta:$term->slug");
}

function banner_area_meta_save($tag_ID) {

    $slug = $_POST['slug'];
    if (!empty($_POST['banner_area_meta'])) {
        $meta = banner_area_meta($slug);
        foreach ($_POST['banner_area_meta'] as $k => $v) {
            $meta[$k] = $v;
        }

        update_option("banner_area-meta:$slug", $meta);
    }
}


/*-----------------------------------------------------------------------------------*/
// This starts the Banner Ad widget.
/*-----------------------------------------------------------------------------------*/


class Bannerman_Widget extends WP_Widget {

    function Bannerman_Widget() {
        /* Widget settings. */
        $widget_ops = array( 'classname' => 'bannerman', 'description' => __('This a widget shows a banner from a particular area.', "bannerman") );
        /* Widget control settings. */
        $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'bannerman-widget' );
        /* Create the widget. */
        $this->WP_Widget( 'bannerman-widget', __('Bannerman Ad', "bannerman"), $widget_ops, $control_ops );
    }

    function widget( $args, $instance ) {
        extract( $args );

        /* Our variables from the widget settings. */
        $title = apply_filters('widget_title', $instance['title'] );
        $banneradcode = get_banner($instance['banner_area']);

        /* Before widget (defined by themes). */
        echo $before_widget;

        /* Display the widget title if one was input (before and after defined by themes). */
        if ( $title )
            echo $before_title . $title . $after_title;

        /* Display ad code. */
        echo $banneradcode;

        /* After widget (defined by themes). */
        echo $after_widget;
    }

    function form( $instance ) {
        /* Set up some default widget settings. */
        $defaults = array(
            'title' => __('Advertisement', "bannerman"));

        $instance = wp_parse_args( (array) $instance, $defaults ); ?>

        <!-- Widget Title: Text Input -->
        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', "bannerman"); ?></label>
            <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" /></p>

        <!-- banneradcode: Text Input -->
        <?php $banner_areas = get_terms( 'banner_area', 'hide_empty=0' ); ?>
        <p><label for="<?php echo $this->get_field_id( 'banner_area' ); ?>"><?php _e('Banner Area:', "bannerman"); ?></label>


            <select id="<?php echo $this->get_field_id( 'banner_area' ); ?>" name="<?php echo $this->get_field_name( 'banner_area' ); ?>" style="width:100%;"><?php echo $instance['banner_area']; ?>
            <?php foreach($banner_areas as $area): ?>
            <option value="<?php echo $area->slug ?>" <?php if($instance['banner_area'] == $area->slug) echo 'selected'; ?>><?php echo $area->name ?></option>


            <?php endforeach; ?>
            </select></p>

    <?php
    }
}


/*
 * Utility functions
 */

function banner($area, $count = 1, $orderby = 'rand') {
    echo get_banner($area, $count, $orderby);
}


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
    return sprintf('<a href="%s" target="_blank">%s</a>',
        empty($arr[0]->post_content) ? '#' : $arr[0]->post_content,
        get_the_post_thumbnail($arr[0]->ID, 'banner_area-'.$area)
    );

}
