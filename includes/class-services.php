<?php

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Services {
    
    public static function get_all_services($status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_services';
        $where = $status ? $wpdb->prepare("WHERE status = %s", $status) : '';
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY name ASC");
    }
    
    public static function get_service($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_services';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function create_service($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_services';
        
        $defaults = array(
            'name' => '',
            'description' => '',
            'duration' => 60,
            'price' => 0.00,
            'category_id' => null,
            'status' => 'active'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert(
            $table,
            array(
                'name' => sanitize_text_field($data['name']),
                'description' => sanitize_textarea_field($data['description']),
                'duration' => absint($data['duration']),
                'price' => floatval($data['price']),
                'category_id' => $data['category_id'] ? absint($data['category_id']) : null,
                'status' => sanitize_text_field($data['status'])
            ),
            array('%s', '%s', '%d', '%f', '%d', '%s')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    public static function update_service($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_services';
        
        $update_data = array();
        $format = array();
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }
        
        if (isset($data['duration'])) {
            $update_data['duration'] = absint($data['duration']);
            $format[] = '%d';
        }
        
        if (isset($data['price'])) {
            $update_data['price'] = floatval($data['price']);
            $format[] = '%f';
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
    
    public static function delete_service($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'abs_services';
        return $wpdb->delete($table, array('id' => absint($id)), array('%d'));
    }
}