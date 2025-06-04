<?php
/*
Plugin Name: WP MU Takvim Notları
Description: WordPress Multisite için not yönetimine sahip basit bir takvim sistemi.
Version: 1.0
Author: OpenAI Codex
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Dogrudan erisim engellendi
}

// Takvim notlari icin "custom post type" kaydet
function wpmu_calendar_note_cpt() {
    $labels = array(
        'name'          => 'Takvim Notları',
        'singular_name' => 'Takvim Notu',
    );

    $args = array(
        'public' => true,
        'labels' => $labels,
        'supports' => array('title', 'editor'),
    );

    register_post_type('calendar_note', $args);
}
add_action('init', 'wpmu_calendar_note_cpt');

// Not tarih alanı için meta kutusu ekle
function wpmu_calendar_note_meta_box() {
    add_meta_box(
        'wpmu_note_date',
        'Not Tarihi',
        'wpmu_calendar_note_meta_box_callback',
        'calendar_note',
        'side'
    );
}
add_action('add_meta_boxes', 'wpmu_calendar_note_meta_box');

function wpmu_calendar_note_meta_box_callback($post) {
    wp_nonce_field('wpmu_save_note_date', 'wpmu_note_date_nonce');
    $value = get_post_meta($post->ID, '_wpmu_note_date', true);
    echo '<label for="wpmu_note_date">Tarih:</label> ';
    echo '<input type="date" id="wpmu_note_date" name="wpmu_note_date" value="' . esc_attr($value) . '" />';
}

function wpmu_save_calendar_note_date($post_id) {
    if (!isset($_POST['wpmu_note_date_nonce']) || !wp_verify_nonce($_POST['wpmu_note_date_nonce'], 'wpmu_save_note_date')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['wpmu_note_date'])) {
        update_post_meta($post_id, '_wpmu_note_date', sanitize_text_field($_POST['wpmu_note_date']));
    }
}
// Yalnizca "calendar_note" gonderileri kaydedilirken not tarihi sakla
add_action('save_post_calendar_note', 'wpmu_save_calendar_note_date');

// Takvimi gosterme shortcode'u
function wpmu_calendar_notes_calendar($atts) {
    $atts = shortcode_atts(array(
        'month' => date('n'),
        'year' => date('Y'),
    ), $atts, 'calendar_notes_calendar');

    $month = intval($atts['month']);
    $year = intval($atts['year']);
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = date('t', $first_day);
    $start_day = date('w', $first_day);

    $notes = get_posts(array(
        'post_type' => 'calendar_note',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_wpmu_note_date',
                'value' => array(sprintf('%04d-%02d-01', $year, $month), sprintf('%04d-%02d-%02d', $year, $month, $days_in_month)),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
        ),
    ));

    $notes_by_date = array();
    foreach ($notes as $note) {
        $date = get_post_meta($note->ID, '_wpmu_note_date', true);
        $notes_by_date[$date][] = $note;
    }

    $output = '<table class="calendar-notes">';
    $output .= '<tr>';
    $output .= '<th>Paz</th><th>Pts</th><th>Sal</th><th>Çar</th><th>Per</th><th>Cum</th><th>Cts</th>';
    $output .= '</tr><tr>';

    $day_of_week = 0;
    for ($i = 0; $i < $start_day; $i++) {
        $output .= '<td></td>';
        $day_of_week++;
    }

    for ($day = 1; $day <= $days_in_month; $day++) {
        $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $output .= '<td>';
        $output .= '<div class="date">' . $day . '</div>';
        if (isset($notes_by_date[$current_date])) {
            foreach ($notes_by_date[$current_date] as $note) {
                $output .= '<div class="note">' . esc_html($note->post_title) . '</div>';
            }
        }
        $output .= '</td>';
        $day_of_week++;
        if ($day_of_week == 7) {
            $output .= '</tr><tr>';
            $day_of_week = 0;
        }
    }

    while ($day_of_week > 0 && $day_of_week < 7) {
        $output .= '<td></td>';
        $day_of_week++;
    }

    $output .= '</tr></table>';

    return $output;
}
add_shortcode('calendar_notes_calendar', 'wpmu_calendar_notes_calendar');
