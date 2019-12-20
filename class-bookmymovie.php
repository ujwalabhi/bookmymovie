<?php
/**
 * BookMySeat.
 *
 * @package   bookmyseat
 * @author    Ujwal Abhishek
 * @copyright 2019 Ujwal Abhishek
 */

/**
 * BookMySeat class.
 *
 * @package BookMySeat
 * @author  Ujwal Abhishek
 */
class BookMyMovie
{
    protected $version = '1.0.4';

    protected $plugin_slug = 'bookmymovie';

    protected $page_url;

    protected static $instance = null;
    protected $session_id;

    protected $place_types = array('1' => 'seat',);
    protected $place_statuses = array(
        '1' => 'available',
        '2' => 'booked',
        '3' => 'in-cart',
        '4' => 'in-others-cart',
        '5' => 'unavailable'
    );
    protected $event_booking_open = null; // check if movie booking is still open
    private $success_user_messages = array(1 => 'Tickets has been successfully booked.');
    private $error_user_messages = array(2 => 'Error. Places has not been booked.');
    private $order_statuses = array(
        0 => 'Not defined',
        1 => 'Set',
        2 => 'Paid',
        3 => 'Cancelled',
    );

    /**
     * Initialize the plugin by setting filters, and administration functions.
     *
     */
    private function __construct()
    {
        session_start();
        $this->session_id = session_id();
        $this->page_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // Add the admin menu items for the plugin.
        add_action('admin_menu', array(
            $this,
            'add_plugin_admin_menu'
        ));

        add_action('init', array(
            'BookMyMovie',
            'register_tables'
        ), 1);

        // Load admin style sheet and JavaScript.
        add_action('admin_enqueue_scripts', array(
            $this,
            'enqueue_admin_styles'
        ));

        add_action('admin_enqueue_scripts', array(
            $this,
            'enqueue_admin_scripts'
        ));

        // Load public-facing style sheet and JavaScript.
        add_action('wp_enqueue_scripts', array(
            $this,
            'enqueue_styles'
        ));

        add_action('wp_enqueue_scripts', array(
            $this,
            'enqueue_scripts'
        ), 999);

        add_shortcode('book_my_movie', array(
            $this,
            'book_my_movie_shortcode'
        ));

        add_shortcode('book_my_movie_event', array(
            $this,
            'book_my_movie_event_shortcode'
        ));

        add_action('wp_ajax_add_to_cart', array(
            $this,
            'add_to_cart'
        ));
        add_action('wp_ajax_nopriv_add_to_cart', array(
            $this,
            'add_to_cart'
        ));
        add_action('wp_ajax_refresh_scheme', array(
            $this,
            'refresh_scheme_front'
        ));
        add_action('wp_ajax_nopriv_refresh_scheme', array(
            $this,
            'refresh_scheme_front'
        ));

        add_action('wp_ajax_delete_from_cart', array(
            $this,
            'delete_from_cart'
        ));
        add_action('wp_ajax_nopriv_delete_from_cart', array(
            $this,
            'delete_from_cart'
        ));
        add_action('wp_ajax_checkout', array(
            $this,
            'ajax_checkout'
        ));
        add_action('wp_ajax_nopriv_checkout', array(
            $this,
            'ajax_checkout'
        ));

    }

    /**
     * Return an instance of this class.
     *
     * @return    object    A single instance of this class(Singelton).
     */
    public static function get_instance()
    {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public static function activate()
    {

        // activation functionality
        self::create_tables();
    }

    /**
     * Register the tables
     */
    public static function register_tables()
    {
        global $wpdb;
        $wpdb->bmm_schemes = "{$wpdb->prefix}bmm_schemes";
        $wpdb->bmm_places = "{$wpdb->prefix}bmm_places";
        $wpdb->bmm_carts = "{$wpdb->prefix}bmm_carts";
        $wpdb->bmm_orders = "{$wpdb->prefix}bmm_orders";
        $wpdb->bmm_events = "{$wpdb->prefix}bmm_events";
    }

    /**
     * Create the schema and insert sample data for the screen and movie events.
     */

    public static function create_tables()
    {
        global $wpdb;

        $wpdb->hide_errors();

        global $charset_collate;
        // Call this manually as we may have missed the init hook
        self::register_tables();

        // bmm_schemes
        $sql_create_table = "CREATE TABLE `{$wpdb->bmm_schemes}` (
                                  `scheme_id` int(11) NOT NULL AUTO_INCREMENT,
                                  `name` varchar(255) NOT NULL,
                                  `width` int(11) NOT NULL,
                                  `height` int(11) NOT NULL,
                                  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
                                  `description` text NOT NULL,
                                  `purchase_limit` int(11) NOT NULL,                                  
                                  PRIMARY  KEY (`scheme_id`)
                                ) $charset_collate; ";
        $wpdb->query($sql_create_table);

        $sql_insert_data = "INSERT INTO `{$wpdb->bmm_schemes}` (`scheme_id`, `name`, `width`, `height`, `is_hidden`, `description`, `purchase_limit`) VALUES
(1, 'Screen A', 20, 8, 0, '', 0),(2, 'Screen B', 20, 8, 0, '', 0)";

        $wpdb->query($sql_insert_data);

        // bmm_places
        $sql_create_table = "CREATE TABLE `{$wpdb->bmm_places}` (
                                  `place_id` int(11) NOT NULL AUTO_INCREMENT,
                                  `type` int(11) NOT NULL DEFAULT '0',
                                  `cells` text NOT NULL,
                                  `name` varchar(255) NOT NULL,
                                  `description` varchar(255) DEFAULT NULL,
                                  `price` float NOT NULL DEFAULT '0',
                                  `scheme_id` int(11) NOT NULL,
                                  `status_id` text NOT NULL,
                                  `color` varchar(255) DEFAULT NULL,
                                  PRIMARY  KEY (`place_id`),
                                  KEY `scheme_id` (`scheme_id`)
                                ) $charset_collate;";
        $wpdb->query($sql_create_table);
// prepare the seat type array
        $platinumSeats = range(1, 40);
        $goldSeats = range(41, 100);
        $silverSeats = range(101, 160);


