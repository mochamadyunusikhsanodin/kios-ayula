/**
 * Barcode Scanner Feature
 * Adds barcode scanning functionality to Ayula Store POS System
 */

$(document).ready(function() {
    // Initialize barcode scanner functionality
    initBarcodeScanner();

    // Check for barcode scanner toggle
    setupBarcodeScannerToggle();
    
    // Force update the clear cart button state
    forceUpdateClearCartButton();
});

/**
 * Force update the clear cart button state based on actual cart content
 */
function forceUpdateClearCartButton() {
    // Check if cart actually has items
    const hasItems = $('.product-lists').length > 0;
    console.log('Force updating clear cart button. Cart has items:', hasItems);
    
    const clearCartBtn = $('#clear-cart-btn');
    
    if (hasItems) {
        clearCartBtn.attr('href', 'javascript:void(0);')
            .removeClass('disabled')
            .css({
                'opacity': '1',
                'cursor': 'pointer'
            })
            .off('click')
            .on('click', function(e) {
                e.preventDefault();
                if (typeof bootstrap !== 'undefined' && $('#clearCartModal').length) {
                    try {
                        const clearModal = new bootstrap.Modal(document.getElementById('clearCartModal'));
                        clearModal.show();
                    } catch (error) {
                        console.log('Modal error, using fallback:', error);
                        if (confirm('Apakah Anda yakin ingin menghapus semua item dari keranjang Anda?')) {
                            window.location.href = 'index.php?clear_cart=1';
                        }
                    }
                } else {
                    if (confirm('Apakah Anda yakin ingin menghapus semua item dari keranjang Anda?')) {
                        window.location.href = 'index.php?clear_cart=1';
                    }
                }
            });
    } else {
        clearCartBtn.attr('href', '#')
            .addClass('disabled')
            .css({
                'opacity': '0.5',
                'cursor': 'not-allowed'
            })
            .off('click');
    }
}

/**
 * Initialize barcode scanner functionality
 */
function initBarcodeScanner() {
    const barcodeInput = $('#barcode-input');
    
    // Focus barcode input when barcode scanner container is clicked
    $('.barcode-scanner-container').on('click', function(e) {
        // Don't focus if clicking on a button or another interactive element
        if (!$(e.target).is('button, a, input')) {
            barcodeInput.focus();
        }
    });
    
    // Make barcode scanner sticky at top
    makeBarcodeSticky();
    
    // Handle barcode input
    barcodeInput.on('keydown', function(e) {
        // If Enter key is pressed, process the barcode
        if (e.keyCode === 13) {
            e.preventDefault();
            
            const barcode = $(this).val().trim();
            if (barcode) {
                processBarcode(barcode);
                $(this).val(''); // Clear input after processing
            }
        }
    });
    
    // Auto focus the barcode input when page loads
    setTimeout(function() {
        barcodeInput.focus();
    }, 500);
    
    // Handle window focus to automatically focus barcode input
    $(window).on('focus', function() {
        // Only focus if scanner is not collapsed
        if (!$('.barcode-scanner-container').hasClass('collapsed')) {
            setTimeout(function() {
                barcodeInput.focus();
            }, 100);
        }
    });
}

/**
 * Make barcode scanner sticky at top of page
 */
function makeBarcodeSticky() {
    const barcodeContainer = $('.barcode-scanner-container');
    if (!barcodeContainer.length) return;
    
    const originalPosition = barcodeContainer.offset() ? barcodeContainer.offset().top : 0;
    const headerHeight = $('.header').outerHeight() || 0;
    
    $(window).on('scroll', function() {
        const scrollPosition = $(window).scrollTop();
        
        if (scrollPosition > originalPosition - headerHeight) {
            barcodeContainer.addClass('sticky-scanner');
            barcodeContainer.css('top', headerHeight + 'px');
        } else {
            barcodeContainer.removeClass('sticky-scanner');
            barcodeContainer.css('top', '');
        }
    });
}

/**
 * Setup toggle for barcode scanner container
 */
function setupBarcodeScannerToggle() {
    $('#toggle-barcode-scanner').on('click', function() {
        $('.barcode-scanner-container').toggleClass('collapsed');
        $(this).find('i').toggleClass('fa-chevron-up fa-chevron-down');
        
        // Focus the input when expanded
        if (!$('.barcode-scanner-container').hasClass('collapsed')) {
            $('#barcode-input').focus();
        }
    });
}

/**
 * Process barcode and add product to cart
 */
