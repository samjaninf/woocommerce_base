<?php
if (!defined('ABSPATH'))
    die("Can't load this file directly");

class wpie_import_export {

    function __construct() {
        register_activation_hook(WPIE_PLUGIN_DIR . '/woo-import-export.php', array('wpie_import_export', 'wpie_install_data'));

        register_uninstall_hook(WPIE_PLUGIN_DIR . '/woo-import-export.php', array('wpie_import_export', 'wpie_uninstall'));

        global $woocommerce;

        $plugins = get_option('active_plugins');

        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $required_woo_plugin = 'woocommerce/woocommerce.php';

        if (in_array($required_woo_plugin, $plugins) || is_plugin_active_for_network($required_woo_plugin)) {

            if (class_exists('Woocommerce')) {
                $this->wpie_set_action();
            } else {
                add_action('woocommerce_loaded', array(&$this, 'wpie_set_action'));
            }
        }
    }

    function wpie_set_action() {
        add_action('plugins_loaded', array(&$this, 'wpie_load_textdomain'));

        add_action('admin_enqueue_scripts', array(&$this, 'wpie_set_admin_css'), 10);

        add_action('admin_enqueue_scripts', array(&$this, 'wpie_set_admin_js'), 10);

        add_action('admin_menu', array(&$this, 'wpie_set_menu'));

        add_action('admin_init', array(&$this, 'wpie_db_check'));

        add_action('admin_head', array($this, 'wpie_hide_all_notice_to_admin_side'), 10000);

        add_filter('admin_footer_text', array(&$this, 'wpie_replace_footer_admin'));

        add_filter('update_footer', array(&$this, 'wpie_replace_footer_version'), '1234');

        add_action('wp_ajax_wpie_deactivate_license', array(&$this, 'wpie_deactivate_license'));

        add_action('wp_ajax_wpie_activate_license', array(&$this, 'wpie_activate_license'));

        add_action('init', array(&$this, 'wpie_download_exported_data'));
    }

