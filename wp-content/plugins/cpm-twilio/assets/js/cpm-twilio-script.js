let $ = jQuery;

$(document).ready(function () {

    let userID = 0;
    let nonce = 0;
    const $inputs = $('.otp-input');

    function validatePhoneNumber(phone) {
        return /^\d{10}$/.test(phone);
    }

    function validateOTP(otp) {
        return /^\d{6}$/.test(otp);
    }

    function validateIndividualOTPinput(otp) {
        return /^\d{1}$/.test(otp);
    }

    function checkIfAllOTPFieldsValid() {
        let allFilled = true;
        $inputs.each(function () {
            if ($(this).val().length === 0) {
                allFilled = false;
                return false;
            }
        });
        $('#validate_otp').prop('disabled', !allFilled);
    }

    //display form error
    function displayFormMsg(msg, type = 'fail') {
        $('.form-msg').html('');
        let icon = '';
        let pClass = '';
        if (type == 'fail') {
            pClass = 'form-err';
            icon = '<i class="fa-regular fa-circle-xmark"></i>';
        } else if (type == 'success') {
            pClass = 'form-success';
            icon = '<i class="fa-regular fa-circle-check"></i>';
        }
        $('.form-msg').html('<p class=' + pClass + '>' + icon + msg + '</p>');
    }

    //clear form error
    function clearFormMsg() {
        $('.form-msg').html('');
    }

    // Function to get scan data from localStorage
    function getScanDataFromLocalStorage() {
        const scanData = localStorage.getItem('scan data');
        if (scanData) {
            try {
                return JSON.parse(scanData);
            } catch (e) {
                console.error('Error parsing localStorage data:', e);
                return null;
            }
        }
        return null;
    }

    // Function to save/update scan data to localStorage
    function saveScanDataToLocalStorage(additionalData) {
        const urlParams = new URLSearchParams(window.location.search);
        const scanType = urlParams.get('scan_type');
        
        if (scanType === 'proof') {
            // Get existing data if any
            let scanData = getScanDataFromLocalStorage();
            
            // If no existing data, create new
            if (!scanData) {
                scanData = {
                    scan_type: scanType,
                    delivery_proof: 'yes',
                    timestamp: new Date().toISOString()
                };
            }
            
            // Merge additional data
            Object.assign(scanData, additionalData);
            
            // Save back to localStorage
            localStorage.setItem('scan data', JSON.stringify(scanData));
            console.log('Scan data saved to localStorage:', scanData);
        }
    }

    // Function to proceed with user login and redirect
    // Made available globally to ensure it's accessible from popup close handlers
    window.proceedWithLogin = function(userId, nonce, redirectUrl) {
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: "POST",
            data: {
                action: "ct_user_signin",
                userId: userId,
                nonce: nonce,
            },
            success: function (response) {
                if (response.data[0] == "logged_in") {
                    // Redirect to specified URL or default to wallet page
                    const redirect = redirectUrl || (window.pendingLogin && window.pendingLogin.redirectUrl) || '/my-account/detente-wallet/';
                    window.location.href = redirect;
                } else if (response.data[0] == 'nonce_failed') {
                    displayFormMsg('Security check failed');
                } else {
                    console.error('Login failed:', response);
                    displayFormMsg('Error signing in. Please try again.');
                }
            },
            error: function () {
                console.error('Login AJAX error');
                displayFormMsg('Error signing in. Please try again.');
            }
        });
    };

    // Function to show transaction code input popup for buyers
    function showTransactionCodePopup() {
        jQuery('#cpp-popup-transaction-code').fadeIn();
        
        // Clear any previous error messages
        jQuery('#transaction-code-error').hide().text('');
        jQuery('#transaction-code-input').val('');
        
        // Close button handler
        jQuery('.cpp-close-transaction-code').off('click').on('click', function() {
            jQuery('#cpp-popup-transaction-code').fadeOut();
        });
        
        // Form submission handler
        jQuery('#transaction-code-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            
            const transactionCode = jQuery('#transaction-code-input').val().trim();
            const errorDiv = jQuery('#transaction-code-error');
            const submitBtn = jQuery('#submit-transaction-code');
            
            if (!transactionCode) {
                errorDiv.text('Please enter a transaction code').show();
                return;
            }
            
            // Disable submit button and show loading
            submitBtn.prop('disabled', true).text('Verifying...');
            errorDiv.hide();
            
            // Verify transaction code via AJAX
            jQuery.ajax({
                url: ct_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ct_verify_transaction_code',
                    transaction_code: transactionCode,
                    nonce: ct_ajax.nonce || ''
                },
                success: function(response) {
                    submitBtn.prop('disabled', false).text('Submit');
                    
                    if (response.success && response.data && response.data.verified) {
                        console.log('Transaction code verified successfully');
                        console.log('Seller ID:', response.data.seller_id);
                        console.log('Entry Index:', response.data.entry_index);
                        
                        // Update transaction_id and store seller info in pending buyer data
                        if (window.pendingBuyerData) {
                            window.pendingBuyerData.allData.transaction_id = transactionCode;
                            window.pendingBuyerData.seller_id = response.data.seller_id;
                            window.pendingBuyerData.seller_entry_index = response.data.entry_index;
                            
                            // Close transaction code popup
                            jQuery('#cpp-popup-transaction-code').fadeOut();
                            
                            // Proceed with buyer data insertion
                            proceedWithBuyerDataInsertion(window.pendingBuyerData);
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Invalid transaction code. Please check and try again.';
                        errorDiv.text(errorMsg).show();
                    }
                },
                error: function(xhr, status, error) {
                    submitBtn.prop('disabled', false).text('Submit');
                    errorDiv.text('Error verifying transaction code. Please try again.').show();
                    console.error('Transaction code verification error:', error);
                }
            });
        });
    }
    
    // Function to proceed with buyer data insertion after transaction code verification
    function proceedWithBuyerDataInsertion(pendingData) {
        const { allData, selectedRole, discordData, nonce, userID, seller_id, seller_entry_index } = pendingData;
        
        console.log('Proceeding with buyer scan insertion after transaction code verification...');
        console.log('Seller ID:', seller_id);
        console.log('Seller Entry Index:', seller_entry_index);
        
        // Get geolocation first, then prepare data for usermeta insertion
        console.log('Getting geolocation for buyer scan...');
        getGeolocation(function(geoData) {
            // Get current timestamp
            const currentTimestamp = new Date().toISOString();
            
            // Prepare data for usermeta insertion
            const roleToSend = allData.role || selectedRole || '';
            console.log('Role being sent to server:', roleToSend);
            
            const usermetaData = {
                delivery_proof: allData.delivery_proof || 'yes',
                discord_join: allData.discord_join || false,
                'mega-mobile': allData['mega-mobile'] || '',
                percentage: allData.percentage || 0,
                transaction_id: allData.transaction_id || '', // This is the verified transaction code
                role: roleToSend,
                scan_status: 'confirmed', // Buyer scan is confirmed when transaction code matches
                scan_type: allData.scan_type || 'proof',
                status: allData.status || 'pending',
                timestamp: currentTimestamp,
                treasury_distributed: allData.treasury_distributed || 0,
                xp_units: allData.xp_units || 0,
                yam_value: allData.yam_value || 0,
                user_id: allData.user_id,
                seller_id: seller_id || null, // Add seller_id to buyer entry
                // Add geolocation data
                geolocation: {
                    latitude: geoData.latitude,
                    longitude: geoData.longitude,
                    accuracy: geoData.accuracy,
                    timestamp: geoData.timestamp,
                    error: geoData.error
                }
            };
            
            console.log('Final usermetaData being sent:', usermetaData);
            
            // Insert data to usermeta with seller info for updating seller's entry
            insertScanDataToUsermeta(usermetaData, function(success) {
                if (success) {
                    console.log('Buyer scan data inserted successfully with geolocation');
                    
                    // Save all data to localStorage (including geolocation and transaction_id)
                    allData.geolocation = geoData;
                    allData.timestamp = currentTimestamp;
                    saveScanDataToLocalStorage(allData);
                    
                    console.log('All calculation data saved to localStorage:', allData);
                    
                    // Store userID and nonce for later login (after popup closes)
                    window.pendingLogin = {
                        userId: discordData.user_id,
                        nonce: nonce
                    };
                    
                    // Clear pending buyer data
                    window.pendingBuyerData = null;
                    
                    // For buyer role, redirect to payment page if pending orders exist
                    if (roleToSend && (roleToSend.toLowerCase().indexOf('buyer') !== -1 || roleToSend.indexOf('7%') !== -1)) {
                        // Buyer role - redirect to payment page if pending orders exist
                        redirectToPaymentPageIfPendingOrders(discordData.user_id, nonce);
                    } else {
                        // Other roles (seller, personal) - show calculation popup
                        showCalculationPopup();
                    }
                } else {
                    console.error('Failed to insert buyer scan data. Not showing calculation popup.');
                    // Error popup is already shown by insertScanDataToUsermeta
                    // Don't show calculation popup or proceed with login
                }
            }, seller_id, seller_entry_index); // Pass seller info for updating seller's entry
        });
    }

    // Function to show calculation popup with localStorage data
    function showCalculationPopup() {
        const scanData = getScanDataFromLocalStorage();
        
        if (!scanData) {
            console.warn('No scan data found in localStorage');
            // Redirect to wallet page if no data
            window.location.href = '/my-account/detente-wallet/';
            return;
        }
        
        // Determine role and percentage
        const role = scanData.role || '';
        let roleName = '';
        let percentage = scanData.percentage || 0;
        // Use full trade_value (not user's share) for display
        let tradeValue = scanData.trade_value || 10.30;
        let xpAmount = scanData.xp_units || 0;
        
        // Set role name based on role string, but preserve percentage from scanData if available
        if (role.toLowerCase().indexOf('seller') !== -1 || role.indexOf('3%') !== -1) {
            roleName = 'seller';
            if (!percentage) percentage = 3;
        } else if (role.toLowerCase().indexOf('buyer') !== -1 || role.indexOf('7%') !== -1) {
            roleName = 'buyer';
            if (!percentage) percentage = 7;
        } else if (role.toLowerCase().indexOf('personal') !== -1 || role.indexOf('10%') !== -1) {
            roleName = 'personal';
            if (!percentage) percentage = 10;
        }
        
        // Helper function to format XP in scientific notation (e.g., "3.09 × 10²²")
        function formatXPInScientificNotation(xpValue) {
            if (!xpValue || xpValue == 0) return '0 XP';
            
            const xpNum = parseFloat(xpValue);
            if (xpNum == 0) return '0 XP';
            
            // Convert to scientific notation
            const scientific = xpNum.toExponential(2);
            const parts = scientific.split('e');
            const base = parseFloat(parts[0]);
            const exponent = parseInt(parts[1]);
            
            // Format as: base × 10^exponent
            // Remove trailing zeros from base if it's a whole number
            const baseDisplay = base % 1 === 0 ? base.toFixed(0) : base.toFixed(2);
            
            return baseDisplay + ' × 10<sup>' + exponent + '</sup> XP';
        }
        
        // Format XP amount in scientific notation
        const xpFormatted = xpAmount ? formatXPInScientificNotation(xpAmount) : '0 XP';
        
        // Get transaction_id from scanData
        const transactionId = scanData.transaction_id || '';
        
        // Format the simplified message
        let html = '<div style="font-family: Arial, sans-serif; text-align: center;">';
        
        // Title
        html += '<h2 style="color: #27ae60; margin-top: 0; margin-bottom: 20px; font-size: 28px;">Congratulations!</h2>';
        
        // Main message
        html += '<p style="color: #2c3e50; font-size: 18px; margin: 20px 0; line-height: 1.6;">Proof of Delivery recorded. XP minted successfully</p>';
        
        // Role-specific message
        if (roleName) {
            html += '<p style="color: #2c3e50; font-size: 16px; margin: 20px 0; line-height: 1.6;">';
            html += 'As a <strong>' + roleName + '</strong> you have received <strong>' + percentage + '%</strong> of trade value <strong>$' + parseFloat(tradeValue).toFixed(2) + '</strong> (' + xpFormatted + ')';
            html += '</p>';
        }
        
        // Transaction ID section - Only show for seller role
        if (transactionId && roleName && roleName.toLowerCase() === 'seller') {
            html += '<div style="background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 25px 0; text-align: center;">';
            html += '<p style="color: #2c3e50; font-size: 16px; font-weight: bold; margin: 0 0 15px 0;">Your Transaction Code</p>';
            html += '<div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 15px;">';
            html += '<span id="transaction-code-display" style="background: #fff; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 15px; font-size: 18px; font-weight: bold; color: #27ae60; letter-spacing: 1px; font-family: monospace; flex: 1; max-width: 300px; word-break: break-all;">' + transactionId + '</span>';
            html += '<button id="copy-transaction-code" style="background: #27ae60; color: white; border: none; border-radius: 4px; padding: 10px 20px; font-size: 14px; font-weight: bold; cursor: pointer; transition: background 0.3s; white-space: nowrap;">Copy</button>';
            html += '</div>';
            html += '<p style="color: #7f8c8d; font-size: 13px; margin: 0; line-height: 1.5;">Save the code that you can share the code to the buyer later.</p>';
            html += '</div>';
        }
        
        // Tagline
        html += '<p style="color: #7f8c8d; font-size: 14px; margin: 30px 0 0 0; font-style: italic; line-height: 1.5;">No money moves — trade value accrues until August 31, 2030.</p>';
        
        html += '</div>';
        
        // Update popup content
        jQuery('#calculation-results').html(html);
        
        // Fallback copy function for older browsers
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    const copyBtn = jQuery('#copy-transaction-code');
                    const originalText = copyBtn.text();
                    copyBtn.text('Copied!').css('background', '#2ecc71');
                    
                    setTimeout(function() {
                        copyBtn.text(originalText).css('background', '#27ae60');
                    }, 2000);
                } else {
                    alert('Failed to copy. Please copy manually: ' + text);
                }
            } catch (err) {
                console.error('Fallback copy failed: ', err);
                alert('Failed to copy. Please copy manually: ' + text);
            }
            
            document.body.removeChild(textArea);
        }
        
        // Add copy functionality for transaction code
        if (transactionId) {
            jQuery('#copy-transaction-code').off('click').on('click', function() {
                const codeToCopy = transactionId;
                
                // Use modern Clipboard API if available
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(codeToCopy).then(function() {
                        // Show success feedback
                        const copyBtn = jQuery('#copy-transaction-code');
                        const originalText = copyBtn.text();
                        copyBtn.text('Copied!').css('background', '#2ecc71');
                        
                        setTimeout(function() {
                            copyBtn.text(originalText).css('background', '#27ae60');
                        }, 2000);
                    }).catch(function(err) {
                        console.error('Failed to copy: ', err);
                        // Fallback to old method
                        fallbackCopyTextToClipboard(codeToCopy);
                    });
                } else {
                    // Fallback for older browsers
                    fallbackCopyTextToClipboard(codeToCopy);
                }
            });
        }
        
        // Show popup
        jQuery('#cpp-popup-calculation').fadeIn();
        
        // Close handlers
        jQuery('.cpp-close-calculation, #cpp-close-calculation-btn').off('click').on('click', function() {
            jQuery('#cpp-popup-calculation').fadeOut(function() {
                // If there's pending login data, log in first, then redirect
                if (window.pendingLogin && window.pendingLogin.userId && window.pendingLogin.nonce) {
                    window.proceedWithLogin(window.pendingLogin.userId, window.pendingLogin.nonce);
                } else {
                    // Direct redirect to wallet page if no pending login
                    window.location.href = '/my-account/detente-wallet/';
                }
            });
        });
    }

    // Function to redirect to payment page if pending orders exist
    function redirectToPaymentPageIfPendingOrders(userId, nonce) {
        console.log('Checking for pending orders for user:', userId);
        
        // Fetch pending orders via AJAX
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ct_get_pending_orders',
                user_id: userId,
                nonce: nonce || ct_ajax.nonce || ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    const orders = response.data.orders || [];
                    const orderCount = response.data.count || 0;
                    
                    if (orderCount > 0 && orders.length > 0) {
                        // Get the latest order (first one in the array, or find the most recent)
                        // Orders are typically returned in reverse chronological order
                        const latestOrder = orders[0];
                        
                        if (latestOrder && latestOrder.payment_url) {
                            console.log('Redirecting to payment page for order:', latestOrder.order_id);
                            // If there's pending login data, log in first, then redirect
                            if (window.pendingLogin && window.pendingLogin.userId && window.pendingLogin.nonce) {
                                window.proceedWithLogin(window.pendingLogin.userId, window.pendingLogin.nonce, latestOrder.payment_url);
                            } else {
                                // Direct redirect to payment page
                                window.location.href = latestOrder.payment_url;
                            }
                        } else {
                            console.log('No payment URL found for latest order');
                            // Redirect to orders page if no payment URL
                            if (window.pendingLogin && window.pendingLogin.userId && window.pendingLogin.nonce) {
                                window.proceedWithLogin(window.pendingLogin.userId, window.pendingLogin.nonce, '/my-account/detente-orders/');
                            } else {
                                window.location.href = '/my-account/detente-orders/';
                            }
                        }
                    } else {
                        console.log('No pending orders found');
                        // No pending orders - redirect to orders page or wallet
                        if (window.pendingLogin && window.pendingLogin.userId && window.pendingLogin.nonce) {
                            window.proceedWithLogin(window.pendingLogin.userId, window.pendingLogin.nonce, '/my-account/detente-orders/');
                        } else {
                            window.location.href = '/my-account/detente-orders/';
                        }
                    }
                } else {
                    console.error('Failed to fetch pending orders');
                    // On error, redirect to orders page
                    if (window.pendingLogin && window.pendingLogin.userId && window.pendingLogin.nonce) {
                        window.proceedWithLogin(window.pendingLogin.userId, window.pendingLogin.nonce, '/my-account/detente-orders/');
                    } else {
                        window.location.href = '/my-account/detente-orders/';
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching pending orders:', error);
                // On error, redirect to orders page
                if (window.pendingLogin && window.pendingLogin.userId && window.pendingLogin.nonce) {
                    window.proceedWithLogin(window.pendingLogin.userId, window.pendingLogin.nonce, '/my-account/detente-orders/');
                } else {
                    window.location.href = '/my-account/detente-orders/';
                }
            }
        });
    }

    // Function to show pending orders popup for buyer (kept for reference, not used anymore)
    function showPendingOrdersPopup(userId, nonce) {
        console.log('Fetching pending orders for user:', userId);
        
        // Show loading state
        let html = '<div style="font-family: Arial, sans-serif; text-align: center; padding: 20px;">';
        html += '<h2 style="color: #2c3e50; margin-top: 0; margin-bottom: 20px; font-size: 24px;">Loading Pending Orders...</h2>';
        html += '<p style="color: #7f8c8d; font-size: 14px;">Please wait...</p>';
        html += '</div>';
        jQuery('#calculation-results').html(html);
        jQuery('#cpp-popup-calculation').fadeIn();
        
        // Fetch pending orders via AJAX
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ct_get_pending_orders',
                user_id: userId,
                nonce: nonce || ct_ajax.nonce || ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    const orders = response.data.orders || [];
                    const totalSevenPercent = response.data.total_seven_percent || '$0.00';
                    const orderCount = response.data.count || 0;
                    
                    // Build HTML for pending orders
                    let html = '<div style="font-family: Arial, sans-serif;">';
                    html += '<h2 style="color: #2c3e50; margin-top: 0; margin-bottom: 20px; font-size: 24px; text-align: center;">Pending Orders</h2>';
                    
                    if (orderCount > 0) {
                        html += '<div style="overflow-x: auto; margin: 20px 0;">';
                        html += '<table style="width: 100%; border-collapse: collapse; font-size: 14px;">';
                        html += '<thead>';
                        html += '<tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">';
                        html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50;">Order</th>';
                        html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50;">Date</th>';
                        html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50;">Total</th>';
                        html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50;">7% (unfunded)</th>';
                        html += '<th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50;">Actions</th>';
                        html += '</tr>';
                        html += '</thead>';
                        html += '<tbody>';
                        
                        orders.forEach(function(order) {
                            html += '<tr style="border-bottom: 1px solid #e9ecef;">';
                            html += '<td style="padding: 12px;"><a href="' + order.order_url + '" style="color: #3498db; text-decoration: none;">' + order.order_number + '</a></td>';
                            html += '<td style="padding: 12px; color: #2c3e50;">' + order.date + '</td>';
                            html += '<td style="padding: 12px; color: #2c3e50;">' + order.total + ' for ' + order.quantity + ' item(s)</td>';
                            html += '<td style="padding: 12px; color: #2c3e50;">' + order.seven_percent + '</td>';
                            html += '<td style="padding: 12px;">';
                            if (order.can_pay) {
                                html += '<a href="' + order.payment_url + '" style="background: #27ae60; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600;">Pay Now</a>';
                            } else {
                                html += '<span style="color: #95a5a6; font-style: italic; font-size: 13px;">Not Payable</span>';
                            }
                            html += '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody>';
                        html += '</table>';
                        html += '</div>';
                        html += '<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">';
                        html += '<p style="margin: 0; color: #2c3e50; font-size: 16px; font-weight: 600;">Total 7% (unfunded): ' + totalSevenPercent + '</p>';
                        html += '</div>';
                    } else {
                        html += '<div style="text-align: center; padding: 40px 20px;">';
                        html += '<p style="color: #7f8c8d; font-size: 16px; margin: 0;">No pending orders found.</p>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    
                    // Update popup content
                    jQuery('#calculation-results').html(html);
                } else {
                    // Error fetching orders
                    let html = '<div style="font-family: Arial, sans-serif; text-align: center; padding: 20px;">';
                    html += '<h2 style="color: #e74c3c; margin-top: 0; margin-bottom: 20px; font-size: 24px;">Error</h2>';
                    html += '<p style="color: #7f8c8d; font-size: 14px;">Failed to load pending orders. Please try again.</p>';
                    html += '</div>';
                    jQuery('#calculation-results').html(html);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching pending orders:', error);
                let html = '<div style="font-family: Arial, sans-serif; text-align: center; padding: 20px;">';
                html += '<h2 style="color: #e74c3c; margin-top: 0; margin-bottom: 20px; font-size: 24px;">Error</h2>';
                html += '<p style="color: #7f8c8d; font-size: 14px;">Failed to load pending orders. Please try again.</p>';
                html += '</div>';
                jQuery('#calculation-results').html(html);
            }
        });
        
        // Close handlers
        jQuery('.cpp-close-calculation, #cpp-close-calculation-btn').off('click').on('click', function() {
            jQuery('#cpp-popup-calculation').fadeOut(function() {
                // If there's pending login data, log in first, then redirect to wallet page
                if (window.pendingLogin && window.pendingLogin.userId && window.pendingLogin.nonce) {
                    window.proceedWithLogin(window.pendingLogin.userId, window.pendingLogin.nonce);
                } else {
                    // Just close the popup, no redirect
                    // User can navigate manually if needed
                }
            });
        });
    }

    // Function to calculate all trade values and treasury calculations based on role
    // NEW CONVERSION: 1 XP = 1 sextillionth of a penny (10^-21)
    // $1.00 = 100 pennies = 100 × 10^21 = 10^23 XP
    // Formula: XP = (USD × 100) × 10^21
    function calculateTradeValue(role) {
        // Fixed trade value - $10.00 base (not $10.30)
        const trade_value = 10.00;
        
        // Role-based percentage rates and treasury_distributed
        // Seller: 3% of $10 = $0.30 = 3 × 10²² XP
        // Buyer: 7% of $10 = $0.70 = 7 × 10²² XP
        // Personal: 10% of $10 = $1.00 = 1 × 10²³ XP
        const roleData = {
            'seller': {
                percentage: 3,
                treasury_distributed: 0.30
            },
            'buyer': {
                percentage: 7,
                treasury_distributed: 0.70
            },
            'personal': {
                percentage: 10,
                treasury_distributed: 1.00
            }
        };
        
        // Get role data
        const roleInfo = roleData[role] || { percentage: 0, treasury_distributed: 0 };
        const percentage = roleInfo.percentage;
        const treasury_distributed = roleInfo.treasury_distributed;
        
        // Calculate trade value USD based on role percentage (using $10.00 base)
        const tradeValueUSD = trade_value * (percentage / 100);
        
        // NEW: Convert USD directly to XP (integer-safe calculation using string math)
        // Formula: XP = (USD × 100) × 10^21
        // Step 1: Convert USD to cents (as integer)
        const centsDecimal = tradeValueUSD * 100; // e.g., 0.30 × 100 = 30 cents, 0.70 × 100 = 70 cents, 1.00 × 100 = 100 cents
        // Step 2: Convert to integer string (no decimals needed for whole cents)
        const centsStr = Math.floor(centsDecimal).toString(); // e.g., "30", "70", "100"
        // Step 3: Multiply by 10^21 (append 21 zeros)
        // cents × 10^21 = cents followed by 21 zeros
        const xpUnitsStr = centsStr + '000000000000000000000'; // Append 21 zeros
        // This gives us the exact XP value as a string
        const xpUnits = parseFloat(xpUnitsStr); // For display calculations (may lose precision)
        
        // Calculate treasury remainder (10.00 - treasury_distributed)
        const treasury_reminder = parseFloat((trade_value - treasury_distributed).toFixed(2));
        
        // Calculate XP for treasury remainder using string math (same method as above)
        const treasury_reminder_cents = treasury_reminder * 100; // Convert to cents
        const treasury_reminder_cents_str = Math.floor(treasury_reminder_cents).toString(); // Integer cents
        const xp_reminder_str = treasury_reminder_cents_str + '000000000000000000000'; // Append 21 zeros
        const xp_reminder = parseFloat(xp_reminder_str); // For display
        
        // Legacy YAM calculation (for backward compatibility in display)
        // YAM is no longer used as intermediate, but kept for display purposes
        const yamValue = tradeValueUSD * 21000; // Legacy: 21,000 YAM = $1 USD
        const yam_reminder = treasury_reminder * 21000; // Legacy calculation
        
        // Display calculation in console for debugging
        console.log('=== Trade Value Calculation (UPDATED: $10.00 Base) ===');
        console.log('Trade Value (Base):', trade_value);
        console.log('Selected Role:', role);
        console.log('Percentage:', percentage + '%');
        console.log('Trade Value USD (user share):', tradeValueUSD.toFixed(2));
        console.log('XP Units (user share):', xpUnits.toExponential(2), '(', xpUnits, ')');
        console.log('Expected XP: Seller=3×10²², Buyer=7×10²², Personal=1×10²³');
        console.log('Treasury Distributed:', treasury_distributed);
        console.log('Treasury Reminder:', treasury_reminder.toFixed(2));
        console.log('XP Reminder:', xp_reminder.toExponential(2), '(', xp_reminder, ')');
        console.log('Legacy YAM Value (display only):', yamValue.toFixed(2));
        console.log('================================');
        
        // Return values - XP values are already strings to avoid JavaScript precision loss
        return {
            trade_value: trade_value,
            percentage: percentage,
            treasury_distributed: parseFloat(treasury_distributed.toFixed(3)),
            trade_value_usd: parseFloat(tradeValueUSD.toFixed(2)),
            yam_value: parseFloat(yamValue.toFixed(2)), // Legacy, kept for display
            xp_units: xpUnitsStr, // String representation for large integer (cents × 10^21)
            treasury_reminder: treasury_reminder,
            yam_reminder: parseFloat(yam_reminder.toFixed(2)), // Legacy
            xp_reminder: xp_reminder_str // String representation for large integer
        };
    }
    
    // Function to check Discord membership via AJAX
    function checkDiscordMembership(userId, callback) {
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: "POST",
            data: {
                action: "ct_check_discord_membership",
                user_id: userId
            },
            success: function(response) {
                if (response.success && response.data) {
                    callback(response.data);
                } else {
                    console.warn('Discord check failed, defaulting to false');
                    callback({ discord_join: false, user_id: userId });
                }
            },
            error: function() {
                console.warn('Discord check error, defaulting to false');
                callback({ discord_join: false, user_id: userId });
            }
        });
    }
    
    // Function to check if proof_id exists in seller_scan
    function checkProofIdInSellerScan(proofId, callback) {
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: "POST",
            data: {
                action: "ct_check_proof_id",
                proof_id: proofId
            },
            success: function(response) {
                if (response.success && response.data) {
                    callback(response.data);
                } else {
                    console.error('Error checking proof ID:', response);
                    callback({ found: false });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error checking proof ID:', error);
                callback({ found: false });
            }
        });
    }
    
    // Function to show error popup
    function showErrorPopup(message) {
        // Create error popup HTML if it doesn't exist
        if (jQuery('#cpp-popup-error').length === 0) {
            jQuery('body').append(`
                <div id="cpp-popup-error" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 99999; justify-content: center; align-items: center;">
                    <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <h3 style="color: #e74c3c; margin-top: 0;">Error</h3>
                        <p id="cpp-error-message" style="color: #2c3e50; font-size: 16px; margin: 20px 0;">${message}</p>
                        <button id="cpp-close-error-btn" style="background: #e74c3c; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px;">Close</button>
                    </div>
                </div>
            `);
        }
        
        // Always remove old handlers and attach new ones (in case popup already exists)
        jQuery('.cpp-close-error, #cpp-close-error-btn').off('click');
        jQuery('#cpp-popup-error').off('click');
        
        // Close handler - just close the popup, no login
        jQuery('.cpp-close-error, #cpp-close-error-btn').on('click', function() {
            jQuery('#cpp-popup-error').fadeOut();
        });
        
        // Close on background click
        jQuery('#cpp-popup-error').on('click', function(e) {
            if (e.target === this) {
                jQuery('#cpp-popup-error').fadeOut();
            }
        });
        
        // Update message and show
        jQuery('#cpp-error-message').text(message);
        jQuery('#cpp-popup-error').css({
            'display': 'flex',
            'justify-content': 'center',
            'align-items': 'center'
        }).fadeIn();
    }
    
    // Function to perform login
    function performLogin(userId, nonce) {
        console.log('performLogin called with userId:', userId, 'nonce:', nonce);
        displayFormMsg('Logging you in...', 'success');
        
        //ajax to signin the user
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: "POST",
            data: {
                action: "ct_user_signin",
                userId: userId,
                nonce: nonce,
            },
            success: function (response) {
                console.log('Login response:', response);
                if (response.data[0] == "logged_in") {
                    // Redirect to wallet page after login
                    console.log('Login successful, redirecting to wallet...');
                    window.location.href = '/my-account/detente-wallet/';
                } else if (response.data[0] == 'nonce_failed') {
                    displayFormMsg('Security check failed');
                } else {
                    console.error('Login failed:', response.data);
                    displayFormMsg('Login failed. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Login AJAX error:', status, error);
                displayFormMsg('Login error. Please try again.');
            }
        });
    }
    
    // Function to get geolocation
    function getGeolocation(callback) {
        if (!navigator.geolocation) {
            console.warn('Geolocation is not supported by this browser');
            callback({
                latitude: null,
                longitude: null,
                accuracy: null,
                error: 'Geolocation not supported'
            });
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const geoData = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: new Date().toISOString(),
                    error: null
                };
                console.log('Geolocation retrieved:', geoData);
                callback(geoData);
            },
            function(error) {
                console.warn('Geolocation error:', error);
                callback({
                    latitude: null,
                    longitude: null,
                    accuracy: null,
                    timestamp: new Date().toISOString(),
                    error: error.message || 'Geolocation error'
                });
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }
    
    // Function to insert scan data to usermeta
    function insertScanDataToUsermeta(scanData, callback, seller_id, seller_entry_index) {
        // Check if scan data insertion should be skipped
        if (window.skipScanDataInsertion) {
            console.log('Skipping scan data insertion - product already scanned');
            window.skipScanDataInsertion = false; // Clear flag
            if (callback) callback(false);
            return;
        }
        
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: "POST",
            data: {
                action: "ct_insert_scan_data",
                user_id: scanData.user_id,
                scan_data: JSON.stringify(scanData),
                seller_id: seller_id || null,
                seller_entry_index: seller_entry_index !== undefined ? seller_entry_index : null
            },
            success: function(response) {
                if (response.success) {
                    console.log('Scan data inserted to usermeta successfully:', response.data);
                    if (callback) callback(true);
                } else {
                    console.error('Failed to insert scan data:', response.data);
                    // Show error popup with message
                    const errorMessage = response.data && response.data.message ? response.data.message : 'Failed to insert scan data';
                    showErrorPopup(errorMessage);
                    if (callback) callback(false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error inserting scan data:', error);
                // Try to parse error response
                let errorMessage = 'Failed to insert scan data';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.data && errorResponse.data.message) {
                        errorMessage = errorResponse.data.message;
                    }
                } catch (e) {
                    // If parsing fails, use default message
                }
                showErrorPopup(errorMessage);
                if (callback) callback(false);
            }
        });
    }

    // Disable buttons by default
    $('#send_otp').prop('disabled', true);
    $('#validate_otp').prop('disabled', true);

    // Hide the OTP group by default
    $('.cpm_otp_group').hide();

    // validate phone number
    $(document).on('input', '#otp_phone_num', function () {
        const phoneNumber = $(this).val();
        $(this).val($(this).val().replace(/\D/g, ''));
        clearFormMsg();
        if (validatePhoneNumber(phoneNumber)) {
            $('#send_otp').prop('disabled', false);
        } else {
            $('#send_otp').prop('disabled', true);
        }
    });

    // send OTP
    $(document).on('click', '#send_otp', function () {
        clearFormMsg();

        let phoneNumber = $('#otp_phone_num').val();
        nonce = $('#phone_num_verification_nonce').val();
        $this = $(this);

        $('#otp_phone_num').prop('disabled', true); //disable input
        $this.prop('disabled', true); //disable button
        $this.html("Verifying phone number...");

        //ajax to check if the phone number belongs to an actual user
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: "POST",
            data: {
                action: "ct_verify_user_phone_number",
                phone_number: phoneNumber,
                nonce: nonce,
            },
            success: function (response) {
                //console.log("111:::", response.data[1]);
                $this.prop('disabled', true);

                if (response.data[0] != 'valid_phone') {
                    if (response.data[0] == 'invalid_phone') {
                        displayFormMsg('The phone number you entered does not belong to any user');
                    } else if (response.data[0] == 'nonce_failed') {
                        displayFormMsg('Securtiy check failed');
                    }

                    $('#otp_phone_num').prop('disabled', false);
                    $this.prop('disabled', false);
                    $this.html("Send OTP");
                    return;
                }

                nonce = response.data[2];
                userID = response.data[1];
                $this.html("Sending OTP...");

                //ajax to send the otp
                jQuery.ajax({
                    url: ct_ajax.ajax_url,
                    type: "POST",
                    data: {
                        action: "ct_send_twilio_otp",
                        phone_number: phoneNumber,
                        nonce: nonce,
                    },
                    success: function (response) {
                        //console.log("222:::", response);
                        $this.prop('disabled', true);
                        if (response.data[0] != 'otp_sent') {
                            if (response.data[0] == 'otp_failed') {
                                displayFormMsg(response.data[1]);
                            } else if (response.data[0] == 'nonce_failed') {
                                displayFormMsg('Securtiy check failed');
                            }

                            $this.prop('disabled', false);
                            $('#otp_phone_num').prop('disabled', false);
                            $this.html("Send OTP");
                            return;
                        }

                        nonce = response.data[2];
                        $('.cpm_phone_group').hide();
                        $('.cpm_otp_group').show();
                    },
                });

            },
        });
    });




    // validate otp button
    $(document).on('click', '#validate_otp', function () {
        clearFormMsg();
        let phoneNumber = $('#otp_phone_num').val();

        let otp = '';
        $inputs.each(function () { otp += $(this).val(); });

        $this = $(this);

        $("#twilio_otp").prop('disabled', true);
        $this.prop('disabled', true);
        $this.html("Validating...");


        if (!validateOTP(otp)) {
            displayFormMsg('OTP format not correct');
            $this.html("Validate");
            return;
        }

        ///ajax to validate otp
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: "POST",
            data: {
                action: "ct_validate_twilio_otp",
                phone_number: phoneNumber,
                otp: otp,
                nonce: nonce
            },
            success: function (response) {
                //console.log("333:::", response);
                console.log('OTP Validation Response:', response);

                // Check if response has warning about product already scanned
                // Show error popup but still allow login after popup closes
                if (response.data && response.data.length >= 4 && response.data[2] === "warning") {
                    console.log('Warning detected - product already scanned');
                    const errorMessage = response.data[3] || 'Product qr is already scanned';
                    nonce = response.data[1]; // Get nonce for login
                    
                    // Set flag to prevent scan data insertion
                    window.skipScanDataInsertion = true;
                    
                    // Show error popup - do NOT log in after popup closes
                    showErrorPopup(errorMessage);
                    
                    // Don't proceed with normal flow - stop here, no login
                    $this.html("Validate");
                    $this.prop('disabled', false);
                    return;
                }

                //check if otp invalid
                if (response.data[0] == "invalid_otp") {
                    if (response.data[0] == 'invalid_otp') {
                        displayFormMsg('OTP is invalid. Please try again');
                    } else if (response.data[0] == 'nonce_failed') {
                        displayFormMsg('Securtiy check failed');
                    }

                    $this.html("Validate");
                    $this.prop('disabled', false);
                    return;
                }

                nonce = response.data[1];
                displayFormMsg('OTP validated !! Logging you in...', 'success');
                $this.html("Logging you in...");

                // Calculate trade value after OTP verification if this is a proof of delivery scan
                const urlParams = new URLSearchParams(window.location.search);
                const scanType = urlParams.get('scan_type');
                
                // Skip scan data insertion if product is already scanned
                if (window.skipScanDataInsertion) {
                    console.log('Skipping scan data insertion - product already scanned');
                    // Clear the flag
                    window.skipScanDataInsertion = false;
                    // Just perform login without scan data insertion
                    performLogin(userID, nonce);
                    return;
                }
                
                if (scanType === 'proof') {
                    console.log('OTP verified successfully! Calculating trade value and checking Discord...');
                    
                    // Get role from localStorage first to determine if we need to generate transaction_id
                    const scanData = getScanDataFromLocalStorage();
                    console.log('Scan data from localStorage:', scanData);
                    if (scanData && scanData.role) {
                        const selectedRole = scanData.role;
                        console.log('Selected role from localStorage:', selectedRole);
                        
                        // Calculate all trade values based on role
                        const calculationResult = calculateTradeValue(selectedRole);
                        
                        // Check Discord membership
                        checkDiscordMembership(userID, function(discordData) {
                            // Determine status based on Discord membership
                            const status = discordData.discord_join ? 'completed' : 'pending';
                            const scan_status = 'pending';
                            
                            // Get existing scan data to merge with new data
                            const existingScanData = getScanDataFromLocalStorage();
                            
                            // Generate transaction_id only for seller and personal roles (not for buyer)
                            let transactionId = '';
                            if (selectedRole !== 'buyer') {
                                const currentTimestamp = Math.floor(Date.now() / 1000); // Unix timestamp in seconds
                                transactionId = userID + '_' + currentTimestamp;
                                console.log('Generated transaction_id:', transactionId);
                            } else {
                                console.log('Buyer role - transaction_id will be set after verification');
                            }
                            
                            // Prepare all data to save (including data for usermeta insertion)
                            const allData = {
                                // Calculation results
                                trade_value: calculationResult.trade_value,
                                percentage: calculationResult.percentage,
                                treasury_distributed: calculationResult.treasury_distributed,
                                trade_value_usd: calculationResult.trade_value_usd,
                                yam_value: calculationResult.yam_value,
                                xp_units: calculationResult.xp_units,
                                treasury_reminder: calculationResult.treasury_reminder,
                                yam_reminder: calculationResult.yam_reminder,
                                xp_reminder: calculationResult.xp_reminder,
                                
                                // User and status info
                                user_id: discordData.user_id,
                                discord_join: discordData.discord_join,
                                status: status,
                                scan_status: scan_status,
                                
                                // Transaction ID (user_id + timestamp) - generated after OTP verification (only for seller/personal)
                                transaction_id: transactionId
                            };
                            
                            // Merge with existing data (scan_type, delivery_proof, role, mega-mobile, timestamp)
                            // IMPORTANT: Always use selectedRole to ensure correct role is set
                            if (existingScanData) {
                                Object.assign(allData, {
                                    scan_type: existingScanData.scan_type || 'proof',
                                    delivery_proof: existingScanData.delivery_proof || 'yes',
                                    role: selectedRole, // Always use selectedRole from localStorage
                                    'mega-mobile': existingScanData['mega-mobile'] || phoneNumber || '',
                                    timestamp: new Date().toISOString()
                                });
                            } else {
                                // If no existing data, use phoneNumber from OTP form
                                allData['mega-mobile'] = phoneNumber || '';
                                allData.scan_type = scanType || 'proof';
                                allData.delivery_proof = 'yes';
                                allData.role = selectedRole; // Always use selectedRole from localStorage
                                allData.timestamp = new Date().toISOString();
                            }
                            
                            console.log('AllData after merge - role:', allData.role, 'selectedRole:', selectedRole);
                            
                            // For buyer role, show transaction code input popup first
                            if (selectedRole === 'buyer') {
                                console.log('Buyer role detected. Showing transaction code input popup...');
                                
                                // Store all data temporarily for later use after transaction code verification
                                window.pendingBuyerData = {
                                    allData: allData,
                                    selectedRole: selectedRole,
                                    discordData: discordData,
                                    nonce: nonce,
                                    userID: userID
                                };
                                
                                // Show transaction code input popup
                                showTransactionCodePopup();
                            } else {
                                // For seller or personal role, proceed normally
                                // Get geolocation first, then prepare data for usermeta insertion
                                console.log('Getting geolocation for scan...');
                                getGeolocation(function(geoData) {
                                    // Get current timestamp
                                    const currentTimestamp = new Date().toISOString();
                                    
                                // Prepare data for usermeta insertion (only the fields specified by user)
                                    const roleToSend = allData.role || selectedRole || '';
                                    console.log('Role being sent to server (seller/personal):', roleToSend);
                                    console.log('All data role:', allData.role);
                                    console.log('Selected role:', selectedRole);
                                    
                                const usermetaData = {
                                    delivery_proof: allData.delivery_proof || 'yes',
                                    discord_join: allData.discord_join || false,
                                    'mega-mobile': allData['mega-mobile'] || '',
                                    percentage: allData.percentage || 0,
                                        transaction_id: allData.transaction_id || '',
                                        role: roleToSend,
                                    scan_status: allData.scan_status || 'pending',
                                    scan_type: allData.scan_type || 'proof',
                                    status: allData.status || 'pending',
                                        timestamp: currentTimestamp,
                                    treasury_distributed: allData.treasury_distributed || 0,
                                    xp_units: allData.xp_units || 0,
                                    yam_value: allData.yam_value || 0,
                                        user_id: allData.user_id,
                                        // Add geolocation data
                                        geolocation: {
                                            latitude: geoData.latitude,
                                            longitude: geoData.longitude,
                                            accuracy: geoData.accuracy,
                                            timestamp: geoData.timestamp,
                                            error: geoData.error
                                        }
                                    };
                                    
                                    console.log('Final usermetaData being sent (seller/personal):', usermetaData);
                                
                                // Insert data to usermeta BEFORE showing popup
                                insertScanDataToUsermeta(usermetaData, function(success) {
                                    if (success) {
                                            console.log('Scan data inserted to usermeta successfully with geolocation');
                                        
                                            // Save all data to localStorage (including geolocation and transaction_id)
                                            allData.geolocation = geoData;
                                            allData.timestamp = currentTimestamp;
                                        saveScanDataToLocalStorage(allData);
                                        
                                        console.log('All calculation data saved to localStorage:', allData);
                                        
                                        // Store userID and nonce for later login (after popup closes)
                                        window.pendingLogin = {
                                            userId: discordData.user_id,
                                            nonce: nonce
                                        };
                                        
                                        // Show calculation popup ONLY if data insertion was successful
                                        showCalculationPopup();
                                    } else {
                                        console.error('Failed to insert scan data to usermeta. Not showing calculation popup.');
                                        // Error popup is already shown by insertScanDataToUsermeta
                                        // Don't show calculation popup or proceed with login
                                    }
                                    });
                                });
                            }
                        });
                    } else {
                        console.warn('No role found in localStorage. Cannot calculate trade value.');
                    }
                }

                // For proof-of-delivery scans, login happens after popup closes
                // Only sign in immediately for non-proof scans
                const urlParamsForLogin = new URLSearchParams(window.location.search);
                const scanTypeForLogin = urlParamsForLogin.get('scan_type');
                
                if (scanTypeForLogin !== 'proof') {
                    // Normal flow - sign in immediately and redirect
                    //ajax to signin the user
                    jQuery.ajax({
                        url: ct_ajax.ajax_url,
                        type: "POST",
                        data: {
                            action: "ct_user_signin",
                            userId: userID,
                            nonce: nonce,
                        },
                        success: function (response) {
                            //console.log('444:::', response);
                            if (response.data[0] == "logged_in") {
                                // Normal redirect for non-proof scans
                                window.location.href = '/my-account/detente-orders/';
                            } else if (response.data[0] == 'nonce_failed') {
                                displayFormMsg('Securtiy check failed');
                            }
                        },
                    });
                }
                // For proof-of-delivery scans, login happens in showCalculationPopup close handler
            },
        });
    });

    //refresh the page to restsrt the otp verification process again.
    $(document).on('click', '#otp_retry, #phone_retry', function () {
        window.location.reload();
    });


    //=====OTP input fields start=====

    // Validate individual OTP input field
    $inputs.on('input', function (e) {
        if (e.key === 'Backspace') {
            return;
        }
        clearFormMsg();
        $(this).css('border-color', '');//change border color to default

        const $this = $(this);
        if (validateIndividualOTPinput($this.val())) {
            const nextIndex = $inputs.index(this) + 1;
            if (nextIndex < $inputs.length) {
                $inputs.eq(nextIndex).focus();
            }
        } else {
            $(this).val(''); //clear the input
            displayFormMsg('OTP can only be numbers'); //show appropriate display message
            $(this).css('border-color', 'red');//change border color to red
        }

        checkIfAllOTPFieldsValid();
    });

    $inputs.on('keydown', function (event) {
        const $this = $(this);
        if (event.key === 'Backspace' && $this.val().length === 0) {
            $(this).css('border-color', '');//change border color to default
            const prevIndex = $inputs.index(this) - 1;
            if (prevIndex >= 0) {
                $inputs.eq(prevIndex).focus();
            }
        }
    });

    //=====OTP input fields end=====
});
