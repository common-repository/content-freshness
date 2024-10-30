<?php
/**
 * Plugin Name: Content Freshness
 * Plugin URI: https://techpilipinas.com
 * Description: Keep your content fresh and up to date! Content Freshness plugin shows a list of posts that have not been updated for a set period of time.
 * Author: Luis Reginaldo Medilo
 * Author URI: https://luismedilo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: content-freshness
 * Domain Path: /languages/
 * Version: 1.0
 * Requires at least: 4.9
 * Requires PHP: 5.6 or later
 */
 
 /*
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 any later version.
  
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.
  
 You should have received a copy of the GNU General Public License
 along with this program. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 */

defined('ABSPATH') or die('Direct script access disallowed.');

add_action('admin_menu', 'content_freshness_add_admin_menu');
add_action('admin_init', 'content_freshness_settings_init');
add_action('admin_enqueue_scripts', 'content_freshness_enqueue_styles');

function content_freshness_add_admin_menu() { 
    add_menu_page('Content Freshness', 'Content Freshness', 'manage_options', 'content_freshness', 'content_freshness_options_page', 'dashicons-chart-line', 6);
    add_submenu_page('content_freshness', 'Settings', 'Settings', 'manage_options', 'content_freshness', 'content_freshness_options_page');
    add_submenu_page('content_freshness', 'Posts to Update', 'Posts to Update', 'manage_options', 'content_freshness_view_posts', 'content_freshness_posts_page');
}

function content_freshness_settings_init() { 
    register_setting('pluginPage', 'content_freshness_settings');

    add_settings_section(
        'content_freshness_pluginPage_section', 
        __('Settings', 'content-freshness'), 
        'content_freshness_settings_section_callback', 
        'pluginPage'
    );

    add_settings_field( 
        'content_freshness_select_field_0', 
        __('Select Timeframe', 'content-freshness'), 
        'content_freshness_select_field_0_render', 
        'pluginPage', 
        'content_freshness_pluginPage_section'
    );

    add_settings_field( 
        'content_freshness_gsc_performance', 
        __('Enable GSC Performance', 'content-freshness'), 
        'content_freshness_gsc_performance_render', 
        'pluginPage', 
        'content_freshness_pluginPage_section'
    );
}

function content_freshness_select_field_0_render() { 
    $options = get_option('content_freshness_settings');
    ?>
    <select name='content_freshness_settings[content_freshness_select_field_0]'>
        <option value='3' <?php if (isset($options['content_freshness_select_field_0'])) selected($options['content_freshness_select_field_0'], 3); ?>>3 months</option>
        <option value='6' <?php if (!isset($options['content_freshness_select_field_0']) || $options['content_freshness_select_field_0'] == 6) echo 'selected="selected"'; ?>>6 months</option>
        <option value='12' <?php if (isset($options['content_freshness_select_field_0'])) selected($options['content_freshness_select_field_0'], 12); ?>>1 year</option>
    </select>
    <?php
}

function content_freshness_gsc_performance_render() { 
    $options = get_option('content_freshness_settings');
    ?>
    <input type='checkbox' name='content_freshness_settings[content_freshness_gsc_performance]' <?php checked(isset($options['content_freshness_gsc_performance']), 1); ?> value='1'>
    <?php
}

function content_freshness_settings_section_callback() { 
    echo esc_html__('Configure settings for Content Freshness.', 'content-freshness');
}

function content_freshness_options_page() { 
    ?>
    <form action='options.php' method='post'>

        <h2><?php echo esc_html__('Content Freshness', 'content-freshness'); ?></h2>

        <?php
        settings_fields('pluginPage');
        do_settings_sections('pluginPage');
        submit_button();
        ?>

    </form>
    <?php
}

