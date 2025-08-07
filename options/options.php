<?php

if (!defined('ABSPATH')) {
    exit;
}

function acg_add_admin_menu() {
    add_options_page('WP Auto Comment', 'WP Auto Comment', 'manage_options', 'wp-auto-comment', 'acg_options_page');
}
add_action('admin_menu', 'acg_add_admin_menu');

function acg_options_page() {
    $comment_publish_mode = get_option('acg_comment_publish_mode', 'duration');
    $auto_comment_default = get_option('acg_auto_comment_default', 1);
    $auto_comment_default_mode = get_option('acg_auto_comment_default_mode', 'all');
    $auto_comment_default_frequency = get_option('acg_auto_comment_default_frequency', 2);
    $auto_comment_default_random_percent = get_option('acg_auto_comment_default_random_percent', 50);

    $api_key = get_option('acg_api_key', '');
    $min_words = get_option('acg_min_words', 5);
    $max_words = get_option('acg_max_words', 20);
    $cron_interval = get_option('acg_cron_interval', 5);
    $auto_comment_enabled = get_option('acg_auto_comment_enabled', 1);
    $gpt_model = get_option('acg_gpt_model', 'gpt-4o-mini');
    $writing_styles = (array) get_option('acg_writing_styles', []);
    $include_author_names = (array) get_option('acg_include_author_names', []);
    $allowed_post_types = (array) get_option('acg_allowed_post_types', ['post']);
    $no_duplicate_persona_per_post = get_option('acg_no_duplicate_persona_per_post', 0);
    $persona_preference_enabled = get_option('acg_persona_preference_enabled', 0);

    $disable_auto_comment_hours = get_option('acg_disable_auto_comment_hours', 0);
    $enable_max_comments_per_post = get_option('acg_enable_max_comments_per_post', 0);
    $comment_max_per_post_value_min = get_option('acg_comment_max_per_post_value_min', 1);
    $comment_max_per_post_value_max = get_option('acg_comment_max_per_post_value_max', 5);

    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

    $delay_display = ($auto_comment_default && $comment_publish_mode === 'duration') ? '' : 'display:none;';
    ?>
    <div class="wrap">
        <h1>WP Auto Comment</h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=wp-auto-comment&tab=general" class="nav-tab <?php echo $current_tab == 'general' ? 'nav-tab-active' : ''; ?>" data-tab="general">Réglages générales</a>
            <a href="?page=wp-auto-comment&tab=templates" class="nav-tab <?php echo $current_tab == 'templates' ? 'nav-tab-active' : ''; ?>" data-tab="templates">Modèles de commentaires</a>
            <a href="?page=wp-auto-comment&tab=auto-comments" class="nav-tab <?php echo $current_tab == 'auto-comments' ? 'nav-tab-active' : ''; ?>" data-tab="auto-comments">Commentaires automatiques</a>
            <a href="?page=wp-auto-comment&tab=restrictions" class="nav-tab <?php echo $current_tab == 'restrictions' ? 'nav-tab-active' : ''; ?>" data-tab="restrictions">Restrictions</a>
            <a href="?page=wp-auto-comment&tab=don" class="nav-tab <?php echo $current_tab == 'don' ? 'nav-tab-active' : ''; ?>" data-tab="don">Don ❤️</a>
        </h2>

        <div id="ajax-message" class="notice" style="display:none;"></div>

        <form id="acg-options-form" method="post" action="options.php">
            <?php
            settings_fields('acg_options_group');
            do_settings_sections('acg_options_group');
            ?>

            <div id="tab-content-general" class="tab-content" style="<?php echo $current_tab == 'general' ? '' : 'display:none;'; ?>">
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

                    <tr valign="top">
                        <th scope="row">Modèle GPT</th>
                        <td>
                            <select name="acg_gpt_model">
                                <option value="gpt-5-nano" <?php selected($gpt_model, 'gpt-5-nano'); ?>>gpt-5-nano</option>
                                <option value="gpt-5-mini" <?php selected($gpt_model, 'gpt-5-mini'); ?>>gpt-5-mini</option>
                                <option value="gpt-5" <?php selected($gpt_model, 'gpt-5'); ?>>gpt-5</option>
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
                </table>
            </div>

            <div id="tab-content-templates" class="tab-content" style="<?php echo $current_tab == 'templates' ? '' : 'display:none;'; ?>">
                <table class="form-table">
                    <tr valign="top">
                        <td style="padding:0px !important;" colspan="2">
                            <h2 style="margin:8px 0px !important;">Modèles de commentaires</h2>
                            <p style="max-width: 590px;">
                                Chaque modèle peut comprendre des informations sur l'auteur (nom/prénom) ainsi que des caractéristiques spécifiques qui définissent le ton et le style du commentaire. Grâce à ces modèles, vous pouvez créer des personas en plus d'éviter les redondances de l'IA.
                            </p>
                            <br>
                            <b>Vous pouvez générer ces modèles en masse avec gpt-4o-mini :</b>
                            <div style="display: flex;flex-direction: column;align-items: flex-start;margin-bottom: 15px;">
                                <p>
                                    Entrez le nombre de modèles à générer :
                                    <input type="number" id="template_count" min="1" value="1" style="width: 50px;" />
                                </p>
                                <div id="generated_templates"></div>
                                <button type="button" id="generate_templates_button" class="button action">Générer</button>
                            </div>
                            <hr>

                            <label style="font-weight: bold; display: block; margin-bottom: 7px;">
                                <input type="checkbox" id="acg_persona_preference_enabled" name="acg_persona_preference_enabled" value="1" <?php checked(get_option('acg_persona_preference_enabled', 0), 1); ?> />
                                Activer la préférence des personas (modifiez leur ordre par glisser-déposer)
                            </label>

                            <label style="font-weight: bold; display: block; margin-bottom: 7px;">
                                <input type="checkbox" name="acg_no_duplicate_persona_per_post" value="1" <?php checked(get_option('acg_no_duplicate_persona_per_post', 0), 1); ?> />
                                Ne pas utiliser deux fois le même persona sur une publication
                            </label>

                            <div id="writing-styles-container" style="gap: 10px; display: flex; flex-direction: column; margin-bottom: 10px;">
                                <style>
                                    .writing-style {
                                        gap: 10px; display: flex; flex-direction: row; margin-bottom: 10px;
                                        flex-wrap: nowrap; align-content: center; align-items: center;
                                        background: #f8f8f8; padding: 8px; border-radius: 5px;
                                        border: 1px solid #ddd; cursor: move;
                                    }
                                    .drag-handle {
                                        font-size: 18px;
                                        cursor: grab;
                                        margin-right: 10px;
                                        color: #888;
                                        user-select:none;
                                    }
                                    .writing-style.sortable-chosen {
                                        background-color: #e3eefb;
                                        border-color: #53a7ea;
                                    }
                                    .writing-style.sortable-ghost {
                                        opacity: 0.6;
                                    }
                                </style>
                                <?php if (!empty($writing_styles)): ?>
                                    <?php foreach ($writing_styles as $index => $style): ?>
                                        <div class="writing-style" data-index="<?php echo $index; ?>">
                                            <span class="dashicons dashicons-move drag-handle" title="Déplacer" aria-label="Déplacer">&#9776;</span>
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
                </table>
            </div>

            <div id="tab-content-auto-comments" class="tab-content" style="<?php echo $current_tab == 'auto-comments' ? '' : 'display:none;'; ?>">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row" colspan="2"><h2 style="margin:8px 0px !important;">Commentaires automatiques</h2>
                            <p style="font-weight: 400; max-width: 640px;">
                                Vous pouvez créer des commentaires automatiquement à une fréquence donnée. Pour utiliser cette option, vous devez activer les cases à cocher "Commentaires automatiques" dans le tableau des publications sur la page listing des articles.
                            </p>
                        </th>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Activer la génération de commentaires automatiques</th>
                        <td><input type="checkbox" name="acg_auto_comment_enabled" value="1" <?php checked($auto_comment_enabled, 1); ?> />
                            <p>Cette option permet de générer automatiquement les commentaires sur les articles qui ont la case cochée "commentaire automatique".</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Activer les commentaires automatiques pour les nouvelles publications</th>
                        <td>
                            <input type="checkbox" id="acg_auto_comment_default" name="acg_auto_comment_default" value="1" <?php checked($auto_comment_default, 1); ?> />
                            <p>Cette option permet de cocher la case "commentaire automatique" par défaut sur les nouvelles publications.</p>

                            <div id="auto-comment-default-mode-container" style="<?php echo ($auto_comment_default ? '' : 'display:none;'); ?> margin-top:10px;">
                                <label for="acg_auto_comment_default_mode"><b>Mode</b> :</label>
                                <select name="acg_auto_comment_default_mode" id="acg_auto_comment_default_mode">
                                    <option value="all" <?php selected($auto_comment_default_mode, 'all'); ?>>Activer la case sur toutes les publications</option>
                                    <option value="frequency" <?php selected($auto_comment_default_mode, 'frequency'); ?>>Activer la case toutes les X publications</option>
                                    <option value="random" <?php selected($auto_comment_default_mode, 'random'); ?>>Activer la case aléatoirement (pourcentage personnalisable)</option>
                                </select>
                                <span id="auto_comment_default_frequency_container" style="<?php echo ($auto_comment_default_mode === 'frequency') ? '' : 'display:none;'; ?>">
                                    <input type="number" name="acg_auto_comment_default_frequency" id="acg_auto_comment_default_frequency"
                                        value="<?php echo esc_attr($auto_comment_default_frequency); ?>" style="width:70px;" min="1" /> publications
                                </span>
                                <span id="auto_comment_default_random_percent_container" style="<?php echo ($auto_comment_default_mode === 'random') ? '' : 'display:none;'; ?>">
                                    <input type="number" name="acg_auto_comment_default_random_percent" id="acg_auto_comment_default_random_percent"
                                        value="<?php echo esc_attr($auto_comment_default_random_percent); ?>" style="width:70px;" min="1" max="100" /> % de chance
                                </span>
                            </div>

                            <div id="auto-comment-delay-container" style="<?php echo $delay_display; ?>">
                                <label for="acg_auto_comment_delay">Délai (minutes) avant la publication des commentaires :</label>
                                <input type="number" name="acg_auto_comment_delay" value="<?php echo esc_attr(get_option('acg_auto_comment_delay', 30)); ?>" min="0" />
                                <p>Temps d'attente avant la première publication de commentaires après la publication d'un nouvel article.</p>
                            </div>
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

                    <tr valign="top" id="cron-settings-row" style="<?php echo $comment_publish_mode === 'visits' ? 'display: none;' : ''; ?>">
                        <th scope="row">Planifier les commentaires</th>
                        <td>
                            Publier entre <input style="width:50px;" type="number" name="acg_comment_min_per_post" value="<?php echo esc_attr(get_option('acg_comment_min_per_post', 1)); ?>" min="1" /> et <input style="width:50px;" type="number" name="acg_comment_max_per_post" value="<?php echo esc_attr(get_option('acg_comment_max_per_post', 5)); ?>" min="1" /> commentaires toutes les <input style="width:50px;" type="number" name="acg_cron_interval" value="<?php echo esc_attr($cron_interval); ?>" min="1" /> minutes par publication.
                        </td>
                    </tr>
                </table>
            </div>

            <div id="tab-content-restrictions" class="tab-content" style="<?php echo $current_tab == 'restrictions' ? '' : 'display:none;'; ?>">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row" colspan="2" style="padding:0px !important;">
                            <h2 style="margin:8px 0px !important;">Restrictions</h2>
                            <p style="font-weight:400;">Configurez les restrictions pour la génération et la publication des commentaires automatiques.</p>
                        </th>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Désactiver les commentaires automatiques sur une plage horaire</th>
                        <td>
                            <input type="checkbox" id="acg_disable_auto_comment_hours" name="acg_disable_auto_comment_hours" value="1"
                                <?php checked($disable_auto_comment_hours, 1); ?> />
                            <label for="acg_disable_auto_comment_hours">Activer la restriction horaire</label>
                            <div id="acg_hour_range_fields" style="<?php echo $disable_auto_comment_hours ? '' : 'display:none;'; ?> margin-top:10px;">
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
                            Exemple : 3h00–7h00 = Pas de commentaires générés par l’IA entre 3h et 7h du matin.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Activer les commentaires automatiques pour les types de contenu suivants :</th>
                        <td>
                            <?php
                            $post_types = get_post_types(['public' => true], 'objects');
                            foreach ($post_types as $post_type) {
                                if (in_array($post_type->name, ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset'])) {
                                    continue;
                                }
                                $is_checked = in_array($post_type->name, $allowed_post_types);
                                echo '<label style="display: block; margin-bottom: 5px;">';
                                echo '<input type="checkbox" name="acg_allowed_post_types[]" value="' . esc_attr($post_type->name) . '" ' . checked($is_checked, true, false) . ' /> ';
                                echo esc_html($post_type->labels->singular_name . ' (' . $post_type->name . ')');
                                echo '</label>';
                            }
                            ?>
                            <p>Cochez les types de contenu sur lesquels les commentaires automatiques pourront être activés (via la case à cocher sur la page de modification de l'article).</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Nombre maximum de commentaires par publication</th>
                        <td>
                            <input type="checkbox" id="acg_enable_max_comments_per_post" name="acg_enable_max_comments_per_post" value="1"
                                <?php checked($enable_max_comments_per_post, 1); ?> />
                            <label for="acg_enable_max_comments_per_post">Activer la limite de commentaires par publication</label>
                            <div id="acg_max_comments_per_post_fields" style="<?php echo $enable_max_comments_per_post ? '' : 'display:none;'; ?> margin-top:10px;">
                                Ne jamais dépasser entre <input style="width:50px;" type="number" name="acg_comment_max_per_post_value_min" value="<?php echo esc_attr($comment_max_per_post_value_min); ?>" min="1" /> et <input style="width:50px;" type="number" name="acg_comment_max_per_post_value_max" value="<?php echo esc_attr($comment_max_per_post_value_max); ?>" min="1" /> commentaires par publication.
                                <p>Cette option générera un nombre aléatoire dès la première publication de commentaire automatique.</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="tab-content-don" class="tab-content" style="<?php echo $current_tab == 'don' ? '' : 'display:none;'; ?>">
                <h2 style="margin:16px 0;">Soutenez ce plugin 🙏</h2>
                <p style="font-size: 1.18em; margin-bottom:20px; max-width:600px;">
                    Si ce plugin vous a aidé ou plu, vous pouvez me soutenir via un don Stripe.<br>
                    Merci beaucoup pour votre geste 💙
                </p>
                <script async
                  src="https://js.stripe.com/v3/buy-button.js">
                </script>
                <stripe-buy-button
                  buy-button-id="buy_btn_1RlhVzIGhPRsDj2KyfnwoUAR"
                  publishable-key="pk_live_VuvFnnYJgYxQFD26h0JGZRdb00USkLyDaH"
                >
                </stripe-buy-button>
            </div>

            <?php submit_button('Sauvegarder les modifications'); ?>
        </form>
    </div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function($){
    function updateOptionsVisibility() {
        var mode = $('#comment_publish_mode').val();
        var autoCommentDefault = $('#acg_auto_comment_default').prop('checked');
        $('#ip-comment-interval-row').toggle(mode === 'visits');
        $('#cron-settings-row').toggle(mode !== 'visits');
        $('#auto-comment-delay-container').toggle(mode === 'duration' && autoCommentDefault);
    }
    $('#acg_disable_auto_comment_hours').on('change', function() {
        $('#acg_hour_range_fields').toggle($(this).prop('checked'));
    });
    $('#acg_enable_max_comments_per_post').on('change', function() {
        $('#acg_max_comments_per_post_fields').toggle($(this).prop('checked'));
    });
    function bindRemoveButtons() {
        $('.remove-style-button').off('click').on('click', function() {
            $(this).closest('.writing-style').remove();
        });
    }
    bindRemoveButtons();

    $('#add-writing-style-button').on('click', function () {
        var container = $('#writing-styles-container');
        var nextIndex = container.find('.writing-style').length;
        var div = `
            <div class="writing-style" data-index="${nextIndex}">
                <span class="dashicons dashicons-move drag-handle" title="Déplacer" aria-label="Déplacer">&#9776;</span>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <span>Description des auteurs des commentaires (identité, style d'écriture..)</span>
                    <textarea name="acg_writing_styles[${nextIndex}]" rows="4" cols="50"></textarea>
                </div>
                <label>
                    <input type="checkbox" name="acg_include_author_names[${nextIndex}]" value="1" />
                    S'adresse directement à l'auteur de l'article
                </label>
                <button type="button" class="button action remove-style-button">Supprimer</button>
            </div>
        `;
        container.append(div);
        bindRemoveButtons();
    });

    var writingStylesContainer = document.getElementById('writing-styles-container');
    if(window.Sortable && writingStylesContainer) {
        var sortable = Sortable.create(writingStylesContainer, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function (evt) {
                $('#writing-styles-container .writing-style').each(function(i){
                    $(this).attr('data-index', i);
                    $(this).find('textarea').attr('name', 'acg_writing_styles['+i+']');
                    $(this).find('input[type="checkbox"]').attr('name', 'acg_include_author_names['+i+']');
                });
            }
        });
    }

    $('#generate_templates_button').on('click', function() {
        var count = parseInt($('#template_count').val());
        if (isNaN(count) || count < 1) {
            alert("Veuillez entrer un nombre valide.");
            return;
        }
        var generatedTemplatesContainer = $('#generated_templates');
        generatedTemplatesContainer.html("");
        var index = 0;
        function generateTemplate() {
            if (index >= count) return;
            var loadingMessage = $('<p>').text("Génération du template " + (index + 1) + " en cours...");
            generatedTemplatesContainer.append(loadingMessage);

            $.ajax({
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
                        var writingStylesContainer = $('#writing-styles-container');
                        var nextIndex = writingStylesContainer.find('.writing-style').length;
                        var div = `
                            <div class="writing-style" data-index="${nextIndex}">
                                <span class="dashicons dashicons-move drag-handle" title="Déplacer" aria-label="Déplacer">&#9776;</span>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <span>Description des auteurs des commentaires (identité, style d'écriture..)</span>
                                    <textarea name="acg_writing_styles[${nextIndex}]" rows="4" cols="50"></textarea>
                                </div>
                                <label>
                                    <input type="checkbox" name="acg_include_author_names[${nextIndex}]" value="1" />
                                    S'adresse directement à l'auteur de l'article
                                </label>
                                <button type="button" class="button action remove-style-button">Supprimer</button>
                            </div>
                        `;
                        writingStylesContainer.append(div);
                        bindRemoveButtons();
                        writingStylesContainer.find('textarea').last().val(template);
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

    function updateDefaultModeVisibility() {
        var defaultChecked = $('#acg_auto_comment_default').prop('checked');
        $('#auto-comment-default-mode-container').toggle(defaultChecked);
        var mode = $('#acg_auto_comment_default_mode').val();
        $('#auto_comment_default_frequency_container').toggle(mode === 'frequency');
        $('#auto_comment_default_random_percent_container').toggle(mode === 'random');
    }

    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var tab_id = $(this).data('tab');
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab_id);

        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').hide();

        $(this).addClass('nav-tab-active');
        $('#tab-content-' + tab_id).show();
        history.pushState(null, null, url.toString());
    });

    $('#acg-options-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        var submitButton = form.find('input[type="submit"]');
        var ajaxMessage = $('#ajax-message');

        submitButton.prop('disabled', true).val('Sauvegarde en cours...');
        ajaxMessage.hide().removeClass('notice-success notice-error');

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            success: function(response) {
                if (response.includes('settings-updated')) {
                    ajaxMessage.addClass('notice-success').html('<p><strong>Paramètres sauvegardés.</strong></p>').show();
                } else {
                    ajaxMessage.addClass('notice-error').html('<p><strong>Erreur lors de la sauvegarde :</strong> La réponse du serveur est inattendue. Veuillez vérifier votre console pour plus de détails.</p>').show();
                    console.log('Server response:', response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                ajaxMessage.addClass('notice-error').html('<p><strong>Erreur lors de la sauvegarde :</strong> ' + textStatus + ' - ' + errorThrown + '</p>').show();
                console.error('AJAX Error:', textStatus, errorThrown, jqXHR);
            },
            complete: function() {
                submitButton.prop('disabled', false).val('Sauvegarder les modifications');
                setTimeout(function() {
                    ajaxMessage.fadeOut('slow');
                }, 3000);
            }
        });
    });

    $(document).ready(function() {
        updateOptionsVisibility();
        updateDefaultModeVisibility();
        $('#acg_hour_range_fields').toggle($('#acg_disable_auto_comment_hours').prop('checked'));
        $('#acg_max_comments_per_post_fields').toggle($('#acg_enable_max_comments_per_post').prop('checked'));
        $('#comment_publish_mode').on('change', updateOptionsVisibility);
        $('#acg_auto_comment_default').on('change', updateOptionsVisibility);
        $('#acg_auto_comment_default_mode').on('change', updateDefaultModeVisibility);
    });

})(jQuery);
</script>
<?php
}

function acg_set_auto_comment_default($post_id, $post, $update) {
    if ($update) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $allowed_post_types = (array) get_option('acg_allowed_post_types', ['post']);
    if (!in_array(get_post_type($post_id), $allowed_post_types)) {
        update_post_meta($post_id, '_acg_auto_comment_enabled', '0');
        return;
    }

    $auto_comment_default = get_option('acg_auto_comment_default', 1);
    $mode = get_option('acg_auto_comment_default_mode', 'all');
    $frequency = max(intval(get_option('acg_auto_comment_default_frequency', 2)), 1);
    $random_percent = intval(get_option('acg_auto_comment_default_random_percent', 50));

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
            $counter = intval(get_option('acg_auto_comment_post_counter', 0)) + 1;
            update_option('acg_auto_comment_post_counter', $counter);
            $enabled = ($frequency && ($counter % $frequency) === 0) ? '1' : '0';
            break;
        case 'random':
            $rand = mt_rand(1, 100);
            $enabled = ($rand <= $random_percent) ? '1' : '0';
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
    register_setting('acg_options_group', 'acg_auto_comment_default_random_percent');
    register_setting('acg_options_group', 'acg_allowed_post_types');
    register_setting('acg_options_group', 'acg_enable_max_comments_per_post');
    register_setting('acg_options_group', 'acg_comment_max_per_post_value_min');
    register_setting('acg_options_group', 'acg_comment_max_per_post_value_max');
    register_setting('acg_options_group', 'acg_no_duplicate_persona_per_post');
    register_setting('acg_options_group', 'acg_persona_preference_enabled');
}
add_action('admin_init', 'acg_register_settings');