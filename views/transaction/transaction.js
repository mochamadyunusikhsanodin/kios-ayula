/**
 * Ayula Store POS System
 * Main JavaScript file for transaction page functionality
 */

$(document).ready(function() {
    // Initialize globals
    initializeGlobals();
    
    // Setup event handlers
    setupEventHandlers();
    
    // Initialize UI components
    initializeUI();
    
    // Check and update the "Clear All" button state on page load
    updateClearCartButtonInitialState();
});

/**
 * Initialize global variables and state
 */
function initializeGlobals() {
    // Variables to track selected products
    window.selectedProducts = [];
    window.multiSelectToolbar = $('.multi-select-toolbar');
    
    // Store product stock information
    window.productStock = {};
    $('.productset').each(function() {
        const productId = $(this).data('product-id');
        const stockText = $(this).find('.productsetimg h6').text();
        const stockValue = parseInt(stockText.replace('Stok: ', '')) || 0;
        window.productStock[productId] = stockValue;
    });
}

/**
 * Check and update the "Clear All" button state on page load
 */
function updateClearCartButtonInitialState() {
    const cartItemsCount = $('.product-lists').length;
    console.log('Initial cart items count:', cartItemsCount);
    
    // Get the clear cart button
    const clearCartBtn = $('#clear-cart-btn');
    
    if (cartItemsCount > 0) {
        // Enable the clear cart button
        clearCartBtn.attr('href', 'javascript:void(0);')
            .removeClass('disabled')
            .css({
                'opacity': '1',
                'cursor': 'pointer'
            });
        
        console.log('Clear cart button initially enabled');
    } else {
        // Disable the clear cart button
        clearCartBtn.attr('href', '#')
            .addClass('disabled')
            .css({
                'opacity': '0.5',
                'cursor': 'not-allowed'
            });
        
        console.log('Clear cart button initially disabled');
    }
}

/**
 * Setup all event handlers for the page
 */
function setupEventHandlers() {
    // Product card selection
    setupProductSelection();
    
    // Toolbar actions
    setupToolbarActions();
}

/**
 * Initialize UI components
 */
function initializeUI() {
    // Hide all check marks initially
    $('.check-product i').hide();
    
    // Trigger toggle button click to activate it when page loads
    setTimeout(function() {
        $("#toggle_btn").trigger('click');
    }, 100);
    
    // Focus search field when search icon is clicked
    $('.responsive-search').on('click', function() {
        setTimeout(function() {
            $('input[name="search"]').focus();
        }, 100);
    });
    
    // Focus the page header search field on click
    $('.product-search-form input[name="search"]').on('click', function() {
        $(this).focus();
    });
    
    // Enable live search functionality
    setupLiveSearch();
    
    // Enable AJAX category navigation
    setupCategoryNavigation();
    
    // Initialize cash payment functionality
    setupCashPayment();
}

/**
 * Setup product selection functionality
 */
function setupProductSelection() {
    // Click on product card to toggle selection
    $('.productset').on('click', function(e) {
        // Only handle clicks on the card itself, not buttons or links inside
        if ($(e.target).closest('button, a, form').length === 0) {
            const productId = $(this).data('product-id');
            const checkbox = $(this).find('.product-checkbox');
            const isChecked = checkbox.prop('checked');

            console.log('Clicked product ID:', productId);

            // Toggle checkbox
            checkbox.prop('checked', !isChecked);

            if (!isChecked) {
                // Add to selected list and highlight
                window.selectedProducts.push(productId);
                $(this).addClass('selected');
                $(this).find('.check-product i').show();
            } else {
                // Remove from selected list and unhighlight
                window.selectedProducts = window.selectedProducts.filter(id => id !== productId);
                $(this).removeClass('selected');
                $(this).find('.check-product i').hide();
            }

            console.log('Selected products:', window.selectedProducts);
            updateToolbar();
        }
    });
}

/**
 * Setup toolbar action buttons
 */
function setupToolbarActions() {
    // Cancel selection button
    $('#cancel-selection').on('click', function() {
        // Uncheck all checkboxes and hide check marks
        $('.product-checkbox').prop('checked', false);
        $('.productset').removeClass('selected');
        $('.check-product i').hide();
        window.selectedProducts = [];
        updateToolbar();
    });

    // Add selected products to cart
    $('#add-selected-to-cart').on('click', function() {
        if (window.selectedProducts.length > 0) {
            console.log('Adding products to cart:', window.selectedProducts);

            // Create a form to submit selected products
            const form = $('<form>', {
                method: 'post',
                action: 'index.php'
            });

            // Add each product ID as a hidden input
            window.selectedProducts.forEach(function(productId) {
                form.append(
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'product_ids[]',
                        value: productId
                    })
                );
            });

            // Log form contents before submission
            console.log('Form contents:', form.serialize());

            // Add the form to the body and submit it
            $('body').append(form);
            form.submit();
        }
    });
}

/**
 * Update the toolbar state based on selected products
 */
function updateToolbar() {
    if (window.selectedProducts.length > 0) {
        $('.selected-count').text(window.selectedProducts.length + ' item' + (window.selectedProducts.length > 1 ? 's' : '') + ' selected');
        window.multiSelectToolbar.addClass('active');
    } else {
        window.multiSelectToolbar.removeClass('active');
    }
}

/**
 * Setup live search functionality
 */