    function wpie_install_data() {

        $wpie_plugin_version = get_option('wpie_plugin_version');

        if (!isset($wpie_plugin_version) || $wpie_plugin_version == '') {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            global $wpdb, $wpie_product, $wpie_order, $wpie_user, $wpie_product_cat, $wpie_coupon;

            $charset_collate = '';

            if ($wpdb->has_cap('collation')) {

                if (!empty($wpdb->charset))
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

                if (!empty($wpdb->collate))
                    $charset_collate .= " COLLATE $wpdb->collate";
            }

            update_option('wpie_plugin_version', WPIE_PLUGIN_VERSION);

            $export_log = $wpdb->prefix . 'wpie_export_log';

            $export_log_table = "CREATE TABLE IF NOT EXISTS {$export_log}(
							
									 export_log_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
									 export_log_file_type VARCHAR(25) NOT NULL, 
									 export_log_file_name VARCHAR(50) NOT NULL,
									 export_log_data VARCHAR(50) NOT NULL,
									 create_date DATETIME NOT NULL 
									 
									 ){$charset_collate}";

            dbDelta($export_log_table);

            $product_fields = $wpie_product->get_new_product_fields();

            update_option('wpie_product_fields', $product_fields);

            $order_fields = $wpie_order->get_new_order_fields();

            update_option('wpie_order_fields', $order_fields);

            $user_fields = $wpie_user->get_new_user_fields();

            update_option('wpie_user_fields', $user_fields);

            $product_cat_fields = $wpie_product_cat->get_new_product_cat_fields();

            update_option('wpie_product_cat_fields', $product_cat_fields);

            $coupon_fields = $wpie_coupon->get_new_coupon_fields();

            update_option('wpie_coupon_fields', $coupon_fields);
        }
    }

    function wpie_deactivate_license() {
        global $wpie_import_export;

        $site_data = array();

        $return_value = array();

        $return_value['message'] = 'error';

        $new_plugin_code = $wpie_import_export->generate_plugin_code();

        $plugin_data = $wpie_import_export->get_wpie_sort_order();

        $site_data['plugin_info'] = $plugin_data;

        $site_data['plugin_data'] = $new_plugin_code;

        $site_data['plugin_url'] = WPIE_PLUGIN_URL;

        $site_data['plugin_version'] = get_option("wpie_plugin_version");

        $site_data['plugin_status'] = 'deactive';

        $valstring = maybe_serialize($site_data);

        $post_data = base64_encode($valstring);

        $response = wp_remote_post(WPIE_PLUGIN_SITE, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array(),
            'body' => array('wae_deacivate_license' => $post_data),
            'cookies' => array()
                )
        );

        if (array_key_exists('body', $response) && isset($response["body"]) && $response["body"] != "") {
            $response_result = @maybe_unserialize(base64_decode($response["body"]));

            if ($response_result{'verify'} == 'deactivate_product_success') {
                update_option('wpie_sort_order', $post_data);

                $return_value['message'] = 'success';

                $return_value['message_content'] = __('License Deactivated Successfully.', WPIE_TEXTDOMAIN);
            } else {
                $return_value['message_content'] = __('Invalid Request Data.', WPIE_TEXTDOMAIN);
            }
        }

        echo json_encode($return_value);

        die();
    }

    function wpie_db_check() {
        global $wpie_import_export;

        $wpie_plugin_version = get_option('wpie_plugin_version');

        if ((!isset($wpie_plugin_version) || $wpie_plugin_version == '') && is_multisite()) {
            $wpie_import_export->wpie_install_data();
        }
    }

    function wpie_uninstall() {

        global $wpdb, $wpie_scheduled;
        if (is_multisite()) {
            $blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
            if ($blogs) {

                foreach ($blogs as $blog) {

                    switch_to_blog($blog['blog_id']);

                    delete_option('wpie_plugin_version');

                    delete_option('wpie_sort_order');

                    delete_option('wpie_product_scheduled_data');

                    delete_option('wpie_order_scheduled_data');

                    delete_option('wpie_user_scheduled_data');

                    delete_option('wpie_product_fields');

                    delete_option('wpie_product_cat_fields');

                    delete_option('wpie_order_fields');

                    delete_option('wpie_user_fields');

                    delete_option('wpie_coupon_fields');

                    $wpie_export_log = $wpdb->prefix . 'wpie_export_log';

                    $wpdb->query("DROP TABLE IF EXISTS $wpie_export_log");

                    $wpie_scheduled->wpie_delete_all_cron();
                }
                restore_current_blog();
            }
        } else {
            delete_option('wpie_plugin_version');

            delete_option('wpie_sort_order');

            delete_option('wpie_product_scheduled_data');

            delete_option('wpie_order_scheduled_data');

            delete_option('wpie_user_scheduled_data');

            delete_option('wpie_product_fields');

            delete_option('wpie_product_cat_fields');

            delete_option('wpie_order_fields');

            delete_option('wpie_user_fields');

            delete_option('wpie_coupon_fields');

            $wpie_export_log = $wpdb->prefix . 'wpie_export_log';

            $wpdb->query("DROP TABLE IF EXISTS $wpie_export_log");

            $wpie_scheduled->wpie_delete_all_cron();
        }
    }

    function wpie_load_textdomain() {
        load_plugin_textdomain(WPIE_TEXTDOMAIN, false, 'woo-import-export/languages/');
    }

    function wpie_activate_license() {
        global $wpie_product, $wpie_import_export;

        $wpie_product->wpie_set_time_limit(0);

        $site_data = array();

        $return_value = array();

        $return_value['message'] = 'error';

        $site_data['customer_name'] = $_POST["wpie_customer_name"];

        $site_data['customer_email'] = $_POST["wpie_customer_email"];

        $site_data['purchase_code'] = $_POST["wpie_customer_purchase_code"];

        $site_data['domain_name'] = $_POST["wpie_product_domain_name"];

        if (!isset($_POST["wpie_product_domain_name"]) || $_POST["wpie_product_domain_name"] == "" || $_SERVER["HTTP_HOST"] != $_POST["wpie_product_domain_name"]) {

            $return_value['message_content'] = 'Invalid Host Name';

            echo json_encode($return_value);

            die();
        }

        $new_plugin_code = $wpie_import_export->generate_plugin_code();

        $site_data['plugin_data'] = $new_plugin_code;

        $site_data['plugin_url'] = WPIE_PLUGIN_URL;

        $site_data['plugin_version'] = get_option("wpie_plugin_version");

        $site_data['plugin_status'] = 'active';

        $valstring = maybe_serialize($site_data);

        $post_data = base64_encode($valstring);

        $response = wp_remote_post(WPIE_PLUGIN_SITE, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array(),
            'body' => array('verify_product_purchase' => $post_data),
            'cookies' => array()
                )
        );
        if (array_key_exists('body', $response) && isset($response["body"]) && $response["body"] != "") {
            $response_result = @maybe_unserialize(base64_decode($response["body"]));

            if (isset($response_result{'verify'}) && $response_result{'verify'} == 'purchase_code_verify_success') {
                update_option('wpie_sort_order', $post_data);

                $return_value['message'] = 'success';

                $return_value['message_content'] = __('License Activated Successfully.', WPIE_TEXTDOMAIN);
            } else if (isset($response_result{'verify'}) && $response_result{'verify'} == 'purchase_code_verify_other_sites') {
                $return_value['message_content'] = __('License already activated on other site.', WPIE_TEXTDOMAIN);
            } else if (isset($response_result{'verify'}) && $response_result{'verify'} == 'purchase_code_verify_error') {
                $return_value['message_content'] = __('Invalid Purchase code.', WPIE_TEXTDOMAIN);
            } else {
                $return_value['message_content'] = __('Invalid Request Data.', WPIE_TEXTDOMAIN);
            }
        }

        echo json_encode($return_value);

        die();
    }

    function wpie_set_admin_css() {
        wp_register_style('wpie_admin_css', WPIE_CSS_URL . '/wpie_admin.css');

        //wp_register_style('wpie_admin_chosen_css', WPIE_CURRENT_PLUGIN_URL . '/woocommerce/assets/css/chosen.css');

        wp_register_style('wpie_admin_chosen_css', WPIE_CSS_URL . '/chosen.css');

        wp_register_style('wpie_admin_flexi_grid_css', WPIE_CSS_URL . '/wpie_flexigrid.css');

        wp_register_style('wpie_admin_jquery_ui_css', WPIE_CSS_URL . '/jquery-ui.css');

        if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wpie-woo-export' || $_REQUEST['page'] == 'wpie-woo-import' || $_REQUEST['page'] == 'wpie-woo-log' || $_REQUEST['page'] == 'wpie-settings' || $_REQUEST['page'] == 'wpie-woo-scheduled-export')) {
            wp_enqueue_style('wpie_admin_css');
        }
        if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wpie-woo-export')) {
            wp_enqueue_style('wpie_admin_chosen_css');
            wp_enqueue_style('wpie_admin_flexi_grid_css');
            wp_enqueue_style('wpie_admin_jquery_ui_css');
        }
        if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wpie-woo-import')) {
            wp_enqueue_style('wpie_admin_chosen_css');
            wp_enqueue_style('wpie_admin_flexi_grid_css');
        }
        if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wpie-woo-scheduled-export')) {
            wp_enqueue_style('wpie_admin_flexi_grid_css');
        }
    }

    function generate_plugin_code() {
        $site_info = array();

        $site_info['blog_name'] = get_bloginfo('name');

        $site_info['description'] = get_bloginfo('description');

        $site_info['site_home_url'] = home_url();

        $site_info['admin_email'] = get_bloginfo('admin_email');

        $site_info['server_addr'] = $_SERVER['SERVER_ADDR'];

        $new_str = implode("^|^", $site_info);

        $post_val = base64_encode($new_str);

        return $post_val;
    }

    function wpie_set_admin_js() {
        wp_register_script('wpie_admin_js', WPIE_JS_URL . '/wpie_admin.js');

        //wp_register_script('wpie_admin_chosen_js', WPIE_CURRENT_PLUGIN_URL . '/woocommerce/assets/js/chosen/chosen.jquery.min.js');

        wp_register_script('wpie_admin_chosen_js', WPIE_JS_URL . '/chosen.jquery.min.js');

        wp_register_script('wpie_admin_flexi_grid_js', WPIE_JS_URL . '/wpie_flexigrid.js');

        wp_register_script('wpie_form_min_js', WPIE_JS_URL . '/wpie.form.min.js');


        if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wpie-woo-export' || $_REQUEST['page'] == 'wpie-woo-import' || $_REQUEST['page'] == 'wpie-woo-log' || $_REQUEST['page'] == 'wpie-settings' || $_REQUEST['page'] == 'wpie-woo-scheduled-export')) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('wpie_admin_js');
        }
        if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wpie-woo-export')) {
            wp_enqueue_script('jquery-ui-datepicker');

            wp_enqueue_script('wpie_admin_flexi_grid_js');

            wp_enqueue_script('wpie_admin_chosen_js');

            wp_enqueue_script('wpie_admin_flexi_grid_js');
        }
        if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wpie-woo-import')) {
            wp_enqueue_script('wpie_admin_chosen_js');

            wp_enqueue_script('wpie_form_min_js');

            wp_enqueue_script('wpie_admin_flexi_grid_js');
        }
        if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wpie-woo-scheduled-export')) {

            wp_enqueue_script('wpie_admin_flexi_grid_js');
        }
    }

    function wpie_hide_all_notice_to_admin_side() {
        if (isset($_REQUEST['page']) && ($_REQUEST['page'] == 'wpie-woo-export' || $_REQUEST['page'] == 'wpie-woo-import' || $_REQUEST['page'] == 'wpie-woo-log' || $_REQUEST['page'] == 'wpie-settings')) {
            remove_all_actions('admin_notices', 10000);
            remove_all_actions('all_admin_notices', 10000);
            remove_all_actions('network_admin_notices', 10000);
            remove_all_actions('user_admin_notices', 10000);
        }
    }

    function wpie_set_menu() {
        global $wpie_import_export, $current_user;

        if (current_user_can('administrator') || is_super_admin()) {
            $wpie_caps = $wpie_import_export->wpie_capabilities();

            foreach ($wpie_caps as $wpie_cap => $cap_desc) {
                $current_user->add_cap($wpie_cap);
            }
        }

        $menu_place = $wpie_import_export->get_dynamic_position(28.1, .1);

        add_menu_page(__('Import Export', WPIE_TEXTDOMAIN), __('Woo Imp Exp', WPIE_TEXTDOMAIN), 'wpie_export', 'wpie-woo-export', array(&$this, 'wpie_get_page'), WPIE_IMAGES_URL . '/wpie_logo.png', (string) $menu_place);

        add_submenu_page('wpie-woo-export', __('Export', WPIE_TEXTDOMAIN), __('Export', WPIE_TEXTDOMAIN), 'wpie_export', 'wpie-woo-export', array(&$this, 'wpie_get_page'));

        add_submenu_page('wpie-woo-export', __('Import', WPIE_TEXTDOMAIN), __('Import', WPIE_TEXTDOMAIN), 'wpie_import', 'wpie-woo-import', array(&$this, 'wpie_get_page'));

        add_submenu_page('wpie-woo-export', __('Manage Scheduled', WPIE_TEXTDOMAIN), __('Manage Scheduled', WPIE_TEXTDOMAIN), 'wpie_manage_scheduled_export', 'wpie-woo-scheduled-export', array(&$this, 'wpie_get_page'));

        add_submenu_page('wpie-woo-export', __('Export Log', WPIE_TEXTDOMAIN), __('Export Log', WPIE_TEXTDOMAIN), 'wpie_manage', 'wpie-woo-log', array(&$this, 'wpie_get_page'));

        add_submenu_page('wpie-woo-export', __('Settings', WPIE_TEXTDOMAIN), __('Settings', WPIE_TEXTDOMAIN), 'wpie_settings', 'wpie-settings', array(&$this, 'wpie_get_page'));
    }

    function wpie_get_page() {
        global $wpie_import_export;

        if (isset($_REQUEST['page']) and $_REQUEST['page'] == 'wpie-woo-export') {

            $wpie_import_export->wpie_get_export();
        } else if (isset($_REQUEST['page']) and $_REQUEST['page'] == 'wpie-woo-import') {

            $wpie_import_export->wpie_get_import();
        } else if (isset($_REQUEST['page']) and $_REQUEST['page'] == 'wpie-woo-scheduled-export') {

            $wpie_import_export->wpie_get_scheduled_export();
        } else if (isset($_REQUEST['page']) and $_REQUEST['page'] == 'wpie-woo-log') {

            $wpie_import_export->wpie_get_product_log();
        } else if (isset($_REQUEST['page']) and $_REQUEST['page'] == 'wpie-settings') {

            $wpie_import_export->wpie_get_settings();
        }

        global $WPIE_AJAXURL;
        ?>
        <script type="text/javascript">var wpie_ajax_url = "<?php echo $WPIE_AJAXURL; ?>";</script>
        <?php
    }

    function wpie_capabilities() {
        $cap = array(
            'wpie_export' => __('manage woocommerce export.', WPIE_TEXTDOMAIN),
            'wpie_import' => __('manage woocommerce import.', WPIE_TEXTDOMAIN),
            'wpie_manage' => __('manage woocommerce import/export.', WPIE_TEXTDOMAIN),
            'wpie_manage_scheduled_export' => __('manage scheduled export.', WPIE_TEXTDOMAIN),
            'wpie_settings' => __('manage woocommerce import/export settings.', WPIE_TEXTDOMAIN),
        );

        return $cap;
    }

    function get_dynamic_position($start, $increment = 0.1) {
        foreach ($GLOBALS['menu'] as $key => $menu) {
            $menus_positions[] = $key;
        }
        if (!in_array($start, $menus_positions))
            return $start;

        while (in_array($start, $menus_positions)) {
            $start += $increment;
        }
        return $start;
    }

    function wpie_get_export() {
        if (file_exists(WPIE_VIEW_DIR . '/wpie_export.php')) {
            include( WPIE_VIEW_DIR . '/wpie_export.php' );
        }
    }

    function wpie_get_import() {
        if (file_exists(WPIE_VIEW_DIR . '/wpie_import.php')) {
            include( WPIE_VIEW_DIR . '/wpie_import.php' );
        }
    }

    function wpie_get_scheduled_export() {
        if (file_exists(WPIE_VIEW_DIR . '/wpie_scheduled_export.php')) {
            include( WPIE_VIEW_DIR . '/wpie_scheduled_export.php' );
        }
    }

    function wpie_get_product_log() {
        if (file_exists(WPIE_VIEW_DIR . '/wpie_export_log.php')) {
            include( WPIE_VIEW_DIR . '/wpie_export_log.php' );
        }
    }

    function wpie_get_settings() {
        if (file_exists(WPIE_VIEW_DIR . '/wpie_settings.php')) {
            include( WPIE_VIEW_DIR . '/wpie_settings.php' );
        }
    }

    function wpie_replace_footer_admin() {
        echo '';
    }

    function wpie_replace_footer_version() {
        return '';
    }

    function get_wpie_sort_order() {
        $wpie_sort_order = get_option('wpie_sort_order');

        if ($wpie_sort_order && $wpie_sort_order != "") {
            return @maybe_unserialize(base64_decode($wpie_sort_order));
        } else {
            return "";
        }
    }

    function wpie_set_time_limit($time) {
        $safe_mode = ini_get('safe_mode');

        if (!$safe_mode or $safe_mode == 'Off' or $safe_mode == 'off' or $safe_mode == 'OFF') {
            @set_time_limit($time);
        }
    }

    function wpie_download_exported_data() {
        if (isset($_POST['wpie_product_export_verify']) && $_POST['wpie_product_export_verify'] == 1 && !isset($_POST['action'])) {

            global $wpie_product, $wpdb;

            $filename = 'product_' . date('Y_m_d_H_i_s') . '.csv';

            $product_export_data = $wpie_product->get_product_export_data($_POST);

            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');

            header('Content-Description: File Transfer');

            header('Content-Type: text/csv;');

            header('Content-Disposition: attachment; filename=' . $filename);

            header('Expires:0');

            header('Pragma: public');

            $fh = @fopen(WPIE_UPLOAD_DIR . '/' . $filename, 'w+');

            foreach ($product_export_data as $new_data) {
                @fputcsv($fh, $new_data);
            }

            @fclose($fh);

            readfile(WPIE_UPLOAD_DIR . '/' . $filename);

            $new_values = array();

            $new_values['export_log_file_type'] = 'csv';
            $new_values['export_log_file_name'] = $filename;
            $new_values['export_log_data'] = 'Product';
            $new_values['create_date'] = current_time('mysql');

            $res = $wpdb->insert($wpdb->prefix . "wpie_export_log", $new_values);

            die();
        } else if (isset($_POST['wpie_download_exported_file']) && $_POST['wpie_download_exported_file'] != "" && !isset($_POST['action'])) {

            $filename = $_POST['wpie_download_exported_file'];

            if (file_exists(WPIE_UPLOAD_DIR . '/' . $filename)) {

                header('Cache-Control:must-revalidate,post-check=0,pre-check=0');

                header('Content-Description: File Transfer');

                header('Content-Type: text/csv;');

                header('Content-Disposition: attachment; filename=' . $filename);

                header('Expires:0');

                header('Pragma: public');

                readfile(WPIE_UPLOAD_DIR . '/' . $filename);
            }

            die();
        } else if (isset($_POST['wpie_ordert_export_verify']) && $_POST['wpie_ordert_export_verify'] == 1 && !isset($_POST['action'])) {

            global $wpie_order, $wpdb;

            $filename = 'order_' . date('Y_m_d_H_i_s') . '.csv';

            $order_export_data = $wpie_order->get_order_csv_data($_POST);

            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');

            header('Content-Description: File Transfer');

            header('Content-Type: text/csv;');

            header('Content-Disposition: attachment; filename=' . $filename);

            header('Expires:0');

            header('Pragma: public');

            $fh = @fopen(WPIE_UPLOAD_DIR . '/' . $filename, 'w+');

            foreach ($order_export_data as $new_data) {
                @fputcsv($fh, $new_data);
            }

            @fclose($fh);

            readfile(WPIE_UPLOAD_DIR . '/' . $filename);

            $new_values = array();

            $new_values['export_log_file_type'] = 'csv';
            $new_values['export_log_file_name'] = $filename;
            $new_values['export_log_data'] = 'Order';
            $new_values['create_date'] = current_time('mysql');

            $res = $wpdb->insert($wpdb->prefix . "wpie_export_log", $new_values);

            die();
        } else if (isset($_POST['wpie_user_export_verify']) && $_POST['wpie_user_export_verify'] == 1 && !isset($_POST['action'])) {

            global $wpie_user, $wpdb;

            $filename = 'user_' . date('Y_m_d_H_i_s') . '.csv';

            $user_export_data = $wpie_user->get_user_csv_data($_POST);

            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');

            header('Content-Description: File Transfer');

            header('Content-Type: text/csv;');

            header('Content-Disposition: attachment; filename=' . $filename);

            header('Expires:0');

            header('Pragma: public');

            $fh = @fopen(WPIE_UPLOAD_DIR . '/' . $filename, 'w+');

            foreach ($user_export_data as $new_data) {
                @fputcsv($fh, $new_data);
            }

            @fclose($fh);

            readfile(WPIE_UPLOAD_DIR . '/' . $filename);

            $new_values = array();

            $new_values['export_log_file_type'] = 'csv';
            $new_values['export_log_file_name'] = $filename;
            $new_values['export_log_data'] = 'User';
            $new_values['create_date'] = current_time('mysql');

            $res = $wpdb->insert($wpdb->prefix . "wpie_export_log", $new_values);

            die();
        } else if (isset($_POST['wpie_product_cat_export_verify']) && $_POST['wpie_product_cat_export_verify'] == 1 && !isset($_POST['action'])) {

            global $wpie_product, $wpdb, $wpie_product_cat;

            $filename = 'product_category_' . date('Y_m_d_H_i_s') . '.csv';

            $product_cat_export_data = $wpie_product_cat->get_product_cat_export_data($_POST);

            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');

            header('Content-Description: File Transfer');

            header('Content-Type: text/csv;');

            header('Content-Disposition: attachment; filename=' . $filename);

            header('Expires:0');

            header('Pragma: public');

            $fh = @fopen(WPIE_UPLOAD_DIR . '/' . $filename, 'w+');

            foreach ($product_cat_export_data as $new_data) {
                @fputcsv($fh, $new_data);
            }

            @fclose($fh);

            readfile(WPIE_UPLOAD_DIR . '/' . $filename);

            $new_values = array();

            $new_values['export_log_file_type'] = 'csv';
            $new_values['export_log_file_name'] = $filename;
            $new_values['export_log_data'] = 'Product Category';
            $new_values['create_date'] = current_time('mysql');

            $res = $wpdb->insert($wpdb->prefix . "wpie_export_log", $new_values);

            die();
        } else if (isset($_POST['wpie_coupon_export_verify']) && $_POST['wpie_coupon_export_verify'] == 1 && !isset($_POST['action'])) {

            global $wpie_product, $wpdb, $wpie_coupon;

            $filename = 'coupon_' . date('Y_m_d_H_i_s') . '.csv';

            $coupon_export_data = $wpie_coupon->get_coupon_export_data($_POST);

            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');

            header('Content-Description: File Transfer');

            header('Content-Type: text/csv;');

            header('Content-Disposition: attachment; filename=' . $filename);

            header('Expires:0');

            header('Pragma: public');

            $fh = @fopen(WPIE_UPLOAD_DIR . '/' . $filename, 'w+');

            foreach ($coupon_export_data as $new_data) {
                @fputcsv($fh, $new_data);
            }

            @fclose($fh);

            readfile(WPIE_UPLOAD_DIR . '/' . $filename);

            $new_values = array();

            $new_values['export_log_file_type'] = 'csv';
            $new_values['export_log_file_name'] = $filename;
            $new_values['export_log_data'] = 'Coupon';
            $new_values['create_date'] = current_time('mysql');

            $res = $wpdb->insert($wpdb->prefix . "wpie_export_log", $new_values);

            die();
        }
    }

}
?>