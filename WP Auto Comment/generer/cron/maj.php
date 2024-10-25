<?php 

if (!defined('ABSPATH')) {
    exit; 
}

function acg_update_cron() {
    $auto_comment_enabled = get_option('acg_auto_comment_enabled', 1);
  
    if (!$auto_comment_enabled) {
        $timestamp = wp_next_scheduled('acg_cron_hook');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'acg_cron_hook');
        }
    } else {
        $interval = get_option('acg_cron_interval', 5);
        if (!wp_next_scheduled('acg_cron_hook')) {
            wp_schedule_event(time(), 'every_five_minutes', 'acg_cron_hook');
        }
    }
}

// activer la tâche cron
function acg_activate_cron() {
    $enabled = get_option('acg_auto_comment_enabled', 1);
    if ($enabled) {
        $interval = get_option('acg_cron_interval', 5);

        if (!wp_next_scheduled('acg_cron_hook')) {
            wp_schedule_event(time(), 'every_five_minutes', 'acg_cron_hook');
        }
    }
}
add_action('wp', 'acg_activate_cron');

// intervalle personnalisé pour la tâche cron
function acg_cron_intervals($schedules) {
    $interval = get_option('acg_cron_interval', 5);

    if ($interval > 0) {
        $schedules['every_five_minutes'] = [
            'interval' => $interval * 60,
            'display' => __('Chaque X minutes')
        ];
    }

    return $schedules;
}
add_filter('cron_schedules', 'acg_cron_intervals');