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
            $citationprenomauteur = get_option('citationprenomauteur', 1); 
            $writing_styles = get_option('acg_writing_styles', []);
            $include_author_names = get_option('acg_include_author_names', []);
            ?>
            <table class="form-table">
                <tr valign="top"><th scope="row" colspan="2" style="padding:0px !important;"><h2 style="margin:8px 0px !important;">Commentaire</h2></th></tr>
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
                        <p> Nombre de mots minimum dans un commentaire </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nombre de mots (max)</th>
                    <td><input type="number" name="acg_max_words" value="<?php echo esc_attr($max_words); ?>" min="1" />
                    <p> Nombre de mots maximum dans un commentaire </p>
                    </td>
                </tr>
                <tr valign="top">
                    <td colspan="2"><h2 style="margin:8px 0px !important;">Templates de commentaires</h2><p style="max-width:740px;">Personnalisez les commentaires générés avec des templates prédéfinis en ajoutant des styles d'écriture. Le système alterne automatiquement entre les différents templates pour chaque commentaire créant ainsi une diversité dans les réponses.</p></td>
					
                </tr>
                <tr valign="top">
                    <td colspan="2">
                        <div id="writing-styles-container" style="gap: 10px; display: flex; flex-direction: column; margin-bottom: 10px;">
							<style>.writing-style{display: flex; flex-direction: row; flex-wrap: nowrap; align-content: center; align-items: center; gap: 20px;}</style>
							<?php foreach ($writing_styles as $index => $style): ?>
                                <div class="writing-style">
                                    <textarea name="acg_writing_styles[]" rows="4" cols="50"><?php echo esc_textarea($style); ?></textarea>
                                    <label>
                                        <input type="checkbox" name="acg_include_author_names[]" value="<?php echo $index; ?>" <?php checked(in_array($index, $include_author_names), true); ?> />
                                        S'adresser à l'auteur
                                    </label>
                                    <button type="button" class="button action remove-style-button"  >Supprimer</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button action" id="add-writing-style-button">Ajouter un template</button>
                    </td>
                </tr>
                <tr valign="top">
                    <th style="padding:0 !important;"scope="row" colspan="2"><hr /></th>
                </tr>
                <tr valign="top"><th scope="row" colspan="2" style="padding:0px !important;"><h2 style="margin:8px 0px !important;">Génération automatique</h2></th></tr>
                <tr valign="top">
                    <th scope="row">Activer la génération de commentaires automatiques</th>
                    <td><input type="checkbox" name="acg_auto_comment_enabled" value="1" <?php checked($auto_comment_enabled, 1); ?> />
                    <p>Activez ou désactivez la génération automatiques de commentaire</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Intervalle de publication des commentaires en minutes</th>
                    <td><input type="number" name="acg_cron_interval" value="<?php echo esc_attr($cron_interval); ?>" min="1" <?php disabled(!$auto_comment_enabled); ?> />
                        <p>Choisissez une intervale entre la publication des commentaires</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nombre de commentaires par article</th>
                    <td><input type="number" name="acg_comment_count" value="<?php echo esc_attr($comment_count); ?>" min="1" />
                    <p>Indiquez un nombre de commentaire par article</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>



<!-- pour gérer le répéteur -->
    <script>
        document.getElementById('add-writing-style-button').addEventListener('click', function() {
            var container = document.getElementById('writing-styles-container');
            var newStyle = document.createElement('div');
            newStyle.className = 'writing-style';
            newStyle.innerHTML = '<textarea name="acg_writing_styles[]" rows="4" cols="50"></textarea><label><input type="checkbox" name="acg_include_author_names[]" value="" /> Inclure le nom de l\'auteur</label><button type="button" class="remove-style-button">Supprimer</button>';
            container.appendChild(newStyle);


            newStyle.querySelector('.remove-style-button').addEventListener('click', function() {
                container.removeChild(newStyle);
            });
        });

        document.querySelectorAll('.remove-style-button').forEach(function(button) {
            button.addEventListener('click', function() {
                button.parentElement.remove();
            });
        });
    </script>
    <?php
}



function acg_register_settings() {
    register_setting('acg_options_group', 'acg_api_key');
    register_setting('acg_options_group', 'acg_writing_styles');
    register_setting('acg_options_group', 'acg_include_author_names');
    register_setting('acg_options_group', 'acg_min_words');
    register_setting('acg_options_group', 'acg_max_words');
    register_setting('acg_options_group', 'acg_cron_interval'); 
    register_setting('acg_options_group', 'acg_auto_comment_enabled'); 
    register_setting('acg_options_group', 'acg_gpt_model'); 
    register_setting('acg_options_group', 'acg_comment_count'); 
    register_setting('acg_options_group', 'citationprenomauteur'); 
}
add_action('admin_init', 'acg_register_settings');