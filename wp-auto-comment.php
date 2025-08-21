<?php

/*
Plugin Name: WP Auto Comment
Plugin URI: https://kevin-benabdelhak.fr/plugins/wp-auto-comment/
Description: WP Auto Comment est un plugin conçu pour WordPress qui automatise la génération de commentaires sur les articles de blog. Il utilise l'API OpenAI pour créer des commentaires pertinents et personnalisés, apportant ainsi une valeur ajoutée aux articles et favorisant une interaction plus dynamique au sein de la communauté de lecteurs.
Version: 2.7
Author: Kevin BENABDELHAK
Author URI: https://kevin-benabdelhak.fr/
Contributors: kevinbenabdelhak
*/

if (!defined('ABSPATH')) {
    exit; 
}



if ( !class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
    require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
}
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$monUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/kevinbenabdelhak/WP-Auto-Comment/', 
    __FILE__,
    'wp-auto-comment' 
);

$monUpdateChecker->setBranch('main');



/* champ & ajax */
require_once plugin_dir_path(__FILE__) . 'script-admin/script.php';
require_once plugin_dir_path(__FILE__) . 'options/options.php';
require_once plugin_dir_path(__FILE__) . 'options/generer-modele.php';
require_once plugin_dir_path(__FILE__) . 'colonne/colonne-articles.php';


/* generer commentaire */
require_once plugin_dir_path(__FILE__) . 'generer/commentaire-instant.php';


/* auto*/
require_once plugin_dir_path(__FILE__) . 'generer/cron/maj.php';
require_once plugin_dir_path(__FILE__) . 'generer/cron/desactiver.php';
require_once plugin_dir_path(__FILE__) . 'generer/cron/commentaire-cron.php';
