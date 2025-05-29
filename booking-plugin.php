<?php
/**
 * Plugin Name: Advanced Booking System
 * Plugin URI: https://example.com/advanced-booking
 * Description: Professional appointment booking and scheduling plugin for WordPress
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: advanced-booking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ABS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ABS_VERSION', '1.0.0');

// Main plugin class
class AdvancedBookingSystem {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Load includes immediately for activation
        $this->includes();
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->init_hooks();
    }
    
    private function includes() {
        // Check if files exist before including
        $files = array(
            'includes/class-database.php',
            'includes/class-services.php',
            'includes/class-staff.php',
            'includes/class-bookings.php',
            'includes/class-notifications.php',
            'includes/class-payments.php',
            'public/class-frontend.php'
        );
        
        foreach ($files as $file) {
            $file_path = ABS_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Load admin class only in admin
        if (is_admin()) {
            $admin_file = ABS_PLUGIN_PATH . 'admin/class-admin.php';
            if (file_exists($admin_file)) {
                require_once $admin_file;
                // Admin class is initialized within its own file
            }
        }
    }
    
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_shortcode('booking_form', array($this, 'booking_form_shortcode'));
        
        // AJAX hooks
        add_action('wp_ajax_abs_get_staff_for_service', array($this, 'ajax_get_staff_for_service'));
        add_action('wp_ajax_nopriv_abs_get_staff_for_service', array($this, 'ajax_get_staff_for_service'));
        add_action('wp_ajax_abs_get_available_dates', array($this, 'ajax_get_available_dates'));
        add_action('wp_ajax_nopriv_abs_get_available_dates', array($this, 'ajax_get_available_dates'));
        add_action('wp_ajax_abs_get_time_slots', array($this, 'ajax_get_time_slots'));
        add_action('wp_ajax_nopriv_abs_get_time_slots', array($this, 'ajax_get_time_slots'));
        add_action('wp_ajax_abs_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_nopriv_abs_create_booking', array($this, 'ajax_create_booking'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        
        wp_enqueue_script('abs-frontend-js', ABS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery', 'jquery-ui-datepicker'), ABS_VERSION, true);
        wp_enqueue_style('abs-frontend-css', ABS_PLUGIN_URL . 'assets/css/frontend.css', array(), ABS_VERSION);
        
        // Localize script for AJAX
        wp_localize_script('abs-frontend-js', 'abs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('abs_nonce')
        ));
        
        wp_localize_script('abs-frontend-js', 'abs_localize', array(
            'select_staff' => __('Select Staff Member', 'advanced-booking'),
            'no_slots_available' => __('No time slots available for selected date', 'advanced-booking'),
            'booking_success' => __('Booking created successfully!', 'advanced-booking'),
            'booking_error' => __('Error creating booking. Please try again.', 'advanced-booking'),
            'required_fields' => __('Please fill in all required fields', 'advanced-booking'),
            'invalid_email' => __('Please enter a valid email address', 'advanced-booking'),
            'processing' => __('Processing...', 'advanced-booking'),
            'book_appointment' => __('Book Appointment', 'advanced-booking')
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'abs-') !== false || strpos($hook, 'booking') !== false) {
            wp_enqueue_script('abs-admin-js', ABS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ABS_VERSION, true);
            wp_enqueue_style('abs-admin-css', ABS_PLUGIN_URL . 'assets/css/admin.css', array(), ABS_VERSION);
            
            // Localize admin script
            wp_localize_script('abs-admin-js', 'abs_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('abs_admin_nonce')
            ));
        }
    }
    
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'service_id' => '',
            'staff_id' => '',
            'theme' => 'default'
        ), $atts);
        
        if (class_exists('ABS_Frontend')) {
            return ABS_Frontend::render_booking_form($atts);
        }
        
        return $this->render_basic_booking_form($atts);
    }
    
    public function render_basic_booking_form($atts) {
        ob_start();
        ?>
        <div class="abs-booking-form">
            <div class="abs-messages"></div>
            <form id="abs-booking-form">
                <!-- Form fields go here -->
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // AJAX handlers
    public function ajax_get_staff_for_service() {
        check_ajax_referer('abs_nonce', 'nonce');
        
        $service_id = absint($_POST['service_id']);
        
        if (class_exists('ABS_Staff')) {
            $staff = ABS_Staff::get_staff_for_service($service_id);
            wp_send_json_success($staff);
        }
        
        wp_send_json_error('Staff class not found');
    }
    
    public function ajax_get_available_dates() {
        check_ajax_referer('abs_nonce', 'nonce');
        
        $staff_id = absint($_POST['staff_id']);
        
        // Generate next 30 days as available (basic implementation)
        $dates = array();
        for ($i = 0; $i < 30; $i++) {
            $dates[] = date('Y-m-d', strtotime("+$i days"));
        }
        
        wp_send_json_success($dates);
    }
    
    public function ajax_get_time_slots() {
        check_ajax_referer('abs_nonce', 'nonce');
        
        $staff_id = absint($_POST['staff_id']);
        $date = sanitize_text_field($_POST['date']);
        
        // Generate basic time slots (9 AM to 5 PM, hourly)
        $slots = array();
        for ($hour = 9; $hour <= 17; $hour++) {
            $time = sprintf('%02d:00', $hour);
            $slots[] = $time;
        }
        
        wp_send_json_success($slots);
    }
    
    public function ajax_create_booking() {
        check_ajax_referer('abs_nonce', 'nonce');
        
        $data = array(
            'service_id' => absint($_POST['service_id']),
            'staff_id' => absint($_POST['staff_id']),
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'booking_date' => sanitize_text_field($_POST['date']),
            'booking_time' => sanitize_text_field($_POST['time']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );
        
        if (class_exists('ABS_Bookings')) {
            $result = ABS_Bookings::create_booking($data);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } elseif ($result) {
                wp_send_json_success('Booking created successfully!');
            } else {
                wp_send_json_error('Failed to create booking');
            }
        }
        
        wp_send_json_error('Booking system not available');
    }
    
    public function activate() {
        // Create database tables
        if (class_exists('ABS_Database')) {
            ABS_Database::create_tables();
        }
        
        // Add default options
        add_option('abs_version', ABS_VERSION);
        add_option('abs_booking_settings', array(
            'time_slot_duration' => 60,
            'advance_booking_days' => 30,
            'booking_confirmation' => 'manual',
            'business_hours_start' => '09:00',
            'business_hours_end' => '17:00',
            'email_notifications' => true,
            'booking_buffer' => 0
        ));
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('advanced-booking', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

// Initialize the plugin
new AdvancedBookingSystem();