<?php

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into admin_menu with higher priority
        add_action('admin_menu', array($this, 'add_admin_menu'), 10);
        add_action('admin_init', array($this, 'admin_init'));
        
        // Add AJAX handlers
        add_action('wp_ajax_abs_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_abs_delete_staff', array($this, 'ajax_delete_staff'));
        add_action('wp_ajax_abs_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_action('wp_ajax_abs_delete_booking', array($this, 'ajax_delete_booking'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    public function add_admin_menu() {
        // Check if user has required capability
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Main menu page
        add_menu_page(
            __('Booking System', 'advanced-booking'),
            __('Bookings', 'advanced-booking'),
            'manage_options',
            'abs-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'abs-dashboard',
            __('Dashboard', 'advanced-booking'),
            __('Dashboard', 'advanced-booking'),
            'manage_options',
            'abs-dashboard',
            array($this, 'dashboard_page')
        );
        
        // Bookings submenu
        add_submenu_page(
            'abs-dashboard',
            __('All Bookings', 'advanced-booking'),
            __('All Bookings', 'advanced-booking'),
            'manage_options',
            'abs-bookings',
            array($this, 'bookings_page')
        );
        
        // Services submenu
        add_submenu_page(
            'abs-dashboard',
            __('Services', 'advanced-booking'),
            __('Services', 'advanced-booking'),
            'manage_options',
            'abs-services',
            array($this, 'services_page')
        );
        
        // Staff submenu
        add_submenu_page(
            'abs-dashboard',
            __('Staff Members', 'advanced-booking'),
            __('Staff', 'advanced-booking'),
            'manage_options',
            'abs-staff',
            array($this, 'staff_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'abs-dashboard',
            __('Booking Settings', 'advanced-booking'),
            __('Settings', 'advanced-booking'),
            'manage_options',
            'abs-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_init() {
        // Register settings
        register_setting('abs_settings', 'abs_booking_settings');
        
        // Handle form submissions
        if (isset($_POST['abs_action']) && wp_verify_nonce($_POST['abs_nonce'], 'abs_admin_action')) {
            $this->handle_form_submission();
        }
    }
    
    public function admin_notices() {
        // Show success/error messages
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            
            switch ($message) {
                case 'service_added':
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Service added successfully!', 'advanced-booking') . '</p></div>';
                    break;
                case 'staff_added':
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Staff member added successfully!', 'advanced-booking') . '</p></div>';
                    break;
                case 'settings_saved':
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'advanced-booking') . '</p></div>';
                    break;
            }
        }
    }
    
    private function handle_form_submission() {
        switch ($_POST['abs_action']) {
            case 'add_service':
                $this->add_service();
                break;
            case 'edit_service':
                $this->edit_service();
                break;
            case 'add_staff':
                $this->add_staff();
                break;
            case 'edit_staff':
                $this->edit_staff();
                break;
            case 'save_settings':
                $this->save_settings();
                break;
        }
    }
    
    public function dashboard_page() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap abs-admin">
            <h1 class="wp-heading-inline"><?php _e('Booking System Dashboard', 'advanced-booking'); ?></h1>
            
            <div class="abs-dashboard-stats">
                <div class="abs-stat-card">
                    <h3><?php _e('Today\'s Bookings', 'advanced-booking'); ?></h3>
                    <div class="abs-stat-number"><?php echo esc_html($stats['today_bookings']); ?></div>
                </div>
                
                <div class="abs-stat-card">
                    <h3><?php _e('This Week', 'advanced-booking'); ?></h3>
                    <div class="abs-stat-number"><?php echo esc_html($stats['week_bookings']); ?></div>
                </div>
                
                <div class="abs-stat-card">
                    <h3><?php _e('This Month', 'advanced-booking'); ?></h3>
                    <div class="abs-stat-number"><?php echo esc_html($stats['month_bookings']); ?></div>
                </div>
                
                <div class="abs-stat-card">
                    <h3><?php _e('Total Revenue', 'advanced-booking'); ?></h3>
                    <div class="abs-stat-number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                </div>
            </div>
            
            <div class="abs-dashboard-content">
                <div class="abs-recent-bookings">
                    <h2><?php _e('Recent Bookings', 'advanced-booking'); ?></h2>
                    <?php $this->render_recent_bookings(); ?>
                </div>
                
                <div class="abs-quick-actions">
                    <h2><?php _e('Quick Actions', 'advanced-booking'); ?></h2>
                    <div class="abs-action-buttons">
                        <a href="<?php echo admin_url('admin.php?page=abs-services&action=add'); ?>" class="button button-primary">
                            <?php _e('Add New Service', 'advanced-booking'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=abs-staff&action=add'); ?>" class="button button-primary">
                            <?php _e('Add New Staff', 'advanced-booking'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=abs-bookings'); ?>" class="button">
                            <?php _e('View All Bookings', 'advanced-booking'); ?>
                        </a>
                        <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="button">
                            <?php _e('Create Booking Page', 'advanced-booking'); ?>
                        </a>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                        <h4><?php _e('Quick Setup', 'advanced-booking'); ?></h4>
                        <p><?php _e('To display the booking form on your website, add this shortcode to any page or post:', 'advanced-booking'); ?></p>
                        <code style="background: white; padding: 5px 10px; border-radius: 3px;">[booking_form]</code>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function bookings_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'view':
                $this->render_booking_details();
                break;
            default:
                $this->render_bookings_list();
                break;
        }
    }
    
    private function render_bookings_list() {
        // Apply filters if any
        $filters = array();
        if (!empty($_GET['status'])) {
            $filters['status'] = sanitize_text_field($_GET['status']);
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_GET['date_to']);
        }
        
        $bookings = ABS_Bookings::get_bookings($filters);
        ?>
        <div class="wrap abs-admin">
            <h1 class="wp-heading-inline"><?php _e('Bookings', 'advanced-booking'); ?></h1>
            
            <div class="abs-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="abs-bookings">
                    
                    <select name="status">
                        <option value=""><?php _e('All Statuses', 'advanced-booking'); ?></option>
                        <option value="pending" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'pending'); ?>><?php _e('Pending', 'advanced-booking'); ?></option>
                        <option value="confirmed" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'confirmed'); ?>><?php _e('Confirmed', 'advanced-booking'); ?></option>
                        <option value="completed" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'completed'); ?>><?php _e('Completed', 'advanced-booking'); ?></option>
                        <option value="cancelled" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'cancelled'); ?>><?php _e('Cancelled', 'advanced-booking'); ?></option>
                    </select>
                    
                    <input type="date" name="date_from" value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>" placeholder="<?php _e('From Date', 'advanced-booking'); ?>">
                    <input type="date" name="date_to" value="<?php echo isset($_GET['date_to']) ? esc_attr($_GET['date_to']) : ''; ?>" placeholder="<?php _e('To Date', 'advanced-booking'); ?>">
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'advanced-booking'); ?>">
                    
                    <?php if (!empty($filters)): ?>
                        <a href="<?php echo admin_url('admin.php?page=abs-bookings'); ?>" class="button"><?php _e('Clear Filters', 'advanced-booking'); ?></a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('ID', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Customer', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Service', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Staff', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Date & Time', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Status', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Price', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Actions', 'advanced-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bookings): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            $service = ABS_Services::get_service($booking->service_id);
                            $staff = ABS_Staff::get_staff($booking->staff_id);
                            ?>
                            <tr>
                                <td><?php echo esc_html($booking->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($booking->customer_name); ?></strong><br>
                                    <small><?php echo esc_html($booking->customer_email); ?></small>
                                </td>
                                <td><?php echo $service ? esc_html($service->name) : __('Deleted Service', 'advanced-booking'); ?></td>
                                <td><?php echo $staff ? esc_html($staff->name) : __('Deleted Staff', 'advanced-booking'); ?></td>
                                <td>
                                    <?php echo date_i18n('M j, Y', strtotime($booking->booking_date)); ?><br>
                                    <small><?php echo date_i18n('g:i A', strtotime($booking->booking_time)); ?></small>
                                </td>
                                <td>
                                    <select class="abs-status-select" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                                        <option value="pending" <?php selected($booking->status, 'pending'); ?>><?php _e('Pending', 'advanced-booking'); ?></option>
                                        <option value="confirmed" <?php selected($booking->status, 'confirmed'); ?>><?php _e('Confirmed', 'advanced-booking'); ?></option>
                                        <option value="completed" <?php selected($booking->status, 'completed'); ?>><?php _e('Completed', 'advanced-booking'); ?></option>
                                        <option value="cancelled" <?php selected($booking->status, 'cancelled'); ?>><?php _e('Cancelled', 'advanced-booking'); ?></option>
                                    </select>
                                </td>
                                <td>$<?php echo number_format($booking->total_price, 2); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=abs-bookings&action=view&id=' . $booking->id); ?>" class="button button-small">
                                        <?php _e('View', 'advanced-booking'); ?>
                                    </a>
                                    <button class="button button-small abs-delete-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                                        <?php _e('Delete', 'advanced-booking'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">
                                <?php _e('No bookings found', 'advanced-booking'); ?>
                                <?php if (!empty($filters)): ?>
                                    <br><small><?php _e('Try adjusting your filters', 'advanced-booking'); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function services_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
                $this->render_add_service_form();
                break;
            case 'edit':
                $this->render_edit_service_form();
                break;
            default:
                $this->render_services_list();
                break;
        }
    }
    
    private function render_services_list() {
        $services = ABS_Services::get_all_services();
        ?>
        <div class="wrap abs-admin">
            <h1 class="wp-heading-inline">
                <?php _e('Services', 'advanced-booking'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=abs-services&action=add'); ?>" class="page-title-action">
                <?php _e('Add New Service', 'advanced-booking'); ?>
            </a>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('Name', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Duration', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Price', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Status', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Actions', 'advanced-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($services): ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($service->name); ?></strong>
                                    <?php if ($service->description): ?>
                                        <br><small style="color: #666;"><?php echo esc_html(wp_trim_words($service->description, 15)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($service->duration); ?> <?php _e('minutes', 'advanced-booking'); ?></td>
                                <td>$<?php echo number_format($service->price, 2); ?></td>
                                <td>
                                    <span class="abs-status abs-status-<?php echo esc_attr($service->status); ?>">
                                        <?php echo esc_html(ucfirst($service->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=abs-services&action=edit&id=' . $service->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'advanced-booking'); ?>
                                    </a>
                                    <button class="button button-small abs-delete-service" data-service-id="<?php echo esc_attr($service->id); ?>">
                                        <?php _e('Delete', 'advanced-booking'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">
                                <?php _e('No services found', 'advanced-booking'); ?><br>
                                <a href="<?php echo admin_url('admin.php?page=abs-services&action=add'); ?>" class="button button-primary" style="margin-top: 10px;">
                                    <?php _e('Add Your First Service', 'advanced-booking'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function render_add_service_form() {
        ?>
        <div class="wrap abs-admin">
            <h1><?php _e('Add New Service', 'advanced-booking'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('abs_admin_action', 'abs_nonce'); ?>
                <input type="hidden" name="abs_action" value="add_service">
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="service_name"><?php _e('Service Name', 'advanced-booking'); ?> <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="service_name" name="service_name" class="regular-text" required>
                                <p class="description"><?php _e('Enter the name of the service', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="service_description"><?php _e('Description', 'advanced-booking'); ?></label>
                            </th>
                            <td>
                                <textarea id="service_description" name="service_description" class="large-text" rows="5"></textarea>
                                <p class="description"><?php _e('Optional description of the service', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="service_duration"><?php _e('Duration (minutes)', 'advanced-booking'); ?> <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <input type="number" id="service_duration" name="service_duration" value="60" min="5" max="480" required>
                                <p class="description"><?php _e('Duration in minutes (5-480)', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="service_price"><?php _e('Price ($)', 'advanced-booking'); ?> <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <input type="number" id="service_price" name="service_price" step="0.01" min="0" required>
                                <p class="description"><?php _e('Price in USD', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="service_status"><?php _e('Status', 'advanced-booking'); ?></label>
                            </th>
                            <td>
                                <select id="service_status" name="service_status">
                                    <option value="active"><?php _e('Active', 'advanced-booking'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'advanced-booking'); ?></option>
                                </select>
                                <p class="description"><?php _e('Only active services are available for booking', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Add Service', 'advanced-booking'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=abs-services'); ?>" class="button"><?php _e('Cancel', 'advanced-booking'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    public function staff_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
                $this->render_add_staff_form();
                break;
            case 'edit':
                $this->render_edit_staff_form();
                break;
            default:
                $this->render_staff_list();
                break;
        }
    }
    
    private function render_staff_list() {
        $staff_members = ABS_Staff::get_all_staff();
        ?>
        <div class="wrap abs-admin">
            <h1 class="wp-heading-inline">
                <?php _e('Staff Members', 'advanced-booking'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=abs-staff&action=add'); ?>" class="page-title-action">
                <?php _e('Add New Staff', 'advanced-booking'); ?>
            </a>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('Name', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Email', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Phone', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Status', 'advanced-booking'); ?></th>
                        <th scope="col"><?php _e('Actions', 'advanced-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($staff_members): ?>
                        <?php foreach ($staff_members as $staff): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($staff->name); ?></strong>
                                    <?php if ($staff->bio): ?>
                                        <br><small style="color: #666;"><?php echo esc_html(wp_trim_words($staff->bio, 15)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($staff->email); ?></td>
                                <td><?php echo esc_html($staff->phone); ?></td>
                                <td>
                                    <span class="abs-status abs-status-<?php echo esc_attr($staff->status); ?>">
                                        <?php echo esc_html(ucfirst($staff->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=abs-staff&action=edit&id=' . $staff->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'advanced-booking'); ?>
                                    </a>
                                    <button class="button button-small abs-delete-staff" data-staff-id="<?php echo esc_attr($staff->id); ?>">
                                        <?php _e('Delete', 'advanced-booking'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">
                                <?php _e('No staff members found', 'advanced-booking'); ?><br>
                                <a href="<?php echo admin_url('admin.php?page=abs-staff&action=add'); ?>" class="button button-primary" style="margin-top: 10px;">
                                    <?php _e('Add Your First Staff Member', 'advanced-booking'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function render_add_staff_form() {
        $users = get_users(array('role__not_in' => array('subscriber')));
        ?>
        <div class="wrap abs-admin">
            <h1><?php _e('Add New Staff Member', 'advanced-booking'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('abs_admin_action', 'abs_nonce'); ?>
                <input type="hidden" name="abs_action" value="add_staff">
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="wp_user_id"><?php _e('WordPress User', 'advanced-booking'); ?></label>
                            </th>
                            <td>
                                <select id="wp_user_id" name="wp_user_id" class="regular-text">
                                    <option value=""><?php _e('Select User (Optional)', 'advanced-booking'); ?></option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>">
                                            <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Link this staff member to an existing WordPress user', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="staff_name"><?php _e('Name', 'advanced-booking'); ?> <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="staff_name" name="staff_name" class="regular-text" required>
                                <p class="description"><?php _e('Full name of the staff member', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="staff_email"><?php _e('Email', 'advanced-booking'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="staff_email" name="staff_email" class="regular-text">
                                <p class="description"><?php _e('Email address for notifications', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="staff_phone"><?php _e('Phone', 'advanced-booking'); ?></label>
                            </th>
                            <td>
                                <input type="tel" id="staff_phone" name="staff_phone" class="regular-text">
                                <p class="description"><?php _e('Contact phone number', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="staff_bio"><?php _e('Bio', 'advanced-booking'); ?></label>
                            </th>
                            <td>
                                <textarea id="staff_bio" name="staff_bio" class="large-text" rows="5"></textarea>
                                <p class="description"><?php _e('Brief biography or description', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="staff_status"><?php _e('Status', 'advanced-booking'); ?></label>
                            </th>
                            <td>
                                <select id="staff_status" name="staff_status">
                                    <option value="active"><?php _e('Active', 'advanced-booking'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'advanced-booking'); ?></option>
                                </select>
                                <p class="description"><?php _e('Only active staff members are available for booking', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Add Staff Member', 'advanced-booking'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=abs-staff'); ?>" class="button"><?php _e('Cancel', 'advanced-booking'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    public function settings_page() {
        $settings = get_option('abs_booking_settings', array());
        $defaults = array(
            'time_slot_duration' => 60,
            'advance_booking_days' => 30,
            'booking_confirmation' => 'manual',
            'business_hours_start' => '09:00',
            'business_hours_end' => '17:00',
            'email_notifications' => true,
            'booking_buffer' => 0
        );
        $settings = wp_parse_args($settings, $defaults);
        ?>
        <div class="wrap abs-admin">
            <h1><?php _e('Booking Settings', 'advanced-booking'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('abs_admin_action', 'abs_nonce'); ?>
                <input type="hidden" name="abs_action" value="save_settings">
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Time Slot Duration', 'advanced-booking'); ?></th>
                            <td>
                                <select name="time_slot_duration">
                                    <option value="15" <?php selected($settings['time_slot_duration'], 15); ?>>15 <?php _e('minutes', 'advanced-booking'); ?></option>
                                    <option value="30" <?php selected($settings['time_slot_duration'], 30); ?>>30 <?php _e('minutes', 'advanced-booking'); ?></option>
                                    <option value="60" <?php selected($settings['time_slot_duration'], 60); ?>>60 <?php _e('minutes', 'advanced-booking'); ?></option>
                                </select>
                                <p class="description"><?php _e('Default time slot duration for bookings', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Advance Booking Days', 'advanced-booking'); ?></th>
                            <td>
                                <input type="number" name="advance_booking_days" value="<?php echo esc_attr($settings['advance_booking_days']); ?>" min="1" max="365">
                                <p class="description"><?php _e('How many days in advance customers can book', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Business Hours Start', 'advanced-booking'); ?></th>
                            <td>
                                <input type="time" name="business_hours_start" value="<?php echo esc_attr($settings['business_hours_start']); ?>">
                                <p class="description"><?php _e('Default start time for business hours', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Business Hours End', 'advanced-booking'); ?></th>
                            <td>
                                <input type="time" name="business_hours_end" value="<?php echo esc_attr($settings['business_hours_end']); ?>">
                                <p class="description"><?php _e('Default end time for business hours', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Booking Confirmation', 'advanced-booking'); ?></th>
                            <td>
                                <select name="booking_confirmation">
                                    <option value="manual" <?php selected($settings['booking_confirmation'], 'manual'); ?>><?php _e('Manual Confirmation', 'advanced-booking'); ?></option>
                                    <option value="automatic" <?php selected($settings['booking_confirmation'], 'automatic'); ?>><?php _e('Automatic Confirmation', 'advanced-booking'); ?></option>
                                </select>
                                <p class="description"><?php _e('How bookings should be confirmed', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Email Notifications', 'advanced-booking'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="email_notifications" value="1" <?php checked($settings['email_notifications'], true); ?>>
                                    <?php _e('Send email notifications for bookings', 'advanced-booking'); ?>
                                </label>
                                <p class="description"><?php _e('Enable/disable email notifications', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Booking Buffer (minutes)', 'advanced-booking'); ?></th>
                            <td>
                                <input type="number" name="booking_buffer" value="<?php echo esc_attr($settings['booking_buffer']); ?>" min="0" max="60">
                                <p class="description"><?php _e('Buffer time between bookings', 'advanced-booking'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Settings', 'advanced-booking'); ?>">
                </p>
            </form>
            
            <div style="margin-top: 40px; padding: 20px; background: #f0f0f1; border-radius: 4px;">
                <h3><?php _e('Shortcode Usage', 'advanced-booking'); ?></h3>
                <p><?php _e('Use the following shortcode to display the booking form:', 'advanced-booking'); ?></p>
                <code style="background: white; padding: 10px; display: block; margin: 10px 0;">[booking_form]</code>
                
                <h4><?php _e('Shortcode Parameters', 'advanced-booking'); ?></h4>
                <ul style="margin-left: 20px;">
                    <li><code>service_id</code> - <?php _e('Pre-select a specific service', 'advanced-booking'); ?></li>
                    <li><code>staff_id</code> - <?php _e('Pre-select a specific staff member', 'advanced-booking'); ?></li>
                    <li><code>theme</code> - <?php _e('Choose form theme (default, modern, minimal)', 'advanced-booking'); ?></li>
                </ul>
                
                <p><strong><?php _e('Example:', 'advanced-booking'); ?></strong></p>
                <code style="background: white; padding: 10px; display: block;">[booking_form service_id="1" theme="modern"]</code>
            </div>
        </div>
        <?php
    }
    
    // Helper methods
    private function get_dashboard_stats() {
        global $wpdb;
        
        $booking_table = $wpdb->prefix . 'abs_bookings';
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $month_start = date('Y-m-01');
        
        $today_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $booking_table WHERE booking_date = %s AND status != 'cancelled'",
            $today
        ));
        
        $week_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $booking_table WHERE booking_date >= %s AND status != 'cancelled'",
            $week_start
        ));
        
        $month_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $booking_table WHERE booking_date >= %s AND status != 'cancelled'",
            $month_start
        ));
        
        $total_revenue = $wpdb->get_var(
            "SELECT SUM(total_price) FROM $booking_table WHERE status IN ('confirmed', 'completed')"
        );
        
        return array(
            'today_bookings' => (int) $today_bookings,
            'week_bookings' => (int) $week_bookings,
            'month_bookings' => (int) $month_bookings,
            'total_revenue' => (float) $total_revenue
        );
    }
    
    private function render_recent_bookings() {
        $bookings = ABS_Bookings::get_bookings(array('limit' => 5));
        ?>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th><?php _e('Customer', 'advanced-booking'); ?></th>
                    <th><?php _e('Service', 'advanced-booking'); ?></th>
                    <th><?php _e('Date', 'advanced-booking'); ?></th>
                    <th><?php _e('Status', 'advanced-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings): ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php $service = ABS_Services::get_service($booking->service_id); ?>
                        <tr>
                            <td><?php echo esc_html($booking->customer_name); ?></td>
                            <td><?php echo $service ? esc_html($service->name) : __('Deleted Service', 'advanced-booking'); ?></td>
                            <td><?php echo date_i18n('M j, Y g:i A', strtotime($booking->booking_date . ' ' . $booking->booking_time)); ?></td>
                            <td><span class="abs-status abs-status-<?php echo esc_attr($booking->status); ?>"><?php echo esc_html(ucfirst($booking->status)); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">
                            <?php _e('No recent bookings', 'advanced-booking'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    // Form handlers
    private function add_service() {
        $data = array(
            'name' => sanitize_text_field($_POST['service_name']),
            'description' => sanitize_textarea_field($_POST['service_description']),
            'duration' => absint($_POST['service_duration']),
            'price' => floatval($_POST['service_price']),
            'status' => sanitize_text_field($_POST['service_status'])
        );
        
        $result = ABS_Services::create_service($data);
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=abs-services&message=service_added'));
            exit;
        }
    }
    
    private function add_staff() {
        $data = array(
            'wp_user_id' => !empty($_POST['wp_user_id']) ? absint($_POST['wp_user_id']) : null,
            'name' => sanitize_text_field($_POST['staff_name']),
            'email' => sanitize_email($_POST['staff_email']),
            'phone' => sanitize_text_field($_POST['staff_phone']),
            'bio' => sanitize_textarea_field($_POST['staff_bio']),
            'status' => sanitize_text_field($_POST['staff_status'])
        );
        
        $result = ABS_Staff::create_staff($data);
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=abs-staff&message=staff_added'));
            exit;
        }
    }
    
    private function save_settings() {
        $settings = array(
            'time_slot_duration' => absint($_POST['time_slot_duration']),
            'advance_booking_days' => absint($_POST['advance_booking_days']),
            'business_hours_start' => sanitize_text_field($_POST['business_hours_start']),
            'business_hours_end' => sanitize_text_field($_POST['business_hours_end']),
            'booking_confirmation' => sanitize_text_field($_POST['booking_confirmation']),
            'email_notifications' => isset($_POST['email_notifications']),
            'booking_buffer' => absint($_POST['booking_buffer'])
        );
        
        update_option('abs_booking_settings', $settings);
        wp_redirect(admin_url('admin.php?page=abs-settings&message=settings_saved'));
        exit;
    }
    
    // AJAX handlers
    public function ajax_delete_service() {
        check_ajax_referer('abs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'advanced-booking'));
        }
        
        $service_id = absint($_POST['service_id']);
        $result = ABS_Services::delete_service($service_id);
        
        if ($result) {
            wp_send_json_success(__('Service deleted successfully', 'advanced-booking'));
        } else {
            wp_send_json_error(__('Failed to delete service', 'advanced-booking'));
        }
    }
    
    public function ajax_delete_staff() {
        check_ajax_referer('abs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'advanced-booking'));
        }
        
        $staff_id = absint($_POST['staff_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'abs_staff';
        $result = $wpdb->delete($table, array('id' => $staff_id), array('%d'));
        
        if ($result) {
            wp_send_json_success(__('Staff member deleted successfully', 'advanced-booking'));
        } else {
            wp_send_json_error(__('Failed to delete staff member', 'advanced-booking'));
        }
    }
    
    public function ajax_update_booking_status() {
        check_ajax_referer('abs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'advanced-booking'));
        }
        
        $booking_id = absint($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $result = ABS_Bookings::update_booking_status($booking_id, $status);
        
        if ($result) {
            wp_send_json_success(__('Booking status updated successfully', 'advanced-booking'));
        } else {
            wp_send_json_error(__('Failed to update booking status', 'advanced-booking'));
        }
    }
    
    public function ajax_delete_booking() {
        check_ajax_referer('abs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'advanced-booking'));
        }
        
        $booking_id = absint($_POST['booking_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'abs_bookings';
        $result = $wpdb->delete($table, array('id' => $booking_id), array('%d'));
        
        if ($result) {
            wp_send_json_success(__('Booking deleted successfully', 'advanced-booking'));
        } else {
            wp_send_json_error(__('Failed to delete booking', 'advanced-booking'));
        }
    }
    
    private function render_edit_service_form() {
        // Placeholder for edit service form
        echo '<div class="wrap"><h1>Edit Service - Coming Soon</h1></div>';
    }
    
    private function render_edit_staff_form() {
        // Placeholder for edit staff form
        echo '<div class="wrap"><h1>Edit Staff - Coming Soon</h1></div>';
    }
    
    private function render_booking_details() {
        // Placeholder for booking details view
        echo '<div class="wrap"><h1>Booking Details - Coming Soon</h1></div>';
    }
    
    private function edit_service() {
        // Placeholder for edit service handler
    }
    
    private function edit_staff() {
        // Placeholder for edit staff handler
    }
}

// Initialize admin only once using singleton pattern
if (is_admin()) {
    ABS_Admin::get_instance();
}