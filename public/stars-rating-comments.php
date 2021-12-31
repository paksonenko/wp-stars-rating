<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('Stars_Rating')) :
    /**
     * Class Stars_Rating
     *
     * Plugin's main class.
     *
     */
    final class Stars_Rating
    {
        /**
         * Single instance of Class.
         *
         * @var Stars_Rating
         */
        protected static $_instance;

        /**
         * Provides singleton instance.
         *
         * @return self instance
         */
        public static function instance()
        {

            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Status of the stars rating for the current post type
         *
         * @return bool
         */
        public static function status()
        {

            $enabled_posts = get_option('enabled_post_types');
            $post_status   = get_post_meta(get_the_ID(), '_comments_rating', true);

            if (!is_array($enabled_posts)) {
                $enabled_posts = (array) $enabled_posts;
            }

            $status = (in_array(get_post_type(), $enabled_posts) && ('0' !== $post_status)) ? true : false;

            return $status;
        }

        /**
         * Stars_Rating constructor.
         */
        public function __construct()
        {

            $this->init_hooks();

            // Stars Rating plugin loaded action hook
            do_action('Stars_Rating_loaded');
        }

        /**
         * Initialize hooks.
         *
         */
        public function init_hooks()
        {

            add_action('comment_form_logged_in_before', array($this, 'comment_form_fields'));
            add_action('comment_form_top', array($this, 'comment_form_fields'));
            add_filter('preprocess_comment', array($this, 'verify_comment_rating'));
            add_action('comment_post', array($this, 'save_comment_rating'), 10, 3);
            add_filter('comment_text', array($this, 'modify_comment'));
            add_filter("comments_template", array($this, 'rating_average'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_plugin_files'));
        }

        /**
         * Add fields after default fields above the comment box, always visible
         */
        public function comment_form_fields()
        {

            if (!self::status()) return;
?>
            <div class="rating-group">
                <input value="5" type="hidden" name="rating" id="stars-rating-comment" />
                <label aria-label="1 star" class="rating__label" for="rating2-10"><i class="rating__icon rating__icon--star fa fa-star"></i></label>
                <input class="rating__input rating__choose" id="rating2-10" value="1" type="radio">
                <label aria-label="1.5 stars" class="rating__label rating__label--half" for="rating2-15"><i class="rating__icon rating__icon--star fa fa-star-half"></i></label>
                <input class="rating__input rating__choose" id="rating2-15" value="1.5" type="radio">
                <label aria-label="2 stars" class="rating__label" for="rating2-20"><i class="rating__icon rating__icon--star fa fa-star"></i></label>
                <input class="rating__input rating__choose" id="rating2-20" value="2" type="radio">
                <label aria-label="2.5 stars" class="rating__label rating__label--half" for="rating2-25"><i class="rating__icon rating__icon--star fa fa-star-half"></i></label>
                <input class="rating__input rating__choose" id="rating2-25" value="2.5" type="radio">
                <label aria-label="3 stars" class="rating__label" for="rating2-30"><i class="rating__icon rating__icon--star fa fa-star"></i></label>
                <input class="rating__input rating__choose" id="rating2-30" value="3" type="radio">
                <label aria-label="3.5 stars" class="rating__label rating__label--half" for="rating2-35"><i class="rating__icon rating__icon--star fa fa-star-half"></i></label>
                <input class="rating__input rating__choose" id="rating2-35" value="3.5" type="radio">
                <label aria-label="4 stars" class="rating__label" for="rating2-40"><i class="rating__icon rating__icon--star fa fa-star"></i></label>
                <input class="rating__input rating__choose" id="rating2-40" value="4" type="radio">
                <label aria-label="4.5 stars" class="rating__label rating__label--half" for="rating2-45"><i class="rating__icon rating__icon--star fa fa-star-half"></i></label>
                <input class="rating__input rating__choose" id="rating2-45" value="4.5" type="radio">
                <label aria-label="5 stars" class="rating__label" for="rating2-50"><i class="rating__icon rating__icon--star fa fa-star"></i></label>
                <input class="rating__input rating__choose" id="rating2-50" value="5" type="radio" checked>
            </div>

<?php
        }

        /**
         * Add the filter to check whether the comment rating has been set
         */
        public function verify_comment_rating($comment_data)
        {

            if ((isset($_POST['rating'])) && ($_POST['rating'] == '')) {

                wp_die(esc_html('Error: You did not add a rating. Hit the Back button on your Web browser and resubmit your comment with a rating.'));
            }

            return $comment_data;
        }

        /**
         * Save the comment rating along with comment
         */
        public function save_comment_rating($comment_id, $comment_approved, $commentdata)
        {

            if ((isset($_POST['rating'])) && ($_POST['rating'] != '')) {

                $rating = wp_filter_nohtml_kses($_POST['rating']);
                $post_id = $commentdata['comment_post_ID'];
                $post_title = get_the_title($post_id);

                list($response_rating, $count_comments, $rrors) = $this->APIrequest($post_id, $post_title, $rating);
                if ($rrors) {
                    var_dump($rrors);
                    die();
                }

                update_post_meta($post_id, 'avg_rating', $response_rating);
                update_post_meta($post_id, 'comments_count', $count_comments);

                add_comment_meta($comment_id, 'rating', $rating);
            }
        }
        /**
         * Add the comment rating (saved earlier) to the comment text
         * You can also output the comment rating values directly to the comments template
         */
        private function APIrequest($post_id, $post_title, $rate)
        {
            // default values
            $count_comments = 1;
            $avg_rate = 0;
            $errors = '';

            $args_data = [
                'id_post' => $post_id,
                'rating' => $rate,
                'title' => $post_title
            ];

            $arguments = [
                'headers'     => [
                    'Content-Type: application/json'
                ],
                'body' => wp_json_encode($args_data),
                'timeout'     => 60,
                'redirection' => 5,
                'blocking'    => true,
                'httpversion' => '1.0',
                'sslverify'   => false,
                'data_format' => 'body'
            ];

            $url = 'http://pakson-webservice.herokuapp.com/api/posts';

            $response = wp_remote_post($url, $arguments);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $errors = 'Error: ' . $error_message;
            } else {

                $json_dc = json_decode(wp_remote_retrieve_body($response));

                if (isset($json_dc['error'])) {
                    $errors = 'Error: ' . $json_dc['error'];
                } else {
                    $avg_rate = number_format($json_dc['average'], 2);
                    $count_comments = (int)$json_dc['count'];
                }
            }

            return [$avg_rate, $count_comments, $errors];
        }

        /**
         * Add the comment rating (saved earlier) to the comment text
         * You can also output the comment rating values directly to the comments template
         */
        public function modify_comment($comment)
        {

            if (!self::status()) return $comment;

            if ($rating = get_comment_meta(get_comment_ID(), 'rating', true)) {

                $rating =  $this->rating_stars($rating);

                return $rating . $comment;
            } else {
                return $comment;
            }
        }

        /**
         * Display rating average
         */
        public function rating_average()
        {
            if (!self::status()) return;

            $avg_rate = get_post_meta(get_the_ID(), 'avg_rating', true);
            $avg_count = get_post_meta(get_the_ID(), 'comments_count', true);

            if (('' != $avg_rate) && ($avg_count !== 0)) {
                echo $this->rating_stars($avg_rate);
                echo '<p>' . $avg_rate . ' based on ' . $avg_count . ' reviews</p>';
            }
        }

        /**
         * Display rated stars based on given number of rating
         * @param int
         * @return string
         */
        public function rating_stars($rating)
        {
            $output = '<div class="rating-group-fix" style="width:350px;">';

            if (!empty($rating)) {

                $output .= '<label aria-label="1 stars" class="rating__label_static" for="post-rating2-10"><i class="rating__icon rating__icon--star fa fa-star"></i></label><input class="rating__input" id="post-rating2-10" value="1" type="radio" ' . ((int)$rating === 1 ? 'checked' : '') . '>';

                $rating = (int)(($rating - 1) / 0.5);

                for ($count = 1; $count <= $rating; $count++) {

                    $rate = 1 + $count * 0.5;
                    $frac  = $rate - (int) $rate;

                    $output .= '<label aria-label="' . $rate . ' stars" class="rating__label_static ' . ($frac != 0 ? 'rating__label_static--half' : '') . '" for="post-rating2-' . ($rate * 10) . '"><i class="rating__icon rating__icon--star fa fa-star' . ($frac != 0 ? '-half' : '') . '"></i></label><input class="rating__input" id="post-rating2-' . ($rate * 10) . '" value="' . $rate . '" type="radio" ' . ($rating === $count ? 'checked' : '') . '>';
                }

                $unrated = 8 - $rating;
                for ($count = 1; $count <= $unrated; $count++) {

                    $rate = 1 + $rating * 0.5 + $count * 0.5;
                    $frac  = $rate - (int) $rate;

                    $output .= '<label aria-label="' . $rate . ' stars" class="rating__label_static ' . ($frac != 0 ? 'rating__label_static--half' : '') . '" for="post-rating2-' . ($rate * 10) . '"><i class="rating__icon rating__icon--star fa fa-star' . ($frac != 0 ? '-half' : '') . '"></i></label><input class="rating__input" id="post-rating2-' . ($rate * 10) . '" value="' . $rate . '" type="radio">';
                }
            }
            $output .= '</div>';

            return $output;
        }

        public function enqueue_plugin_files()
        {

            if (!self::status()) return;

            $plugin_url = WP_PLUGIN_URL;

            $plugin_include_url = $plugin_url . '/stars-rating/includes/';

            // plugin css
            wp_enqueue_style(
                'stars-rating-styles',
                $plugin_include_url . 'css/style.css',
                array(),
                '1.0.0'
            );

            // register custom js
            wp_enqueue_script(
                'stars-rating-script',
                $plugin_include_url . 'js/script.js',
                array('jquery'),
                '1.0.0'
            );
        }
    }

endif;
