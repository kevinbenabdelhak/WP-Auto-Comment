<?php 

if (!defined('ABSPATH')) {
    exit; 
}

function acg_generate_comment() {
    check_ajax_referer('acg_nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    $api_key = get_option('acg_api_key', '');
    $post_content = get_post_field('post_content', $post_id);
    $min_words = get_option('acg_min_words', 5);
    $max_words = get_option('acg_max_words', 20);
    $gpt_model = get_option('acg_gpt_model', 'gpt-4o-mini'); 
    $writing_styles = get_option('acg_writing_styles', []);
    $include_author_names = get_option('acg_include_author_names', []);
    $post_author_id = get_post_field('post_author', $post_id); 
    $post_author_first_name = get_user_meta($post_author_id, 'first_name', true);
    $post_author_display_name = get_the_author_meta('display_name', $post_author_id);

 $post_author = !empty($post_author_first_name) ? $post_author_first_name : $post_author_display_name;

   $current_index = get_post_meta($post_id, '_acg_current_style_index', true); 
if ($current_index === '') {
    $current_index = 0; 
}

$style = $writing_styles[$current_index];


$include_author_names = get_option('acg_include_author_names', []);
$include_author_name = is_array($include_author_names) && in_array($current_index, $include_author_names);
if ($include_author_name) {
    $inclureauteur = "Adresse toi directement à l'auteur de l'article en début de commentaire : " . $post_author . ", en répondant : ";
} else {
    $inclureauteur = "";  
}

    if (empty($api_key)) {
        wp_send_json_error(['data' => 'Clé API OpenAI non configurée.']);
    }


    if (empty($writing_styles)) {
        wp_send_json_error(['data' => 'Aucun style d\'écriture disponible.']);
    }

$full_prompt = [
    [
        'role' => 'system',
        'content' => 'Voici le contenu de l\'article : ' . $post_content . '. Voici le style d\'écriture à prendre en compte ainsi que les informations sur le personna à imiter pour la réponse : ' . $style
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
            'max_tokens' => 600,
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

            $current_index = ($current_index + 1) % count($writing_styles);
            update_post_meta($post_id, '_acg_current_style_index', $current_index);

            if ($comment_id) {
                wp_send_json_success(['data' => 'Commentaire créé']);
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