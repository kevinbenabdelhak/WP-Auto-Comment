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
    // JS pour le champ du délai
    function updateDelayContainer() {
        var mode = document.getElementById('comment_publish_mode').value;
        var autoCommentDefault = document.getElementById('acg_auto_comment_default').checked;
        var delayContainer = document.getElementById('auto-comment-delay-container');
        if(mode !== 'duration') {
            delayContainer.style.display = 'none';
        } else {
            delayContainer.style.display = autoCommentDefault ? '' : 'none';
        }
    }

    document.getElementById('comment_publish_mode').addEventListener('change', updateDelayContainer);
    document.getElementById('acg_auto_comment_default').addEventListener('change', updateDelayContainer);
    document.addEventListener('DOMContentLoaded', updateDelayContainer);
    // ...le reste de vos JS existants...
</script>


<script>
function updateOptionsVisibility() {
    var mode = document.getElementById('comment_publish_mode').value;
    var autoCommentDefault = document.getElementById('acg_auto_comment_default').checked;
    // Les champs concernés
    var ipIntervalRow     = document.getElementById('ip-comment-interval-row');
    var cronSettingsRow   = document.getElementById('cron-settings-row');
    var maxCommentsRow    = document.getElementById('max-comments-row');
    var delayContainer    = document.getElementById('auto-comment-delay-container');
    // Bloc IP
    ipIntervalRow.style.display    = (mode === 'visits')   ? '' : 'none';
    // Bloc durée
    cronSettingsRow.style.display  = (mode === 'visits')   ? 'none' : '';
    maxCommentsRow.style.display   = (mode === 'visits')   ? 'none' : '';
    delayContainer.style.display   = (mode === 'duration' && autoCommentDefault) ? '' : 'none';
}
// Pour la sélection et la case à cocher
document.getElementById('comment_publish_mode').addEventListener('change', updateOptionsVisibility);
document.getElementById('acg_auto_comment_default').addEventListener('change', updateOptionsVisibility);
// À l'ouverture de la page
document.addEventListener('DOMContentLoaded', updateOptionsVisibility);
</script>


<?php
}

function acg_set_auto_comment_default($post_id) {
    $all_types = get_post_types(['public' => true, 'show_ui' => true]);
    if (in_array(get_post_type($post_id), $all_types)) {
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
}

add_action('admin_init', 'acg_register_settings');





