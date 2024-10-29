<?php

if (!defined('ABSPATH')) {
    exit; 
}

// AJAX pour générer les templates
function acg_generate_comment_templates() {
    check_ajax_referer('generate_templates_nonce', 'nonce');

    $count = intval($_POST['count']);
    $api_key = get_option('acg_api_key');

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'Clé API OpenAI non configurée.']);
    }

    $templates = [];
    for ($i = 0; $i < $count; $i++) {
 
 $prompt = 'Ta réponse doit être stockée dans la variable "template_com" au format JSON. Je ne veux pas de sous-variable, mais uniquement la variable template_com avec des caractères en valeur. Cette variable doit créer un persona unique et varié qui servira de base pour générer des commentaires. Le persona doit inclure les éléments suivants :

- **Nom de famille et Prénom** : Choisis un nom et un prénom uniques qui ne ressemblent pas aux précédents persona. Evite des noms trop courants.

- **Âge et Situation** : Indique un âge (entre 20 et 60 ans) et précise une brève mention de la situation de ce persona (étudiant, professionnel dans un secteur varié, parent, retraité, etc.), en s\'assurant que cela reflète la diversité des expériences.

- **Profession** : Il faut un nom de métier unique, tout secteur confondue. Sélectionne un métier sauf écrivain , rédacteur et la même thématique.

- **Style d’Écriture** : Fournis un style d\'écriture distinctif. 

La réponse doit être concise, ne dépassant pas 50 mots, et se concentrer sur la création d\'un persona unique. Assure-toi que la réponse soit variée à chaque requête en intégrant des éléments aléatoires et des descriptions différentes.


Voici un exemple de ce à quoi pourrait ressembler une réponse :
{"template_com": "Nom de famille : Malik | Prénom : Gourmand | 35 ans | consultant en développement durable. Passionné de nature et d’innovation, j\'écris souvent sur les astuces écologiques. Mon style est passionné et engageant. J’aime inclure des faits intéressants pour enrichir le débat et j’adore terminer par des appels à l’action."} de fam

Assure-toi que la réponse soit prête à être utilisée comme base pour générer des commentaires ultérieurs au sein d’une application ou d’un service. Utilise des mots simples :';
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 100,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
               'temperature' => 1.2,
                 
                'max_tokens' => 1000,
                'response_format' => [
                    'type' => 'json_object'
                ]
            ]),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Erreur API: ' . $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['choices'][0]['message']['content'])) {
            $json_response = json_decode($data['choices'][0]['message']['content'], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json_response['template_com'])) {
                $templates[] = $json_response['template_com']; 
            } else {
                wp_send_json_error(['message' => 'La réponse JSON n\'est pas au format attendu.']);
            }
        }
    }

    wp_send_json_success(['templates' => $templates]);
}
add_action('wp_ajax_acg_generate_comment_templates', 'acg_generate_comment_templates');