        $sql_insert_data = "INSERT INTO `wp_bmm_places` (`place_id`, `type`, `cells`, `name`, `description`, `price`, `scheme_id`, `status_id`, `color`) VALUES
                            (1, 1,'" . serialize($platinumSeats) . "', 'Platinum', '', 500, 1, 0, '#545454'),
                            (2, 1, '" . serialize($goldSeats) . "', 'Gold', '', 400, 1, 0, '#ad9243'),
                            (3, 1, '" . serialize($silverSeats) . "', 'Silver', '', 200, 1, 0, '#5e83f2'),
                            (4, 1,'" . serialize($platinumSeats) . "', 'Platinum', '', 550, 2, 0, '#545454'),
                            (5, 1, '" . serialize($goldSeats) . "', 'Gold', '', 450, 2, 0, '#ad9243'),
                            (6, 1, '" . serialize($silverSeats) . "', 'Silver', '', 250, 2, 0, '#5e83f2')";
        $wpdb->query($sql_insert_data);

        // bmm_events
        $sql_create_table = "CREATE TABLE `{$wpdb->bmm_events}` (
                                  `event_id` int(11) NOT NULL AUTO_INCREMENT,
                                  `scheme_id` int(11) DEFAULT NULL,
                                  `name` varchar(255) NOT NULL,
                                  `description` varchar(255) DEFAULT NULL,
                                  `url` varchar(255) DEFAULT NULL,
                                  `hours` float NOT NULL DEFAULT '0',
                                  `background_color` varchar(255) DEFAULT NULL,
                                  `border_color` varchar(255) DEFAULT NULL,
                                  `text_color` varchar(255) DEFAULT NULL,
                                  `start` datetime NOT NULL,
                                  `end` datetime NOT NULL,
                                  `timezone_offset` float NOT NULL DEFAULT '0',
                                  `all_day` int(11) NOT NULL DEFAULT '1',
                                  `status_id` int(11) NOT NULL,
                                  PRIMARY  KEY (`event_id`),
                                  KEY `scheme_id` (`scheme_id`)
                                ) $charset_collate;";
        $wpdb->query($sql_create_table);

        $sql_insert_data = "INSERT INTO {$wpdb->bmm_events} (`event_id`, `scheme_id`, `name`, `description`, `url`, `hours`, `background_color`, `border_color`, `text_color`, `start`, `end`, `timezone_offset`, `all_day`, `status_id`) VALUES
    (1, 1, 'Fast And Furious', '', '', 0, '#3a87ad', '#3a87ad', '#ffffff', '2020-12-18 10:00:00', '2020-12-18 13:30:00', -5, 0, 1),
