<?php 

if (!defined('ABSPATH')) {
    exit; 
}

// page d'options
function acg_add_admin_menu() {
    add_options_page('WP Auto Comment', 'WP Auto Comment', 'manage_options', 'wp-auto-comment', 'acg_options_page');
}
add_action('admin_menu', 'acg_add_admin_menu');

function acg_options_page() {
    ?>
    <div class="wrap">
        <h1>WP Auto Comment</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('acg_options_group');
            do_settings_sections('acg_options_group');
            $api_key = get_option('acg_api_key', '');
            $min_words = get_option('acg_min_words', 5);
            $max_words = get_option('acg_max_words', 20);
            $cron_interval = get_option('acg_cron_interval', 5);
            $auto_comment_enabled = get_option('acg_auto_comment_enabled', 1); 
            $gpt_model = get_option('acg_gpt_model', 'gpt-4o-mini'); 
            $comment_count = get_option('acg_comment_count', 1); 
            $writing_styles = (array) get_option('acg_writing_styles', []); 
            $include_author_names = (array) get_option('acg_include_author_names', []); 
            ?>
            <table class="form-table">
                <tr valign="top"><th scope="row" colspan="2" style="padding:0px !important;"><h2 style="margin:8px 0px !important;">Réglages générales</h2>
					<p style="font-weight:400;">Pour utiliser ce plugin, vous devez générer une clé API sur OpenAI et l'enregistrer dans cette page d'option avant de passer aux étapes suivantes.</p>
					</th></tr>
                <tr valign="top">
                    <th scope="row">Clé API OpenAI</th>
                    <td><input type="text" name="acg_api_key" value="<?php echo esc_attr($api_key); ?>" />
                        <p><a href="https://platform.openai.com/api-keys" target="_blank">Générer une clé OpenAI</a></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Modèle GPT</th>
                    <td>
                        <select name="acg_gpt_model">
                            <option value="gpt-4o-mini" <?php selected($gpt_model, 'gpt-4o-mini'); ?>>gpt-4o-mini</option>
                            <option value="gpt-4o" <?php selected($gpt_model, 'gpt-4o'); ?>>gpt-4o</option>
                            <option value="gpt-3.5-turbo" <?php selected($gpt_model, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo</option>
                        </select>
                        <p>Sélectionnez un modèle d'OpenAI</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nombre de mots (min)</th>
                    <td><input type="number" name="acg_min_words" value="<?php echo esc_attr($min_words); ?>" min="1" />
                        <p>Nombre de mots minimum dans un commentaire</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nombre de mots (max)</th>
                    <td><input type="number" name="acg_max_words" value="<?php echo esc_attr($max_words); ?>" min="1" />
                        <p>Nombre de mots maximum dans un commentaire</p>
                    </td>
                </tr>
         
                <tr valign="top">
                     <td style="padding:0px !important;" colspan="2">
                        <h2 style="margin:8px 0px !important;">Modèles de commentaires</h2>
						 <p style="max-width: 590px;">Chaque modèle peut comprendre des informations sur l'auteur (nom/prénom) ainsi que des caractéristiques spécifiques qui définissent le ton et le style du commentaire. Grâce à ces modèles, vous pouvez créer des personas en plus d'éviter les redondances de l'IA. </p><br>
						 <b>Vous pouvez générer ces modèles en masse avec gpt-4o-mini :</b>
						<div style="display: flex;flex-direction: column;align-items: flex-start;margin-bottom: 15px;">  
							<p>Entrez le nombre de modèles à générer : 
                            <input type="number" id="template_count" min="1" value="1" style="width: 50px;" />
                        </p>
							<div id="generated_templates" ></div>
                        <button type="button" id="generate_templates_button" class="button action">Générer</button>
							
						</div>
						 <hr>
                        <div id="writing-styles-container" style="gap: 10px; display: flex; flex-direction: column; margin-bottom: 10px;">
							<style>.writing-style{gap: 10px; display: flex; flex-direction: row; margin-bottom: 10px; flex-wrap: nowrap; align-content: center; align-items: center;}</style>
                            <?php if (!empty($writing_styles)): ?>
                                <?php foreach ($writing_styles as $index => $style): ?>
                                    <div class="writing-style">
                                      <div style="display: flex; flex-direction: column; gap: 8px;">
										  <span>Description des auteurs des commentaires (identité, style d'écriture..)</span>
										  <textarea name="acg_writing_styles[]" rows="4" cols="50"><?php echo esc_textarea($style); ?></textarea>
										</div>  
                                        <label>
                                           <input type="checkbox" name="acg_include_author_names[]" value="<?php echo esc_attr($index); ?>" <?php checked(in_array($index, $include_author_names)); ?> />
                                            S'adresse directement à l'auteur de l'article
                                        </label>
                                        <button type="button" class="button action remove-style-button">Supprimer</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Aucun modèle de commentaire n'est actuellement défini.</p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button action" id="add-writing-style-button">Ajouter un modèle</button>
                    </td>
                </tr>
                <tr valign="top">
                    <th style="padding:0 !important;" scope="row" colspan="2"><hr /></th>
                </tr>
                <tr valign="top"><th scope="row" colspan="2" style="padding:0px !important;"><h2 style="margin:8px 0px !important;">Commentaires automatiques</h2>
					
					<p style="font-weight: 400; max-width: 640px;">
						Vous pouvez créer des commentaires automatiquement à une fréquence donnée. Pour utiliser cette option, vous devez activer les cases à cocher "Commentaires automatiques" dans le tableau des publication sur la page listing des articles.
					</p>
					</th></tr>
               
				
				<tr valign="top">
                    <th scope="row">Activer la génération de commentaires automatiques</th>					
                    <td><input type="checkbox" name="acg_auto_comment_enabled" value="1" <?php checked($auto_comment_enabled, 1); ?> />  
						<p>
							Cette option permet de générer automatiquement les coms sur les articles qui ont la case cochée "commentaire automatique"
						</p>
                    </td>
                </tr>
				
				
	<tr valign="top">
    <th scope="row">Activer les commentaires automatiques pour les nouvelles publications</th>
    <td>
        <input type="checkbox" name="acg_auto_comment_default" value="1" <?php checked(get_option('acg_auto_comment_default', 1), 1); ?> />
		<p>
							Cette option permet de cocher la case "commentaire automatique" par défaut sur les nouvelles publications
						</p>
    </td>
	</tr>
				
                <tr valign="top">
                    <th scope="row">Planifier les commentaires</th>
					
                    <td>
					Publier entre <input style="width:50px;" type="number" name="acg_comment_min_per_post" value="<?php echo esc_attr(get_option('acg_comment_min_per_post', 1)); ?>" min="1" /> et <input style="width:50px;" type="number" name="acg_comment_max_per_post" value="<?php echo esc_attr(get_option('acg_comment_max_per_post', 5)); ?>" min="1" /> commentaires toutes les <input style="width:50px;" type="number" name="acg_cron_interval" value="<?php echo esc_attr($cron_interval); ?>" min="1"  /> minutes par publication
                    </td>
                </tr>				
				
				
								

				
				
				
			<tr valign="top">
    <th scope="row">Nombre maximum de commentaires par publication</th>
    <td>
		Ne jamais dépasser entre <input style="width:50px;" type="number" name="acg_comment_max_per_post_value_min" value="<?php echo esc_attr(get_option('acg_comment_max_per_post_value_min', 1)); ?>" min="1" /> et <input style="width:50px;" type="number" name="acg_comment_max_per_post_value_max" value="<?php echo esc_attr(get_option('acg_comment_max_per_post_value_max', 5)); ?>" min="1" /> commentaires par publication
       
        <p>Cette option génèrera un nombre aléatoire dès la première publication de commentaire automatique </p>
    </td>
			</tr>
				

				
				
				
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

    <script>
        document.getElementById('generate_templates_button').addEventListener('click', function() {
            var count = parseInt(document.getElementById('template_count').value);
            if (isNaN(count) || count < 1) {
                alert("Veuillez entrer un nombre valide.");
                return;
            }
            
            var generatedTemplatesContainer = document.getElementById('generated_templates');
            generatedTemplatesContainer.innerHTML = ""; 

            var index = 0; 

            function generateTemplate() {
                if (index >= count) {
                    return; 
                }

                var loadingMessage = document.createElement('p');
                loadingMessage.textContent = "Génération du template " + (index + 1) + " en cours...";
                generatedTemplatesContainer.appendChild(loadingMessage);

                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'acg_generate_comment_templates',
                        count: 1, 
                        nonce: '<?php echo wp_create_nonce('generate_templates_nonce'); ?>'
                    },
                    success: function(response) {
                        loadingMessage.remove();

                        if (response.success) {
                            var template = response.data.templates[0];
                            var div = document.createElement('div');
                            div.className = 'writing-style ';
                            div.innerHTML = '<textarea name="acg_writing_styles[]" rows="4" cols="50">' + template + '</textarea>' +
                                '<label><input type="checkbox" name="acg_include_author_names[]" value="" /> Inclure le nom de l\'auteur</label>' +
                                '<button type="button" class="button action remove-style-button">Supprimer</button>';
                            generatedTemplatesContainer.appendChild(div);
                            
                            index++; 
                            generateTemplate();
                        } else {
                            alert("Erreur lors de la génération des templates: " + response.data.message);
                        }
                    },
                    error: function() {
                        loadingMessage.remove();
                        alert("Une erreur s'est produite lors de la communication avec le serveur.");
                    }
                });
            }

            generateTemplate(); 
            
        });
        
        document.querySelectorAll('.remove-style-button').forEach(function(button) {
            button.addEventListener('click', function() {
                button.parentElement.remove();
            });
        });
        
        document.getElementById('add-writing-style-button').addEventListener('click', function() {
            var container = document.getElementById('writing-styles-container');
            var newStyle = document.createElement('div');
            newStyle.className = 'writing-style';
            newStyle.innerHTML = '<textarea name="acg_writing_styles[]" rows="4" cols="50"></textarea><label><input type="checkbox" name="acg_include_author_names[]" value="" /> S\'adresser à l\'auteur</label><button type="button" class="remove-style-button button action">Supprimer</button>';
            container.appendChild(newStyle);

            newStyle.querySelector('.remove-style-button').addEventListener('click', function() {
                container.removeChild(newStyle);
            });
        });

    </script>
    <?php
}






