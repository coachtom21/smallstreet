# Client Requirements Summary: humanblockchain.info & Wallet XP System

## Overview
Client has provided detailed specifications for:
1. **Wallet/XP System Redesign** - Implementing "dust" concept and proper redemption rules
2. **Landing Page** - New domain humanblockchain.info for referrals

---

## 1. WALLET/XP SYSTEM REDESIGN

### Core Constants (NEW)
- **XP = sextillionths of a penny** (10⁻²¹ of $0.01)
- **1 penny = 10²¹ XP** (1,000,000,000,000,000,000,000 XP)
- **1 dollar = 100 pennies = 10²³ XP**
- **YAM pegged at 21,000 YAM = 1 USD**
- **1 cent = 210 YAM**

### Key Changes from Current System
**Current:** 1 USD = 21,000 YAM = 10²³ XP (direct)
**New:** 1 penny = 10²¹ XP, with dust accumulation rules

### Dust Concept
- **Dust = XP under 1 penny** (XP < 10²¹)
- Dust cannot be redeemed
- Dust is displayed in scientific notation only
- Whole pennies must accrue to a dollar value before redemption is possible
- Treasury keeps the XP dust and displays "residual" totals

### Redemption Rules
1. **Under 1 penny (pennies == 0):**
   - Entire XP balance = dust
   - User cannot redeem anything
   - Dust XP shown in scientific notation only

2. **Under 1 dollar (pennies > 0 but dollars == 0):**
   - User is accumulating pennies
   - Redemption NOT allowed yet (must reach ≥ 100 pennies)
   - Show "Pending: X¢ (not yet redeemable)"

3. **Redemption allowed only when dollars ≥ 1:**
   - User can redeem whole dollars only
   - On redemption of R dollars:
     - Subtract R × 10²³ XP from user_xp
     - Subtract R × 100 from pennies
     - Convert payout: R USD or R × 21,000 YAM

### Wallet Display (Three Layers)
Each member wallet should show:

1. **Redeemable Fiat (whole dollars only)**
   - `redeemable_usd = floor(pennies / 100)`
   - Display normally: $3, $27, etc.
   - Redemption button active only if `redeemable_usd ≥ 1`

2. **Pending Pennies**
   - `pending_pennies = pennies % 100`
   - Show as "Pending: 37¢ (not yet redeemable)" or similar

3. **XP Dust (sub-penny)**
   - `xp_dust = user_xp % XP_PER_PENNY`
   - Always displayed in scientific notation: `3.42e19 XP`
   - No rounding concerns; just format integer into m × 10^n style

### XP Display Logic
**General XP Notation (leaderboards, awards):**
- < 1,000,000 XP: Comma-separated integer (250,000 XP)
- 1,000,000 – 999,999,999 XP: Compact integer with commas (12,500,000 XP)
- ≥ 1,000,000,000 XP: Scientific notation (1.25e9 XP)
- ≥ 1 sextillion XP (10²¹): Scientific notation mandatory (1e21 XP)

**Special rule for Dust (sub-penny):**
- Dust = any XP < 10²¹ that is not part of a full penny
- Always display as scientific notation, regardless of size
- Examples:
  - 500,000 XP → 5.0e5 XP (dust)
  - 3,200,000,000 XP → 3.2e9 XP (dust)
  - 9,999,999,999 XP → 1.0e10 XP (dust)

### Treasury Residual / "No Spend" Ledger
- For display, Treasury can compute:
  - `global_xp_dust = Σ (user_xp % XP_PER_PENNY)`
- This is not redeemable
- Displayed as community residual XP total in scientific notation

### Technical Requirements
- **PHP must use BCMath or GMP** for all XP calculations
- `bcdiv($xp, XP_PER_PENNY, 0)` for integer division
- `bcmod($xp, XP_PER_PENNY)` for remainder
- **Never use floats / standard PHP integers** for XP
- All XP stored as big integers (string + BCMath/GMP)

