<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('Stars_Rating_Metabox')) :

    /**
     * Class Stars_Rating_Metabox
     *
     * Plugin's settings class
     *
     */
    final class Stars_Rating_Metabox
    {

        /**
         * Single instance of Class.
         *
         * @var Stars_Rating_Metabox
         */
        protected static $_instance;

        /**
         * Provides singleton instance.
         *
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Stars_Rating_Metabox constructor.
         */
        public function __construct()
        {

            $this->init_hooks();

            // Stars Rating plugin metabox loaded action hook
            do_action('Stars_Rating_Metabox_loaded');
        }

        /**
         * Status of the stars rating for the current post type
         *
         * @return bool
         */
        public static function status()
        {

            $enabled_posts = get_option('enabled_post_types');

            if (!is_array($enabled_posts)) {
                $enabled_posts = (array) $enabled_posts;
            }

            $status = in_array(get_post_type(), $enabled_posts) ? true : false;

            return $status;
        }

        public function init_hooks()
        {
            add_action('post_comment_status_meta_box-options', [$this, 'stars_rating_meta_box']);
            add_action("save_post", [$this, 'save_stars_rating_meta_box'], 10, 3);
        }


        public function stars_rating_meta_box($post)
        {

            if (!self::status()) return;

            $key     = '_comments_rating';
            $current = 1;

            $key_value = get_post_meta($post->ID, $key, true);

            if ('0' === $key_value || !empty($key_value)) {
                $current = $key_value;
            }

            printf(
                '<br /><label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" class="selectit" %2$s/> %3$s</label>',
                $key,
                checked(1, $current, false),
                __('Allow <a href="https://wordpress.org/plugins/stars-rating/" target="_blank">Stars Rating</a> for comments on this page.', 'stars-rating')
            );
        }
        /**/
        public function save_stars_rating_meta_box($post_id, $post, $update)
        {
            if (!self::status()) return;

            // AJAX autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                return;

            // Some other POST request
            if (!isset($_POST['post_type']))
                return;

            // Missing capability
            if (!current_user_can('edit_' . $_POST['post_type'], $post_id))
                return;

            $key = '_comments_rating';
            // update_post_meta($post_id, $key, 1);

            // Checkbox successfully clicked
            if (isset($_POST[$key]) && 'on' === strtolower($_POST[$key])) {
                return update_post_meta($post_id, $key, 1);
            } else {
                return update_post_meta($post_id, $key, 0);
            }
        }
    }

endif;


/**
 * Main instance of Stars_Rating_Metabox.
 *
 * Returns the main instance of Stars_Rating_Metabox to prevent the need to use globals.
 *
 * @return Stars_Rating_Metabox
 */
function Stars_Rating_Metabox()
{
    return Stars_Rating_Metabox::instance();
}

// Get Stars_Rating_Metabox Running.
Stars_Rating_Metabox();
