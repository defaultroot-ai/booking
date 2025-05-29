jQuery(document).ready(function($) {
    
    // Initialize booking form
    initBookingForm();
    
    function initBookingForm() {
        // Service selection handler
        $('#abs-service-select').on('change', function() {
            const serviceId = $(this).val();
            const $option = $(this).find('option:selected');
            
            if (serviceId) {
                loadStaffForService(serviceId);
                updateServiceInfo($option);
            } else {
                hideServiceInfo();
                resetStaffSelect();
            }
        });
        
        // Staff selection handler
        $('#abs-staff-select').on('change', function() {
            const staffId = $(this).val();
            if (staffId) {
                loadAvailableDates(staffId);
            } else {
                resetDatePicker();
            }
        });
        
        // Date selection handler
        $('#abs-date-picker').on('change', function() {
            const date = $(this).val();
            const staffId = $('#abs-staff-select').val();
            if (date && staffId) {
                loadAvailableTimeSlots(staffId, date);
            }
        });
        
        // Form submission
        $('#abs-booking-form').on('submit', function(e) {
            e.preventDefault();
            submitBooking();
        });
    }
    
    function updateServiceInfo($option) {
        const price = $option.data('price');
        const duration = $option.data('duration');
        
        if (price !== undefined && duration !== undefined) {
            $('.abs-service-price').text('$' + parseFloat(price).toFixed(2));
            $('.abs-service-duration').text(duration + ' minutes');
            $('.abs-service-info').show();
        }
    }
    
    function hideServiceInfo() {
        $('.abs-service-info').hide();
    }
    
    function resetStaffSelect() {
        const $staffSelect = $('#abs-staff-select');
        $staffSelect.empty().append('<option value="">First select a service</option>');
        $staffSelect.prop('disabled', true);
        resetDatePicker();
    }
    
    function resetDatePicker() {
        $('#abs-date-picker').val('').datepicker('destroy');
        resetTimeSlots();
    }
    
    function resetTimeSlots() {
        $('#abs-time-slots').empty();
        $('#abs-selected-time').val('');
    }
    
    function loadStaffForService(serviceId) {
        $.ajax({
            url: abs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'abs_get_staff_for_service',
                service_id: serviceId,
                nonce: abs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStaffSelect(response.data);
                } else {
                    showError('Failed to load staff members');
                }
            },
            error: function() {
                showError('Error loading staff members');
            }
        });
    }
    
    function updateStaffSelect(staff) {
        const $staffSelect = $('#abs-staff-select');
        $staffSelect.empty().append('<option value="">Select Staff Member</option>');
        
        if (staff && staff.length > 0) {
            staff.forEach(function(member) {
                $staffSelect.append(`<option value="${member.id}">${member.name}</option>`);
            });
            $staffSelect.prop('disabled', false);
        } else {
            $staffSelect.append('<option value="">No staff available</option>');
        }
    }
    
    function loadAvailableDates(staffId) {
        $.ajax({
            url: abs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'abs_get_available_dates',
                staff_id: staffId,
                nonce: abs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    initDatePicker(response.data);
                }
            }
        });
    }
    
    function initDatePicker(availableDates) {
        $('#abs-date-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            maxDate: '+30d',
            beforeShowDay: function(date) {
                const dateString = $.datepicker.formatDate('yy-mm-dd', date);
                return [availableDates.includes(dateString), ''];
            },
            onSelect: function(dateText) {
                const staffId = $('#abs-staff-select').val();
                if (staffId) {
                    loadAvailableTimeSlots(staffId, dateText);
                }
            }
        });
    }
    
    function loadAvailableTimeSlots(staffId, date) {
        $.ajax({
            url: abs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'abs_get_time_slots',
                staff_id: staffId,
                date: date,
                nonce: abs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateTimeSlots(response.data);
                }
            }
        });
    }
    
    function updateTimeSlots(slots) {
        const $container = $('#abs-time-slots');
        $container.empty();
        
        if (slots.length === 0) {
            $container.append('<p style="text-align: center; color: #666;">No time slots available for selected date</p>');
            return;
        }
        
        slots.forEach(function(slot) {
            const $button = $(`<button type="button" class="abs-time-slot" data-time="${slot}">${formatTime(slot)}</button>`);
            $container.append($button);
        });
        
        // Time slot selection
        $('.abs-time-slot').on('click', function() {
            $('.abs-time-slot').removeClass('selected');
            $(this).addClass('selected');
            $('#abs-selected-time').val($(this).data('time'));
        });
    }
    
    function formatTime(time24) {
        const [hours, minutes] = time24.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }
    
    function submitBooking() {
        const formData = {
            action: 'abs_create_booking',
            service_id: $('#abs-service-select').val(),
            staff_id: $('#abs-staff-select').val(),
            date: $('#abs-date-picker').val(),
            time: $('#abs-selected-time').val(),
            customer_name: $('#abs-customer-name').val(),
            customer_email: $('#abs-customer-email').val(),
            customer_phone: $('#abs-customer-phone').val(),
            notes: $('#abs-notes').val(),
            nonce: abs_ajax.nonce
        };
        
        // Validate form
        if (!validateBookingForm(formData)) {
            return;
        }
        
        // Show loading
        showLoading();
        
        $.ajax({
            url: abs_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showSuccess('Booking created successfully! You will receive a confirmation email shortly.');
                    resetForm();
                } else {
                    showError(response.data || 'Error creating booking. Please try again.');
                }
            },
            error: function() {
                hideLoading();
                showError('Error creating booking. Please try again.');
            }
        });
    }
    
    function validateBookingForm(data) {
        const required = ['service_id', 'staff_id', 'date', 'time', 'customer_name', 'customer_email'];
        
        for (let field of required) {
            if (!data[field]) {
                showError('Please fill in all required fields');
                return false;
            }
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(data.customer_email)) {
            showError('Please enter a valid email address');
            return false;
        }
        
        return true;
    }
    
    function showLoading() {
        $('#abs-booking-form').addClass('abs-loading');
        $('#abs-submit-btn').prop('disabled', true).text('Processing...');
    }
    
    function hideLoading() {
        $('#abs-booking-form').removeClass('abs-loading');
        $('#abs-submit-btn').prop('disabled', false).text('Book Appointment');
    }
    
    function showSuccess(message) {
        $('.abs-messages').html(`<div class="abs-success">${message}</div>`);
        $('html, body').animate({
            scrollTop: $('.abs-booking-form').offset().top - 50
        }, 500);
    }
    
    function showError(message) {
        $('.abs-messages').html(`<div class="abs-error">${message}</div>`);
        $('html, body').animate({
            scrollTop: $('.abs-booking-form').offset().top - 50
        }, 500);
    }
    
    function resetForm() {
        $('#abs-booking-form')[0].reset();
        $('.abs-time-slot').removeClass('selected');
        $('#abs-selected-time').val('');
        $('#abs-staff-select').prop('disabled', true).empty().append('<option value="">First select a service</option>');
        $('#abs-date-picker').datepicker('destroy');
        $('#abs-time-slots').empty();
        $('.abs-service-info').hide();
    }
});