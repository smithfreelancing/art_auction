/*
Name of file: /assets/js/main.js
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Main JavaScript functionality for the art auction platform
*/

// Document ready function
$(document).ready(function() {
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Countdown timer for auctions
    function updateCountdowns() {
        $('.countdown').each(function() {
            const endTime = new Date($(this).data('end-time')).getTime();
            const now = new Date().getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                $(this).html('Auction ended');
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            let countdownText = '';
            if (days > 0) {
                countdownText += days + 'd ';
            }
            countdownText += hours + 'h ' + minutes + 'm ' + seconds + 's';
            
            $(this).html(countdownText);
        });
    }
    
    // Update countdowns every second if there are any countdown elements
    if ($('.countdown').length > 0) {
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    }
    
    // Bid form validation
    $('#bidForm').on('submit', function(e) {
        const currentBid = parseFloat($('#currentBid').val());
        const bidAmount = parseFloat($('#bidAmount').val());
        const minBidIncrement = parseFloat($('#minBidIncrement').val());
        
        if (bidAmount <= currentBid) {
            e.preventDefault();
            $('#bidError').text('Your bid must be higher than the current bid').show();
            return false;
        }
        
        if (bidAmount < currentBid + minBidIncrement) {
            e.preventDefault();
            $('#bidError').text('Minimum bid increment is $' + minBidIncrement).show();
            return false;
        }
    });
    
    // Image preview for artwork upload
    $('#artworkImage').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Confirm delete actions