function setupLiveSearch() {
    // Handle both search inputs - top nav and product header search
    const searchInputs = $('input[name="search"]');
    
    // Store the original URL for reference
    const originalUrl = window.location.href.split('?')[0];
    const urlParams = new URLSearchParams(window.location.search);
    
    // Clear search buttons functionality
    $('.product-search-form a, .search-addon a').on('click', function(e) {
        e.preventDefault();
        urlParams.delete('search');
        
        // Build the new URL
        let newUrl = originalUrl;
        const paramString = urlParams.toString();
        if (paramString) {
            newUrl += '?' + paramString;
        }
        
        // Navigate to the new URL
        window.location.href = newUrl;
    });
    
    // Custom form submission to prevent loader
    $('.product-search-form form, .top-nav-search form').on('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission
        
        // Get the search query
        const searchQuery = $(this).find('input[name="search"]').val().trim();
        
        // Get any type filter
        const typeParam = $(this).find('input[name="type"]').val();
        
        // Build the URL
        let newUrl = originalUrl;
        if (searchQuery || typeParam) {
            newUrl += '?';
            
            if (searchQuery) {
                newUrl += 'search=' + encodeURIComponent(searchQuery);
                if (typeParam) {
                    newUrl += '&';
                }
            }
            
            if (typeParam) {
                newUrl += 'type=' + encodeURIComponent(typeParam);
            }
        }
        
        // Use AJAX to fetch the page content
        $.ajax({
            url: newUrl,
            type: 'GET',
            beforeSend: function() {
                // Add a simple loading indicator to the product area
                $('.tab_content').addClass('loading');
                $('.tab_content > .row').css('opacity', '0.5');
            },
            success: function(response) {
                // Extract and replace just the product content
                const $response = $(response);
                const newProductContent = $response.find('.tab_content').html();
                
                // Update the DOM with new content
                $('.tab_content').html(newProductContent);
                
                // Update browser URL without reloading
                history.pushState({}, '', newUrl);
                
                // Update search info area if it exists
                const searchInfoContent = $response.find('.search-results-info').html();
                if (searchInfoContent) {
                    if ($('.search-results-info').length) {
                        $('.search-results-info').html(searchInfoContent);
                    } else {
                        $('<div class="search-results-info mb-3"></div>')
                            .html(searchInfoContent)
                            .insertAfter('.page-header');
                    }
                    $('.search-results-info').show();
                } else {
                    $('.search-results-info').hide();
                }
                
                // Update the other search input with the same value
                searchInputs.val(searchQuery);
                
                // Reset the loading state
                $('.tab_content').removeClass('loading');
                $('.tab_content > .row').css('opacity', '1');
                
                // Reinitialize product selection for newly loaded products
                setupProductSelection();
            },
            error: function() {
                // If something goes wrong, just do a normal page load
                window.location.href = newUrl;
            }
        });
    });
}

/**
 * Setup AJAX-based category navigation
 */
function setupCategoryNavigation() {
    // Original URL for reference
    const originalUrl = window.location.href.split('?')[0];
    
    // Add click event handlers to all category links
    $('.tabs li a.category-tab').on('click', function(e) {
        e.preventDefault();
        
        // Get category type from URL
        const href = $(this).attr('href');
        const url = new URL(href, window.location.origin);
        const typeParam = url.searchParams.get('type');
        
        // Get current search query if any
        const currentUrl = new URL(window.location.href);
        const searchQuery = currentUrl.searchParams.get('search');
        
        // Build the target URL
        let targetUrl = originalUrl;
        const params = new URLSearchParams();
        
        // Add type parameter if set
        if (typeParam) {
            params.set('type', typeParam);
        }
        
        // Preserve search query if exists
        if (searchQuery) {
            params.set('search', searchQuery);
        }
        
        // Append parameters to URL if any
        const paramString = params.toString();
        if (paramString) {
            targetUrl += '?' + paramString;
        }
        
        // Highlight the active category tab
        $('.tabs li').removeClass('active');
        $(this).closest('li').addClass('active');
        
        // Use AJAX to fetch the category products
        $.ajax({
            url: targetUrl,
            type: 'GET',
            beforeSend: function() {
                // Add loading indicator to product area
                $('.tab_content').addClass('loading');
                $('.tab_content > .row').css('opacity', '0.5');
            },
            success: function(response) {
                // Extract and replace just the product content
                const $response = $(response);
                const newProductContent = $response.find('.tab_content').html();
                
                // Update the DOM with new content
                $('.tab_content').html(newProductContent);
                
                // Update browser URL without reloading
                history.pushState({}, '', targetUrl);
                
                // Reset the loading state
                $('.tab_content').removeClass('loading');
                $('.tab_content > .row').css('opacity', '1');
                
                // Reinitialize product selection for newly loaded products
                setupProductSelection();
            },
            error: function() {
                // If something goes wrong, just do a normal page load
                window.location.href = targetUrl;
            }
        });
    });
}

/**
 * Setup cash payment functionality
 */
function setupCashPayment() {
    const cashInput = $('#cash-amount');
    const changeDisplay = $('#change-amount');
    const changeContainer = $('#change-container');
    const hiddenCashAmount = $('#hidden-cash-amount');
    const hiddenChangeAmount = $('#hidden-change-amount');
    const totalAmount = parseFloat($('.total-value h6').text().replace(/[^\d]/g, ''));
    
    // Format cash input with thousand separators
    cashInput.on('input', function() {
        // Remove non-numeric characters
        let value = $(this).val().replace(/[^\d]/g, '');
        
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
    
    // Form submission check
    $('#checkout-form').on('submit', function(e) {
        const cashValue = parseFloat(hiddenCashAmount.val());
        
        // Check if cash amount is sufficient
        if (cashValue < totalAmount) {
            e.preventDefault();
            alert('Cash amount is not sufficient!');
            return false;
        }
        
        return true;
    });
    
    // Trigger input event to initialize on page load
    cashInput.trigger('input');
}

/**
 * Format number with thousand separator
 */
function formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}