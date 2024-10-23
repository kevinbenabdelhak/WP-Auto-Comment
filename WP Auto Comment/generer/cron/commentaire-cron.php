<?php 

if (!defined('ABSPATH')) {
    exit; 
}

// Fonction de génération des commentaires via Cron
function acg_cron_generate_comments() {
    $enabled = get_option('acg_auto_comment_enabled', 1);

    if (!$enabled) {
        return; // Ne pas générer de commentaires si désactivé
    }

    $posts = get_posts(['numberposts' => -1, 'post_type' => 'post', 'post_status' => 'publish']);
    $api_key = get_option('acg_api_key', '');
    $min_words = get_option('acg_min_words', 5);
    $max_words = get_option('acg_max_words', 20);
    $writing_style = get_option('acg_writing_style', '');
    $gpt_model = get_option('acg_gpt_model', 'gpt-4o-mini'); // Obtention du modèle GPT choisi
    $comment_count = get_option('acg_comment_count', 1); // Obtention du nombre de commentaires à générer

    foreach ($posts as $post) {
        $post_id = $post->ID;
        $post_content = $post->post_content;

        if (empty($api_key)) {
            error_log('Clé API OpenAI non configurée.');
            continue;
        }

        $auto_comment_enabled = get_post_meta($post_id, '_acg_auto_comment_enabled', true);
        if (!$auto_comment_enabled) {
            continue; // Passer à la prochaine publication si les commentaires automatiques ne sont pas activés
        }

        for ($i = 0; $i < $comment_count; $i++) {
            // Mettre en place la logique pour appeler l'API OpenAI et insérer le commentaire
            $full_prompt = [
                [
                    'role' => 'system',
                    'content' => 'Voici le contenu de l\'article : ' . $post_content . '. Voici le style d\'écriture : ' . $writing_style
                ],
                [
                    'role' => 'user',
                    'content' => 'Ecris un commentaire d\'environ entre ' . intval($min_words) . ' et ' . intval($max_words) . ' mots. Donne-moi un json avec la variable "auteur" et la variable "commentaire". Invente un nom et prénom unique et rédige un commentaire court et naturel.'
                ]
            ];

            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'timeout' => 100,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'model' => $gpt_model, // Utilisation du modèle choisi
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

            // Traitement de la réponse d'OpenAI
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

