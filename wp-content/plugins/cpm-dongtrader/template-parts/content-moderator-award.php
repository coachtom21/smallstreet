<?php
/**
 * Moderator XP Award Page Template
 * Allows moderators (PMG, Captain, Treasurer) to award XP bonuses
 */

if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Please log in.', 'cpm-dongtrader') . '</p>';
    return;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();

// Check if user has moderator role
$user_roles = $user->roles;
$moderator_roles = array('administrator', 'pmg', 'captain', 'treasurer');
$is_moderator = false;

foreach ($user_roles as $role) {
    if (in_array($role, $moderator_roles)) {
        $is_moderator = true;
        $moderator_role = $role;
        break;
    }
}

if (!$is_moderator) {
    echo '<div style="padding: 20px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b;">';
    echo '<strong>' . esc_html__('Access Denied', 'cpm-dongtrader') . '</strong><br>';
    echo esc_html__('You do not have permission to award XP. This feature is only available to moderators.', 'cpm-dongtrader');
    echo '</div>';
    return;
}

// Award limits by role
$award_limits = array(
    'administrator' => 10000.00,  // $10,000 USD equivalent
    'pmg' => 1000.00,             // $1,000 USD equivalent
    'captain' => 500.00,          // $500 USD equivalent
    'treasurer' => 100.00         // $100 USD equivalent
);

$user_limit = isset($award_limits[$moderator_role]) ? $award_limits[$moderator_role] : 100.00;
$approval_threshold = 100.00; // USD - requires second approval above this

extract($args);
$cs = get_woocommerce_currency_symbol();
?>

<style>
/* Moderator Award Styles */
.moderator-award-container {
  background: linear-gradient(135deg, #f9fafb, #f3f4f6);
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 30px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  font-family: "Inter", system-ui, sans-serif;
  color: #1f2937;
  max-width: 900px;
  margin: 0 auto;
}

.moderator-award-container h2 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: #111827;
}

