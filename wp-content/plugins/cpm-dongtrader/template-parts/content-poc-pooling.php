<?php
/**
 * POC Pooling System Page Template
 * Allows sellers to create and manage XP pooling groups
 */

if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Please log in to access POC pooling.', 'cpm-dongtrader') . '</p>';
    return;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();
$user_name = $user->display_name ? $user->display_name : $user->user_login;

// Check if user is a seller (has seller_scan data)
$seller_scan_raw = get_user_meta($user_id, 'seller_scan', true);
$seller_scan_data = maybe_unserialize($seller_scan_raw);
$is_seller = is_array($seller_scan_data) && !empty($seller_scan_data);

if (!$is_seller) {
    echo '<div style="padding: 20px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b;">';
    echo '<strong>' . esc_html__('Access Restricted', 'cpm-dongtrader') . '</strong><br>';
    echo esc_html__('POC Pooling is only available to sellers. Complete at least one seller scan to create a pool.', 'cpm-dongtrader');
    echo '</div>';
    return;
}

// TODO: Fetch user's pools from database
$user_pools = array(); // Will be populated from wp_xp_pools table

extract($args);
$cs = get_woocommerce_currency_symbol();
?>

<style>
/* POC Pooling Styles */
.poc-pooling-container {
  background: linear-gradient(135deg, #f9fafb, #f3f4f6);
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 30px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  font-family: "Inter", system-ui, sans-serif;
  color: #1f2937;
}

.poc-pooling-container h2 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: #111827;
}

.pool-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 25px;
  border-bottom: 2px solid #e5e7eb;
}

.pool-tab {
  padding: 12px 20px;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.95rem;
}

.pool-tab:hover {
  color: #065f46;
}

.pool-tab.active {
  color: #065f46;
  border-bottom-color: #10b981;
}

.create-pool-section,
.my-pools-section {
  display: none;
}

.create-pool-section.active,
.my-pools-section.active {
  display: block;
}

.pool-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 25px;
  margin-bottom: 20px;
  transition: all 0.2s;
}

.pool-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.pool-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 20px;
}

.pool-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 5px;
}

.pool-status {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.pool-status.active {
  background: #d1fae5;
  color: #065f46;
}

.pool-status.pending {
  background: #fef3c7;
  color: #92400e;
}

.pool-status.closed {
  background: #e5e7eb;
  color: #6b7280;
}

.pool-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
  padding: 15px;
  background: #f9fafb;
  border-radius: 8px;
}

.stat-label {
  font-size: 0.85rem;
  color: #6b7280;
  margin-bottom: 5px;
}

.stat-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: #065f46;
}

.pool-members {
  margin-top: 20px;
}

.members-list {
  display: grid;
  gap: 10px;
}

.member-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.member-info {
  flex: 1;
}

.member-name {
  font-weight: 600;
  color: #111827;
  margin-bottom: 3px;
}

.member-details {
  font-size: 0.85rem;
  color: #6b7280;
}

.member-contribution {
  font-weight: 600;
  color: #065f46;
  font-family: 'Courier New', monospace;
}

