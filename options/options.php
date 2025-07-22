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
    $comment_publish_mode = get_option('acg_comment_publish_mode', 'duration');
    $auto_comment_default = get_option('acg_auto_comment_default', 1);
    $delay_display = ($auto_comment_default && $comment_publish_mode === 'duration') ? '' : 'display:none;';
    $auto_comment_default_mode = get_option('acg_auto_comment_default_mode', 'all');
    $auto_comment_default_frequency = get_option('acg_auto_comment_default_frequency', 2); // par défaut toutes les 2 publications
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
                <tr valign="top">
                    <th scope="row" colspan="2" style="padding:0px !important;">
                        <h2 style="margin:8px 0px !important;">Réglages générales</h2>
                        <p style="font-weight:400;">Pour utiliser ce plugin, vous devez générer une clé API sur OpenAI et l'enregistrer sur cette page d'options avant de passer aux étapes suivantes.</p>
                    </th>
                </tr>
                <tr valign="top">
                    <th scope="row">Clé API OpenAI</th>
                    <td>
                        <input type="text" name="acg_api_key" value="<?php echo esc_attr($api_key); ?>" />
                        <p><a href="https://platform.openai.com/api-keys" target="_blank">Générer une clé OpenAI</a></p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Publier par :</th>
                    <td>
                        <select name="acg_comment_publish_mode" id="comment_publish_mode">
                            <option value="duration" <?php selected($comment_publish_mode, 'duration'); ?>>Publier par durée</option>
                            <option value="visits" <?php selected($comment_publish_mode, 'visits'); ?>>Publier par visites (IP)</option>
                        </select>
                        <p>Choisissez comment vous souhaitez publier des commentaires.</p>
                    </td>
                </tr>

                <tr valign="top" id="ip-comment-interval-row" style="<?php echo $comment_publish_mode === 'visits' ? '' : 'display:none;'; ?>">
                    <th scope="row">Publier X commentaires toutes les X IP</th>
                    <td>
                        <input type="number" name="acg_comment_per_ip" value="<?php echo esc_attr(get_option('acg_comment_per_ip', 1)); ?>" min="1" placeholder="Nombre de commentaires" />
                        <input type="number" name="acg_interval_per_ip" value="<?php echo esc_attr(get_option('acg_interval_per_ip', 1)); ?>" min="1" placeholder="Intervalle d'IP" />
                        <p>Exemple : Publier 5 commentaires toutes les 2 IP.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Modèle GPT</th>
                    <td>
                        <select name="acg_gpt_model">
                            <option value="gpt-4.1-mini" <?php selected($gpt_model, 'gpt-4.1-mini'); ?>>gpt-4.1-mini</option>
                            <option value="gpt-4.1" <?php selected($gpt_model, 'gpt-4.1'); ?>>gpt-4.1</option>
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
                        <p style="max-width: 590px;">Chaque modèle peut comprendre des informations sur l'auteur (nom/prénom) ainsi que des caractéristiques spécifiques qui définissent le ton et le style du commentaire. Grâce à ces modèles, vous pouvez créer des personas en plus d'éviter les redondances de l'IA.</p><br>
                        <b>Vous pouvez générer ces modèles en masse avec gpt-4o-mini :</b>
                        <div style="display: flex;flex-direction: column;align-items: flex-start;margin-bottom: 15px;">  
                            <p>Entrez le nombre de modèles à générer : 
                                <input type="number" id="template_count" min="1" value="1" style="width: 50px;" />
                            </p>
                            <div id="generated_templates"></div>
                            <button type="button" id="generate_templates_button" class="button action">Générer</button>
                        </div>
                        <hr>
                        <div id="writing-styles-container" style="gap: 10px; display: flex; flex-direction: column; margin-bottom: 10px;">
                            <style>
                                .writing-style{gap: 10px; display: flex; flex-direction: row; margin-bottom: 10px; flex-wrap: nowrap; align-content: center; align-items: center;}
                            </style>
                            <?php if (!empty($writing_styles)): ?>
                                <?php foreach ($writing_styles as $index => $style): ?>
                                    <div class="writing-style">
                                      <div style="display: flex; flex-direction: column; gap: 8px;">
                                          <span>Description des auteurs des commentaires (identité, style d'écriture..)</span>
                                          <textarea name="acg_writing_styles[<?php echo $index; ?>]" rows="4" cols="50"><?php echo esc_textarea($style); ?></textarea>
                                      </div>  
                                      <label>
                                           <input type="checkbox" name="acg_include_author_names[<?php echo $index; ?>]" value="1" <?php checked(isset($include_author_names[$index]) && $include_author_names[$index] == 1); ?> />
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
                <tr valign="top"><th scope="row" colspan="2"><h2 style="margin:8px 0px !important;">Commentaires automatiques</h2>
                    <p style="font-weight: 400; max-width: 640px;">
                        Vous pouvez créer des commentaires automatiquement à une fréquence donnée. Pour utiliser cette option, vous devez activer les cases à cocher "Commentaires automatiques" dans le tableau des publications sur la page listing des articles.
                    </p>
                </th></tr>

                <tr valign="top">
                    <th scope="row">Activer la génération de commentaires automatiques</th>                 
                    <td><input type="checkbox" name="acg_auto_comment_enabled" value="1" <?php checked($auto_comment_enabled, 1); ?> />  
                        <p>Cette option permet de générer automatiquement les commentaires sur les articles qui ont la case cochée "commentaire automatique".</p>
                    </td>
                </tr>

                <!-- Nouvelle option : désactiver par tranche horaire -->
                <tr valign="top">
                    <th scope="row">Désactiver les commentaires automatiques sur une plage horaire</th>
                    <td>
                        <input type="checkbox" id="acg_disable_auto_comment_hours" name="acg_disable_auto_comment_hours" value="1"
                            <?php checked(get_option('acg_disable_auto_comment_hours'), 1); ?> />
                        <label for="acg_disable_auto_comment_hours">Activer la restriction horaire</label>
                        <div id="acg_hour_range_fields" style="<?php echo get_option('acg_disable_auto_comment_hours') ? '' : 'display:none;'; ?> margin-top:10px;">
                            <label for="acg_disable_auto_comment_start_hour" style="margin-right:10px;">Heure de début:</label>
                            <input type="time" name="acg_disable_auto_comment_start_hour" id="acg_disable_auto_comment_start_hour"
                                value="<?php echo esc_attr(get_option('acg_disable_auto_comment_start_hour', '03:00')); ?>" min="00:00" max="23:59"
                            />
                            <label for="acg_disable_auto_comment_end_hour" style="margin-left:20px;margin-right:10px;">Heure de fin:</label>
                            <input type="time" name="acg_disable_auto_comment_end_hour" id="acg_disable_auto_comment_end_hour"
                                value="<?php echo esc_attr(get_option('acg_disable_auto_comment_end_hour', '07:00')); ?>" min="00:00" max="23:59"
                            />
                        </div>
                        <p>Les commentaires automatiques NE seront PAS publiés dans cette tranche horaire.<br>
                        Exemple : 3h00–7h00 = Pas de commentaires générés par l’IA entre 3h et 7h du matin.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Activer les commentaires automatiques pour les nouvelles publications</th>
                    <td>
                        <input type="checkbox" id="acg_auto_comment_default" name="acg_auto_comment_default" value="1" <?php checked($auto_comment_default, 1); ?> />
                        <p>Cette option permet de cocher la case "commentaire automatique" par défaut sur les nouvelles publications.</p>
                        
                        <div id="auto-comment-default-mode-container" style="<?php echo ($auto_comment_default ? '' : 'display:none;'); ?> margin-top:10px;">
    <label for="acg_auto_comment_default_mode"><b>Mode</b> :</label>
    <select name="acg_auto_comment_default_mode" id="acg_auto_comment_default_mode">
        <option value="all" <?php selected($auto_comment_default_mode, 'all'); ?>>Activer la case sur toutes les publications</option>
        <option value="frequency" <?php selected($auto_comment_default_mode, 'frequency'); ?>>Activer la case toutes les X publications</option>
        <option value="random" <?php selected($auto_comment_default_mode, 'random'); ?>>Activer la case aléatoirement (50% de chance)</option>
    </select>
    <span id="auto_comment_default_frequency_container" style="<?php echo ($auto_comment_default_mode === 'frequency') ? '' : 'display:none;'; ?>">
        <input type="number" name="acg_auto_comment_default_frequency" id="acg_auto_comment_default_frequency"
            value="<?php echo esc_attr($auto_comment_default_frequency); ?>" style="width:70px;" min="1" /> publications
    </span>
</div>
                        
                        
                        <div id="auto-comment-delay-container" style="<?php echo $delay_display; ?>">
                            <label for="acg_auto_comment_delay">Délai (minutes) avant la publication des commentaires :</label>
                            <input type="number" name="acg_auto_comment_delay" value="<?php echo esc_attr(get_option('acg_auto_comment_delay', 30)); ?>" min="0" />
                            <p>Temps d'attente avant la première publication de commentaires après la publication d'un nouvel article.</p>
                        </div>
                    </td>
                </tr>
                
                <tr valign="top" id="cron-settings-row" style="<?php echo $comment_publish_mode === 'visits' ? 'display: none;' : ''; ?>">
                    <th scope="row">Planifier les commentaires</th>
                    <td>
                        Publier entre <input style="width:50px;" type="number" name="acg_comment_min_per_post" value="<?php echo esc_attr(get_option('acg_comment_min_per_post', 1)); ?>" min="1" /> et <input style="width:50px;" type="number" name="acg_comment_max_per_post" value="<?php echo esc_attr(get_option('acg_comment_max_per_post', 5)); ?>" min="1" /> commentaires toutes les <input style="width:50px;" type="number" name="acg_cron_interval" value="<?php echo esc_attr($cron_interval); ?>" min="1" /> minutes par publication.
                    </td>
                </tr>               
                <tr valign="top" id="max-comments-row" style="<?php echo $comment_publish_mode === 'visits' ? 'display: none;' : ''; ?>">
                    <th scope="row">Nombre maximum de commentaires par publication</th>
                    <td>
                        Ne jamais dépasser entre <input style="width:50px;" type="number" name="acg_comment_max_per_post_value_min" value="<?php echo esc_attr(get_option('acg_comment_max_per_post_value_min', 1)); ?>" min="1" /> et <input style="width:50px;" type="number" name="acg_comment_max_per_post_value_max" value="<?php echo esc_attr(get_option('acg_comment_max_per_post_value_max', 5)); ?>" min="1" /> commentaires par publication.
                        <p>Cette option générera un nombre aléatoire dès la première publication de commentaire automatique.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

<script>
(function(){
    // Gestion dynamique des options visibles/masquées
    function updateOptionsVisibility() {
        var mode = document.getElementById('comment_publish_mode').value;
        var autoCommentDefault = document.getElementById('acg_auto_comment_default').checked;
        document.getElementById('ip-comment-interval-row').style.display    = (mode === 'visits')   ? '' : 'none';
        document.getElementById('cron-settings-row').style.display  = (mode === 'visits')   ? 'none' : '';
        document.getElementById('max-comments-row').style.display   = (mode === 'visits')   ? 'none' : '';
        document.getElementById('auto-comment-delay-container').style.display = (mode === 'duration' && autoCommentDefault) ? '' : 'none';
    }
    document.getElementById('comment_publish_mode').addEventListener('change', updateOptionsVisibility);
    document.getElementById('acg_auto_comment_default').addEventListener('change', updateOptionsVisibility);
    document.addEventListener('DOMContentLoaded', updateOptionsVisibility);

    // Plage horaire activation
    document.getElementById('acg_disable_auto_comment_hours').addEventListener('change', function() {
        document.getElementById('acg_hour_range_fields').style.display = this.checked ? '' : 'none';
    });

    // Gestion modèles de commentaires (add/supprimer/génération)
    function bindRemoveButtons() {
        document.querySelectorAll('.remove-style-button').forEach(function(button) {
            button.onclick = function() {
                button.closest('.writing-style').remove();
            }
        });
    }
    bindRemoveButtons();
    // Ajouter nouveau modèle
    document.getElementById('add-writing-style-button').addEventListener('click', function () {
        var container = document.getElementById('writing-styles-container');
        var nextIndex = container.querySelectorAll('.writing-style').length;
        var div = document.createElement('div');
        div.className = 'writing-style';
        div.innerHTML = `
          <div style="display: flex; flex-direction: column; gap: 8px;">
            <span>Description des auteurs des commentaires (identité, style d'écriture..)</span>
            <textarea name="acg_writing_styles[`+nextIndex+`]" rows="4" cols="50"></textarea>
          </div>
          <label>
            <input type="checkbox" name="acg_include_author_names[`+nextIndex+`]" value="1" />
            S'adresse directement à l'auteur de l'article
          </label>
          <button type="button" class="button action remove-style-button">Supprimer</button>
        `;
        container.appendChild(div);
        bindRemoveButtons();
    });
    // Génération IA (ajax, si activée côté serveur)
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
            if (index >= count) return;
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
                        var writingStylesContainer = document.getElementById('writing-styles-container');
                        var nextIndex = writingStylesContainer.querySelectorAll('.writing-style').length;
                        var div = document.createElement('div');
                        div.className = 'writing-style';
                        div.innerHTML = `
                          <div style="display: flex; flex-direction: column; gap: 8px;">
                            <span>Description des auteurs des commentaires (identité, style d'écriture..)</span>
                            <textarea name="acg_writing_styles[`+nextIndex+`]" rows="4" cols="50"></textarea>
                          </div>
                          <label>
                            <input type="checkbox" name="acg_include_author_names[`+nextIndex+`]" value="1" />
                            S'adresse directement à l'auteur de l'article
                          </label>
                          <button type="button" class="button action remove-style-button">Supprimer</button>
                        `;
                        writingStylesContainer.appendChild(div);
                        bindRemoveButtons();
                        div.querySelector('textarea').value = template;
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
})();
    
    
    
        function updateDefaultModeVisibility() {
        var defaultChecked = document.getElementById('acg_auto_comment_default').checked;
        document.getElementById('auto-comment-default-mode-container').style.display = defaultChecked ? '' : 'none';
        var mode = document.getElementById('acg_auto_comment_default_mode').value;
        document.getElementById('auto_comment_default_frequency_container').style.display = (mode === 'frequency') ? '' : 'none';
    }
    document.getElementById('acg_auto_comment_default_mode').addEventListener('change', updateDefaultModeVisibility);
    document.getElementById('acg_auto_comment_default').addEventListener('change', updateDefaultModeVisibility);
    document.addEventListener('DOMContentLoaded', updateDefaultModeVisibility);
    
    
</script>

<?php
}

