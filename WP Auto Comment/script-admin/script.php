<?php 

if (!defined('ABSPATH')) {
    exit; 
}

function acg_enqueue_scripts($hook) {
    if ($hook !== 'edit.php') {
        return;
    }

    wp_enqueue_script('jquery');

    wp_add_inline_script('jquery', '
    jQuery(document).ready(function($) {
        if ($("select[name=\'action\'] option[value=\'generate_auto_comment\']").length === 0) {
            $("select[name=\'action\'], select[name=\'action2\']").append(\'<option value="generate_auto_comment">Générer des commentaires</option>\');
        }

        var commentCountDiv = \'<div class="alignleft actions" id="comment-count-input" style="display: none;">Nombre de commentaires: <input type="number" id="comment_count" value="1" min="1" style="width: 100px; margin-left: 10px;" /></div>\';
        $(".tablenav.top").after(commentCountDiv);

        $("select[name=\'action\'], select[name=\'action2\']").on("change", function() {
            var selectedAction = $(this).val();
            if (selectedAction === "generate_auto_comment") {
                $("#comment-count-input").show();
            } else {
                $("#comment-count-input").hide();
            }
        });

        $(document).on("click", "#doaction, #doaction2", function(e) {
            var action = $("select[name=\'action\']").val() !== "-1" ? $("select[name=\'action\']").val() : $("select[name=\'action2\']").val();

            if (action !== "generate_auto_comment") return;

            e.preventDefault();

            var post_ids = [];
            $("tbody th.check-column input[type=\'checkbox\']:checked").each(function() {
                post_ids.push($(this).val());
            });

            var comment_count = parseInt($("#comment_count").val()) || 1; // Obtenir le nombre de commentaires à générer

            if (post_ids.length === 0) {
                alert("Aucun article sélectionné");
                return;
            }

            $("#bulk-action-loader").remove();
            $("#doaction, #doaction2").after("<div id=\'bulk-action-loader\'><span class=\'spinner is-active\' style=\'margin-left: 10px;\'></span> <span id=\'comment-generation-progress\'>0 / " + (post_ids.length * comment_count) + " commentaires générés</span></div>");

            var generatedCount = 0;
            var failedCount = 0;

            function generateCommentNext(postIndex, commentIndex) {
                if (postIndex >= post_ids.length) {
                    $("#bulk-action-loader").remove();
                    var message = generatedCount + " commentaire(s) générés avec succès.";
                    if (failedCount > 0) {
                        message += " " + failedCount + " échec(s).";
                    }
                    $("<div class=\'notice notice-success is-dismissible\'><p>" + message + "</p></div>").insertAfter(".wp-header-end");
                    location.reload();
                    return;
                }

                if (commentIndex >= comment_count) {
                    generateCommentNext(postIndex + 1, 0); // Passer au prochain post
                    return;
                }

                $.ajax({
                    url: acg_ajax.ajax_url,
                    method: "POST",
                    data: {
                        action: "acg_generate_comment",
                        nonce: acg_ajax.nonce,
                        post_id: post_ids[postIndex]
                    },
                    success: function(response) {
                        if (response.success) {
                            generatedCount++;
                        } else {
                            failedCount++;
                            console.error("Erreur de génération de commentaire pour ID " + post_ids[postIndex] + ": " + response.data);
                        }
                        $("#comment-generation-progress").text(generatedCount + " / " + (post_ids.length * comment_count) + " commentaires générés");
                        generateCommentNext(postIndex, commentIndex + 1); // Passer au prochain commentaire
                    },
                    error: function() {
                        failedCount++;
                        console.error("Erreur de génération de commentaire pour ID " + post_ids[postIndex]);
                        $("#comment-generation-progress").text(generatedCount + " / " + (post_ids.length * comment_count) + " commentaires générés");
                        generateCommentNext(postIndex, commentIndex + 1); // Passer au prochain commentaire
                    }
                });
            }

            generateCommentNext(0, 0); // Commencer à générer des commentaires
        });
        

        // Gestion des boutons on/off pour les commentaires automatiques
        
        $(".acg-auto-comment-toggle").change(function() {
            var postId = $(this).data("post-id");
            var enabled = this.checked ? 1 : 0;

            $.ajax({
                url: acg_ajax.ajax_url,
                method: "POST",
                data: {
                    action: "acg_toggle_auto_comment",
                    nonce: acg_ajax.nonce,
                    post_id: postId,
                    enabled: enabled
                },
                success: function(response) {
                    if (response.success) {
                        console.log("Statut des commentaires automatiques mis à jour pour article ID " + postId);
                    } else {
                        console.error("Erreur: " + response.data);
                    }
                },
                error: function() {
                    console.error("Erreur de communication AJAX.");
                }
            });
        });
    });');

    wp_localize_script('jquery', 'acg_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('acg_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'acg_enqueue_scripts');