<?php

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Bookings {
    
    public static function create_booking($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_bookings';
        
        // Validate required fields
        $required_fields = array('service_id', 'staff_id', 'customer_name', 'customer_email', 'booking_date', 'booking_time');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Field %s is required', 'advanced-booking'), $field));
            }
        }
        
        // Check if slot is available
        if (!self::is_slot_available($data['staff_id'], $data['booking_date'], $data['booking_time'], $data['duration'])) {
            return new WP_Error('slot_unavailable', __('Selected time slot is not available', 'advanced-booking'));
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'service_id' => absint($data['service_id']),
                'staff_id' => absint($data['staff_id']),
                'customer_name' => sanitize_text_field($data['customer_name']),
                'customer_email' => sanitize_email($data['customer_email']),
                'customer_phone' => sanitize_text_field($data['customer_phone']),
                'booking_date' => sanitize_text_field($data['booking_date']),
                'booking_time' => sanitize_text_field($data['booking_time']),
                'duration' => isset($data['duration']) ? absint($data['duration']) : 60,
                'total_price' => floatval($data['total_price']),
                'notes' => sanitize_textarea_field($data['notes']),
                'status' => 'pending'
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s')
        );
        
        if ($result !== false) {
            $booking_id = $wpdb->insert_id;
            
            // Send confirmation email if notifications class exists
            if (class_exists('ABS_Notifications')) {
                ABS_Notifications::send_booking_confirmation($booking_id);
            }
            
            return $booking_id;
        }
        
        return false;
    }
    
    public static function is_slot_available($staff_id, $date, $time, $duration = 60) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_bookings';
        
        // Calculate end time
        $start_datetime = $date . ' ' . $time;
        $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + ($duration * 60));
        
        // Check for overlapping bookings
        $query = $wpdb->prepare("
            SELECT COUNT(*) FROM $table 
            WHERE staff_id = %d 
            AND booking_date = %s 
            AND status NOT IN ('cancelled') 
            AND (
                (CONCAT(booking_date, ' ', booking_time) < %s AND 
                 DATE_ADD(CONCAT(booking_date, ' ', booking_time), INTERVAL duration MINUTE) > %s)
            )",
            $staff_id,
            $date,
            $end_datetime,
            $start_datetime
        );
        
        $overlapping = $wpdb->get_var($query);
        
        return $overlapping == 0;
    }
    
    public static function get_bookings($filters = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_bookings';
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($filters['staff_id'])) {
            $where_conditions[] = "staff_id = %d";
            $where_values[] = absint($filters['staff_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "booking_date >= %s";
            $where_values[] = sanitize_text_field($filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "booking_date <= %s";
            $where_values[] = sanitize_text_field($filters['date_to']);
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = %s";
            $where_values[] = sanitize_text_field($filters['status']);
        }
        
        if (!empty($filters['limit'])) {
            $limit_clause = "LIMIT " . absint($filters['limit']);
        } else {
            $limit_clause = "";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT * FROM $table $where_clause ORDER BY booking_date DESC, booking_time DESC $limit_clause";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query);
    }
    
    public static function get_booking($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_bookings';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function update_booking_status($booking_id, $status) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_bookings';
        $valid_statuses = array('pending', 'confirmed', 'completed', 'cancelled');
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        return $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => absint($booking_id)),
            array('%s'),
            array('%d')
        );
    }
    
    public static function update_booking($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_bookings';
        
        $update_data = array();
        $format = array();
        
        if (isset($data['service_id'])) {
            $update_data['service_id'] = absint($data['service_id']);
            $format[] = '%d';
        }
        
        if (isset($data['staff_id'])) {
            $update_data['staff_id'] = absint($data['staff_id']);
            $format[] = '%d';
        }
        
        if (isset($data['customer_name'])) {
            $update_data['customer_name'] = sanitize_text_field($data['customer_name']);
            $format[] = '%s';
        }
        
        if (isset($data['customer_email'])) {
            $update_data['customer_email'] = sanitize_email($data['customer_email']);
            $format[] = '%s';
        }
        
        if (isset($data['customer_phone'])) {
            $update_data['customer_phone'] = sanitize_text_field($data['customer_phone']);
            $format[] = '%s';
        }
        
        if (isset($data['booking_date'])) {
            $update_data['booking_date'] = sanitize_text_field($data['booking_date']);
            $format[] = '%s';
        }
        
        if (isset($data['booking_time'])) {
            $update_data['booking_time'] = sanitize_text_field($data['booking_time']);
            $format[] = '%s';
        }
        
        if (isset($data['duration'])) {
            $update_data['duration'] = absint($data['duration']);
            $format[] = '%d';
        }
        
        if (isset($data['total_price'])) {
            $update_data['total_price'] = floatval($data['total_price']);
            $format[] = '%f';
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }
        
        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
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
    
    public static function delete_booking($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_bookings';
        return $wpdb->delete($table, array('id' => absint($id)), array('%d'));
    }
    
    public static function get_booking_counts_by_status() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_bookings';
        
        $results = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $table 
            GROUP BY status
        ");
        
        $counts = array(
            'pending' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0
        );
        
        foreach ($results as $result) {
            $counts[$result->status] = (int) $result->count;
        }
        
        return $counts;
    }
    
    public static function get_upcoming_bookings($staff_id = null, $limit = 10) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_bookings';
        $today = date('Y-m-d');
        
        $where_conditions = array("booking_date >= %s", "status NOT IN ('cancelled')");
        $where_values = array($today);
        
        if ($staff_id) {
            $where_conditions[] = "staff_id = %d";
            $where_values[] = absint($staff_id);
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        $limit_clause = "LIMIT " . absint($limit);
        
        $query = "SELECT * FROM $table $where_clause ORDER BY booking_date ASC, booking_time ASC $limit_clause";
        $query = $wpdb->prepare($query, $where_values);
        
        return $wpdb->get_results($query);
    }
}