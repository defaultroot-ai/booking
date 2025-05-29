<?php

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Notifications {
    
    public static function send_booking_confirmation($booking_id) {
        global $wpdb;
        
        $booking_table = $wpdb->prefix . 'abs_bookings';
        $service_table = $wpdb->prefix . 'abs_services';
        $staff_table = $wpdb->prefix . 'abs_staff';
        
        // Get booking details
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, s.name as service_name, st.name as staff_name
            FROM $booking_table b
            LEFT JOIN $service_table s ON b.service_id = s.id
            LEFT JOIN $staff_table st ON b.staff_id = st.id
            WHERE b.id = %d
        ", $booking_id));
        
        if (!$booking) {
            return false;
        }
        
        $subject = sprintf(__('Booking Confirmation - %s', 'advanced-booking'), $booking->service_name);
        
        $message = sprintf(__("
Dear %s,

Your booking has been received and is pending confirmation.

Booking Details:
- Service: %s
- Staff: %s
- Date: %s
- Time: %s
- Duration: %d minutes
- Total Price: $%.2f

Notes: %s

We will contact you shortly to confirm your appointment.

Thank you!
        ", 'advanced-booking'), 
            $booking->customer_name,
            $booking->service_name,
            $booking->staff_name,
            date('F j, Y', strtotime($booking->booking_date)),
            date('g:i A', strtotime($booking->booking_time)),
            $booking->duration,
            $booking->total_price,
            $booking->notes
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($booking->customer_email, $subject, nl2br($message), $headers);
    }
}