.mod-badge {
  display: inline-block;
  background: linear-gradient(90deg, #f59e0b, #fbbf24);
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 20px;
}

.limit-info-box {
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 25px;
}

.limit-info-box h4 {
  font-size: 0.9rem;
  font-weight: 600;
  color: #92400e;
  margin-bottom: 8px;
}

.limit-info-box p {
  font-size: 0.85rem;
  color: #78350f;
  margin: 5px 0;
}

.award-form {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 25px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

.reason-select {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
  margin-top: 10px;
}

.reason-option {
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
}

.reason-option:hover {
  border-color: #10b981;
  background: #f0fdf4;
}

.reason-option.selected {
  border-color: #10b981;
  background: #d1fae5;
}

.approval-notice {
  background: #dbeafe;
  border: 1px solid #93c5fd;
  border-radius: 8px;
  padding: 15px;
  margin-top: 20px;
  display: none;
}

.approval-notice.show {
  display: block;
}

.approval-notice p {
  color: #1e40af;
  margin: 5px 0;
  font-size: 0.9rem;
}

.recent-awards {
  margin-top: 30px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
}

.recent-awards h3 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 15px;
  color: #111827;
}

.award-list {
  display: grid;
  gap: 10px;
}

.award-item {
  padding: 12px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.award-item .info {
  flex: 1;
}

.award-item .recipient {
  font-weight: 600;
  color: #111827;
  margin-bottom: 3px;
}

.award-item .details {
  font-size: 0.85rem;
  color: #6b7280;
}

.award-item .amount {
  font-weight: 700;
  color: #065f46;
  font-family: 'Courier New', monospace;
}
</style>

<div class="moderator-award-container">
    <h2><?php esc_html_e('Award XP', 'cpm-dongtrader'); ?></h2>
    
    <div class="mod-badge">
        <?php echo esc_html(strtoupper($moderator_role)); ?> <?php esc_html_e('Moderator', 'cpm-dongtrader'); ?>
    </div>

    <!-- Limit Information -->
    <div class="limit-info-box">
        <h4><?php esc_html_e('Your Award Limits', 'cpm-dongtrader'); ?></h4>
        <p><strong><?php esc_html_e('Maximum per award:', 'cpm-dongtrader'); ?></strong> <?php echo $cs . number_format($user_limit, 2); ?> <?php esc_html_e('USD equivalent', 'cpm-dongtrader'); ?></p>
        <p><strong><?php esc_html_e('Approval required:', 'cpm-dongtrader'); ?></strong> <?php esc_html_e('Awards above', 'cpm-dongtrader'); ?> <?php echo $cs . number_format($approval_threshold, 2); ?> <?php esc_html_e('require second moderator approval', 'cpm-dongtrader'); ?></p>
    </div>

    <!-- Award Form -->
    <form id="award-xp-form" class="award-form">
        <?php wp_nonce_field('award_xp', 'award_xp_nonce'); ?>
        
        <div class="form-row">
            <!-- Recipient Search -->
            <div class="form-group">
                <label for="recipient_search"><?php esc_html_e('Recipient', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
                <div class="receiver-search">
                    <input 
                        type="text" 
                        id="recipient_search" 
                        name="recipient_search" 
                        placeholder="<?php esc_attr_e('Search by email, username, or FonePay ID', 'cpm-dongtrader'); ?>"
                        autocomplete="off"
                        required
                    />
                    <div class="receiver-results" id="recipient_results"></div>
                </div>
                <input type="hidden" id="recipient_id" name="recipient_id" required />
                <div class="help-text"><?php esc_html_e('Start typing to search for a user', 'cpm-dongtrader'); ?></div>
                <div class="error-text" id="recipient_error"></div>
            </div>

            <!-- XP Amount -->
            <div class="form-group">
                <label for="award_xp_amount"><?php esc_html_e('XP Amount', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
                <div class="amount-input-wrapper">
                    <input 
                        type="number" 
                        id="award_xp_amount" 
                        name="award_xp_amount" 
                        step="0.000001" 
                        min="0.000001"
                        max="<?php echo esc_attr($user_limit / 0.000047619); ?>" // Convert USD limit to XP
                        placeholder="0.000000"
                        required
                    />
                </div>
                <div class="help-text"><?php esc_html_e('Maximum:', 'cpm-dongtrader'); ?> <?php echo $cs . number_format($user_limit, 2); ?> <?php esc_html_e('USD equivalent', 'cpm-dongtrader'); ?></div>
                <div class="error-text" id="amount_error"></div>
                
                <!-- Conversion Display -->
                <div class="conversion-display" id="award_conversion_display" style="display: none;">
                    <div class="conversion-item">
                        <span class="conversion-label"><?php esc_html_e('YAM Equivalent:', 'cpm-dongtrader'); ?></span>
                        <span class="conversion-value" id="award_yam_equiv">0</span>
                    </div>
                    <div class="conversion-item">
                        <span class="conversion-label"><?php esc_html_e('USD Trade Value:', 'cpm-dongtrader'); ?></span>
                        <span class="conversion-value"><?php echo $cs; ?><span id="award_usd_value">0.00</span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Recipient Display -->
        <div id="selected_recipient" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 600; color: #065f46; margin-bottom: 3px;" id="recipient_name"></div>
                    <div style="font-size: 0.85rem; color: #6b7280;" id="recipient_email"></div>
                </div>
                <button type="button" onclick="clearRecipient()" style="background: none; border: none; color: #6b7280; cursor: pointer; font-size: 1.2rem;">×</button>
            </div>
        </div>

        <!-- Award Reason -->
        <div class="form-group">
            <label><?php esc_html_e('Award Reason', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
            <div class="reason-select">
                <div class="reason-option" data-reason="event_participation">
                    <?php esc_html_e('Event Participation', 'cpm-dongtrader'); ?>
                </div>
                <div class="reason-option" data-reason="governance">
                    <?php esc_html_e('Governance Participation', 'cpm-dongtrader'); ?>
                </div>
                <div class="reason-option" data-reason="community">
                    <?php esc_html_e('Community Contribution', 'cpm-dongtrader'); ?>
                </div>
                <div class="reason-option" data-reason="achievement">
                    <?php esc_html_e('Special Achievement', 'cpm-dongtrader'); ?>
                </div>
                <div class="reason-option" data-reason="other">
                    <?php esc_html_e('Other', 'cpm-dongtrader'); ?>
                </div>
            </div>
            <input type="hidden" id="award_reason" name="award_reason" required />
            <div class="error-text" id="reason_error"></div>
        </div>

        <!-- Memo -->
        <div class="form-group">
            <label for="award_memo"><?php esc_html_e('Memo / Description', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
            <textarea 
                id="award_memo" 
                name="award_memo" 
                placeholder="<?php esc_attr_e('Describe why this award is being given...', 'cpm-dongtrader'); ?>"
                maxlength="1000"
                required
            ></textarea>
            <div class="help-text">
                <span id="award_char_count">0</span> / 1000 <?php esc_html_e('characters', 'cpm-dongtrader'); ?>
            </div>
        </div>

        <!-- Approval Notice -->
        <div class="approval-notice" id="approval_notice">
            <p><strong><?php esc_html_e('Approval Required', 'cpm-dongtrader'); ?></strong></p>
            <p><?php esc_html_e('This award exceeds the automatic approval threshold. A second moderator must approve this award before it can be processed.', 'cpm-dongtrader'); ?></p>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-primary" id="submit_award_btn">
            <?php esc_html_e('Award XP', 'cpm-dongtrader'); ?>
        </button>
    </form>

    <!-- Recent Awards -->
    <div class="recent-awards">
        <h3><?php esc_html_e('Your Recent Awards', 'cpm-dongtrader'); ?></h3>
        <div class="award-list" id="recent_awards_list">
            <!-- Will be loaded via AJAX -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let selectedRecipient = null;
    const approvalThreshold = <?php echo $approval_threshold; ?>;
    // NEW CONVERSION: XP to USD directly
    const xpPerDollar = 100000000000000000000000; // 10^23 XP per USD
    const usdPerXP = 1 / xpPerDollar; // USD per XP
    // NEW CONVERSION: 1 USD = 21,000 YAM = 10^23 XP
    const yamPerUSD = 21000; // 1 USD = 21,000 YAM

    // Recipient search (same as send-xp.php)
    $('#recipient_search').on('input', function() {
        const query = $(this).val();
        if (query.length < 2) {
            $('#recipient_results').hide();
            return;
        }
        // AJAX search implementation (same as send-xp.php)
    });

    // Reason selection
    $('.reason-option').on('click', function() {
        $('.reason-option').removeClass('selected');
        $(this).addClass('selected');
        $('#award_reason').val($(this).data('reason'));
    });

    // Amount input with conversion
    $('#award_xp_amount').on('input', function() {
        const amount = parseFloat($(this).val()) || 0;
        updateAwardConversion(amount);
        checkApprovalRequired(amount);
    });

    function updateAwardConversion(amount) {
        if (amount > 0) {
            // NEW CONVERSION: Calculate USD directly from XP (USD = XP / 10^23)
            const usd = amount * usdPerXP;
            // NEW CONVERSION: 1 USD = 21,000 YAM = 10^23 XP
            const yam = usd * yamPerUSD; // 1 USD = 21,000 YAM
            $('#award_yam_equiv').text(yam.toExponential(2));
            $('#award_usd_value').text(usd.toFixed(2));
            $('#award_conversion_display').show();
        } else {
            $('#award_conversion_display').hide();
        }
    }

    function checkApprovalRequired(amount) {
        // NEW CONVERSION: Calculate USD directly from XP (USD = XP / 10^23)
        const usd = amount * usdPerXP;
        if (usd > approvalThreshold) {
            $('#approval_notice').addClass('show');
        } else {
            $('#approval_notice').removeClass('show');
        }
    }

    // Memo character count
    $('#award_memo').on('input', function() {
        $('#award_char_count').text($(this).val().length);
    });

    // Form submission
    $('#award-xp-form').on('submit', function(e) {
        e.preventDefault();
        // Validation and AJAX submission
    });

    // Load recent awards
    function loadRecentAwards() {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'get_recent_awards',
                nonce: '<?php echo wp_create_nonce('get_recent_awards'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    renderRecentAwards(response.data.awards);
                }
            }
        });
    }

    function renderRecentAwards(awards) {
        let html = '';
        if (awards.length === 0) {
            html = '<p style="color: #6b7280; text-align: center; padding: 20px;"><?php echo esc_js(__('No recent awards', 'cpm-dongtrader')); ?></p>';
        } else {
            awards.forEach(function(award) {
                html += '<div class="award-item">';
                html += '<div class="info">';
                html += '<div class="recipient">' + award.recipient_name + '</div>';
                html += '<div class="details">' + award.reason_label + ' • ' + award.date + '</div>';
                html += '</div>';
                html += '<div class="amount">' + parseFloat(award.xp_amount).toFixed(6) + ' XP</div>';
                html += '</div>';
            });
        }
        $('#recent_awards_list').html(html);
    }

    loadRecentAwards();
});
</script>