function content_freshness_posts_page() {
    $options = get_option('content_freshness_settings');
    $months = isset($options['content_freshness_select_field_0']) ? absint($options['content_freshness_select_field_0']) : 6;
    $gsc_performance_enabled = isset($options['content_freshness_gsc_performance']) && $options['content_freshness_gsc_performance'] == 1;

    $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $posts_per_page = 20;

    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'post_modified';
    $order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? sanitize_text_field($_GET['order']) : 'desc';

    $date_query = date('Y-m-d', strtotime("-{$months} months"));

    $args = array(
        'date_query' => array(
            array(
                'column' => 'post_modified',
                'before' => $date_query,
            ),
        ),
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'orderby' => $orderby,
        'order' => $order,
    );

    $query = new WP_Query($args);

    $title_sort_url = add_query_arg(['orderby' => 'title', 'order' => $order === 'asc' ? 'desc' : 'asc']);
    $date_sort_url = add_query_arg(['orderby' => 'post_modified', 'order' => $order === 'asc' ? 'desc' : 'asc']);

    $site_url = esc_url(get_site_url()); // Get the site URL
	$settings_url = menu_page_url('content_freshness', false);

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Posts to Update', 'content-freshness') . '</h1>';
	echo '<p>' . esc_html__('A list of outdated posts that are in need of an update. To adjust the timeframe, go to the', 'content-freshness') . ' <a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'content-freshness') . '</a>' . esc_html__(' page.', 'content-freshness') . '</p>';
    echo '<table class="wp-list-table widefat fixed striped posts up-posts-table">';

    echo '<thead><tr>';
    echo '<th><a href="' . esc_url($title_sort_url) . '">' . esc_html__('Title', 'content-freshness') . '</a></th>';
    echo '<th><a href="' . esc_url($date_sort_url) . '">' . esc_html__('Last Updated', 'content-freshness') . '</a></th>';
    echo '<th>' . esc_html__('Time Since Last Update', 'content-freshness') . '</th>';
    if ($gsc_performance_enabled) {
        echo '<th>' . esc_html__('Google Search Console', 'content-freshness') . '</th>'; // Conditional GSC Performance column
    }
    echo '</tr></thead>';
    echo '<tbody>';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_url = esc_url(get_permalink());
            $gsc_link = "https://search.google.com/search-console/performance/search-analytics?resource_id=" . urlencode($site_url) . "&page=*" . urlencode($post_url);
            $last_updated = get_the_modified_date('Y-m-d');
            $date_diff = date_diff(date_create($last_updated), date_create(date('Y-m-d')));
            $months_diff = intval($date_diff->format('%m')) + (intval($date_diff->format('%y')) * 12);
            $days_diff = intval($date_diff->format('%d'));

            echo '<tr>';
            echo '<td class="title"><a href="' . esc_url(get_edit_post_link()) . '">' . esc_html(get_the_title()) . '</a></td>';
            echo '<td>' . esc_html(get_the_modified_date()) . '</td>';
            echo '<td>' . esc_html($months_diff) . ' ' . esc_html__('months', 'content-freshness') . ' ' . esc_html($days_diff) . ' ' . esc_html__('days', 'content-freshness') . '</td>';
            if ($gsc_performance_enabled) {
                echo '<td><a href="' . esc_url($gsc_link) . '" target="_blank">' . esc_html__('GSC Performance', 'content-freshness') . '</a></td>'; // Conditional GSC link
            }
            echo '</tr>';
        }

        echo '</tbody></table>';

        $big = 999999999;
        echo '<div class="pagination">';
        echo paginate_links(array(
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => $query->max_num_pages
        ));
        echo '</div>';

    } else {
        echo '<tr><td colspan="' . ($gsc_performance_enabled ? '4' : '3') . '">' . esc_html__('No posts found.', 'content-freshness') . '</td></tr>';
        echo '</tbody></table>';
    }

    echo '</div>';

    wp_reset_postdata();
}

function content_freshness_enqueue_styles() {
    $screen = get_current_screen();
    if (isset($screen->id) && strpos($screen->id, 'content_freshness') !== false) {
        wp_enqueue_style('content-freshness-styles', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }
}

?>
