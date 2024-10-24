<?php 

if (!defined('ABSPATH')) {
    exit; 
}


function acg_cron_generate_comments() {
    $enabled = get_option('acg_auto_comment_enabled', 1);

    if (!$enabled) {
        return;
    }

    $posts = get_posts(['numberposts' => -1, 'post_type' => 'post', 'post_status' => 'publish']);
    $api_key = get_option('acg_api_key', '');
    $min_words = get_option('acg_min_words', 5);
    $max_words = get_option('acg_max_words', 20);
    $writing_style = get_option('acg_writing_style', '');
    $gpt_model = get_option('acg_gpt_model', 'gpt-4o-mini');
    $comment_count = get_option('acg_comment_count', 1);

    foreach ($posts as $post) {
        $post_id = $post->ID;
        $post_content = $post->post_content;

        if (empty($api_key)) {
            error_log('Clé API OpenAI non configurée.');
            continue;
        }

        $auto_comment_enabled = get_post_meta($post_id, '_acg_auto_comment_enabled', true);
        if (!$auto_comment_enabled) {
            continue; 
        }

        // Récupération de l'auteur de l'article
        $post_author_id = get_post_field('post_author', $post_id); 
        $post_author_first_name = get_user_meta($post_author_id, 'first_name', true);
        $post_author_display_name = get_the_author_meta('display_name', $post_author_id);

        // Prénom si existant, sinon, nom d'affichage
        $post_author = !empty($post_author_first_name) ? $post_author_first_name : $post_author_display_name;

        for ($i = 0; $i < $comment_count; $i++) {
     
            $full_prompt = [
                [
                    'role' => 'system',
                    'content' => 'Voici le contenu de l\'article : ' . $post_content . '. Voici le style d\'écriture : ' . $writing_style
                ],
                [
                    'role' => 'user',
                    'content' => 'Adresse toi directement à l\'auteur de l\'article, ' . $post_author . ', en répondant : Ecris un commentaire d\'environ entre ' . intval($min_words) . ' et ' . intval($max_words) . ' mots. Donne-moi un json avec la variable "auteur" et la variable "commentaire". Invente un nom et prénom unique différents de noms-prenoms classiques et rédige un commentaire court.'
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
                error_log('Erreur API: ' . $response->get_error_message());
                continue;
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

                    wp_insert_comment($comment_data);
                } else {
                    error_log('La réponse JSON n\'est pas au format attendu pour l\'article ID ' . $post_id);
                }
            } else {
                error_log('Aucune réponse valide reçue de l\'API pour l\'article ID ' . $post_id);
            }
        }
    }
}
add_action('acg_cron_hook', 'acg_cron_generate_comments');