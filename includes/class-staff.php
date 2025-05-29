<?php

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Staff {
    
    public static function get_all_staff($status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_staff';
        $where = $status ? $wpdb->prepare("WHERE status = %s", $status) : '';
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY name ASC");
    }
    
    public static function get_staff_for_service($service_id) {
        global $wpdb;
        
        $staff_table = $wpdb->prefix . 'abs_staff';
        $services_table = $wpdb->prefix . 'abs_staff_services';
        
        // If no staff-service relations exist, return all active staff
        $relations_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
        
        if ($relations_count == 0) {
            return self::get_all_staff('active');
        }
        
        $query = $wpdb->prepare("
            SELECT s.* 
            FROM $staff_table s
            INNER JOIN $services_table ss ON s.id = ss.staff_id
            WHERE ss.service_id = %d AND s.status = 'active'
            ORDER BY s.name ASC
        ", $service_id);
        
        return $wpdb->get_results($query);
    }
    
    public static function get_staff($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_staff';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function create_staff($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_staff';
        
        $defaults = array(
            'wp_user_id' => null,
            'name' => '',
            'email' => '',
            'phone' => '',
            'bio' => '',
            'photo' => '',
            'status' => 'active'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert(
            $table,
            array(
                'wp_user_id' => $data['wp_user_id'] ? absint($data['wp_user_id']) : null,
                'name' => sanitize_text_field($data['name']),
                'email' => sanitize_email($data['email']),
                'phone' => sanitize_text_field($data['phone']),
                'bio' => sanitize_textarea_field($data['bio']),
                'photo' => sanitize_url($data['photo']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    public static function update_staff($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_staff';
        
        $update_data = array();
        $format = array();
        
        if (isset($data['wp_user_id'])) {
            $update_data['wp_user_id'] = $data['wp_user_id'] ? absint($data['wp_user_id']) : null;
            $format[] = '%d';
        }
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        
        if (isset($data['email'])) {
            $update_data['email'] = sanitize_email($data['email']);
            $format[] = '%s';
        }
        
        if (isset($data['phone'])) {
            $update_data['phone'] = sanitize_text_field($data['phone']);
            $format[] = '%s';
        }
        
        if (isset($data['bio'])) {
            $update_data['bio'] = sanitize_textarea_field($data['bio']);
            $format[] = '%s';
        }
        
        if (isset($data['photo'])) {
            $update_data['photo'] = sanitize_url($data['photo']);
            $format[] = '%s';
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        return $wpdb->update(
            $table,
            $update_data,
            array('id' => absint($id)),
            $format,
            array('%d')
        );
    }
    
    public static function delete_staff($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_staff';
        return $wpdb->delete($table, array('id' => absint($id)), array('%d'));
    }
    
    public static function assign_service($staff_id, $service_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_staff_services';
        
        // Check if assignment already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE staff_id = %d AND service_id = %d",
            $staff_id,
            $service_id
        ));
        
        if ($exists) {
            return true; // Already assigned
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'staff_id' => absint($staff_id),
                'service_id' => absint($service_id)
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    public static function unassign_service($staff_id, $service_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_staff_services';
        
        return $wpdb->delete(
            $table,
            array(
                'staff_id' => absint($staff_id),
                'service_id' => absint($service_id)
            ),
            array('%d', '%d')
        );
    }
    
    public static function get_staff_services($staff_id) {
        global $wpdb;
        
        $staff_services_table = $wpdb->prefix . 'abs_staff_services';
        $services_table = $wpdb->prefix . 'abs_services';
        
        $query = $wpdb->prepare("
            SELECT s.* 
            FROM $services_table s
            INNER JOIN $staff_services_table ss ON s.id = ss.service_id
            WHERE ss.staff_id = %d AND s.status = 'active'
            ORDER BY s.name ASC
        ", $staff_id);
        
        return $wpdb->get_results($query);
    }
    
    public static function get_available_staff($service_id, $date, $time) {
        global $wpdb;
        
        $staff_table = $wpdb->prefix . 'abs_staff';
        $bookings_table = $wpdb->prefix . 'abs_bookings';
        
        // Get all active staff for the service
        $staff_for_service = self::get_staff_for_service($service_id);
        
        if (empty($staff_for_service)) {
            return array();
        }
        
        $available_staff = array();
        
        foreach ($staff_for_service as $staff) {
            // Check if staff is available at the given time
            $conflicts = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM $bookings_table 
                WHERE staff_id = %d 
                AND booking_date = %s 
                AND booking_time = %s 
                AND status NOT IN ('cancelled')
            ", $staff->id, $date, $time));
            
            if ($conflicts == 0) {
                $available_staff[] = $staff;
            }
        }
        
        return $available_staff;
    }
}