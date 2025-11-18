# XP Transfer/Trading System - Quick Reference

## 🎯 CORE REQUIREMENTS SUMMARY

### 1. Peer-to-Peer Transfer (`TransferXP()`)
- ✅ Zero-sum ledger (sender loses, receiver gains)
- ✅ Minimum: 0.000001 XP (1 YAM)
- ✅ Maximum: 50% of sender's balance
- ✅ Both users must be verified
- ✅ Tagged `XP_TRADE_ONLY`
- ✅ No fees during LAUGH Mode

### 2. Moderator Award (`AwardXP()`)
- ✅ Only PMG, Captain, Treasurer roles
- ✅ Creates new XP (not zero-sum)
- ✅ Required memo/reason
- ✅ Approval workflow for large amounts
- ✅ Full audit trail

### 3. POC Pooling
- ✅ Groups of exactly 5 sellers
- ✅ 4% bonus on pooled XP
- ✅ Rotation schedule (weekly/monthly)
- ✅ Equal distribution + bonus to current recipient

---

## 🔄 FLOW SUMMARY

### Transfer Flow (5 Steps)
1. **Initiate** → User enters receiver + amount
2. **Validate** → Check balance, limits, receiver status
3. **Confirm** → Display summary, user confirms
4. **Execute** → Update balances, create ledger entry
5. **Notify** → Send confirmations, update displays

### Award Flow (6 Steps)
1. **Initiate** → Moderator selects recipient + amount
2. **Permission** → Check role and limits
3. **Approve** → Large amounts need second approval
4. **Confirm** → Display summary
5. **Execute** → Add XP, create ledger entry
6. **Notify** → Notify recipient

### Pool Flow (8 Steps)
1. **Create** → Seller creates pool, invites 4 others
2. **Invite** → System sends invitations
3. **Accept** → All 4 must accept
4. **Activate** → Pool becomes active
5. **Contribute** → Members add XP
6. **Rotate** → System distributes bonus on schedule
7. **Manage** → View stats, add contributions
8. **Dissolve** → Members can leave or close pool

---

## 🗄️ DATABASE TABLES

1. **`wp_xp_transfers`** - All transfer records
2. **`wp_xp_pools`** - POC pool groups
3. **`wp_xp_pool_members`** - Pool membership

---

## 🔌 API ENDPOINTS

- `POST /wp-json/cpm-dongtrader/v1/xp/transfer` - Send XP
- `POST /wp-json/cpm-dongtrader/v1/xp/award` - Award XP (moderator)
- `POST /wp-json/cpm-dongtrader/v1/xp/pool/create` - Create pool
- `POST /wp-json/cpm-dongtrader/v1/xp/pool/contribute` - Add to pool
- `GET /wp-json/cpm-dongtrader/v1/xp/pool/{id}` - Get pool details
- `GET /wp-json/cpm-dongtrader/v1/xp/transfers` - Transfer history

---

## 🔒 VALIDATION RULES

**Transfer:**
- Balance >= amount
- Amount >= 0.000001 XP
- Amount <= 50% of balance
- Receiver exists and verified

**Award:**
- User has moderator role
- Amount within role limits
- Memo required
- Large amounts need approval

**Pool:**
- Exactly 5 members
- All must be sellers
- All must accept invitation

---

## 📋 UI PAGES NEEDED

1. **Send XP Page** - Transfer form
2. **Transfer History** - Transaction list
3. **Moderator Award** - Admin interface
4. **Create Pool** - Pool creation form
5. **Pool Details** - Pool management
6. **Notifications** - In-app alerts

---

## ⚠️ LAUGH MODE RULES

- All transfers tagged `XP_TRADE_ONLY`
- No fiat/crypto conversion
- USD value display only (reference)
- Trade credits only until Aug 31, 2026
- Clear disclaimers on all pages

---

## 📅 IMPLEMENTATION PHASES

**Phase 1:** Core Transfer (Week 1-2)
**Phase 2:** Moderator Awards (Week 3)
**Phase 3:** POC Pooling (Week 4-5)
**Phase 4:** UI Enhancement (Week 6)
**Phase 5:** Testing (Week 7)

---

**See full specification:** `XP_TRANSFER_SYSTEM_SPEC.md`





