/**
 * Ayula Store POS System - Cart Functionality
 * Handles cart operations and user interactions for shopping cart
 */

$(document).ready(function() {
    // Setup quantity controls for cart items
    setupQuantityControls();
    
    // Setup checkout form functionality
    setupCheckoutForm();
    
    // Setup cart item deletion confirmation
    setupCartDeletionConfirmation();
    
    // Setup clear cart confirmation
    setupClearCartConfirmation();
    
    // Setup cash amount input formatting
    setupCashAmountFormatting();
});

/**
 * Set up quantity controls (increment, decrement, manual input) for cart items
 */
function setupQuantityControls() {
    // Handle increment button
    $('.quantity-btn.increment').off('click').on('click', function() {
        const input = $(this).siblings('.quantity-field');
        const currentValue = parseInt(input.val()) || 1;
        const maxStock = parseInt(input.data('max-stock')) || 999;
        
        if (currentValue < maxStock) {
            input.val(currentValue + 1);
        }
    });
    
    // Handle decrement button
    $('.quantity-btn.decrement').off('click').on('click', function() {
        const input = $(this).siblings('.quantity-field');
        const currentValue = parseInt(input.val()) || 1;
        
        if (currentValue > 1) {
            input.val(currentValue - 1);
        }
    });
    
    // Validate manual input in quantity field
    $('.quantity-field').off('change keyup').on('change keyup', function() {
        let value = parseInt($(this).val()) || 1;
        const maxStock = parseInt($(this).data('max-stock')) || 999;
        
        // Ensure value is within limits
        if (value < 1) value = 1;
        if (value > maxStock) value = maxStock;
        
        $(this).val(value);
    });
}

/**
 * Set up checkout form validation and submission
 */
function setupCheckoutForm() {
    $('#checkout-form').off('submit').on('submit', function(e) {
        // Get the cash amount and total
        const cashAmount = parseFloat($('#hidden-cash-amount').val()) || 0;
        const totalText = $('.total-value h6').text();
        const totalAmount = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;
        
        // Validate that cash amount is sufficient
        if (cashAmount < totalAmount) {
            e.preventDefault();
            alert('Jumlah tunai tidak mencukupi. Mohon masukkan nilai yang sama atau lebih besar dari total.');
            return false;
        }
        
        // All good, allow form submission
        return true;
    });
}

/**
 * Setup confirmation dialog when removing items from cart
 */
function setupCartDeletionConfirmation() {
    $('.delete-cart-item').off('click').on('click', function(e) {
        e.preventDefault();
        const index = $(this).data('index');
        
        // Set up the confirmation link to the correct index
        $('#confirm-delete-btn').attr('href', 'index.php?remove_item=' + index);
        
        // Show modal if available, otherwise use confirm dialog
        if (typeof bootstrap !== 'undefined' && $('#deleteConfirmModal').length) {
            try {
                const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                modal.show();
            } catch (error) {
                console.error('Modal error, using fallback:', error);
                if (confirm('Apakah Anda yakin ingin menghapus item ini dari keranjang?')) {
                    window.location.href = 'index.php?remove_item=' + index;
                }
            }
        } else {
            if (confirm('Apakah Anda yakin ingin menghapus item ini dari keranjang?')) {
                window.location.href = 'index.php?remove_item=' + index;
            }
        }
    });
}

/**
 * Setup clear cart confirmation
 */
function setupClearCartConfirmation() {
    // This is handled in another part of the code (index.php inline script)
    // Just making sure all confirmations are set up correctly
    
    // Clear Cart confirmation - Confirm button handler
    $('#confirm-clear-cart').off('click').on('click', function(e) {
        window.location.href = 'index.php?clear_cart=1';
    });
    
    // Clear Cart confirmation - Cancel button handler
    $('#cancel-clear-cart').off('click').on('click', function() {
        if (typeof bootstrap !== 'undefined') {
            try {
                const clearModal = bootstrap.Modal.getInstance(document.getElementById('clearCartModal'));
                if (clearModal) {
                    clearModal.hide();
                }
            } catch (error) {
                console.error('Modal hide error:', error);
                // Force modal to close
                $('#clearCartModal').modal('hide');
            }
        }
    });
}

/**
 * Setup cash amount input formatting with thousand separator
 */
function setupCashAmountFormatting() {
    const cashInput = $('#cash-amount');
    const hiddenCashAmount = $('#hidden-cash-amount');
    const hiddenChangeAmount = $('#hidden-change-amount');
    const changeDisplay = $('#change-amount');
    const changeContainer = $('#change-container');
    
    // Format the input with thousand separator on change
    cashInput.on('input', function() {
        // Remove non-numeric characters
        let value = $(this).val().replace(/[^\d]/g, '');
        
        // Get the total amount
        const totalText = $('.total-value h6').text();
        const totalAmount = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;
        
        // Convert to number and format with thousand separator
        if (value !== '') {
            const numericValue = parseInt(value);
            
            // Calculate change
            const change = numericValue - totalAmount;
            
            // Update hidden fields for form submission
            hiddenCashAmount.val(numericValue);
            hiddenChangeAmount.val(Math.max(0, change));
            
            // Format display value
            $(this).val(formatNumber(numericValue));
            
            // Show change if payment is sufficient
            if (change >= 0) {
                changeDisplay.text('Rp. ' + formatNumber(change));
                changeContainer.show();
            } else {
                changeContainer.hide();
            }
        } else {
            $(this).val('');
            changeContainer.hide();
        }
    });
    
    // Quick cash buttons
    $('.quick-cash').on('click', function() {
        const value = $(this).data('value');
        cashInput.val(formatNumber(value));
        
        // Calculate change
        const totalText = $('.total-value h6').text();
        const totalAmount = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;
        const change = value - totalAmount;
        
        // Update hidden fields for form submission
        hiddenCashAmount.val(value);
        hiddenChangeAmount.val(Math.max(0, change));
        
        // Show change
        if (change >= 0) {
            changeDisplay.text('Rp. ' + formatNumber(change));
            changeContainer.show();
        } else {
            changeContainer.hide();
        }
    });
    
    // Trigger input event to initialize on page load
    cashInput.trigger('input');
}

/**
 * Format number with thousand separator
 * 
 * @param {number} number The number to format
 * @return {string} Formatted number with thousand separator
 */
function formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}