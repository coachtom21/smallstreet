# XP Transfer/Trading System - Module Specification

## Overview

The XP Transfer/Trading System enables users to transfer XP (Experience Points) between verified wallets, award bonuses, and participate in POC (Proof of Concept) pooling groups. All transfers remain off-chain, tagged as `XP_TRADE_ONLY`, and operate under LAUGH Mode rules (no fiat/crypto conversion until August 31, 2026).

---

## 📋 TABLE OF CONTENTS

1. [System Requirements](#system-requirements)
2. [Module Flow Diagrams](#module-flow-diagrams)
3. [Database Schema](#database-schema)
4. [API Endpoints](#api-endpoints)
5. [User Interface Requirements](#user-interface-requirements)
6. [Security & Validation](#security--validation)
7. [Integration Points](#integration-points)
8. [Implementation Phases](#implementation-phases)

---

## 🎯 SYSTEM REQUIREMENTS

### 1. Core Functionality Requirements

#### 1.1 Peer-to-Peer XP Transfer (`TransferXP()`)
- **Purpose**: Allow users to send XP to other verified users
- **Zero-Sum Ledger**: Total XP in system remains constant (sender loses, receiver gains)
- **Verification**: Both sender and receiver must be verified users
- **Status**: Off-chain only, tagged `XP_TRADE_ONLY`
- **LAUGH Mode**: No monetary value, trade credits only

**Requirements:**
- [ ] Sender must have sufficient XP balance
- [ ] Receiver must be a verified/active user
- [ ] Minimum transfer amount: 0.000001 XP (1 YAM equivalent)
- [ ] Maximum transfer amount: Configurable (default: 50% of sender's balance)
- [ ] Transfer fee: None (0% during LAUGH Mode)
- [ ] Transaction logging with full audit trail
- [ ] Real-time balance updates
- [ ] Email/notification to receiver (optional)

#### 1.2 Moderator XP Award (`AwardXP()`)
- **Purpose**: Allow moderators/PMG to issue XP bonuses for verified actions
- **Authority**: Only users with `PMG`, `Captain`, or `Treasurer` roles
- **Use Cases**: 
  - Event participation rewards
  - Governance participation
  - Community contributions
  - Special achievements
- **Status**: Creates new XP (not zero-sum)

**Requirements:**
- [ ] Role-based permission check
- [ ] Award amount limits (configurable per role)
- [ ] Reason/memo field required
- [ ] Approval workflow (optional, for large awards)
- [ ] Audit trail with moderator signature
- [ ] Notification to recipient

#### 1.3 POC Pooling System
- **Purpose**: Allow groups of 5 sellers to pool XP for rotating bonuses
- **Group Bonus**: 4% bonus on pooled XP
- **Rotation**: XP rotates among group members
- **Group Management**: Create, join, leave pools

**Requirements:**
- [ ] Minimum group size: 5 members
- [ ] Maximum group size: 5 members (fixed)
- [ ] Only sellers can create/join pools
- [ ] Pool creation requires all 5 members to accept
- [ ] 4% bonus calculation on total pooled XP
- [ ] Rotation schedule (weekly/monthly)
- [ ] Pool status tracking (active, pending, closed)
- [ ] Member contribution tracking

---

## 🔄 MODULE FLOW DIAGRAMS

### Flow 1: Peer-to-Peer XP Transfer

```
┌─────────────────────────────────────────────────────────────┐
│                    XP TRANSFER FLOW                          │
└─────────────────────────────────────────────────────────────┘

1. USER INITIATES TRANSFER
   │
   ├─> User navigates to "Send XP" page
   ├─> Enters receiver identifier (email/username/FonePay ID)
   ├─> Enters XP amount
   ├─> Enters optional memo/reason
   │
   ▼
2. VALIDATION PHASE
   │
   ├─> Check sender balance >= transfer amount
   ├─> Verify receiver exists and is active
   ├─> Check transfer limits (min/max)
   ├─> Verify LAUGH Mode status (no fiat conversion)
   ├─> Check if receiver account is verified
   │
   ├─> ❌ VALIDATION FAILS
   │   │
   │   └─> Display error message
   │       └─> Return to form
   │
   └─> ✅ VALIDATION PASSES
       │
       ▼
3. CONFIRMATION PHASE
   │
   ├─> Display transfer summary:
   │   ├─> Receiver name
   │   ├─> XP amount
   │   ├─> YAM equivalent
   │   ├─> USD trade value (display only)
   │   └─> Memo (if provided)
   │
   ├─> User confirms transfer
   │
   ▼
4. EXECUTION PHASE
   │
   ├─> Create transfer ledger entry
   ├─> Deduct XP from sender balance
   ├─> Add XP to receiver balance
   ├─> Update transaction history for both users
   ├─> Update treasury_reminder (if needed)
   │
   ▼
5. NOTIFICATION PHASE
   │
   ├─> Send confirmation to sender
   ├─> Send notification to receiver
   ├─> Update wallet displays (real-time)
   │
   ▼
6. COMPLETION
   │
   └─> Display success message
       └─> Redirect to transaction history
```

### Flow 2: Moderator XP Award

```
┌─────────────────────────────────────────────────────────────┐
│                  MODERATOR XP AWARD FLOW                     │
└─────────────────────────────────────────────────────────────┘

1. MODERATOR INITIATES AWARD
   │
   ├─> Moderator navigates to "Award XP" admin page
   ├─> Selects recipient (user search/select)
   ├─> Enters XP amount
   ├─> Selects award reason/category
   ├─> Enters memo/description (required)
   │
   ▼
2. PERMISSION CHECK
   │
   ├─> Verify user has moderator role:
   │   ├─> PMG (Postmaster General)
   │   ├─> Captain
   │   └─> Treasurer
   │
   ├─> Check award limits for role
   │
   ├─> ❌ PERMISSION DENIED
   │   │
   │   └─> Display error: "Insufficient permissions"
   │
   └─> ✅ PERMISSION GRANTED
       │
       ▼
3. APPROVAL WORKFLOW (if amount > threshold)
   │
   ├─> If award > $100 USD equivalent:
   │   ├─> Require second moderator approval
   │   └─> Send approval request
   │
   ├─> If award <= $100 USD equivalent:
   │   └─> Proceed directly
   │
   ▼
4. CONFIRMATION PHASE
   │
   ├─> Display award summary:
   │   ├─> Recipient name
   │   ├─> XP amount
   │   ├─> Award reason
   │   ├─> Memo
   │   └─> Moderator name (signature)
   │
   ├─> Moderator confirms award
   │
   ▼
5. EXECUTION PHASE
   │
   ├─> Create award ledger entry
   ├─> Add XP to recipient balance (creates new XP)
   ├─> Update recipient transaction history
   ├─> Log moderator action in audit trail
   │
   ▼
6. NOTIFICATION PHASE
   │
   ├─> Send notification to recipient
   ├─> Send confirmation to moderator
   │
   ▼
7. COMPLETION
   │
   └─> Display success message
       └─> Log in audit trail
```

### Flow 3: POC Pooling System

```
┌─────────────────────────────────────────────────────────────┐
│                    POC POOLING FLOW                          │
└─────────────────────────────────────────────────────────────┘

1. POOL CREATION
   │
   ├─> Seller navigates to "Create POC Pool"
   ├─> Enters pool name
   ├─> Invites 4 other sellers (total 5 members)
   ├─> Sets rotation schedule (weekly/monthly)
   ├─> Sets initial contribution amount (optional)
   │
   ▼
2. INVITATION PHASE
   │
   ├─> System sends invitations to 4 invited sellers
   ├─> Invitations include:
   │   ├─> Pool creator name
   │   ├─> Pool name
   │   ├─> Rotation schedule
   │   └─> Accept/Decline buttons
   │
   ▼
3. MEMBER ACCEPTANCE
   │
   ├─> Each invited seller receives invitation
   ├─> Seller reviews pool details
   │
   ├─> ❌ SELLER DECLINES
   │   │
   │   └─> Pool creation cancelled
   │       └─> Notify creator
   │
   └─> ✅ ALL 4 SELLERS ACCEPT
       │
       ▼
4. POOL ACTIVATION
   │
   ├─> Pool status: "active"
   ├─> All 5 members added to pool
   ├─> Initial rotation order established
   ├─> Pool ledger created
   │
   ▼
5. CONTRIBUTION PHASE
   │
   ├─> Members contribute XP to pool
   ├─> Track individual contributions
   ├─> Calculate total pooled XP
   ├─> Calculate 4% bonus:
   │   └─> Bonus = Total Pooled XP × 0.04
   │
   ▼
6. ROTATION PHASE (Weekly/Monthly)
   │
   ├─> System checks rotation schedule
   ├─> Identifies current recipient (by rotation order)
   ├─> Distributes bonus to current recipient:
   │   ├─> Base: Pooled XP / 5 (equal share)
   │   └─> Bonus: 4% of total pooled XP
   ├─> Rotate to next member
   │
   ▼
7. POOL MANAGEMENT
   │
   ├─> Members can view:
   │   ├─> Current pool balance
   │   ├─> Individual contributions
   │   ├─> Rotation schedule
   │   ├─> Next recipient
   │   └─> Pool history
   │
   ├─> Members can:
   │   ├─> Add more contributions
   │   ├─> Leave pool (with 30-day notice)
   │   └─> View pool statistics
   │
   ▼
8. POOL DISSOLUTION
   │
   ├─> If member leaves:
   │   ├─> Remaining members decide: replace or dissolve
   │   └─> If dissolve: distribute remaining XP
   │
   └─> If all agree to dissolve:
       ├─> Distribute pooled XP equally
       └─> Close pool
```

---

## 🗄️ DATABASE SCHEMA

### Table 1: `wp_xp_transfers` (Transfer Ledger)

```sql
CREATE TABLE wp_xp_transfers (
    transfer_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_uuid VARCHAR(36) UNIQUE NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    receiver_id BIGINT UNSIGNED NOT NULL,
    xp_amount DECIMAL(36,21) NOT NULL,
    yam_equivalent BIGINT UNSIGNED NOT NULL,
    usd_trade_value DECIMAL(10,2) NOT NULL,
    transfer_type ENUM('peer_transfer', 'moderator_award', 'pool_contribution', 'pool_distribution') NOT NULL,
    action_type ENUM('mint', 'transfer', 'bonus', 'group_pool') NOT NULL,
    memo TEXT,
    reason VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    moderator_id BIGINT UNSIGNED NULL,
    pool_id BIGINT UNSIGNED NULL,
    verified_by VARCHAR(100) NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    geo_location JSON NULL,
    metadata JSON NULL,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_status (status),
    INDEX idx_transfer_type (transfer_type),
    FOREIGN KEY (sender_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES wp_users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Field Descriptions:**
- `transfer_uuid`: Unique identifier for each transfer
- `xp_amount`: Amount of XP transferred (sextillionth precision)
- `yam_equivalent`: YAM equivalent for display
- `usd_trade_value`: USD trade value (display only, no movement)
- `transfer_type`: Type of transfer operation
- `action_type`: Action classification (matches ledger schema)
- `memo`: Optional message from sender
- `reason`: Award reason (for moderator awards)
- `moderator_id`: ID of moderator who approved (for awards)
- `pool_id`: Reference to POC pool (for pool operations)
- `metadata`: Additional JSON data (flexible storage)

### Table 2: `wp_xp_pools` (POC Pooling Groups)

```sql
CREATE TABLE wp_xp_pools (
    pool_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pool_uuid VARCHAR(36) UNIQUE NOT NULL,
    pool_name VARCHAR(255) NOT NULL,
    creator_id BIGINT UNSIGNED NOT NULL,
    rotation_schedule ENUM('weekly', 'monthly') DEFAULT 'monthly',
    status ENUM('pending', 'active', 'closed', 'dissolved') DEFAULT 'pending',
    total_contributed_xp DECIMAL(36,21) DEFAULT 0,
    bonus_xp DECIMAL(36,21) DEFAULT 0,
    current_recipient_id BIGINT UNSIGNED NULL,
    rotation_order JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    last_rotation_at TIMESTAMP NULL,
    next_rotation_at TIMESTAMP NULL,
    metadata JSON NULL,
    INDEX idx_creator (creator_id),
    INDEX idx_status (status),
    INDEX idx_next_rotation (next_rotation_at),
    FOREIGN KEY (creator_id) REFERENCES wp_users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table 3: `wp_xp_pool_members` (Pool Membership)

```sql
CREATE TABLE wp_xp_pool_members (
    membership_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pool_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    contribution_xp DECIMAL(36,21) DEFAULT 0,
    received_xp DECIMAL(36,21) DEFAULT 0,
    rotation_position INT NOT NULL,
    status ENUM('invited', 'active', 'pending_exit', 'exited') DEFAULT 'invited',
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    joined_at TIMESTAMP NULL,
    exit_requested_at TIMESTAMP NULL,
    UNIQUE KEY unique_pool_user (pool_id, user_id),
    INDEX idx_user (user_id),
    INDEX idx_pool (pool_id),
    FOREIGN KEY (pool_id) REFERENCES wp_xp_pools(pool_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Usermeta Updates

**New Usermeta Keys:**
- `xp_transfer_history`: Array of transfer IDs (sent and received)
- `xp_pool_memberships`: Array of pool IDs user belongs to
- `xp_transfer_settings`: User preferences (notifications, limits)

---

## 🔌 API ENDPOINTS

### 1. Peer-to-Peer Transfer

**POST** `/wp-json/cpm-dongtrader/v1/xp/transfer`

**Request Body:**
```json
{
    "receiver_identifier": "user@example.com",  // email, username, or FonePay ID
    "xp_amount": "0.015141",
    "memo": "Thanks for helping with delivery",
    "nonce": "wp_nonce_value"
}
```

**Response (Success):**
```json
{
    "status": "success",
    "transfer_id": "uuid-here",
    "message": "XP transfer completed. LAUGH Mode active — no monetary movement.",
    "data": {
        "sender_balance": "0.123456",
        "receiver_balance": "0.045789",
        "transfer_amount": "0.015141",
        "yam_equivalent": 15141,
        "usd_trade_value": 0.72
    }
}
```

**Response (Error):**
```json
{
    "status": "error",
    "code": "insufficient_balance",
    "message": "Insufficient XP balance. Available: 0.010000",
    "data": {
        "available_balance": "0.010000",
        "requested_amount": "0.015141"
    }
}
```

### 2. Moderator Award

**POST** `/wp-json/cpm-dongtrader/v1/xp/award`

**Request Body:**
```json
{
    "recipient_id": 123,
    "xp_amount": "0.02163",
    "reason": "event_participation",
    "memo": "Reward for LAUGH Festival participation",
    "nonce": "wp_nonce_value"
}
```

**Response:**
```json
{
    "status": "success",
    "award_id": "uuid-here",
    "message": "XP award issued successfully",
    "data": {
        "recipient_name": "John Doe",
        "award_amount": "0.02163",
        "moderator_name": "PMG Admin"
    }
}
```

### 3. POC Pool Operations

**POST** `/wp-json/cpm-dongtrader/v1/xp/pool/create`

**Request Body:**
```json
{
    "pool_name": "Nepal Distribution Pool",
    "member_ids": [123, 456, 789, 101],
    "rotation_schedule": "monthly",
    "nonce": "wp_nonce_value"
}
```

**POST** `/wp-json/cpm-dongtrader/v1/xp/pool/contribute`

**Request Body:**
```json
{
    "pool_id": 1,
    "xp_amount": "0.050000",
    "nonce": "wp_nonce_value"
}
```

**GET** `/wp-json/cpm-dongtrader/v1/xp/pool/{pool_id}`

**Response:**
```json
{
    "status": "success",
    "pool": {
        "pool_id": 1,
        "pool_name": "Nepal Distribution Pool",
        "status": "active",
        "total_contributed_xp": "0.250000",
        "bonus_xp": "0.010000",
        "members": [
            {
                "user_id": 123,
                "name": "John Doe",
                "contribution": "0.050000",
                "rotation_position": 1
            }
        ],
        "current_recipient": {
            "user_id": 123,
            "name": "John Doe"
        },
        "next_rotation": "2025-02-01T00:00:00Z"
    }
}
```

### 4. Transfer History

**GET** `/wp-json/cpm-dongtrader/v1/xp/transfers`

**Query Parameters:**
- `type`: `sent` | `received` | `all` (default: `all`)
- `limit`: Number of results (default: 50)
- `offset`: Pagination offset

**Response:**
```json
{
    "status": "success",
    "transfers": [
        {
            "transfer_id": "uuid-here",
            "type": "peer_transfer",
            "direction": "sent",
            "counterparty": {
                "user_id": 456,
                "name": "Jane Smith"
            },
            "xp_amount": "0.015141",
            "yam_equivalent": 15141,
            "usd_trade_value": 0.72,
            "memo": "Thanks for helping",
            "timestamp": "2025-01-15T10:30:00Z",
            "status": "completed"
        }
    ],
    "pagination": {
        "total": 25,
        "limit": 50,
        "offset": 0
    }
}
```

---

## 🎨 USER INTERFACE REQUIREMENTS

### 1. Send XP Page

**Location:** `/my-account/send-xp/` or wallet page tab

**Components:**
- [ ] Receiver search/select field (autocomplete)
- [ ] XP amount input (with balance display)
- [ ] YAM equivalent display (read-only)
- [ ] USD trade value display (read-only, with disclaimer)
- [ ] Memo/note field (optional, max 500 chars)
- [ ] Transfer summary preview
- [ ] Confirm button
- [ ] Cancel button
- [ ] LAUGH Mode disclaimer banner

**Validation Messages:**
- "Insufficient balance"
- "Receiver not found"
- "Minimum transfer: 0.000001 XP"
- "Maximum transfer: 50% of balance"
- "Receiver account not verified"

### 2. Receive XP Notification

**Components:**
- [ ] Email notification template
- [ ] In-app notification
- [ ] Transaction history entry
- [ ] Wallet balance update (real-time)

### 3. Transfer History Page

**Location:** `/my-account/xp-transfers/` or wallet page tab

**Components:**
- [ ] Filter tabs: All / Sent / Received
- [ ] Transaction table:
  - Date/Time
  - Type (Transfer / Award / Pool)
  - Counterparty
  - Amount (XP)
  - YAM equivalent
  - Status
  - Memo
- [ ] Pagination
- [ ] Export to CSV (optional)

### 4. Moderator Award Interface

**Location:** Admin dashboard or special page

**Components:**
- [ ] User search/select
- [ ] XP amount input
- [ ] Reason dropdown:
  - Event participation
  - Governance participation
  - Community contribution
  - Special achievement
  - Other
- [ ] Memo field (required)
- [ ] Approval workflow (for large amounts)
- [ ] Audit log display

### 5. POC Pooling Interface

**Components:**
- [ ] Create Pool page
- [ ] Pool list (user's pools)
- [ ] Pool detail page:
  - Members list
  - Contribution history
  - Rotation schedule
  - Current recipient
  - Pool statistics
- [ ] Contribute XP form
- [ ] Leave pool option

---

## 🔒 SECURITY & VALIDATION

### 1. Transfer Validation Rules

**Balance Check:**
```php
if ($sender_balance < $transfer_amount) {
    return error("Insufficient balance");
}
```

**Minimum Transfer:**
```php
$min_transfer = 0.000001; // 1 YAM equivalent
if ($transfer_amount < $min_transfer) {
    return error("Minimum transfer: {$min_transfer} XP");
}
```

**Maximum Transfer:**
```php
$max_transfer = $sender_balance * 0.5; // 50% of balance
if ($transfer_amount > $max_transfer) {
    return error("Maximum transfer: {$max_transfer} XP (50% of balance)");
}
```

**Receiver Validation:**
```php
// Check receiver exists and is active
if (!user_exists($receiver_id) || !is_user_active($receiver_id)) {
    return error("Receiver not found or inactive");
}

// Check receiver is verified
if (!is_user_verified($receiver_id)) {
    return error("Receiver account not verified");
}
```

### 2. Permission Checks

**Moderator Award:**
```php
$allowed_roles = ['pmg', 'captain', 'treasurer'];
if (!in_array($user_role, $allowed_roles)) {
    return error("Insufficient permissions");
}
```

**Award Limits:**
```php
$role_limits = [
    'pmg' => 1000.00,      // $1000 USD equivalent
    'captain' => 500.00,   // $500 USD equivalent
    'treasurer' => 100.00  // $100 USD equivalent
];

$usd_value = xp_to_usd($award_amount);
if ($usd_value > $role_limits[$user_role]) {
    return error("Award exceeds limit for your role");
}
```

### 3. LAUGH Mode Enforcement

**No Fiat Conversion:**
```php
// All transfers must be tagged XP_TRADE_ONLY
$transfer_data['action_type'] = 'transfer';
$transfer_data['label'] = 'XP_TRADE_ONLY';
$transfer_data['laugh_mode'] = true;
$transfer_data['no_fiat_conversion'] = true;
```

**Trade Credits Only:**
- Display USD trade value for reference only
- No withdrawal/redemption functionality
- Clear disclaimers on all transfer pages

### 4. Audit Trail

**Required Logging:**
- All transfer operations
- Moderator actions
- Pool operations
- Failed attempts
- Balance changes

**Log Format:**
```php
$audit_log = [
    'action' => 'xp_transfer',
    'user_id' => $sender_id,
    'target_user_id' => $receiver_id,
    'amount' => $xp_amount,
    'timestamp' => current_time('mysql'),
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'metadata' => $additional_data
];
```

---

## 🔗 INTEGRATION POINTS

### 1. Wallet System Integration

**Update Wallet Balance Calculation:**
```php
// In content-detente-wallet.php
// Add transfer history to balance calculation
$transfers_sent = get_user_xp_transfers_sent($user_id);
$transfers_received = get_user_xp_transfers_received($user_id);

$total_xp = $scan_xp + $transfers_received - $transfers_sent;
```

**Transaction History Integration:**
- Add transfer entries to transaction history table
- Display alongside scan transactions
- Filter by type (Scan / Transfer / Award / Pool)

### 2. Leaderboard Integration

**Transfer Impact:**
- Transfers should NOT affect leaderboard rankings
- Leaderboard based on earned XP (scans only)
- Awards may or may not affect leaderboard (configurable)

### 3. Treasury Reminder Integration

**Update Treasury:**
```php
// Add transfer entries to treasury_reminder
$treasury_entry = [
    'user_id' => $sender_id,
    'action_type' => 'transfer',
    'xp_units' => -$xp_amount, // Negative for sender
    'transfer_id' => $transfer_id,
    'timestamp' => current_time('mysql')
];
```

### 4. Notification System

**Email Notifications:**
- Transfer sent confirmation
- Transfer received notification
- Award received notification
- Pool invitation
- Pool rotation notification

**In-App Notifications:**
- Real-time balance updates
- Transfer status changes
- Pool updates

---

## 📅 IMPLEMENTATION PHASES

### Phase 1: Core Transfer System (Week 1-2)
- [ ] Database schema creation
- [ ] Basic transfer function (`TransferXP()`)
- [ ] Validation and security
- [ ] Basic UI (send XP form)
- [ ] Transaction logging

### Phase 2: Moderator Awards (Week 3)
- [ ] Permission system
- [ ] Award function (`AwardXP()`)
- [ ] Moderator interface
- [ ] Approval workflow (if needed)

### Phase 3: POC Pooling (Week 4-5)
- [ ] Pool creation system
- [ ] Member management
- [ ] Contribution tracking
- [ ] Rotation system
- [ ] 4% bonus calculation

### Phase 4: UI/UX Enhancement (Week 6)
- [ ] Transfer history page
- [ ] Pool management interface
- [ ] Notifications
- [ ] Mobile responsiveness

### Phase 5: Testing & Documentation (Week 7)
- [ ] Unit tests
- [ ] Integration tests
- [ ] User acceptance testing
- [ ] Documentation updates

---

## 📝 NOTES

1. **LAUGH Mode Compliance**: All transfers must clearly indicate they are trade credits only, no monetary value until August 31, 2026.

2. **Zero-Sum Principle**: Peer transfers are zero-sum (sender loses, receiver gains). Awards create new XP.

3. **Audit Requirements**: Full audit trail required for compliance and transparency.

4. **Performance**: Consider caching for frequent balance queries.

5. **Scalability**: Database indexes on frequently queried fields.

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Specification Complete - Ready for Implementation

