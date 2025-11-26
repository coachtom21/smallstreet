<?php
/**
 * Send XP Transfer Page Template
 * Allows users to transfer XP to other verified users
 */

if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Please log in to send XP.', 'cpm-dongtrader') . '</p>';
    return;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();
$user_name = $user->display_name ? $user->display_name : $user->user_login;

// Get current XP balance
$seller_scan_raw = get_user_meta($user_id, 'seller_scan', true);
$buyer_scan_raw = get_user_meta($user_id, 'buyer_scan', true);
$personal_scan_raw = get_user_meta($user_id, 'personal_scan', true);

$seller_scan_data = maybe_unserialize($seller_scan_raw);
$buyer_scan_data = maybe_unserialize($buyer_scan_raw);
$personal_scan_data = maybe_unserialize($personal_scan_raw);

if (!is_array($seller_scan_data)) $seller_scan_data = array();
if (!is_array($buyer_scan_data)) $buyer_scan_data = array();
if (!is_array($personal_scan_data)) $personal_scan_data = array();

$total_xp = 0;
foreach (array_merge($seller_scan_data, $buyer_scan_data, $personal_scan_data) as $entry) {
    if (isset($entry['xp_units']) && isset($entry['scan_status']) && $entry['scan_status'] === 'confirmed') {
        $total_xp += floatval($entry['xp_units']);
    }
}

// TODO: Subtract sent transfers, add received transfers
// $total_xp = $total_xp - $sent_transfers + $received_transfers;

// NEW CONVERSION: Calculate USD from XP, then YAM for display
$usd_trade_value = $total_xp > 0 ? dongtrader_xp_to_usd($total_xp) : 0;
// NEW CONVERSION: 1 USD = 21,000 YAM = 10^23 XP
$yam_equivalent = $total_xp > 0 ? dongtrader_xp_to_yam($total_xp) : 0;

$max_transfer = $total_xp * 0.5; // 50% of balance
$min_transfer = 0.000001; // 1 YAM equivalent

extract($args);
$cs = get_woocommerce_currency_symbol();
?>

<style>
/* Send XP Form Styles */
.send-xp-container {
  background: linear-gradient(135deg, #f9fafb, #f3f4f6);
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 30px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  font-family: "Inter", system-ui, sans-serif;
  color: #1f2937;
  max-width: 800px;
  margin: 0 auto;
}

.send-xp-container h2 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: #111827;
}

.balance-display {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
}

.balance-item {
  text-align: center;
}

.balance-item .label {
  font-size: 0.85rem;
  color: #6b7280;
  margin-bottom: 5px;
}

.balance-item .value {
  font-size: 1.2rem;
  font-weight: 700;
  color: #065f46;
}

.send-xp-form {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 25px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  font-size: 0.9rem;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.form-group .help-text {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 5px;
}

.form-group .error-text {
  font-size: 0.8rem;
  color: #dc2626;
  margin-top: 5px;
  display: none;
}

.receiver-search {
  position: relative;
}

.receiver-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-top: 5px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 100;
  display: none;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.receiver-result-item {
  padding: 12px 15px;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.2s;
}

.receiver-result-item:hover {
  background: #f9fafb;
}

.receiver-result-item:last-child {
  border-bottom: none;
}

.receiver-result-item .name {
  font-weight: 600;
  color: #111827;
  margin-bottom: 3px;
}

.receiver-result-item .email {
  font-size: 0.85rem;
  color: #6b7280;
}

.transfer-summary {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 20px;
  margin-top: 20px;
  display: none;
}

.transfer-summary h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #065f46;
  margin-bottom: 15px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #d1fae5;
}

.summary-row:last-child {
  border-bottom: none;
  font-weight: 600;
  color: #065f46;
  margin-top: 5px;
  padding-top: 12px;
}

.summary-label {
  color: #6b7280;
}

.summary-value {
  color: #111827;
  font-weight: 600;
}

.btn-primary {
  background: linear-gradient(90deg, #047857, #059669);
  color: white;
  border: none;
  padding: 14px 28px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
  margin-top: 20px;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
  padding: 14px 28px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
  margin-top: 10px;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.limit-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #6b7280;
  margin-top: 5px;
}

.amount-input-wrapper {
  position: relative;
}

.amount-input-wrapper::after {
  content: 'XP';
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  font-weight: 600;
  pointer-events: none;
}

.amount-input-wrapper input {
  padding-right: 50px;
}

.conversion-display {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px 15px;
  margin-top: 10px;
  font-size: 0.85rem;
}

.conversion-display .conversion-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 5px;
}

