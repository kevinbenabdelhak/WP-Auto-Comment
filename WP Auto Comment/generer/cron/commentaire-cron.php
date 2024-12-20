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
    $gpt_model = get_option('acg_gpt_model', 'gpt-4o-mini');
    $writing_styles = get_option('acg_writing_styles', []);
    $include_author_names = get_option('acg_include_author_names', []); 

    if (empty($writing_styles)) {
        error_log('Aucun style d\'écriture disponible.');
        return; 
    }

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

        // Vérifier le nombre maximal de commentaires
        $min_limit = (int) get_option('acg_comment_max_per_post_value_min', 1);
        $max_limit = (int) get_option('acg_comment_max_per_post_value_max', 5);
        
        // Générer un nombre aléatoire de commentaires compris entre min et max (pour cette publication)
        $current_comments = wp_count_comments($post_id)->total_comments;
        $current_max_comments = get_post_meta($post_id, '_acg_max_comments', true);
        
        // Si pas encore défini, attribuer un nombre aléatoire
        if (!$current_max_comments) {
            $current_max_comments = rand($min_limit, $max_limit);
            update_post_meta($post_id, '_acg_max_comments', $current_max_comments);
        }

        // Ne pas publier si le nombre de commentaires a été atteint
        if ($current_comments >= $current_max_comments) {
            continue; 
        }

        // Obtenir les valeurs min et max pour les commentaires à générer
        $min_comments = get_option('acg_comment_min_per_post', 1);
        $max_comments = get_option('acg_comment_max_per_post', 5);
        
        // Calculer le nombre de commentaires à générer qui ne dépasse pas la limite
        $available_space = $current_max_comments - $current_comments;
        $comment_count = min(rand($min_comments, $max_comments), $available_space);

        for ($i = 0; $i < $comment_count; $i++) {
            $current_index = get_post_meta($post_id, '_acg_current_style_index', true);
            if ($current_index === '') {
                $current_index = 0; 
            }

            $style = $writing_styles[$current_index];

            $include_author_name = is_array($include_author_names) && in_array($current_index, $include_author_names);
            if ($include_author_name) {
                $post_author_id = get_post_field('post_author', $post_id); 
                $post_author_first_name = get_user_meta($post_author_id, 'first_name', true);
                $post_author_display_name = get_the_author_meta('display_name', $post_author_id);
                $post_author = !empty($post_author_first_name) ? $post_author_first_name : $post_author_display_name;
                $inclureauteur = "Adresse toi directement à l'auteur de l'article en début de commentaire : " . $post_author . ", en répondant : ";
            } else {
                $inclureauteur = ""; 
            }

            $full_prompt = [
                [
                    'role' => 'system',
                    'content' => 'Voici le contenu de l\'article : ' . $post_content . '. Voici le style d\'écriture à prendre en compte ainsi que les informations sur le persona à imiter pour la réponse : ' . $style
                ],
                [
                    'role' => 'user',
                    'content' => ' '. $inclureauteur . 'Donne-moi un JSON avec la variable "auteur" et la variable "commentaire".  Ecris un commentaire (désoptimisé) d\'environ entre ' . intval($min_words) . ' et ' . intval($max_words) . ' mots. Si le prénom et le nom de famille sont spécifiés dans le style d\'écriture ci-dessus/infos du persona à imiter, utilise exactement les mêmes dans la variable auteur. Sinon, invente un nom et un prénom uniques qui ne sont pas classiques. Rédige un commentaire court et pertinent en utilisant ces informations. Commentaire dans la langue dans laquelle est rédigé l\'article. Donne un avis naturel avec des mots simples.'
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
                    'temperature' => 1.0,
                    'max_tokens' => 500,
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

          
            $current_index = ($current_index + 1) % count($writing_styles);
            update_post_meta($post_id, '_acg_current_style_index', $current_index); 
        }
    }
}
add_action('acg_cron_hook', 'acg_cron_generate_comments');