### Conversion Cheat Sheet
| Fiat Value | Pennies | XP Required | XP (Scientific) | YAM Equivalent |
|------------|---------|-------------|-----------------|----------------|
| $0.01      | 1       | 10²¹        | 1e21 XP         | 210 YAM        |
| $0.10      | 10      | 10 × 10²¹   | 1e22 XP         | 2,100 YAM      |
| $1         | 100     | 100 × 10²¹  | 1e23 XP         | 21,000 YAM     |
| $10        | 1,000   | 10 × 10²³   | 1e24 XP         | 210,000 YAM    |
| $100       | 10,000  | 100 × 10²³  | 1e25 XP         | 2,100,000 YAM  |
| $1,000     | 100,000 | 1,000 × 10²³| 1e26 XP         | 21,000,000 YAM |

**Rule of thumb:** Every extra zero in dollars adds one to the exponent of XP (since $1 = 10²³ XP).

---

## 2. LANDING PAGE: humanblockchain.info

### Purpose
- Referral funnel into Gracebook and membership selections
- Domain purchased from GoDaddy: **humanblockchain.info**
- Entry point when someone scans hang tag or voucher QR code
- Convert scans into Discord members and paid memberships (YAM'er, MEGAvoter, Patron)

### Core Flow: Scan → Spiritual Awakening → Discord → Membership

**The Funnel:**
1. **Scan Event** - Someone scans hang tag/sticker QR code
2. **Landing Page** - "Is this Proof of Delivery?" question
3. **Value Breakdown** - Show $10.30 community value created
4. **Registration/Onboarding** - Discord + QRtiger v-card setup
5. **Membership Selection** - Choose YAM'er (free), MEGAvoter ($12/year), or Patron ($360/year)
6. **Success** - XP Wallet activated, user onboarded

### Landing Page Views (Single-Page Application)

#### View 1: Initial Question
- **Headline:** "Is this Proof of Delivery?"
- **Subtext:** "Every validated delivery creates $10.30 in community trade value — tracked in sextillionths of a penny as XP."
- **Buttons:**
  - ✅ "Yes — This is my delivery" → Goes to Value View
  - 🚪 "No — I need to register" → Goes to Registration View
- **Spiritual priming:** "Every act of delivery creates human value. Every scan is a seed. Welcome to the Human Blockchain."

#### View 2: Value Breakdown ($10.30)
- **Headline:** "Delivery confirmed. Human value created."
- **Breakdown:**
  - Buyer Rebate: $5.00 (returned after maturity)
  - Social Impact: $4.00 (directed by MEGAvoters)
  - Patronage: $1.00 (shared with Coach & POC)
  - Service & Voucher Pool: $0.30 (funds operations)
- **Steps shown:**
  1. Join Gracebook (Discord)
  2. Get QRtiger v-card
  3. Choose your role
- **Button:** "Continue — Connect & Choose My Role"

#### View 3: Registration/Onboarding
- **Headline:** "Welcome. Your human value journey starts here."
- **Steps:**
  1. Accept Discord Invite → Join Gracebook server
  2. Create QRtiger v-card → Personal QR identity
  3. Confirm Your Device → Link device & QR to scans
- **Links:**
  - 🔗 Join Discord Gracebook
  - 🪪 Create QRtiger V-card
- **Button:** "I'm ready — show me the membership paths"

#### View 4: Membership Selection
- **Headline:** "Choose how you show up in the You And Me economy."
- **Three Options:**

  **YAM'er (FREE)**
  - Entry Path
  - Join movement, earn XP, receive rebates
  - Access Cookie Jar Economy without financial pledge
  - Message: "Everyone starts here. You belong."

  **MEGAvoter ($12/year)**
  - Voice & Vote
  - Influence the 4% Social Impact Fund
  - Direct Cookie Jar funds to peace projects
  - Message: "Your influence is a flame. Your vote directs the Cookie Jar of Human Value."

  **Patron ($360/year)**
  - Builder Tier
  - Earn full Patronage, bring 5-seller POC
  - Qualify for August 11th LAUGH awards
  - Seed the Genesis Block
  - Message: "You are the craftsman of the Human Blockchain. Your actions mint tomorrow's New World Penny."

#### View 5: Success/Confirmation
- **Message:** "XP Wallet Activated ✅"
- **Details:** Role recorded, XP account tied to device and QRtiger v-card
- **Next Steps:**
  1. Visit Your XP Dashboard
  2. Invite 3 Friends
  3. Scan & Serve

#### View 6: Guide/Explanation
- **Headline:** "What is this 'human blockchain' you're talking about?"
- **Explanation:** Every scan = tiny "penny for your thoughtfulness" in XP
- **Concepts:**
  - Proof of Delivery (2-scan system)
  - XP Instead of Cash (sextillionth-of-a-penny units)
  - Redemption Day (matured XP becomes rebates/impact/patronage)

### Discord Onboarding (Gracebook Server)

#### Server Welcome Text
- **Server Name:** Gracebook • You And Me Economy
- **Welcome Message:** 
  - "Welcome to Gracebook – the Human Blockchain of You And Me."
  - "Every 2-scan Proof of Delivery creates $10.30 of human value as XP."
  - "We practice FAITH: Fair, Accepting, Insightful, Transparent, Humble."

#### Channel Structure
- `#start-here` - Begin here, learn PoD, connect QRtiger, choose role
- `#wallet-activation` - Post QRtiger v-card link (bot links scans to XP wallet)
- `#yamers-lounge` - Space for free YAM'ers
- `#megavoter-circle` - For $12/year MEGAvoters (discuss social impact)
- `#patron-council` - For $360/year Patrons (coordinate POCs, coach sellers)
- `#proof-of-delivery-feed` - Read-only PoD events, XP minted, maturity countdowns
- `#coach-tom-announcements` - Official updates, LAUGH events, spiritual guidance

#### Bot First DM
- "Hey, it's Coach Tom's Gracebook Bot."
- "I see you stepped through a 2-scan Proof of Delivery."
- "To tie that value to you: Reply with your QRtiger v-card link, then choose a path."

### QRtiger Integration

#### For Members
1. Go to QRtiger
2. Choose vCard / dynamic QR
3. Enter: Name, Email, Mobile (optional: website/social/calendar)
4. Save & generate QR
5. Copy dynamic URL
6. Paste into landing page form or `#wallet-activation` channel

#### For Developers
**Data Model:**
```
Member {
  id: UUID,
  discord_id: string,
  email: string,
  mobile: string,
  role: "YAMER" | "MEGAVOTER" | "PATRON",
  qrtiger_url: string,
  qrtiger_id: string | null,
  device_fingerprint: string | null,
  created_at: timestamp,
  updated_at: timestamp
}
```

**Flow:**
- QRtiger URL = root identifier tying:
  - Scans from PoD stickers
  - Discord identity
  - Role (YAM'er / MEGAvoter / Patron)
  - XP entries and maturity timers

### Spiritual Guidebook Excerpts (Per Screen)

- **Screen A (Question):** "You're not just confirming a package; you're witnessing a moment of trust."
- **Screen B (Value):** "Behind every delivery is a quiet agreement: part for the buyer, part for community healing..."
- **Screen C (Registration):** "This isn't another platform trying to mine your data. This is an invitation to step out of someone else's cookie jar..."
- **Screen D (Membership):** "You don't have to be rich to matter here. You only have to choose how deeply you want to participate..."
- **Screen E (Success):** "From here on, each delivery, each act of kindness, each referral leaves a trace..."

### Coach Tom Video Script (90 seconds)
- Warm, calm tone
- Explains: scan = moment of trust, XP = tiny units, $10.30 breakdown
- Calls to action: Join Discord, get QRtiger v-card, choose role
- Ends: "Welcome to the Human Blockchain. Welcome to Legacy to Live By."

### Genesis Block Hash Concept

**Block 0: Detente 2030 • Mariupol Foundry**

```
Block 0: Detente 2030. Planted this day in Mariupol. 
From a single seed of trust, may a forest of prosperity grow. 
The first credit is not wealth, but the promise of a shared future.

Hash: 1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa

This event marks a pivotal turn in human history—where technology 
was harnessed not for speculation or surveillance, but to codify and 
cultivate the most valuable currency of all: trust.
```

**Visual:** Embed "Legacy To Live By – Bounty for Inspirational Services" emblem image as the visual seal of Block 0.

### Technical Implementation

#### API Endpoints Needed
1. `POST /api/pod/scan` - Handle PoD QR scan (seller/buyer)
2. `POST /api/member/role` - Create/update member with role selection
3. `GET /api/member/xp-summary` - Get XP totals in scientific notation

#### Flow States
- `SCAN_PO_D_QR` → Look up/create PoD → Redirect to landing page
- `VIEW_QUESTION` → YES → `VIEW_VALUE` | NO → `VIEW_REGISTER`
- `VIEW_VALUE` → Continue → `VIEW_MEMBERSHIP`
- `VIEW_REGISTER` → Ready → `VIEW_MEMBERSHIP`
- `VIEW_MEMBERSHIP` → Select role → Backend creates member → `VIEW_SUCCESS`

#### XP & Maturity Logic
- On PoD Confirmation: Convert $10.30 into XP units (integer-safe)
- XP Entry table with types: BUYER_REBATE, SOCIAL_IMPACT, PATRONAGE, SERVICE
- Set `matures_at = created_at + random(8-12 weeks)`
- Cron worker moves PENDING → MATURED
- Redemption enforces: discord_verified, total matured XP >= $1.00

### QR Code Integration
- Hang tags and voucher QR codes redirect to this landing page
- QR codes point to: `humanblockchain.info?pod_id=XXX`
- Landing page detects PoD ID and shows appropriate view

---

## 3. IMPLEMENTATION PRIORITY

### Phase 1: Wallet/XP System (Critical)
1. Update conversion constants (XP_PER_PENNY = 10²¹)
2. Implement dust calculation functions
3. Update wallet display (three layers)
4. Update redemption logic (whole dollars only, ≥ $1 minimum)
5. Update XP display formatting
6. Add treasury residual tracking

### Phase 2: Landing Page
1. Create landing page structure for humanblockchain.info
2. Design referral funnel UI
3. Implement redirect logic to Smallstreet/Megavoters/LLB
4. Integrate QR code redirects

---

## 4. CLIENT NOTES

- Client wants to chat/discuss requirements
- Needs timeframe estimate
- System currently not functioning correctly with 1 YAM = $1
- Displayed values in wallet not understandable
- Most people will care about redemption value in fiat (shown as Fiat or YAM)
- XP remains action-based reward system with leaderboards, separate from fiat
- Cash value vs action-based points in scientific notation

---

## Files to Modify

### Core Functions
- `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-functions.php`
  - Update conversion functions
  - Add dust calculation functions
  - Add XP_PER_PENNY constant

### Wallet Display
- `wp-content/plugins/cpm-dongtrader/template-parts/content-detente-wallet.php`
  - Update wallet display (three layers)
  - Update XP formatting

### Redemption
- `wp-content/plugins/cpm-dongtrader/template-parts/content-redemption.php`
  - Update redemption logic (whole dollars only)
  - Update minimum redemption ($1.00)

### JavaScript
- `wp-content/plugins/cpm-twilio/assets/js/cpm-twilio-script.js`
  - Update XP display formatting
  - Update conversion calculations

---

## Next Steps
1. ✅ Document requirements (this file)
2. ⏳ Implement dust calculation functions
3. ⏳ Update conversion constants
4. ⏳ Update wallet display
5. ⏳ Update redemption logic
6. ⏳ Test all changes
7. ⏳ Create landing page (after receiving Google Docs content)

