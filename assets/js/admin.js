jQuery(document).ready(function($) {
    
    // Initialize admin functionality
    initAdminActions();
    
    function initAdminActions() {
        // Delete service
        $('.abs-delete-service').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this service?')) {
                return;
            }
            
            const serviceId = $(this).data('service-id');
            const $row = $(this).closest('tr');
            
            deleteService(serviceId, $row);
        });
        
        // Delete staff
        $('.abs-delete-staff').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this staff member?')) {
                return;
            }
            
            const staffId = $(this).data('staff-id');
            const $row = $(this).closest('tr');
            
            deleteStaff(staffId, $row);
        });
        
        // Delete booking
        $('.abs-delete-booking').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this booking?')) {
                return;
            }
            
            const bookingId = $(this).data('booking-id');
            const $row = $(this).closest('tr');
            
            deleteBooking(bookingId, $row);
        });
        
        // Update booking status
        $('.abs-status-select').on('change', function() {
            const bookingId = $(this).data('booking-id');
            const status = $(this).val();
            const $select = $(this);
            
            updateBookingStatus(bookingId, status, $select);
        });
        
        // Auto-fill staff form when WordPress user is selected
        $('#wp_user_id').on('change', function() {
            const userId = $(this).val();
            if (userId && $(this).find('option:selected').text()) {
                const userText = $(this).find('option:selected').text();
                const matches = userText.match(/^(.+)\s\((.+)\)$/);
                
                if (matches) {
                    $('#staff_name').val(matches[1].trim());
                    $('#staff_email').val(matches[2].trim());
                }
            }
        });
        
        // Form validation
        $('form').on('submit', function(e) {
            if (!validateForm($(this))) {
                e.preventDefault();
            }
        });
    }
    
    function deleteService(serviceId, $row) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'abs_delete_service',
                service_id: serviceId,
                nonce: getAdminNonce()
            },
            beforeSend: function() {
                $row.addClass('abs-loading');
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showMessage(response.data, 'success');
                } else {
                    showMessage(response.data || 'Failed to delete service', 'error');
                    $row.removeClass('abs-loading');
                }
            },
            error: function() {
                showMessage('Error deleting service', 'error');
                $row.removeClass('abs-loading');
            }
        });
    }
    
    function deleteStaff(staffId, $row) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'abs_delete_staff',
                staff_id: staffId,
                nonce: getAdminNonce()
            },
            beforeSend: function() {
                $row.addClass('abs-loading');
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showMessage(response.data, 'success');
                } else {
                    showMessage(response.data || 'Failed to delete staff member', 'error');
                    $row.removeClass('abs-loading');
                }
            },
            error: function() {
                showMessage('Error deleting staff member', 'error');
                $row.removeClass('abs-loading');
            }
        });
    }
    
    function deleteBooking(bookingId, $row) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'abs_delete_booking',
                booking_id: bookingId,
                nonce: getAdminNonce()
            },
            beforeSend: function() {
                $row.addClass('abs-loading');
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showMessage(response.data, 'success');
                } else {
                    showMessage(response.data || 'Failed to delete booking', 'error');
                    $row.removeClass('abs-loading');
                }
            },
            error: function() {
                showMessage('Error deleting booking', 'error');
                $row.removeClass('abs-loading');
            }
        });
    }
    
    function updateBookingStatus(bookingId, status, $select) {
        const originalValue = $select.data('original-value') || $select.val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'abs_update_booking_status',
                booking_id: bookingId,
                status: status,
                nonce: getAdminNonce()
            },
            beforeSend: function() {
                $select.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $select.data('original-value', status);
                    showMessage(response.data, 'success');
                } else {
                    $select.val(originalValue);
                    showMessage(response.data || 'Failed to update booking status', 'error');
                }
            },
            error: function() {
                $select.val(originalValue);
                showMessage('Error updating booking status', 'error');
            },
            complete: function() {
                $select.prop('disabled', false);
            }
        });
    }
    
    function validateForm($form) {
        let isValid = true;
        
        // Check required fields
        $form.find('[required]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        // Email validation
        $form.find('input[type="email"]').each(function() {
            const $field = $(this);
            const email = $field.val().trim();
            
            if (email && !isValidEmail(email)) {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        // Price validation
        $form.find('input[name="service_price"]').each(function() {
            const $field = $(this);
            const price = parseFloat($field.val());
            
            if (isNaN(price) || price < 0) {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        if (!isValid) {
            showMessage('Please correct the highlighted fields', 'error');
        }
        
        return isValid;
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function showMessage(message, type) {
        const messageClass = type === 'success' ? 'abs-success-message' : 'abs-error-message';
        const $message = $(`<div class="${messageClass}">${message}</div>`);
        
        // Remove existing messages
        $('.abs-success-message, .abs-error-message').remove();
        
        // Add new message
        $('.wrap').prepend($message);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $message.offset().top - 50
        }, 300);
    }
    
    function getAdminNonce() {
        // Get nonce from localized script or try to find it in the page
        if (typeof abs_admin !== 'undefined' && abs_admin.nonce) {
            return abs_admin.nonce;
        }
        
        // Try to find nonce in existing nonce fields
        const $nonce = $('input[name="abs_nonce"]');
        if ($nonce.length) {
            return $nonce.val();
        }
        
        // Fallback to WordPress default nonce if available
        if (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce) {
            return wpApiSettings.nonce;
        }
        
        return '';
    }
});