function acg_set_auto_comment_default($post_id) {
    if (get_post_type($post_id) === 'post') {
        $auto_comment_default = get_option('acg_auto_comment_default', 1);
        update_post_meta($post_id, '_acg_auto_comment_enabled', $auto_comment_default ? '1' : '0');
    }
}
add_action('wp_insert_post', 'acg_set_auto_comment_default');




function acg_register_settings() {
    register_setting('acg_options_group', 'acg_api_key');
    register_setting('acg_options_group', 'acg_writing_styles');
    register_setting('acg_options_group', 'acg_include_author_names');
	
	
	
	
	
    register_setting('acg_options_group', 'acg_min_words');
    register_setting('acg_options_group', 'acg_max_words');
    
    register_setting('acg_options_group', 'acg_auto_comment_enabled'); 
    register_setting('acg_options_group', 'acg_gpt_model'); 
    register_setting('acg_options_group', 'acg_comment_count'); 
    register_setting('acg_options_group', 'acg_cron_interval'); 
	
	
	
	// Nombre de coms max par boucle sur une publication
	
	register_setting('acg_options_group', 'acg_comment_min_per_post');
	register_setting('acg_options_group', 'acg_comment_max_per_post');
	
	
	
	
	// Nombre de coms max au total sur une publication

	register_setting('acg_options_group', 'acg_comment_max_per_post_value_min');
	register_setting('acg_options_group', 'acg_comment_max_per_post_value_max');
	
	
	
	
	register_setting('acg_options_group', 'acg_auto_comment_default');
	
}
add_action('admin_init', 'acg_register_settings');

