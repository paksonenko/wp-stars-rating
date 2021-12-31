<?php

/**
 * Plugin Name: Stars Rating 
 * Description: Approbation task.
 * Version: 1.0.0
 * Author: Petro Aksonenko   
 *  
 *
 * @package pakson-stars-rating
 * @category Core
 * @author Petro Aksonenko 
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('stars_rating_update_posts_meta')) {

    function stars_rating_update_posts_meta()
    {

        $posts_100 = get_posts([
            'posts_per_page' => 1000,
            'order' => 'ASC'
        ]);

        $key     = '_comments_rating';
        foreach (array_column($posts_100, 'ID') as $post_id) {

            $key_value = get_post_meta($post_id, $key, true);
            if ($key_value === '') {
                update_post_meta($post_id, $key, 1);
            }
        }
    }

    // load 
    stars_rating_update_posts_meta();
}


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path(__FILE__) . 'includes/stars-rating-include.php';

/**
 * Main instance of Stars_Rating.
 *
 * Returns the main instance of Stars_Rating to prevent the need to use globals.
 * 
 * @return Stars_Rating
 */
function Stars_Rating()
{
    return Stars_Rating::instance();
}

// Get Stars_Rating Running.
Stars_Rating();