.conversion-display .conversion-item:last-child {
  margin-bottom: 0;
}

.conversion-label {
  color: #6b7280;
}

.conversion-value {
  color: #111827;
  font-weight: 600;
}
</style>

<div class="send-xp-container">
    <h2><?php esc_html_e('Send XP', 'cpm-dongtrader'); ?></h2>
    
    <!-- LAUGH Mode Banner -->
    <div class="laugh-mode-banner">
        <span class="status-indicator"></span>
        <div>
            <strong style="font-size: 16px;">LAUGH Mode Active</strong>
            <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.95;">
                XP transfers are trade credits only. No money moves until <?php echo esc_html($laugh_end_date); ?>.
            </p>
        </div>
    </div>

    <!-- Balance Display -->
    <div class="balance-display">
        <div class="balance-item">
            <div class="label"><?php esc_html_e('Available XP', 'cpm-dongtrader'); ?></div>
            <div class="value"><?php echo number_format($total_xp, 6); ?></div>
        </div>
        <div class="balance-item">
            <div class="label"><?php esc_html_e('YAM Equivalent', 'cpm-dongtrader'); ?></div>
            <div class="value">
                <?php 
                // Display YAM in scientific notation
                if ($yam_equivalent > 0 && is_numeric($yam_equivalent)) {
                    $yam_scientific = sprintf('%.0e', (float)$yam_equivalent);
                    $parts = explode('e', $yam_scientific);
                    if (count($parts) == 2) {
                        $base = abs(floatval($parts[0]));
                        $exponent = intval($parts[1]);
                        $base_display = ($base == floor($base)) ? (int)$base : $base;
                        echo esc_html($base_display) . ' × 10<sup>' . esc_html($exponent) . '</sup>';
                    } else {
                        echo esc_html(number_format($yam_equivalent, 18));
                    }
                } else {
                    echo '0';
                }
                ?>
            </div>
        </div>
        <div class="balance-item">
            <div class="label"><?php esc_html_e('USD Trade Value', 'cpm-dongtrader'); ?></div>
            <div class="value"><?php echo $cs . number_format($usd_trade_value, 2); ?></div>
        </div>
    </div>

    <!-- Send XP Form -->
    <form id="send-xp-form" class="send-xp-form">
        <?php wp_nonce_field('send_xp_transfer', 'xp_transfer_nonce'); ?>
        
        <!-- Receiver Search -->
        <div class="form-group">
            <label for="receiver_search"><?php esc_html_e('Send To', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
            <div class="receiver-search">
                <input 
                    type="text" 
                    id="receiver_search" 
                    name="receiver_search" 
                    placeholder="<?php esc_attr_e('Search by email, username, or FonePay ID', 'cpm-dongtrader'); ?>"
                    autocomplete="off"
                    required
                />
                <div class="receiver-results" id="receiver_results"></div>
            </div>
            <input type="hidden" id="receiver_id" name="receiver_id" required />
            <div class="help-text"><?php esc_html_e('Start typing to search for a user', 'cpm-dongtrader'); ?></div>
            <div class="error-text" id="receiver_error"></div>
        </div>

        <!-- Selected Receiver Display -->
        <div id="selected_receiver" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 600; color: #065f46; margin-bottom: 3px;" id="receiver_name"></div>
                    <div style="font-size: 0.85rem; color: #6b7280;" id="receiver_email"></div>
                </div>
                <button type="button" onclick="clearReceiver()" style="background: none; border: none; color: #6b7280; cursor: pointer; font-size: 1.2rem;">×</button>
            </div>
        </div>

        <!-- XP Amount -->
        <div class="form-group">
            <label for="xp_amount"><?php esc_html_e('XP Amount', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
            <div class="amount-input-wrapper">
                <input 
                    type="number" 
                    id="xp_amount" 
                    name="xp_amount" 
                    step="0.000001" 
                    min="<?php echo esc_attr($min_transfer); ?>" 
                    max="<?php echo esc_attr($max_transfer); ?>"
                    placeholder="0.000000"
                    required
                />
            </div>
            <div class="limit-info">
                <span><?php echo esc_html(sprintf(__('Min: %s XP', 'cpm-dongtrader'), number_format($min_transfer, 6))); ?></span>
                <span><?php echo esc_html(sprintf(__('Max: %s XP (50%% of balance)', 'cpm-dongtrader'), number_format($max_transfer, 6))); ?></span>
            </div>
            <div class="error-text" id="amount_error"></div>
            
            <!-- Conversion Display -->
            <div class="conversion-display" id="conversion_display" style="display: none;">
                <div class="conversion-item">
                    <span class="conversion-label"><?php esc_html_e('YAM Equivalent:', 'cpm-dongtrader'); ?></span>
                    <span class="conversion-value" id="yam_equiv">0</span>
                </div>
                <div class="conversion-item">
                    <span class="conversion-label"><?php esc_html_e('USD Trade Value:', 'cpm-dongtrader'); ?></span>
                    <span class="conversion-value"><?php echo $cs; ?><span id="usd_value">0.00</span></span>
                </div>
            </div>
        </div>

        <!-- Memo/Note -->
        <div class="form-group">
            <label for="memo"><?php esc_html_e('Memo (Optional)', 'cpm-dongtrader'); ?></label>
            <textarea 
                id="memo" 
                name="memo" 
                placeholder="<?php esc_attr_e('Add a note about this transfer...', 'cpm-dongtrader'); ?>"
                maxlength="500"
            ></textarea>
            <div class="help-text">
                <span id="char_count">0</span> / 500 <?php esc_html_e('characters', 'cpm-dongtrader'); ?>
            </div>
        </div>

        <!-- Transfer Summary -->
        <div class="transfer-summary" id="transfer_summary">
            <h4><?php esc_html_e('Transfer Summary', 'cpm-dongtrader'); ?></h4>
            <div class="summary-row">
                <span class="summary-label"><?php esc_html_e('Receiver:', 'cpm-dongtrader'); ?></span>
                <span class="summary-value" id="summary_receiver">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label"><?php esc_html_e('XP Amount:', 'cpm-dongtrader'); ?></span>
                <span class="summary-value" id="summary_amount">0.000000</span>
            </div>
            <div class="summary-row">
                <span class="summary-label"><?php esc_html_e('YAM Equivalent:', 'cpm-dongtrader'); ?></span>
                <span class="summary-value" id="summary_yam">0</span>
            </div>
            <div class="summary-row">
                <span class="summary-label"><?php esc_html_e('Your New Balance:', 'cpm-dongtrader'); ?></span>
                <span class="summary-value" id="summary_new_balance"><?php echo number_format($total_xp, 6); ?></span>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-primary" id="submit_btn">
            <?php esc_html_e('Send XP', 'cpm-dongtrader'); ?>
        </button>
        <button type="button" class="btn-secondary" onclick="window.location.href='<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>'">
            <?php esc_html_e('Cancel', 'cpm-dongtrader'); ?>
        </button>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    let selectedReceiver = null;
    let searchTimeout = null;
    const minTransfer = <?php echo $min_transfer; ?>;
    const maxTransfer = <?php echo $max_transfer; ?>;
    const currentBalance = <?php echo $total_xp; ?>;
    // NEW CONVERSION: XP to USD directly (USD = XP / 10^23)
    // For JavaScript, we'll use: USD = XP / 100000000000000000000000 (10^23)
    const xpPerDollar = 100000000000000000000000; // 10^23 XP per USD
    const usdPerXP = 1 / xpPerDollar; // USD per XP
    // NEW CONVERSION: 1 USD = 21,000 YAM = 10^23 XP
    const yamPerUSD = 21000; // 1 USD = 21,000 YAM

    // Receiver search
    $('#receiver_search').on('input', function() {
        const query = $(this).val();
        
        if (query.length < 2) {
            $('#receiver_results').hide();
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'search_xp_receiver',
                    query: query,
                    nonce: '<?php echo wp_create_nonce('search_receiver'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(function(user) {
                            html += '<div class="receiver-result-item" data-user-id="' + user.id + '" data-name="' + user.name + '" data-email="' + user.email + '">';
                            html += '<div class="name">' + user.name + '</div>';
                            html += '<div class="email">' + user.email + '</div>';
                            html += '</div>';
                        });
                        $('#receiver_results').html(html).show();
                    } else {
                        $('#receiver_results').hide();
                    }
                }
            });
        }, 300);
    });

    // Select receiver
    $(document).on('click', '.receiver-result-item', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('name');
        const userEmail = $(this).data('email');
        
        selectedReceiver = {
            id: userId,
            name: userName,
            email: userEmail
        };
        
        $('#receiver_id').val(userId);
        $('#receiver_name').text(userName);
        $('#receiver_email').text(userEmail);
        $('#selected_receiver').show();
        $('#receiver_search').val(userName);
        $('#receiver_results').hide();
        updateSummary();
    });

    // Clear receiver
    window.clearReceiver = function() {
        selectedReceiver = null;
        $('#receiver_id').val('');
        $('#receiver_search').val('');
        $('#selected_receiver').hide();
        updateSummary();
    };

    // Amount input with conversion
    $('#xp_amount').on('input', function() {
        const amount = parseFloat($(this).val()) || 0;
        validateAmount(amount);
        updateConversion(amount);
        updateSummary();
    });

    function validateAmount(amount) {
        const $error = $('#amount_error');
        let error = '';

        if (amount < minTransfer) {
            error = '<?php echo esc_js(sprintf(__('Minimum transfer: %s XP', 'cpm-dongtrader'), number_format($min_transfer, 6))); ?>';
        } else if (amount > maxTransfer) {
            error = '<?php echo esc_js(sprintf(__('Maximum transfer: %s XP (50%% of balance)', 'cpm-dongtrader'), number_format($max_transfer, 6))); ?>';
        } else if (amount > currentBalance) {
            error = '<?php echo esc_js(__('Insufficient balance', 'cpm-dongtrader')); ?>';
        }

        if (error) {
            $error.text(error).show();
            $('#submit_btn').prop('disabled', true);
        } else {
            $error.hide();
            $('#submit_btn').prop('disabled', false);
        }
    }

    function updateConversion(amount) {
        if (amount > 0) {
            // NEW CONVERSION: Calculate USD directly from XP (USD = XP / 10^23)
            const usd = amount * usdPerXP;
            // NEW CONVERSION: 1 USD = 21,000 YAM = 10^23 XP
            const yam = usd * yamPerUSD; // 1 USD = 21,000 YAM
            $('#yam_equiv').text(yam.toExponential(2));
            $('#usd_value').text(usd.toFixed(2));
            $('#conversion_display').show();
        } else {
            $('#conversion_display').hide();
        }
    }

    function updateSummary() {
        const amount = parseFloat($('#xp_amount').val()) || 0;
        if (selectedReceiver && amount > 0) {
            // NEW CONVERSION: Calculate USD from XP, then YAM for display
            const usd = amount * usdPerXP;
            const yam = usd * yamPerUSD; // 1 USD = 21,000 YAM
            const newBalance = currentBalance - amount;
            
            $('#summary_receiver').text(selectedReceiver.name);
            $('#summary_amount').text(amount.toFixed(6));
            $('#summary_yam').text(yam.toLocaleString('en-US', {maximumFractionDigits: 2}));
            $('#summary_new_balance').text(newBalance.toFixed(6));
            $('#transfer_summary').show();
        } else {
            $('#transfer_summary').hide();
        }
    }

    // Memo character count
    $('#memo').on('input', function() {
        $('#char_count').text($(this).val().length);
    });

    // Form submission
    $('#send-xp-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!selectedReceiver) {
            $('#receiver_error').text('<?php echo esc_js(__('Please select a receiver', 'cpm-dongtrader')); ?>').show();
            return;
        }

        const amount = parseFloat($('#xp_amount').val());
        if (!validateAmount(amount)) {
            return;
        }

        $('#submit_btn').prop('disabled', true).text('<?php echo esc_js(__('Sending...', 'cpm-dongtrader')); ?>');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'send_xp_transfer',
                receiver_id: selectedReceiver.id,
                xp_amount: amount,
                memo: $('#memo').val(),
                nonce: $('#xp_transfer_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('XP transferred successfully!', 'cpm-dongtrader')); ?>');
                    window.location.href = '<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>';
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Transfer failed. Please try again.', 'cpm-dongtrader')); ?>');
                    $('#submit_btn').prop('disabled', false).text('<?php echo esc_js(__('Send XP', 'cpm-dongtrader')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'cpm-dongtrader')); ?>');
                $('#submit_btn').prop('disabled', false).text('<?php echo esc_js(__('Send XP', 'cpm-dongtrader')); ?>');
            }
        });
    });

    // Close results on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.receiver-search').length) {
            $('#receiver_results').hide();
        }
    });
});
</script>

