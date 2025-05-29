<?php

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Services table
        $services_table = $wpdb->prefix . 'abs_services';
        $services_sql = "CREATE TABLE $services_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            duration int(11) NOT NULL DEFAULT 60,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            category_id int(11),
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Staff table
        $staff_table = $wpdb->prefix . 'abs_staff';
        $staff_sql = "CREATE TABLE $staff_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) UNSIGNED,
            name varchar(255) NOT NULL,
            email varchar(255),
            phone varchar(50),
            bio text,
            photo varchar(255),
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY wp_user_id (wp_user_id)
        ) $charset_collate;";
        
        // Bookings table
        $bookings_table = $wpdb->prefix . 'abs_bookings';
        $bookings_sql = "CREATE TABLE $bookings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            staff_id mediumint(9) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50),
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            duration int(11) NOT NULL,
            total_price decimal(10,2) NOT NULL,
            status enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
            payment_status enum('pending','paid','refunded') DEFAULT 'pending',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY staff_id (staff_id),
            KEY booking_date (booking_date),
            KEY status (status)
        ) $charset_collate;";
        
        // Staff services relation
        $staff_services_table = $wpdb->prefix . 'abs_staff_services';
        $staff_services_sql = "CREATE TABLE $staff_services_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            staff_id mediumint(9) NOT NULL,
            service_id mediumint(9) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY staff_service (staff_id, service_id)
        ) $charset_collate;";
        
        // Working hours table
        $working_hours_table = $wpdb->prefix . 'abs_working_hours';
        $working_hours_sql = "CREATE TABLE $working_hours_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            staff_id mediumint(9) NOT NULL,
            day_of_week tinyint(1) NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            break_start time,
            break_end time,
            is_working boolean DEFAULT true,
            PRIMARY KEY (id),
            KEY staff_id (staff_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($services_sql);
        dbDelta($staff_sql);
        dbDelta($bookings_sql);
        dbDelta($staff_services_sql);
        dbDelta($working_hours_sql);
        
        // Insert default data
        self::insert_default_data();
    }
    
    private static function insert_default_data() {
        global $wpdb;
        
        // Check if data already exists
        $services_table = $wpdb->prefix . 'abs_services';
        $staff_table = $wpdb->prefix . 'abs_staff';
        
        $services_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
        $staff_count = $wpdb->get_var("SELECT COUNT(*) FROM $staff_table");
        
        // Insert default services
        if ($services_count == 0) {
            $default_services = array(
                array(
                    'name' => 'Consultation',
                    'description' => 'General consultation service',
                    'duration' => 60,
                    'price' => 100.00
                ),
                array(
                    'name' => 'Follow-up Appointment',
                    'description' => 'Follow-up appointment',
                    'duration' => 30,
                    'price' => 50.00
                ),
                array(
                    'name' => 'Extended Session',
                    'description' => 'Extended consultation session',
                    'duration' => 90,
                    'price' => 150.00
                )
            );
            
            foreach ($default_services as $service) {
                $wpdb->insert(
                    $services_table,
                    $service,
                    array('%s', '%s', '%d', '%f')
                );
            }
        }
        
        // Insert default staff
        if ($staff_count == 0) {
            $current_user = wp_get_current_user();
            
            $default_staff = array(
                'wp_user_id' => $current_user->ID,
                'name' => $current_user->display_name ?: 'Default Staff',
                'email' => $current_user->user_email,
                'status' => 'active'
            );
            
            $wpdb->insert(
                $staff_table,
                $default_staff,
                array('%d', '%s', '%s', '%s')
            );
        }
    }
}