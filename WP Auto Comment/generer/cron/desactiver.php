<?php 

if (!defined('ABSPATH')) {
    exit; 
}

// Désactivation de la tâche cron sur la désactivation du plugin
function acg_deactivate_cron() {
    $timestamp = wp_next_scheduled('acg_cron_hook');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'acg_cron_hook');
    }
}
register_deactivation_hook(__FILE__, 'acg_deactivate_cron');