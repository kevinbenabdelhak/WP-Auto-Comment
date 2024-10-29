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

// Enregistrer la valeur de la case à cocher via Ajax
function acg_save_auto_comment() {
    if (isset($_POST['post_id']) && isset($_POST['enabled'])) {
        $post_id = intval($_POST['post_id']);
        $enabled = $_POST['enabled'] === 'true' ? '1' : '0';
        update_post_meta($post_id, '_acg_auto_comment_enabled', $enabled);
        wp_send_json_success();
    }
    wp_send_json_error();
}
add_action('wp_ajax_acg_save_auto_comment', 'acg_save_auto_comment');


function acg_enqueue_auto_comment_script() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.acg-auto-comment-toggle').on('change', function() {
                var postId = $(this).data('post-id');
                var isChecked = $(this).is(':checked');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'acg_save_auto_comment',
                        post_id: postId,
                        enabled: isChecked
                    },
                    success: function(response) {
                        if (!response.success) {
                            alert('Une erreur s\'est produite. Veuillez réessayer.');
                        }
                    },
                    error: function() {
                        alert('Une erreur s\'est produite lors de la communication avec le serveur.');
                    }
                });
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'acg_enqueue_auto_comment_script');