function processBarcode(barcode) {
    // Show loading indicator
    $('.barcode-scanner-container').addClass('scanning');
    $('#barcode-status').html('<i class="fa fa-spinner fa-spin"></i> Memindai...');
    
    // Keep focus on the barcode input field
    const barcodeInput = $('#barcode-input');
    
    // Send AJAX request to get product by barcode
    $.ajax({
        url: 'get_product_by_barcode.php',
        type: 'POST',
        data: {
            barcode: barcode
        },
        dataType: 'json',
        success: function(response) {
            $('.barcode-scanner-container').removeClass('scanning');
            
            if (response.success) {
                // Show success message
                $('#barcode-status').html(
                    '<div class="text-success"><i class="fa fa-check-circle"></i> Ditambahkan: ' + 
                    response.product.name + '</div>'
                );
                
                // Highlight the product in the grid if visible (without scrolling)
                highlightProductInGrid(response.product.id);
                
                // Update cart section via AJAX
                if (response.cart_html) {
                    // Update the cart HTML
                    $('.product-table').html(response.cart_html);
                    
                    // Update cart totals and related UI elements
                    updateAllCartElements(response.cart_totals);
                    
                    // Make sure quantity controls and other event handlers are properly set up
                    if (typeof setupQuantityControls === 'function') {
                        setupQuantityControls();
                    }
                    if (typeof setupCartDeletionConfirmation === 'function') {
                        setupCartDeletionConfirmation();
                    }
                    
                    // Force update the clear cart button
                    forceUpdateClearCartButton();
                    
                    // Trigger custom event for other scripts to respond to cart updates
                    document.dispatchEvent(new CustomEvent('cartUpdated'));
                }
            } else {
                // Show error message
                $('#barcode-status').html(
                    '<div class="text-danger"><i class="fa fa-exclamation-circle"></i> ' + 
                    response.message + '</div>'
                );
            }
            
            // Reset status message after delay
            setTimeout(function() {
                $('#barcode-status').html('<i class="fa fa-barcode"></i> Pindai barcode');
                // Restore focus to barcode input
                barcodeInput.focus();
                // Try to restore cursor position
                if (barcodeInput.get(0) && barcodeInput.get(0).setSelectionRange) {
                    barcodeInput.get(0).setSelectionRange(0, 0);
                }
            }, 3000);
        },
        error: function(xhr, status, error) {
            $('.barcode-scanner-container').removeClass('scanning');
            console.error('AJAX Error:', status, error);
            
            // Try to get more detailed error info
            let errorMessage = 'Koneksi error';
            try {
                if (xhr.responseText) {
                    // Check if it's JSON
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        if (errorData && errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (jsonError) {
                        // Not valid JSON, just use generic message
                        errorMessage = 'Kesalahan sistem. Silakan coba lagi.';
                        console.log('Response was not valid JSON:', xhr.responseText.substring(0, 100));
                    }
                }
            } catch (e) {
                console.error('Error parsing error response:', e);
            }
            
            $('#barcode-status').html(
                '<div class="text-danger"><i class="fa fa-exclamation-circle"></i> ' + errorMessage + '</div>'
            );
            
            // Reset status message after delay
            setTimeout(function() {
                $('#barcode-status').html('<i class="fa fa-barcode"></i> Pindai barcode');
                barcodeInput.focus();
            }, 3000);
        }
    });
}

/**
 * Comprehensive update of all cart-related elements
 */
function updateAllCartElements(totals) {
    console.log('Updating all cart elements with totals:', totals);
    
    // 1. Update displayed values
    $('.totalitem h4').text('Total barang: ' + totals.items);
    $('.total-value h6').text('Rp. ' + totals.formatted_total);
    $('.btn-totallabel h6').text('Rp. ' + totals.formatted_total);
    
    // 2. Update form inputs
    $('#cash-amount').val(totals.formatted_total);
    $('#hidden-cash-amount').val(totals.total);
    
    // 3. Create and replace checkout form if doesn't exist or needs updating
    if (totals.items > 0) {
        // Only replace if needed
        if ($('#checkout-form').length === 0 || $('#checkout-form button[type="submit"]').prop('disabled')) {
            const checkoutFormHtml = `
                <form id="checkout-form" method="post" action="index.php">
                    <input type="hidden" name="cash_amount" id="hidden-cash-amount" value="${totals.total}">
                    <input type="hidden" name="change_amount" id="hidden-change-amount" value="0">
                    <input type="hidden" name="checkout" value="1">
                    <button type="submit" class="btn-totallabel w-100">
                        <h5>Pesan Sekarang</h5>
                        <h6>Rp. ${totals.formatted_total}</h6>
                    </button>
                </form>
            `;
            
            // Replace current form with new one
            if ($('#checkout-form').length > 0) {
                $('#checkout-form').replaceWith(checkoutFormHtml);
            } else {
                // If form doesn't exist, replace the disabled button
                $('.btn-totallabel.w-100[disabled]').replaceWith(checkoutFormHtml);
            }
            
            // Setup the new form
            if (typeof setupCheckoutForm === 'function') {
                setupCheckoutForm();
            }
        } else {
            // Just enable the existing button
            $('.btn-totallabel').prop('disabled', false);
            $('#checkout-form button[type="submit"]').prop('disabled', false);
        }
    } else {
        // Cart is empty, show disabled button
        if ($('#checkout-form').length > 0) {
            $('#checkout-form').replaceWith(`
                <button class="btn-totallabel w-100" disabled>
                    <h5>Pesan Sekarang</h5>
                    <h6>Rp. 0</h6>
                </button>
            `);
        }
    }
    
    // 4. Update quick cash buttons
    updateQuickCashButtons(totals.total);
    
    // 5. Show/hide change display
    updateChangeDisplay();
    
    // 6. Re-initialize scrollbars and event handlers
    initializeCartScrolling();
}

/**
 * Initialize scrolling behavior for cart
 */
function initializeCartScrolling() {
    // Make sure product table has correct max height
    const windowHeight = $(window).height();
    const headerHeight = $('.order-list').outerHeight() || 0;
    const cardHeaderHeight = $('.card-order .card-body:first-of-type').outerHeight() || 0;
    const cardFooterHeight = $('.card-order .card-body:last-of-type').outerHeight() || 0;
    const availableHeight = windowHeight - headerHeight - cardHeaderHeight - cardFooterHeight - 50; // 50px buffer
    
    // Set max height for scrollable area
    $('.product-table').css('max-height', availableHeight + 'px');
}

/**
 * Highlight product in grid when added via barcode
 */
function highlightProductInGrid(productId) {
    const productElement = $('.productset[data-product-id="' + productId + '"]');
    
    if (productElement.length) {
        // Flash highlight effect without scrolling
        productElement.addClass('highlight-product');
        setTimeout(function() {
            productElement.removeClass('highlight-product');
        }, 1500);
        
        // Optional: Briefly show which product was added via a popup notification
        showAddedProductNotification(productElement.find('.productsetcontent h4').text());
    }
}

/**
 * Show a notification that a product was added instead of scrolling
 */
function showAddedProductNotification(productName) {
    // Create notification element if it doesn't exist
    if ($('#barcode-notification').length === 0) {
        $('body').append('<div id="barcode-notification" class="barcode-notification"></div>');
    }
    
    // Show notification
    $('#barcode-notification')
        .text('Ditambahkan: ' + productName)
        .addClass('show');
    
    // Hide notification after 2 seconds
    setTimeout(function() {
        $('#barcode-notification').removeClass('show');
    }, 2000);
}

/**
 * Update quick cash buttons based on total amount
 */
function updateQuickCashButtons(totalAmount) {
    if (totalAmount > 0) {
        // Update round-up buttons for different denominations
        const round1000 = Math.ceil(totalAmount / 1000) * 1000;
        const round10000 = Math.ceil(totalAmount / 10000) * 10000;
        const round50000 = Math.ceil(totalAmount / 50000) * 50000;
        const round100000 = Math.ceil(totalAmount / 100000) * 100000;
        
        // Find quick cash buttons and update their values
        $('.quick-cash').each(function(index) {
            let newValue;
            switch(index) {
                case 0: newValue = round1000; break;
                case 1: newValue = round10000; break;
                case 2: newValue = round50000; break;
                case 3: newValue = round100000; break;
                default: newValue = totalAmount;
            }
            
            $(this).data('value', newValue);
            $(this).text('Rp. ' + formatNumber(newValue));
        });
        
        // Show row containing quick cash buttons
        $('.setvaluecash .row').show();
    } else {
        // Hide quick cash buttons if cart is empty
        $('.setvaluecash .row').hide();
    }
}

/**
 * Update change display based on current cash amount and total
 */
function updateChangeDisplay() {
    const cashInput = $('#cash-amount');
    const changeDisplay = $('#change-amount');
    const changeContainer = $('#change-container');
    const hiddenCashAmount = $('#hidden-cash-amount');
    const hiddenChangeAmount = $('#hidden-change-amount');
    
    // Get total from the displayed value or use 0 if not available
    const totalText = $('.total-value h6').text();
    const totalAmount = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;
    
    // Get cash value from hidden field
    let cashValue = parseFloat(hiddenCashAmount.val()) || 0;
    
    // Calculate change
    const change = cashValue - totalAmount;
    
    // Update hidden field for change amount
    hiddenChangeAmount.val(Math.max(0, change));
    
    // Format and display
    if (change >= 0 && totalAmount > 0) {
        changeDisplay.text('Rp. ' + formatNumber(change));
        changeContainer.show();
    } else {
        changeContainer.hide();
    }
}

/**
 * Format number with thousand separator
 */
function formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}