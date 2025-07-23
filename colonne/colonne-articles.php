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
                $minutes = ceil($time_left / 60);
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
        // Edition inline ici :
        $max_comments = get_post_meta($post_id, '_acg_max_comments', true);
        $display_value = ($max_comments && intval($max_comments) > 0) ? intval($max_comments) : '∞';
        echo '<span class="acg-editable-max-comments" data-post-id="' . esc_attr($post_id) . '" data-value="' . esc_attr($max_comments) . '">' . esc_html($display_value) . '</span>';
    }
}

// Ajoute les colonnes UNIQUEMENT aux post types autorisés
add_action('init', function () {
    $allowed_post_types = (array) get_option('acg_allowed_post_types', ['post']);
    foreach ($allowed_post_types as $post_type) {
        if (post_type_exists($post_type)) {
            add_filter("manage_{$post_type}_posts_columns", 'acg_add_auto_comment_column');
            add_action("manage_{$post_type}_posts_custom_column", 'acg_auto_comment_column_content', 10, 2);
        }
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

// Ajax pour sauver le max commentaires "inline"
function acg_save_max_comments() {
    if (!isset($_POST['post_id'])) {
        wp_send_json_error('missing_id');
    }
    $post_id = intval($_POST['post_id']);
    $max_comments = $_POST['max_comments'];
    if ($max_comments === '' || !is_numeric($max_comments) || intval($max_comments) < 1) {
        delete_post_meta($post_id, '_acg_max_comments');
        wp_send_json_success(['value'=>'∞']);
    }
    update_post_meta($post_id, '_acg_max_comments', intval($max_comments));
    wp_send_json_success(['value'=>intval($max_comments)]);
}
add_action('wp_ajax_acg_save_max_comments', 'acg_save_max_comments');

// Scripts JS
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
                            alert('Une erreur s\'est produite lors de la sauvegarde. Veuillez réessayer.');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Une erreur s\'est produite lors de la communication avec le serveur: ' + textStatus);
                        console.error('AJAX Error:', textStatus, errorThrown, jqXHR);
                    }
                });
            });

            // *** Edition inline pour le max commentaires ***
            $(document).on('click', '.acg-editable-max-comments', function(e){
                var $span = $(this);
                if ($span.find('input').length > 0) return; // déjà en édition

                var val = $span.attr('data-value');
                if (!val || parseInt(val) <= 0) val = '';
                var input = $('<input type="number" min="1" style="width:40px;" />').val(val).on('click', function(e){ e.stopPropagation(); });

                $span.html(input).addClass('acg-editing-max-comments');
                input.focus().select();

                // On valide avec Enter ou désélection
                input.on('blur acg_submit', function(){
                    var newVal = input.val();
                    var postId = $span.data('post-id');
                    $span.removeClass('acg-error');
                    input.prop('disabled', true);
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'acg_save_max_comments',
                            post_id: postId,
                            max_comments: newVal
                        },
                        success: function(response) {
                            if (response.success) {
                                var display = response.data.value;
                                $span.html(display).attr('data-value', (display==='∞'?'':display));
                                $span.removeClass('acg-editing-max-comments');
                            } else {
                                $span.html('∞').attr('data-value', '');
                                $span.addClass('acg-error');
                            }
                        },
                        error: function() {
                            $span.html('∞').attr('data-value', '');
                            $span.addClass('acg-error');
                        }
                    });
                });
                input.on('keydown', function(e){
                    if(e.key === 'Enter'){
                        input.trigger('acg_submit');
                    }
                    if(e.key === 'Escape'){
                        // Annuler
                        var prevVal = $span.attr('data-value');
                        $span.html(prevVal && parseInt(prevVal)>0 ? prevVal : '∞');
                        $span.removeClass('acg-editing-max-comments');
                    }
                });
                e.stopPropagation();
            });

            // Un clic ailleurs annule l'édition si on est encore en mode edition
            $(document).on('click', function(){
                $('.acg-editing-max-comments').each(function(){
                    var $span = $(this);
                    var input = $span.find('input');
                    if(input.length){
                        input.trigger('blur');
                    }
                });
            });
        });
    </script>
    <style>
        .acg-editable-max-comments {
            cursor: pointer;
            background: #f6f7f7;
            border-radius: 2px;
            transition: background 0.15s;
            padding: 2px 8px;
        }
        .acg-editable-max-comments:hover, .acg-editing-max-comments {
            background: #dbeafe;
        }
        .acg-editable-max-comments input {
            margin:0; padding:0 1px; box-sizing:border-box;
        }
        .acg-editable-max-comments.acg-error {
            background: #fecaca !important;
        }
    </style>
    <?php
}
add_action('admin_footer', 'acg_enqueue_auto_comment_script');