function acg_set_auto_comment_default($post_id, $post, $update) {
    // Eviter les autosaves/révisions
    if ($update) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $all_types = get_post_types(['public' => true, 'show_ui' => true]);
    if (!in_array(get_post_type($post_id), $all_types)) return;

    // Ne s'applique QUE à la création
    $auto_comment_default = get_option('acg_auto_comment_default', 1);
    $mode = get_option('acg_auto_comment_default_mode', 'all');
    $frequency = max(intval(get_option('acg_auto_comment_default_frequency', 2)), 1);

    if (!$auto_comment_default) {
        update_post_meta($post_id, '_acg_auto_comment_enabled', '0');
        return;
    }

    $enabled = '0';
    switch ($mode) {
        case 'all':
            $enabled = '1';
            break;
        case 'frequency':
            // On stocke le nombre d'articles créés
            $counter = intval(get_option('acg_auto_comment_post_counter', 0)) + 1;
            update_option('acg_auto_comment_post_counter', $counter);
            // Seule la publication courante est impactée
            $enabled = ($frequency && ($counter % $frequency) === 0) ? '1' : '0';
            break;
        case 'random':
            $enabled = (mt_rand(0, 1) === 1) ? '1' : '0';
            break;
        default:
            $enabled = '1';
    }
    update_post_meta($post_id, '_acg_auto_comment_enabled', $enabled);
}
add_action('wp_insert_post', 'acg_set_auto_comment_default', 10, 3);

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
    register_setting('acg_options_group', 'acg_comment_min_per_post'); 
    register_setting('acg_options_group', 'acg_comment_max_per_post'); 
    register_setting('acg_options_group', 'acg_comment_max_per_post_value_min'); 
    register_setting('acg_options_group', 'acg_comment_max_per_post_value_max');    
    register_setting('acg_options_group', 'acg_auto_comment_default'); 
    register_setting('acg_options_group', 'acg_comment_publish_mode'); 
    register_setting('acg_options_group', 'acg_comment_per_ip'); 
    register_setting('acg_options_group', 'acg_interval_per_ip');
    register_setting('acg_options_group', 'acg_disable_auto_comment_hours');
    register_setting('acg_options_group', 'acg_disable_auto_comment_start_hour');
    register_setting('acg_options_group', 'acg_disable_auto_comment_end_hour');
    register_setting('acg_options_group', 'acg_auto_comment_delay');
    register_setting('acg_options_group', 'acg_auto_comment_default_mode');
    register_setting('acg_options_group', 'acg_auto_comment_default_frequency');
}
add_action('admin_init', 'acg_register_settings');