(2, 2, 'Men In Black', '', '', 0, '#3a87ad', '#3a87ad', '#ffffff', '2020-12-18 13:30:00', '2020-12-18 16:30:00', -5, 0, 1)";
        $wpdb->query($sql_insert_data);

        // bmm_carts
        $sql_create_table = "CREATE TABLE `{$wpdb->bmm_carts}` (
                                  `session_id` varchar(255) NOT NULL,
                                  `place_id` int(11) NOT NULL,
                                  `seat_id` int(11) NOT NULL,
                                  `date` datetime NOT NULL,
                                  KEY `place_id` (`place_id`)
                                ) $charset_collate;";
        $wpdb->query($sql_create_table);


        // bmm_orders
        $sql_create_table = "CREATE TABLE `{$wpdb->bmm_orders}` (
                                  `order_id` int(11) NOT NULL AUTO_INCREMENT,
                                  `first_name` varchar(255) DEFAULT NULL,
                                  `last_name` varchar(255) DEFAULT NULL,
                                  `email` varchar(255) DEFAULT NULL,
                                  `phone` varchar(255) DEFAULT NULL,
                                  `notes` text,
                                  `date` datetime DEFAULT NULL,
                                  `code` varchar(255) DEFAULT NULL,
                                  `places` text,
                                  `total_price` float NOT NULL DEFAULT '0',
                                  `status_id` int(11) NOT NULL DEFAULT '0',
                                  `admin_notes` text,
                                  `scheme_id` int(11) NOT NULL DEFAULT '0',
                                  `event_id` int(11) DEFAULT NULL,
                                  `event_name` varchar(255) DEFAULT NULL,
                                  `event` text,
                                  PRIMARY  KEY (`order_id`)
                                ) $charset_collate; ";
        $wpdb->query($sql_create_table);


        $wpdb->show_errors();

    }


    /**
     * Fired when the plugin is deactivated.
     */

    public static function deactivate()
    {
        global $wpdb;

        $wpdb->hide_errors();
        $sql_drop_tables = "DROP TABLE `wp_bmm_carts`, `wp_bmm_events`, `wp_bmm_orders`, `wp_bmm_places`, `wp_bmm_schemes`";
        $wpdb->query($sql_drop_tables);
        $wpdb->show_errors();
    }

    /**
     * Register and enqueue admin-specific style sheet.
     */
    public function enqueue_admin_styles()
    {
        wp_enqueue_style($this->plugin_slug . '-jquery-ui-theme', plugins_url('css/jquery-ui-themes/smoothness/jquery-ui-1.10.3.custom.min.css', __FILE__), array(), $this->version);
        wp_enqueue_style($this->plugin_slug . '-admin-styles', plugins_url('css/admin.css', __FILE__), array(), $this->version);

    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @return    null    Return early if no settings page is registered.
     * @since     0.1.0
     *
     */
    public function enqueue_admin_scripts()
    {

        wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('js/admin.js', __FILE__), array(
            'jquery',
            'jquery-ui-core',
            'jquery-ui-button',
            'jquery-ui-dialog',
            'wp-color-picker',
            'jquery-ui-widget',
            'jquery-ui-position',
            'jquery-ui-tooltip',
            'jquery-ui-tabs',
            'jquery-ui-datepicker',
        ), $this->version);


    }

    /**
     * Register and enqueue public-facing style sheet.
     *
     * @since    0.1.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_slug . '-jquery-ui-theme', plugins_url('css/jquery-ui-themes/smoothness/jquery-ui-1.10.3.custom.min.css', __FILE__), array(), $this->version);
        wp_enqueue_style($this->plugin_slug . '-plugin-styles', plugins_url('css/public.css', __FILE__), array(), $this->version);
    }

    /**
     * Register and enqueue public-facing JavaScript files.
     *
     * @since    0.1.0
     */
    public function enqueue_scripts()
    {
        wp_register_script($this->plugin_slug . '-kkcountdown', plugins_url('js/kkcountdown.js', __FILE__), array('jquery',), $this->version);
        wp_register_script($this->plugin_slug . '-jquery-block-ui', plugins_url('js/jquery.blockUI.js', __FILE__), array('jquery',), $this->version);
        wp_enqueue_script($this->plugin_slug . '-plugin-script', plugins_url('js/public.js', __FILE__), array(
            $this->plugin_slug . '-kkcountdown',
            $this->plugin_slug . '-jquery-block-ui',
            'jquery-ui-core',
            'jquery-ui-widget',
            'jquery-ui-position',
            'jquery-ui-tooltip',
            'jquery-ui-button',
            'jquery-ui-dialog'
        ), $this->version);
        wp_localize_script($this->plugin_slug . '-plugin-script', 'bmm_object', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'loc_strings' => $this->get_loc_strings(),
        ));
    }

    /**
     * @return array
     * @since 0.3.0
     */
    private function get_loc_strings()
    {
        return array(
            'please_wait' => 'Please wait...',
            'checkout' => 'Checkout',
            'cancel' => 'Cancel',
            'ok' => 'Ok',
            'set_a_place' => 'Set a place',
            'change_place_status' => 'Change place status',
            'change_place_price' => 'Change place price',
            'update_a_place' => 'Update a place',
            'unset_this_place' => 'Are you sure you want to unset this place?',
            'delete_this_item' => 'Are you sure you want to delete this item?',
            'scheme_show_text' => 'Show Scheme',
            'scheme_hide_text' => 'Hide Scheme',
            'places_in_cart_cant_add' => 'Sorry, you can not add this place to your cart!',
            'places_in_cart_limit' => 'Sorry, you can not add more places to your cart!',
        );
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     */
    public function add_plugin_admin_menu()
    {
        add_menu_page('BookMyMovie', 'BookMyMovie', 'manage_options', $this->plugin_slug, array(
            $this,
            'display_plugin_admin_page'
        ));
        add_submenu_page($this->plugin_slug, 'View Schedules', 'View Schedules', 'manage_options', $this->plugin_slug . '-movieschedule', array(
            $this,
            'display_plugin_admin_schedule_page'
        ));

        add_submenu_page($this->plugin_slug, 'View Bookings', 'View Bookings', 'manage_options', $this->plugin_slug . '-moviebookings', array(
            $this,
            'display_plugin_admin_schemes_page'
        ));

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    0.1.0
     */
    public function display_plugin_admin_page()
    {
        include_once('views/bmm-home.php');
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    0.1.0
     */
    public function display_plugin_admin_schemes_page()
    {
        include_once('views/bmm-screen-layout.php');
    }
    /**
     * Render the settings page for this plugin.
     *
     * @since    0.1.0
     */
    public function display_plugin_admin_schedule_page()
    {
        include_once('views/bmm-movie-schedule-list.php');
    }
    /**
     * Get the list of Screens and movies scheduled on the screens
     * @param int $include_scheme
     * @return array|object|null
     */

    public function get_schemes($include_scheme = 0)
    {
        global $wpdb;

        $schemes = $wpdb->get_results("
            SELECT `s`.*, `e`.`name` as `event`,`e`.`event_id` as `event_id`, `e`.`start`
            FROM $wpdb->bmm_schemes as `s`
            LEFT JOIN $wpdb->bmm_events as `e`
            ON `s`.`scheme_id` = `e`.`scheme_id`
            WHERE `e`.`event_id` IS NULL OR `e`.`event_id` IS NOT NULL OR `s`.`scheme_id` = $include_scheme
	    ", OBJECT);


        return $schemes;
    }

    /**
     * Get screen details
     * @param $id
     * @param string $output_type
     * @return array|object|void|null
     */
    public function get_scheme_by_id($id, $output_type = OBJECT)
    {
        global $wpdb;

        $scheme = $wpdb->get_row("SELECT * FROM $wpdb->bmm_schemes WHERE scheme_id = " . (int)$id, $output_type);

        return $scheme;
    }

    public function get_cells($scheme_id)
    {
        $places = $this->get_places_with_carts($scheme_id);
        $cells_by_id = array();

        if (!empty($places) && is_array($places)) {
            foreach ($places as $place) {
                $cells = unserialize($place->cells);
                if (!empty($cells) && is_array($cells)) {
                    foreach ($cells as $cell) {
                        foreach ($place as $key => $value) {
                            $cells_by_id[$cell][$key] = $value;
                        }
                    }

                }
            }

        }

        return $cells_by_id;
    }

    /**
     * @param $scheme_id
     * @param string $output_type
     * @return array|object|null
     */
    public function get_places_with_carts($scheme_id, $output_type = OBJECT)
    {
        global $wpdb;

        $places = $wpdb->get_results("SELECT p.*, c.session_id FROM $wpdb->bmm_places AS p LEFT JOIN $wpdb->bmm_carts AS c ON c.place_id=p.place_id WHERE scheme_id = " . (int)$scheme_id, $output_type);

        return $places;
    }

    /**
     * Render the screen layout dynamically
     * @param $id
     * @return string
     */
    public function display_scheme($id)
    {
        $id = (int)trim($id);
        $scheme = $this->get_scheme_by_id($id);

        $cells_by_number = $this->get_cells($id);

        $cell_number = 1; // set initial seat number
        $width = $scheme->width * 30;

        $tooltip_content = '';
        $html = '';
        $html .= "<h2>$scheme->name</h2>";
        $html .= '<ul id="scheme" data-scheme-id="' . $id . '" style="width: ' . $width . 'px;">';
        $places = array();

        for ($i = 1; $i <= $scheme->height; ++$i) { // loop through the rows
            $html .= '<li>';
            $html .= '<ul class="scheme-row clearfix">';
            for ($j = 1; $j <= $scheme->width; ++$j) { // loop through the columns

                $cell_type_class = '';
                $style = '';
                $data_place_id = '';
                $place_id_class = '';
                $place_status_class = '';
                if (isset($cells_by_number[$cell_number]) && !empty($cells_by_number[$cell_number])) {

                    $place = $cells_by_number[$cell_number];

                    $style = 'style="';
                    $style .= 'background-color: ' . $place['color'] . ';';
                    $style .= '"';

                    if (isset($place['type']) && $place['type'] != 0) {
                        $cell_type_class = 'scheme-cell-setted scheme-cell-' . $this->place_types[$place['type']];
                    }

                    $data_place_id = 'data-place-id=' . $place['place_id'];
                    $place_id_class = ' scheme-place-' . $place['place_id'];

                    // if the place is in the cart, check in whose cart
                    $place_status_id = ($place['status_id'] == 3 && $place['session_id'] != $this->session_id) ? 4 : $place['status_id'];
                    $place_status_class = ' scheme-place-' . $this->place_statuses[$place_status_id] . ' ';

                    if (!in_array($place['place_id'], $places)) {
                        $tooltip_content .= '<div id="tooltip-scheme-place-' . $place['place_id'] . '"> Status: ' . $this->place_statuses[$place_status_id];
                        $tooltip_content .= '<br>' . $place['name'];
                        if ($place['status_id'] != 5) {
                            $tooltip_content .= ': &#8377;' . $place['price'];
                        }
                        $tooltip_content .= '<br>' . $place['description'] . '</div>';
                    }


                    $places[$place['place_id']] = $place['place_id'];
                }

                $html .= '<li ' . $style . ' ' . $data_place_id . ' data-x="' . $j . '" data-y="' . $i . '" data-cell="' . $cell_number . '" class="scheme-cell-selectee scheme-cell ' . $cell_type_class . $place_id_class . $place_status_class . '"><span class="number">' . ($cell_number++) . '</span></li>';
            }
            $html .= '</ul>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        $html .= '<div id="scheme-tooltips">' . $tooltip_content . '</div>';

        return $html;
    }

    /**
     * Replace the content of shortcode tag in the post / pages
     * @param $atts
     * @return string
     */

    public function book_my_movie_shortcode($atts)
    {

        $scheme = (!empty($atts['scheme'])) ? $atts['scheme'] : 1;

        $scheme_details = $this->get_scheme_by_id($scheme);

        if (!$scheme_details) {
            $html = '<div id="book-a-place-scheme">';
            $html .= '<h2>There is no such scheme</h2>';
            $html .= '</div>';

            return $html;
        }

        $html = '<div id="book-a-place-scheme">';

        // feedback after offline booking
        if (isset($_GET['s_msg']) && !empty($_GET['s_msg'])) {
            $msg_id = (int)$_GET['s_msg'];
            $html .= '<div id="payment-success">' . $this->success_user_messages[$msg_id] . '</div>';
        }
        if (isset($_GET['e_msg']) && !empty($_GET['e_msg'])) {
            $msg_id = (int)$_GET['e_msg'];
            $html .= '<div id="payment-error">' . $this->error_user_messages[$msg_id] . '</div>';
        }

        $html .= '<h2>' . $scheme_details->name . '</h2>';

        $html .= '<p>' . $scheme_details->description . '</p>';

        if ($scheme_details->is_hidden) {
            $scheme_container_style = ' style="display: none;"';
            $scheme_show_text = __('Show Scheme', $this->plugin_slug);
            $html .= '<a id="scheme-container-visibility" data-visible="0" href="#">' . $scheme_show_text . '</a>';
        } else {
            $scheme_container_style = '';
        }

        $html .= '<div id="scheme-container"' . $scheme_container_style . '>';
        $html .= $this->display_scheme_front($scheme);
        $html .= '</div>';

        $html .= '<div id="shopping-cart-container">';
        //$html .= $this->display_cart($scheme);
        $html .= '</div>';

        $html .= '<div id="shopping-cart-controls-container">';
        //$html .= $this->display_cart_controls();
        $html .= '</div>';

        $html .= '<div id="scheme-warning-message" title="Warning!"></div>';

        $html .= '</div>';

        return $html;
    }

    public function book_my_movie_event_shortcode($atts)
    {
        $id = ($atts['id']) ? $atts['id'] : 1;

        $event = $this->get_event_by_id($id);

        $this->is_event_booking_open($event);

        $scheme = $event->scheme_id;

        $scheme_details = $this->get_scheme_by_id($scheme);

        if (!$scheme_details) {
            $html = '<div id="book-a-place-scheme">';
            $html .= '<h2>There is no such scheme</h2>';
            $html .= '</div>';

            return $html;
        }

        $html = '<div id="book-a-place-scheme">';

        // feedback after offline booking
        if (isset($_GET['s_msg']) && !empty($_GET['s_msg'])) {
            $msg_id = (int)$_GET['s_msg'];
            $html .= '<div id="payment-success">' . $this->success_user_messages[$msg_id] . '</div>';
        }
        if (isset($_GET['e_msg']) && !empty($_GET['e_msg'])) {
            $msg_id = (int)$_GET['e_msg'];
            $html .= '<div id="payment-error">' . $this->error_user_messages[$msg_id] . '</div>';
        }

        $html .= '<h2>' . $event->name . '</h2>';

        $html .= '<p>Start: ' . $event->start . '<br>End: ' . $event->end . '</p>';

        $html .= '<p>' . $event->description . '</p>';

        $html .= '<p><a href="' . $event->url . '">' . $event->url . '</a></p>';

        $html .= '<p><strong>' . $scheme_details->name . '</strong></p>';

        $html .= '<p>' . $scheme_details->description . '</p>';

        if ($scheme_details->is_hidden) {
            $scheme_container_style = ' style="display: none;"';
            $scheme_show_text = 'Show Scheme';
            $html .= '<a id="scheme-container-visibility" data-visible="0" href="#">' . $scheme_show_text . '</a>';
        } else {
            $scheme_container_style = '';
        }

        $html .= '<div id="scheme-container"' . $scheme_container_style . '>';

        $html .= $this->display_scheme_front($scheme, $id);

        $html .= '</div>';

        $html .= '<div id="shopping-cart-container">';
        if ($this->event_booking_open) {
            $html .= $this->display_cart($scheme);
        }
        $html .= '</div>';

        $html .= '<div id="shopping-cart-controls-container">';
        if ($this->event_booking_open) {
            $html .= $this->display_cart_controls();
        }
        $html .= '</div>';

        $html .= '<div id="scheme-warning-message" title="' . __("Warning!", $this->plugin_slug) . '"></div>';

        $html .= '</div>';

        return $html;
    }

    public function display_scheme_front($id, $event_id = 1)
    {
        //$this->delete_expired_carts();

        $html = '';

        if (is_null($this->event_booking_open) && $event_id) {
            $event = $this->get_event_by_id($event_id);
            $this->is_event_booking_open($event);
        }

        if (!is_null($this->event_booking_open)) {
            $html .= '
            <script type="text/javascript">
                var bookAPLaceEventBookingOpen = ' . ($this->event_booking_open ? 1 : 0) . ';
            </script>';
        }

        if (!$event_id) {
            $html .= '
            <script type="text/javascript">
                var bookAPLaceEventBookingOpen = 1;
            </script>';
        }

        if ($this->event_booking_open === false) {
            $html .= '<p>Booking is closed.</p>';
            return $html;
        }

        $id = (int)trim($id);
        $scheme = $this->get_scheme_by_id($id);

        $cells_by_number = $this->get_cells($id);

        $cell_number = 1;
        $width = $scheme->width * 30;

        $tooltip_content = '';
        $html .= '<ul id="scheme" data-event-id="' . $event_id . '" data-scheme-id="' . $id . '" style="width: ' . $width . 'px;">';
        $places = array();

        for ($i = 1; $i <= $scheme->height; ++$i) {
            $html .= '<li>';
            $html .= '<ul class="scheme-row clearfix">';
            for ($j = 1; $j <= $scheme->width; ++$j) {

                $cell_type_class = '';
                $style = '';
                $data_place_id = '';
                $place_id_class = '';
                $place_status_class = '';
                if (isset($cells_by_number[$cell_number]) && !empty($cells_by_number[$cell_number])) {

                    $place = $cells_by_number[$cell_number];

                    $style = 'style="';
                    $style .= 'background-color: ' . $place['color'] . ';';
                    $style .= '"';

                    if (isset($place['type']) && $place['type'] != 0) {
                        $cell_type_class = 'scheme-cell-setted scheme-cell-' . $this->place_types[$place['type']];
                    }

                    $data_place_id = 'data-place-id=' . $place['place_id'];
                    $place_id_class = ' scheme-place-' . $place['place_id'];

                    // if the place is in the cart, check in whose cart
                    if (!empty($place['status_id'])) {
                        $unser_status = unserialize($place['status_id']);
                        $seat_status = array_key_exists($cell_number, $unser_status) ? $unser_status[$cell_number] : 1;
                    } else {
                        $seat_status = 1;
                    }

                    $place_status_id = ($seat_status == 3 && $place['session_id'] != $this->session_id) ? 4 : $seat_status;
                    $place_status_class = ' scheme-place-' . $this->place_statuses[$place_status_id] . ' ';


                    $tooltip_content .= '<div id="tooltip-scheme-place-' . $cell_number . '">Status: ' . $this->place_statuses[$place_status_id];
                    $tooltip_content .= '<br>' . $place['name'];
                    if ($seat_status != 5) {
                        $tooltip_content .= ': &#8377;' . $place['price'];
                    }
                    $tooltip_content .= '<br>' . $place['description'] . '</div>';


                    $places[$place['place_id']] = $place['place_id'];
                }

                $html .= '<li ' . $style . ' ' . $data_place_id . ' data-x="' . $j . '" data-y="' . $i . '" data-cell="' . $cell_number . '" class="scheme-cell-selectee scheme-cell ' . $cell_type_class . $place_id_class . $place_status_class . '"><span class="number">' . $cell_number . '</span></li>';
                $cell_number++;
            }
            $html .= '</ul>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        $html .= '<div id="scheme-tooltips">' . $tooltip_content . '</div>';

        return $html;
    }

    /**
     * @param $id
     * @param string $output_type
     * @return array|object|void|null
     */
    public function get_event_by_id($id, $output_type = OBJECT)
    {
        global $wpdb;
        $event = $wpdb->get_row("SELECT * FROM $wpdb->bmm_events WHERE event_id = " . (int)$id, $output_type);
        return $event;
    }

    /**
     * @param $event
     * @return bool
     */
    private function is_event_booking_open($event)
    {
        if (is_array($event)) {
            $event = json_decode(json_encode($event), FALSE);
        }

        $ts_close_booking = strtotime($event->start) - $event->hours * 3600;
        $ts_now = time() - $event->timezone_offset * 3600;

        if ($ts_now < $ts_close_booking) {
            $this->event_booking_open = true;
            return true;
        } else {
            $this->event_booking_open = false;
            return false;
        }
    }

    /**
     * Add seats to the cart from front end
     */
    public function add_to_cart()
    {

        global $wpdb;

        $scheme_id = (int)$_POST['scheme_id'];

        $session_id = $this->session_id;

        $scheme = $this->get_scheme_by_id($scheme_id);

        $places_ids = $this->get_places_ids_in_cart_by_scheme_id($scheme_id);
        if ($scheme->purchase_limit > 0 && count($places_ids) >= $scheme->purchase_limit) {
            echo "limit";
            die();
        }

        $place_in_cart = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->bmm_carts WHERE place_id = %d", (int)$_POST['seat_id']), ARRAY_A);

        if (!empty($place_in_cart)) {
            echo '0';
            die();
        }

        $save = $wpdb->insert($wpdb->bmm_carts, array(
            'session_id' => $session_id,
            'place_id' => (int)$_POST['place_id'],
            'seat_id' => (int)$_POST['seat_id'],
            'date' => date('Y-m-d H:i:s'),
        ), array(
            '%s',
            '%d',
            '%s'
        ));

        $this->update_cart_time($session_id, $places_ids);

        $update = $this->update_place_status($_POST['place_id'], $_POST['seat_id'], 3);

        $this->refresh_shortcode_content();
        die();

    }

    private function update_cart_time($session_id, $places_ids)
    {
        global $wpdb;
        if (!empty($places_ids)) {
            $in = implode(',', $places_ids);
            $update = $wpdb->query($wpdb->prepare("UPDATE `$wpdb->bmm_carts` SET `date`=%s WHERE `session_id`=%s AND `place_id` IN ($in)", date('Y-m-d H:i:s'), $session_id));
            return $update;
        }
        return;
    }

    private function get_places_ids_in_cart_by_scheme_id($scheme_id)
    {
        $places_in_cart = $this->get_places_in_cart($scheme_id);
        $places_ids = array();
        if (is_array($places_in_cart)) {
            foreach ($places_in_cart as $place) {
                $places_ids[] = $place['seat_id'];
            }

            return $places_ids;
        }

        return false;
    }

    public function get_places_in_cart($scheme_id)
    {
        global $wpdb;
        $places_in_carts = $wpdb->get_results($wpdb->prepare("SELECT c.place_id,c.seat_id, p.name AS place_name, p.scheme_id AS place_scheme_id, p.price AS place_price, c.date FROM $wpdb->bmm_carts AS c LEFT JOIN $wpdb->bmm_places AS p ON c.place_id = p.place_id WHERE session_id = %s", $this->session_id), ARRAY_A);

        $places_in_cart = $this->filter_places_by_scheme_id($places_in_carts, $scheme_id);
        return $places_in_cart;
    }

    private function filter_places_by_scheme_id($places_in_carts, $scheme_id)
    {
        if (!empty($places_in_carts) && is_array($places_in_carts)) {
            $places = array();
            foreach ($places_in_carts as $place) {
                if ($place['place_scheme_id'] == $scheme_id) {
                    $places[] = $place;
                }
            }

            return $places;
        }


        return $places_in_carts;
    }

    /**
     * @param bool $return
     * @return mixed
     */
    public function refresh_shortcode_content($return = false)
    {
        $id = $_POST['scheme_id'];
        $event_id = $_POST['event_id'];

        if ($event_id == 0) {
            $content = $this->book_my_movie_shortcode(array('scheme' => $id));
        } else {
            $content = $this->book_my_movie_event_shortcode(array('id' => $event_id));
        }

        if ($return) {
            return $content;
        } else {
            echo $content;
            die();
        }
    }

    /**
     *
     * @param $place_id
     * @param $status_id
     * @return bool|false|int
     */
    public function update_place_status($place_id, $seat_id = NULL, $status_id)
    {

        if (empty($place_id) || empty($status_id)) return false;

        global $wpdb;

        if (is_array($place_id)) {
            foreach ($place_id as $pid => $place) {
                $status_data = $wpdb->get_row("SELECT status_id FROM $wpdb->bmm_places WHERE place_id=" . (int)$place['place_id'], ARRAY_A);
                $status_data['status_id'] = unserialize($status_data['status_id']);

                if (array_key_exists($place['seat_id'], $status_data['status_id'])) {
                    $status_data['status_id'][$place['seat_id']] = $status_id;
                } else {
                    $status_data['status_id'][$place['seat_id']] = $status_id;
                }
                $status_data = serialize($status_data['status_id']);

                $update = $wpdb->update($wpdb->bmm_places, array('status_id' => $status_data,), array('place_id' => (int)$place['place_id']), array('%s',), array('%d'));

            }


        } else {
            $status_data = $wpdb->get_row("SELECT status_id FROM $wpdb->bmm_places WHERE place_id=" . (int)$place_id, ARRAY_A);
            $status_data['status_id'] = unserialize($status_data['status_id']);
            if (array_key_exists($seat_id, $status_data['status_id'])) {
                $status_data['status_id'][$seat_id] = $status_id;
            } else {
                $status_data['status_id'][$seat_id] = $status_id;
            }
            $status_data = serialize($status_data['status_id']);
            $update = $wpdb->update($wpdb->bmm_places, array('status_id' => $status_data,), array('place_id' => (int)$place_id), array('%s',), array('%d'));

        }

        return $update;
    }

    /**
     * refresh the screen layout on seat selections
     */
    public function refresh_scheme_front()
    {
        $id = $_POST['scheme_id'];
        $event_id = $_POST['event_id'];

        $scheme = $this->display_scheme_front($id, $event_id);

        echo $scheme;
        die();
    }

    /**
     * @param $scheme_id
     * @return string
     */
    public function display_cart($scheme_id)
    {
        $scheme = $this->get_scheme_by_id($scheme_id);

        $places_in_cart = $this->get_places_in_cart($scheme_id);

        //$time_left = $this->calculate_time_left($places_in_cart);

        $html = '<h3>Cart"</h3>';

        if ($scheme->purchase_limit > 0) {
            $places_in_cart_remainder = $scheme->purchase_limit - count($places_in_cart);
            $html .= '
                <p>
                    The number of places you are allowed to add: ' . $places_in_cart_remainder . '
                </p>
            ';
        }

        $html .= '
    <table class="table">
    <thead>
    <tr>
        <th>#</th>
        <th>Class</th>   
        <th>Seat Number</th>   
        <th>Price</th>
        <th></th>
    </tr>
    </thead>
    <tbody>';

        $total_price = 0;
        if ($places_in_cart && is_array($places_in_cart)) {
            foreach ($places_in_cart as $key => $place) {
                ++$key;
                $total_price += $place['place_price'];
                $html .= "<tr class='bap-place-in-cart'>
        <td>$key</td>
        <td>{$place['place_name']}</td>
        <td>{$place['seat_id']}</td>
        <td>&#x20B9 {$place['place_price']}</td>
        <td><a class='delete_from_cart' data-place-id='{$place['place_id']}' data-seat-id='{$place['seat_id']}' href='#'>Delete</a></td>
    </tr>";
            }

        } else {
            $html .= '<tr>
        <td colspan="5">There are no places in the cart.</td>
    </tr>';
        }

        $html .= "</tbody>
    <tfoot>
    <tr>
        <th colspan='1' class=''></th>
        <th colspan='2' class=''>Total Price</th>
        <th colspan='2'>&#x20B9 {$total_price}</th>
    </tr>
    </tfoot>
</table>";

        return $html;
    }


    /**
     * Delete items from the cart
     */
    public function delete_from_cart()
    {
        global $wpdb;

        $session_id = $this->session_id;

        $delete = $wpdb->delete($wpdb->bmm_carts, array(
            'session_id' => $session_id,
            'place_id' => (int)$_POST['place_id'],
            'seat_id' => (int)$_POST['seat_id'],
        ), array(
            '%s',
            '%d',
            '%d',
        ));

        $scheme_id = (int)$_POST['scheme_id'];

        $places_ids = $this->get_places_ids_in_cart_by_scheme_id($scheme_id);

        $this->update_cart_time($session_id, $places_ids);

        $update = $this->update_place_status($_POST['place_id'], $_POST['seat_id'], 1);

        $this->refresh_shortcode_content();
        die();
    }

    /**
     * @return string
     */
    public function display_cart_controls()
    {
        $html = '<div id="cart-controls">
    <input type="submit" id="cart-checkout" value="Checkout" />
</div>';

        $html .= '<div id="bap-cart-form-dialog" title="Checkout">
    <p class="validateTips">' . sprintf('Fields with %s are required.', '<span class="required">*</span>') . '</p>

    <form>
        <fieldset>
            <input type="hidden" name="action" value=""/>
            <div class="field">
            <label for="checkout-first-name">First Name <span class="required">*</span></label>
            <input type="text" name="checkout-first-name" id="checkout-first-name" class="text"/>
            </div>
            <div class="field">
            <label for="checkout-last-name">Last Name <span class="required">*</span></label>
            <input type="text" name="checkout-last-name" id="checkout-last-name" value="" class="text"/>
            </div>
            <div class="field">
            <label for="checkout-email">Email <span class="required">*</span></label>
            <input type="text" name="checkout-email" id="checkout-email" value="" class="text"/>
            </div>
            <div class="field">
            <label for="checkout-phone">Phone<span class="required">*</span></label>
            <input type="text" name="checkout-phone" id="checkout-phone" value="" class="text"/>
            <p class="input-notice">Only digits, e.g. 15417543010</p>
            </div>
             <div class="field" style="width: 100%">
            <label for="checkout-notes">Notes</label>
            <textarea name="checkout-notes" id="checkout-notes" class="text"></textarea>
            </div>
            <div class="field1" >
            <input type="button" value="Checkout" id="checkout-button" style="margin:10px 0 10px" />
            <input type="button" value="Cancel" id="checkout-cancle-button"  style="margin-right:20px;" />
            </div>
        </fieldset>
    </form>
</div>';

        $html .= '<div id="bap-empty-cart-dialog" title="Checkout">
    <p class="validateTips">Please add to your cart at least one place.</p>
    <input type="button" id="emptydialogbutton" value="ok" />
    </div>';

        return $html;
    }

    /**
     * @return bool
     */
    public function checkout()
    {
        global $wpdb;

        // event verifications
        if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
            $event = $this->get_event_by_id($_POST['event_id'], ARRAY_A);
            if (!$this->is_event_booking_open($event)) return false;
        }

        $scheme_id = (int)$_POST['scheme_id'];

        $places_in_cart = $this->get_places_in_cart($scheme_id);
        $places_list = array();
        $total_price = 0;

        if ($places_in_cart && is_array($places_in_cart)) {
            foreach ($places_in_cart as $place) {
                $places_list[] = array(
                    'place_id' => $place['place_id'],
                    'place_name' => $place['place_name'],
                    'place_price' => $place['place_price'],
                    'seat_id' => $place['seat_id'],
                );
                $total_price += $place['place_price'];
            }
        } else {
            return false;
        }

        $event_id = '';
        $event_name = '';
        if ($event) {
            $event_id = $event['event_id'];
            $event_name = $event['name'];
        }


        $save_order = $wpdb->insert($wpdb->bmm_orders, array(
            'first_name' => stripslashes($_POST['first_name']),
            'last_name' => stripslashes($_POST['last_name']),
            'email' => stripslashes($_POST['email']),
            'phone' => stripslashes($_POST['phone']),
            'notes' => stripslashes($_POST['notes']),
            'date' => current_time('mysql'),
            'places' => serialize($places_list),
            'total_price' => $total_price,
            'status_id' => 1,
            'scheme_id' => $scheme_id,
            'event_id' => $event_id,
            'event_name' => $event_name,
            'event' => serialize($event),
        ), array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%f',
            '%d',
            '%d',
            '%d',
            '%s',
            '%s'
        ));

        if ($save_order) {
            $order_id = $wpdb->insert_id;
            $this->generate_order_code($order_id);

            $update_place_status = $this->update_place_status($places_list, NULL, 2);

            $clear_cart = $this->clear_cart($places_list);

            $order = $this->get_order_by_id($order_id);

        }

        return $save_order && $update_place_status && $clear_cart;


    }

    /**
     * ajax call for cart checkout
     */
    public function ajax_checkout()
    {

        $isCheckoutValid = $this->checkout_validation();
        if ($isCheckoutValid === false) {
            echo json_encode(array(
                'error' => 'limit',
                'error_message' => __('You\'ve already reached the purchasing limit for this event', $this->plugin_slug)
            ));
            die;
        }

        $checkout = $this->checkout();

        $out = $this->refresh_shortcode_content(true);

        if (!$checkout) {
            $out['msg'] = 'e_msg=1';
        } else {
            $out['msg'] = 's_msg=1';
        }

        echo json_encode($out);
        die();

    }

    /**
     * @return bool
     * @since 0.7.0
     */
    public function checkout_validation()
    {
        $scheme = $this->get_scheme_by_id($_POST['scheme_id']);

        if ($scheme->purchase_limit > 0) {
            $user_booked_places_count = 0;
            $orders = $this->get_orders_by_email_and_scheme_id($_POST['email'], $_POST['scheme_id']);
            if (is_array($orders) && !empty($orders)) {
                foreach ($orders as $order) {
                    $order_places = unserialize($order->places);
                    $user_booked_places_count += count($order_places);
                }
            }

            if ($user_booked_places_count >= $scheme->purchase_limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $order_id
     * @return false|int
     */
    public function generate_order_code($order_id)
    {
        $order_code = substr($order_id . md5($order_id), 0, 8);
        global $wpdb;
        return $wpdb->update($wpdb->bmm_orders, array('code' => $order_code,), array('order_id' => $order_id), array('%s',), array('%d'));
    }

    /**
     * @param $places_ids
     * @param bool $session_id
     * @return array|object|null
     */
    public function clear_cart($places_ids, $session_id = false)
    {
        if (!$session_id) {
            $session_id = $this->session_id;
        }

        global $wpdb;
        foreach ($places_ids as $key => $val) {
            $delete = $wpdb->get_results($wpdb->prepare("DELETE FROM $wpdb->bmm_carts WHERE session_id = %s AND place_id = %d AND seat_id = %d", $session_id, $val['place_id'], $val['seat_id']), ARRAY_A);

        }

        return $delete;
    }

    /**
     * @param $order_id
     * @return array|object|void|null
     *
     */
    public function get_order_by_id($order_id)
    {
        global $wpdb;

        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->bmm_orders WHERE order_id = %d", (int)$order_id));

        return $order;
    }

    public function get_orders($scheme_id = NULL, $event_id = NULL)
    {
        global $wpdb;

        $orders = $wpdb->get_results("
        SELECT *
        FROM `{$wpdb->bmm_orders}`
        WHERE scheme_id = {$scheme_id} AND event_id = {$event_id}
        ORDER BY `order_id` DESC
	    ");

        return $orders;
    }

    public function get_scheme_by_place_id($id, $output_type = OBJECT)
    {
        global $wpdb;

        $place = $this->get_place_by_id($id);

        $scheme = $wpdb->get_row("SELECT * FROM $wpdb->bap_schemes WHERE scheme_id = " . (int)$place['scheme_id'], $output_type);

        return $scheme;
    }
}