<?php 


if (!defined('ABSPATH')) {
    exit; 
}


function acg_generate_comment() {
    check_ajax_referer('acg_nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    $api_key = get_option('acg_api_key', '');
    $post_content = get_post_field('post_content', $post_id);
    $writing_style = get_option('acg_writing_style', '');
    $min_words = get_option('acg_min_words', 5);
    $max_words = get_option('acg_max_words', 20);
    $gpt_model = get_option('acg_gpt_model', 'gpt-4o-mini'); 

    if (empty($api_key)) {
        wp_send_json_error(['data' => 'Clé API OpenAI non configurée.']);
    }

    $full_prompt = [
        [
            'role' => 'system',
            'content' => 'Voici le contenu de l\'article : ' . $post_content . '. Voici le style d\'écriture : ' . $writing_style
        ],
        [
            'role' => 'user',
            'content' => 'Ecris un commentaire d\'environ entre ' . intval($min_words) . ' et ' . intval($max_words) . ' mots. Donne-moi un json avec la variable "auteur" et la variable "commentaire". Invente un nom et prénom unique et rédige un commentaire court.'
        ]
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 100,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => $gpt_model,
            'messages' => $full_prompt,
            'temperature' => 1,
            'max_tokens' => 150,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'response_format' => [
                'type' => 'json_object'
            ]
        ]),
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['data' => 'Erreur lors de la communication avec l\'API. Détails: ' . $response->get_error_message()]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['choices'][0]['message']['content'])) {
        $json_response = json_decode($data['choices'][0]['message']['content'], true);
        
        if (isset($json_response['auteur']) && isset($json_response['commentaire'])) {
            $comment_content = trim($json_response['commentaire']);
            $comment_author = trim($json_response['auteur']);

            $comment_data = array(
                'comment_post_ID' => $post_id,
                'comment_content' => $comment_content,
                'comment_author' => $comment_author,
                'comment_approved' => 1,
            );


            $comment_id = wp_insert_comment($comment_data);

            $post_data = array(
                'ID' => $post_id,
            );

            wp_update_post($post_data);

            if ($comment_id) {
                wp_send_json_success();
            } else {
                wp_send_json_error('Erreur lors de l\'insertion du commentaire.');
            }
        } else {
            wp_send_json_error(['data' => 'La réponse JSON n\'est pas au format attendu.']);
        }
    } else {
        wp_send_json_error(['data' => 'Aucune réponse valide reçue de l\'API.']);
    }
}
add_action('wp_ajax_acg_generate_comment', 'acg_generate_comment');




// activer/désactiver les commentaires automatiques
function acg_toggle_auto_comment() {
    check_ajax_referer('acg_nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    $enabled = intval($_POST['enabled']);

    update_post_meta($post_id, '_acg_auto_comment_enabled', $enabled);

    wp_send_json_success();
}
add_action('wp_ajax_acg_toggle_auto_comment', 'acg_toggle_auto_comment');