<?php  

if (!defined('ABSPATH')) {
    exit; 
}

function acg_add_auto_comment_column($columns) {
    $columns['auto_comment'] = 'Commentaire automatique';
    $columns['comment_count'] = 'Nombre de commentaires';
    $columns['max_comments'] = 'Max commentaires';
    return $columns;
}

// Contenu des colonnes
function acg_auto_comment_column_content($column_name, $post_id) {
    if ($column_name === 'auto_comment') {
        $is_enabled = get_post_meta($post_id, '_acg_auto_comment_enabled', true);
        echo '<input type="checkbox" class="acg-auto-comment-toggle" data-post-id="' . esc_attr($post_id) . '" ' . checked($is_enabled, '1', false) . ' />';
        
        // Afficher le timer SI mode durée ET activé ET délai restant
        $mode = get_option('acg_comment_publish_mode', 'duration');
        $auto_comment_delay = (int) get_option('acg_auto_comment_delay', 0);

        if ($is_enabled && $mode === 'duration' && $auto_comment_delay > 0) {
            $published_time = strtotime(get_post_field('post_date_gmt', $post_id));
            $current_time = time();
            $delay_sec = $auto_comment_delay * 60;
            $time_left = ($published_time + $delay_sec) - $current_time;

            if ($time_left > 0) {
                // minutes arrondi supérieur
                $minutes = ceil($time_left / 60);
                // Affichage en français
                echo '<div class="acg-auto-comment-timer" style="white-space:nowrap;float:right;display:contents;">Dans ';
                if ($minutes < 2) {
                    echo $minutes . ' minute';
                } else {
                    echo $minutes . ' minutes';
                }
                echo '</div>';
            }
        }
    } elseif ($column_name === 'comment_count') {
        $comments_count = wp_count_comments($post_id)->total_comments;
        echo esc_html($comments_count);
    } elseif ($column_name === 'max_comments') {
        $max_comments = get_post_meta($post_id, '_acg_max_comments', true);
        echo esc_html($max_comments ? $max_comments : '0');
    }
}

// Ajoute les colonnes à TOUS les post types publics (post, page, CPTs)
add_action('init', function () {
    $all_types = get_post_types(['public' => true, 'show_ui' => true]);
    foreach ($all_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'acg_add_auto_comment_column');
        add_action("manage_{$post_type}_posts_custom_column", 'acg_auto_comment_column_content', 10, 2);
    }
});


// enregistrer la valeur de la case à cocher en ajax
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