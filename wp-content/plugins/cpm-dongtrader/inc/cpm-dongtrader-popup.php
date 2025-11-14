<?php
function cpp_popup_markup()
{
    // Check if this is a proof of delivery scan (scan_type=proof in URL)
    $is_proof_delivery = isset($_GET['scan_type']) && $_GET['scan_type'] === 'proof';
    
    // Show popup if: (home/front page and not logged in) OR (proof of delivery scan - for both logged in and logged out users)
    if (((is_home() || is_front_page()) && !is_user_logged_in()) || $is_proof_delivery) {
        echo '
        <div id="cpp-popup" class="cpp-popup">
            <div class="cpp-popup-content">
                <span class="cpp-close">&times;</span>
                <p>Is this proof of delivery?</p>
                <button id="cpp-yes">Yes</button>
                <button id="cpp-no">No</button>
            </div>
        </div>

        <div id="cpp-popup-2" class="cpp-popup">
            <div class="cpp-popup-content">
                <span class="cpp-close">&times;</span>
                <form id="cpp-form">
                    <p>Are you a buyer/seller/personal ?</p>
                    <input type="radio" id="buyer" name="role" value="buyer" checked="checked" required>
                    <label for="buyer">Buyer (7%)</label><br>
                    <input type="radio" id="seller" name="role" value="seller" required>
                    <label for="seller">Seller (3%)</label><br>
                    <input type="radio" id="personal" name="role" value="personal" required>
                    <label for="personal">Personal (10%)</label><br>
                    <button type="submit">Next</button>
                </form>
            </div>
        </div>

        <div id="cpp-popup-3" class="cpp-popup">
            <div class="cpp-popup-content">
                <span class="cpp-close">&times;</span>
                ' . do_shortcode('[cpm_twilio_otp shadow="no" ]') . '
            </div>
        </div>

        <div id="cpp-popup-transaction-code" class="cpp-popup" style="display: none;">
            <div class="cpp-popup-content" style="max-width: 500px;">
                <span class="cpp-close-transaction-code" style="position: absolute; top: 10px; right: 15px; font-size: 28px; font-weight: bold; color: #aaa; cursor: pointer;">&times;</span>
                <div style="padding: 30px 20px; text-align: center;">
                    <h2 style="color: #2c3e50; margin-top: 0; margin-bottom: 20px; font-size: 24px;">Enter Transaction Code</h2>
                    <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 25px; line-height: 1.5;">Please enter the transaction code provided by the seller to complete your purchase verification.</p>
                    <form id="transaction-code-form">
                        <div style="margin-bottom: 20px;">
                            <input type="text" id="transaction-code-input" name="transaction_code" placeholder="Enter Transaction Code" required style="width: 100%; padding: 12px; font-size: 16px; border: 2px solid #e9ecef; border-radius: 6px; font-family: monospace; text-align: center; letter-spacing: 2px;" autocomplete="off">
                            <div id="transaction-code-error" style="color: #e74c3c; font-size: 13px; margin-top: 8px; display: none;"></div>
                        </div>
                        <button type="submit" id="submit-transaction-code" style="background: #27ae60; color: white; border: none; border-radius: 6px; padding: 12px 40px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; transition: background 0.3s;">Submit</button>
                    </form>
                </div>
            </div>
        </div>

        ';
        ?>

        <script>

            jQuery(document).ready(function ($) {

                // Get the current URL
                const url = new URL(window.location.href);

                // Get URL parameters
                const role = url.searchParams.get('role');
                const proofId = url.searchParams.get('proof_id');
                const scanType = url.searchParams.get('scan_type');

                if (role !== null) {
                    closePopup('#cpp-popup');
                    closePopup('#cpp-popup-2');
                    showPopup('#cpp-popup-3');
                }

                function setCookie(name, value, days) {
                    var expires = "";
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        expires = "; expires=" + date.toUTCString();
                    }
                    document.cookie = name + "=" + (value || "") + expires + "; path=/";
                }

                function showPopup(popupId) {
                    $(popupId).fadeIn();
                }

                function closePopup(popupId) {
                    $(popupId).fadeOut();
                }

                // Function to get existing scan data from localStorage
                function getScanDataFromLocalStorage() {
                    // Re-check URL parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentScanType = urlParams.get('scan_type');
                    
                    if (currentScanType === 'proof') {
                        const existingData = localStorage.getItem('scan data');
                        if (existingData) {
                            try {
                                return JSON.parse(existingData);
                            } catch (e) {
                                console.error('Error parsing localStorage data:', e);
                                return null;
                            }
                        }
                    }
                    return null;
                }

                // Function to save/update scan data to localStorage
                function saveScanDataToLocalStorage(additionalData = {}) {
                    // Re-check URL parameters to ensure we have current values
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentScanType = urlParams.get('scan_type');
                    
                    console.log('=== saveScanDataToLocalStorage called ===');
                    console.log('URL scan_type:', currentScanType);
                    console.log('Additional data to add:', additionalData);
                    console.log('Current URL:', window.location.href);
                    
                    if (currentScanType === 'proof') {
                        console.log('✓ Conditions met - proceeding to save');
                        
                        // Get existing data if any
                        let scanData = getScanDataFromLocalStorage();
                        console.log('Existing scanData from localStorage:', scanData);
                        
                        // If no existing data, create new
                        if (!scanData) {
                            scanData = {
                                scan_type: currentScanType,
                                delivery_proof: 'yes',
                                timestamp: new Date().toISOString()
                            };
                            console.log('Creating NEW scan data:', scanData);
                        } else {
                            console.log('Updating EXISTING scan data');
                        }
                        
                        // Merge additional data (role, mega-mobile, etc.) - this will overwrite existing values
                        Object.assign(scanData, additionalData);
                        console.log('Merged scanData:', scanData);
                        console.log('Role in merged data:', scanData.role);
                        
                        // Save back to localStorage
                        localStorage.setItem('scan data', JSON.stringify(scanData));
                        console.log('✓ Scan data saved to localStorage');
                        console.log('localStorage.getItem("scan data"):', localStorage.getItem('scan data'));
                        console.log('=====================================');
                    } else {
                        console.warn('✗ Cannot save - conditions not met:', {
                            scanType: currentScanType,
                            isProof: currentScanType === 'proof'
                        });
                        console.log('=====================================');
                    }
                }

                // Function to remove scan data from localStorage
                function removeScanDataFromLocalStorage() {
                    // Re-check URL parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentProofId = urlParams.get('proof_id');
                    const currentScanType = urlParams.get('scan_type');
                    
                    if (currentProofId && currentScanType === 'proof') {
                        localStorage.removeItem('scan data');
                        console.log('Scan data removed from localStorage (user selected "no")');
                    }
                }

                $(document).ready(function () {
                    // Log initial URL parameters for debugging
                    console.log('=== Page Load - Initial Check ===');
                    console.log('URL proofId:', proofId);
                    console.log('URL scanType:', scanType);
                    console.log('URL role:', role);
                    console.log('Full URL:', window.location.href);
                    console.log('localStorage.getItem("scan data"):', localStorage.getItem('scan data'));
                    console.log('===================================');
                    
                    // Show popup if proof_id and scan_type=proof exist, or if it's the default flow
                    if (proofId && scanType === 'proof') {
                        console.log('✓ Proof of delivery scan detected - showing popup');
                        showPopup('#cpp-popup');
                    } else if (!role) {
                        // Default flow: show popup for home/front page
                        console.log('Default flow - showing popup');
                        showPopup('#cpp-popup');
                    }

                    $('#cpp-yes').click(function () {
                        // Save to localStorage if this is a proof of delivery scan
                        // Explicitly set delivery_proof to 'yes' when user clicks Yes
                        saveScanDataToLocalStorage({ delivery_proof: 'yes' });
                        
                        console.log('User selected Yes - Proof of delivery data saved to localStorage');
                        
                        closePopup('#cpp-popup');
                        showPopup('#cpp-popup-2');
                    });

                    $('#cpp-no').click(function () {
                        // Remove from localStorage if this is a proof of delivery scan
                        removeScanDataFromLocalStorage();
                        
                        closePopup('#cpp-popup');
                    });

                    $('.cpp-close').click(function () {
                        // Remove from localStorage when closing if this is a proof of delivery scan
                        removeScanDataFromLocalStorage();
                        
                        closePopup('#cpp-popup');
                        closePopup('#cpp-popup-2');
                        closePopup('#cpp-popup-3');
                    });


                    // Save role immediately when radio button is selected
                    $(document).on('change', 'input[name="role"]', function() {
                        var selectedRole = $(this).val();
                        console.log('Role selected:', selectedRole);
                        
                        // Save role to localStorage if this is a proof of delivery scan
                        saveScanDataToLocalStorage({ role: selectedRole });
                    });

                    $('#cpp-form').submit(function (e) {
                        e.preventDefault();
                        var selectedRole = $('input[name="role"]:checked').val();
                        console.log('Form submitted with role:', selectedRole);
                        
                        if (!selectedRole) {
                            console.error('No role selected!');
                            return;
                        }
                        
                        setCookie('user_role', selectedRole, 30);
                        
                        // Save role to localStorage if this is a proof of delivery scan (ensure it's saved)
                        saveScanDataToLocalStorage({ role: selectedRole });
                        
                        closePopup('#cpp-popup-2');
                        showPopup('#cpp-popup-3');
                        // Here you can add AJAX call to submit form data if needed
                    });

                    // Listen for phone number input in OTP form (mega-mobile)
                    // Use event delegation since the OTP form is loaded via shortcode
                    $(document).on('input blur', '#otp_phone_num', function() {
                        var phoneNumber = $(this).val();
                        // Only save if phone number is valid (10 digits)
                        if (phoneNumber && phoneNumber.replace(/\D/g, '').length === 10) {
                            saveScanDataToLocalStorage({ 'mega-mobile': phoneNumber.replace(/\D/g, '') });
                        }
                    });

                });
            });
        </script>

        <style>
            .cpp-popup {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgb(0, 0, 0);
                background-color: rgba(0, 0, 0, 0.4);
            }

            .cpp-popup-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 500px;
                position: relative;
            }

            .cpp-close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
            }

            .cpp-close:hover,
            .cpp-close:focus {
                color: black;
                text-decoration: none;
                cursor: pointer;
            }

            .cpp-popup-content #cpp-yes,
            .cpp-popup-content #cpp-no {
                padding: 10px 40px;
            }
        </style>
        <?php
    }
}
add_action('wp_footer', 'cpp_popup_markup');