.current-recipient {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.current-recipient .member-name::after {
  content: ' 👑';
}

.pool-actions {
  display: flex;
  gap: 10px;
  margin-top: 20px;
  flex-wrap: wrap;
}

.btn-pool {
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  font-size: 0.9rem;
}

.btn-contribute {
  background: #10b981;
  color: white;
}

.btn-contribute:hover {
  background: #059669;
}

.btn-leave {
  background: #fee2e2;
  color: #991b1b;
}

.btn-leave:hover {
  background: #fecaca;
}

.btn-view {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
}

.btn-view:hover {
  background: #e5e7eb;
}

.create-pool-form {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 25px;
}

.invite-members {
  margin-top: 20px;
}

.invited-member {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-bottom: 10px;
}

.invited-member .status {
  font-size: 0.85rem;
  padding: 4px 8px;
  border-radius: 4px;
}

.invited-member .status.pending {
  background: #fef3c7;
  color: #92400e;
}

.invited-member .status.accepted {
  background: #d1fae5;
  color: #065f46;
}

.invited-member .status.declined {
  background: #fee2e2;
  color: #991b1b;
}

.contribution-form {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 25px;
  margin-top: 20px;
}

.bonus-info {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 15px;
  margin-top: 15px;
}

.bonus-info h4 {
  font-size: 0.9rem;
  font-weight: 600;
  color: #065f46;
  margin-bottom: 8px;
}

.bonus-info p {
  font-size: 0.85rem;
  color: #047857;
  margin: 5px 0;
}
</style>

<div class="poc-pooling-container">
    <h2><?php esc_html_e('POC Pooling', 'cpm-dongtrader'); ?></h2>
    
    <!-- Info Banner -->
    <div class="laugh-mode-banner" style="margin-bottom: 25px;">
        <span class="status-indicator"></span>
        <div>
            <strong style="font-size: 16px;"><?php esc_html_e('4% Bonus System', 'cpm-dongtrader'); ?></strong>
            <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.95;">
                <?php esc_html_e('Pool XP with 4 other sellers. Earn a 4% bonus that rotates among members monthly.', 'cpm-dongtrader'); ?>
            </p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="pool-tabs">
        <button class="pool-tab active" data-tab="my-pools">
            <?php esc_html_e('My Pools', 'cpm-dongtrader'); ?>
        </button>
        <button class="pool-tab" data-tab="create-pool">
            <?php esc_html_e('Create Pool', 'cpm-dongtrader'); ?>
        </button>
    </div>

    <!-- My Pools Section -->
    <div class="my-pools-section active" id="my-pools-section">
        <?php if (!empty($user_pools)): ?>
            <div id="pools_list">
                <!-- Pools will be loaded via AJAX -->
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                <div style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;">🏊</div>
                <h3><?php esc_html_e('No Pools Yet', 'cpm-dongtrader'); ?></h3>
                <p><?php esc_html_e('Create your first POC pool to start earning bonus XP!', 'cpm-dongtrader'); ?></p>
                <button class="btn-pool btn-contribute" onclick="switchTab('create-pool')" style="margin-top: 20px;">
                    <?php esc_html_e('Create Pool', 'cpm-dongtrader'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create Pool Section -->
    <div class="create-pool-section" id="create-pool-section">
        <form id="create-pool-form" class="create-pool-form">
            <?php wp_nonce_field('create_poc_pool', 'create_pool_nonce'); ?>
            
            <div class="form-group">
                <label for="pool_name"><?php esc_html_e('Pool Name', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
                <input 
                    type="text" 
                    id="pool_name" 
                    name="pool_name" 
                    placeholder="<?php esc_attr_e('e.g., Nepal Distribution Pool', 'cpm-dongtrader'); ?>"
                    required
                />
            </div>

            <div class="form-group">
                <label><?php esc_html_e('Rotation Schedule', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
                <div style="display: flex; gap: 15px; margin-top: 10px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="radio" name="rotation_schedule" value="weekly" style="margin-right: 8px;">
                        <?php esc_html_e('Weekly', 'cpm-dongtrader'); ?>
                    </label>
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="radio" name="rotation_schedule" value="monthly" checked style="margin-right: 8px;">
                        <?php esc_html_e('Monthly', 'cpm-dongtrader'); ?>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label><?php esc_html_e('Invite 4 Sellers', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
                <div class="receiver-search">
                    <input 
                        type="text" 
                        id="invite_search" 
                        placeholder="<?php esc_attr_e('Search sellers to invite...', 'cpm-dongtrader'); ?>"
                        autocomplete="off"
                    />
                    <div class="receiver-results" id="invite_results"></div>
                </div>
                <div class="help-text"><?php esc_html_e('Search and add exactly 4 sellers to complete the pool', 'cpm-dongtrader'); ?></div>
            </div>

            <div class="invite-members" id="invited_members">
                <!-- Invited members will appear here -->
            </div>

            <div class="bonus-info">
                <h4><?php esc_html_e('How It Works', 'cpm-dongtrader'); ?></h4>
                <p>• <?php esc_html_e('Pool requires exactly 5 members (you + 4 invited sellers)', 'cpm-dongtrader'); ?></p>
                <p>• <?php esc_html_e('All members must accept the invitation', 'cpm-dongtrader'); ?></p>
                <p>• <?php esc_html_e('Members contribute XP to the pool', 'cpm-dongtrader'); ?></p>
                <p>• <?php esc_html_e('4% bonus is calculated on total pooled XP', 'cpm-dongtrader'); ?></p>
                <p>• <?php esc_html_e('Bonus rotates to each member on schedule', 'cpm-dongtrader'); ?></p>
            </div>

            <button type="submit" class="btn-primary" id="create_pool_btn" disabled>
                <?php esc_html_e('Create Pool', 'cpm-dongtrader'); ?>
            </button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let invitedMembers = [];
    const maxMembers = 4;

    // Tab switching
    $('.pool-tab').on('click', function() {
        $('.pool-tab').removeClass('active');
        $(this).addClass('active');
        const tab = $(this).data('tab');
        $('.create-pool-section, .my-pools-section').removeClass('active');
        $('#' + tab + '-section').addClass('active');
    });

    window.switchTab = function(tab) {
        $('.pool-tab[data-tab="' + tab + '"]').click();
    };

    // Invite member search
    $('#invite_search').on('input', function() {
        const query = $(this).val();
        if (query.length < 2) {
            $('#invite_results').hide();
            return;
        }
        // AJAX search for sellers only
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'search_seller_for_pool',
                query: query,
                nonce: '<?php echo wp_create_nonce('search_seller'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(seller) {
                        // Check if already invited
                        if (invitedMembers.find(m => m.id === seller.id)) {
                            return;
                        }
                        html += '<div class="receiver-result-item" data-user-id="' + seller.id + '" data-name="' + seller.name + '" data-email="' + seller.email + '">';
                        html += '<div class="name">' + seller.name + '</div>';
                        html += '<div class="email">' + seller.email + '</div>';
                        html += '</div>';
                    });
                    $('#invite_results').html(html).show();
                } else {
                    $('#invite_results').hide();
                }
            }
        });
    });

    // Add invited member
    $(document).on('click', '#invite_results .receiver-result-item', function() {
        if (invitedMembers.length >= maxMembers) {
            alert('<?php echo esc_js(__('Maximum 4 members allowed', 'cpm-dongtrader')); ?>');
            return;
        }

        const member = {
            id: $(this).data('user-id'),
            name: $(this).data('name'),
            email: $(this).data('email')
        };

        invitedMembers.push(member);
        renderInvitedMembers();
        $('#invite_search').val('');
        $('#invite_results').hide();
    });

    function renderInvitedMembers() {
        let html = '';
        invitedMembers.forEach(function(member, index) {
            html += '<div class="invited-member" data-index="' + index + '">';
            html += '<div>';
            html += '<div style="font-weight: 600; color: #111827;">' + member.name + '</div>';
            html += '<div style="font-size: 0.85rem; color: #6b7280;">' + member.email + '</div>';
            html += '</div>';
            html += '<div>';
            html += '<span class="status pending"><?php echo esc_js(__('Pending', 'cpm-dongtrader')); ?></span>';
            html += '<button type="button" onclick="removeMember(' + index + ')" style="margin-left: 10px; background: none; border: none; color: #dc2626; cursor: pointer;">×</button>';
            html += '</div>';
            html += '</div>';
        });
        $('#invited_members').html(html);
        $('#create_pool_btn').prop('disabled', invitedMembers.length !== maxMembers);
    }

    window.removeMember = function(index) {
        invitedMembers.splice(index, 1);
        renderInvitedMembers();
    };

    // Create pool form submission
    $('#create-pool-form').on('submit', function(e) {
        e.preventDefault();
        
        if (invitedMembers.length !== maxMembers) {
            alert('<?php echo esc_js(__('Please invite exactly 4 sellers', 'cpm-dongtrader')); ?>');
            return;
        }

        const memberIds = invitedMembers.map(m => m.id);

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'create_poc_pool',
                pool_name: $('#pool_name').val(),
                rotation_schedule: $('input[name="rotation_schedule"]:checked').val(),
                member_ids: memberIds,
                nonce: $('#create_pool_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Pool created! Invitations sent to members.', 'cpm-dongtrader')); ?>');
                    window.location.reload();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Failed to create pool', 'cpm-dongtrader')); ?>');
                }
            }
        });
    });

    // Load user's pools
    function loadUserPools() {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'get_user_pools',
                nonce: '<?php echo wp_create_nonce('get_user_pools'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    renderPools(response.data.pools);
                }
            }
        });
    }

    function renderPools(pools) {
        let html = '';
        if (pools.length === 0) {
            html = '<div style="text-align: center; padding: 40px; color: #6b7280;"><?php echo esc_js(__('No pools found', 'cpm-dongtrader')); ?></div>';
        } else {
            pools.forEach(function(pool) {
                html += '<div class="pool-card">';
                html += '<div class="pool-header">';
                html += '<div>';
                html += '<div class="pool-name">' + pool.name + '</div>';
                html += '<div style="font-size: 0.85rem; color: #6b7280;"><?php echo esc_js(__('Rotation:', 'cpm-dongtrader')); ?> ' + pool.rotation_schedule + '</div>';
                html += '</div>';
                html += '<span class="pool-status ' + pool.status + '">' + pool.status + '</span>';
                html += '</div>';
                
                html += '<div class="pool-stats">';
                html += '<div class="stat-item">';
                html += '<div class="stat-label"><?php echo esc_js(__('Total Pooled', 'cpm-dongtrader')); ?></div>';
                html += '<div class="stat-value">' + parseFloat(pool.total_xp).toFixed(6) + ' XP</div>';
                html += '</div>';
                html += '<div class="stat-item">';
                html += '<div class="stat-label"><?php echo esc_js(__('Bonus XP', 'cpm-dongtrader')); ?></div>';
                html += '<div class="stat-value">' + parseFloat(pool.bonus_xp).toFixed(6) + ' XP</div>';
                html += '</div>';
                html += '<div class="stat-item">';
                html += '<div class="stat-label"><?php echo esc_js(__('Members', 'cpm-dongtrader')); ?></div>';
                html += '<div class="stat-value">' + pool.member_count + ' / 5</div>';
                html += '</div>';
                html += '</div>';

                html += '<div class="pool-members">';
                html += '<h4 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 10px; color: #374151;"><?php echo esc_js(__('Members', 'cpm-dongtrader')); ?></h4>';
                html += '<div class="members-list">';
                pool.members.forEach(function(member) {
                    const isCurrent = member.user_id === pool.current_recipient_id;
                    html += '<div class="member-item' + (isCurrent ? ' current-recipient' : '') + '">';
                    html += '<div class="member-info">';
                    html += '<div class="member-name">' + member.name + '</div>';
                    html += '<div class="member-details"><?php echo esc_js(__('Contributed:', 'cpm-dongtrader')); ?> ' + parseFloat(member.contribution).toFixed(6) + ' XP</div>';
                    html += '</div>';
                    html += '<div class="member-contribution">';
                    if (isCurrent) {
                        html += '<?php echo esc_js(__('Current Recipient', 'cpm-dongtrader')); ?>';
                    }
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
                html += '</div>';

                html += '<div class="pool-actions">';
                html += '<button class="btn-pool btn-contribute" onclick="openContributeModal(' + pool.id + ')"><?php echo esc_js(__('Contribute XP', 'cpm-dongtrader')); ?></button>';
                html += '<button class="btn-pool btn-view" onclick="viewPoolDetails(' + pool.id + ')"><?php echo esc_js(__('View Details', 'cpm-dongtrader')); ?></button>';
                if (pool.status === 'active') {
                    html += '<button class="btn-pool btn-leave" onclick="requestLeavePool(' + pool.id + ')"><?php echo esc_js(__('Leave Pool', 'cpm-dongtrader')); ?></button>';
                }
                html += '</div>';
                html += '</div>';
            });
        }
        $('#pools_list').html(html);
    }

    // Initial load
    loadUserPools();
});
</script>

