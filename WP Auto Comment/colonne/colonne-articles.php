<?php  

if (!defined('ABSPATH')) {
    exit; 
}

function acg_add_auto_comment_column($columns) {
    $columns['auto_comment'] = 'Commentaire Automatique';
    return $columns;
}
add_filter('manage_posts_columns', 'acg_add_auto_comment_column');

function acg_auto_comment_column_content($column_name, $post_id) {
    if ($column_name === 'auto_comment') {
        $is_enabled = get_post_meta($post_id, '_acg_auto_comment_enabled', true);
        echo '<input type="checkbox" class="acg-auto-comment-toggle" data-post-id="' . esc_attr($post_id) . '" ' . checked($is_enabled, '1', false) . ' />';
    }
}
add_action('manage_posts_custom_column', 'acg_auto_comment_column_content', 10, 2);