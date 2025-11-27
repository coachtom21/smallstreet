# What the Client Wants - Summary

## Overview
The client has two main requests:
1. **Refine the existing wallet/XP system** (add dust concept, clearer display)
2. **Create a new landing page** for humanblockchain.info (referral funnel)

---

## PART 1: WALLET/XP SYSTEM REFINEMENTS

### What Already Exists ✅
- Wallet display (XP Balance, YAM, USD)
- Redemption system ($1 minimum)
- XP scientific notation display
- Conversion functions

### What Client Wants Added/Refined 🔧

#### 1. **Dust Concept** (NEW)
- Calculate XP "dust" = XP under 1 penny (< 10²¹ XP)
- Dust cannot be redeemed
- Show dust separately in scientific notation

#### 2. **Three-Layer Wallet Display** (REFINEMENT)
Currently shows: XP Balance, YAM, USD

**Client wants:**
- **Redeemable Fiat** - Whole dollars only (e.g., $3, $27)
- **Pending Pennies** - Cents accumulating but not yet $1 (e.g., "Pending: 37¢")
- **XP Dust** - Sub-penny XP in scientific notation (e.g., 3.42e19 XP)

#### 3. **Clearer Value Display** (FIX)
- Fix "1 YAM = $1" display issues
- Make wallet values more understandable
- Separate cash value (fiat/YAM) from action points (XP)

#### 4. **Add XP_PER_PENNY Constant** (NEW)
- Add constant: `XP_PER_PENNY = 10²¹`
- Use for dust calculations

---

## PART 2: LANDING PAGE - humanblockchain.info

### Purpose
Create a referral funnel that converts QR code scans into:
- Discord Gracebook members
- Paid memberships (YAM'er free, MEGAvoter $12/year, Patron $360/year)

### What Needs to Be Built 🏗️

#### 1. **Single-Page Landing Application**
HTML page with 6 views:
- **View 1:** "Is this Proof of Delivery?" (Yes/No question)
- **View 2:** Value breakdown ($10.30 explanation)
- **View 3:** Registration/Onboarding (Discord + QRtiger steps)
- **View 4:** Membership selection (3 options)
- **View 5:** Success/Confirmation (XP wallet activated)
- **View 6:** Guide/Explanation (What is human blockchain?)

#### 2. **Discord Integration**
- Gracebook server setup with channels:
  - #start-here, #wallet-activation, #yamers-lounge
  - #megavoter-circle, #patron-council
  - #proof-of-delivery-feed, #coach-tom-announcements
- Bot that sends first DM to new members
- OAuth flow for Discord connection

#### 3. **QRtiger Integration**
- Members create QRtiger v-card
- Link QRtiger URL to member account
- Use QRtiger as root identifier for scans

#### 4. **Backend API Endpoints**
- `POST /api/pod/scan` - Handle PoD QR scans
- `POST /api/member/role` - Create member with role selection
- `GET /api/member/xp-summary` - Get XP totals

#### 5. **Membership System**
Three membership tiers:
- **YAM'er** - Free, basic participation
- **MEGAvoter** - $12/year, influence social impact fund
- **Patron** - $360/year, full patronage, POC access, LAUGH awards

#### 6. **Spiritual/Messaging Elements**
- DeepSeek Insight Hash spiritual guidebook excerpts
- Coach Tom video script (90 seconds)
- Genesis Block Hash concept (Block 0: Detente 2030)

#### 7. **QR Code Redirects**
- Hang tags and voucher QR codes → humanblockchain.info
- Pass PoD ID in URL parameter
- Landing page detects and shows appropriate view

---

## IMPLEMENTATION PRIORITY

### Phase 1: Wallet Refinements (Critical)
1. Add XP_PER_PENNY constant (10²¹)
2. Create dust calculation functions
3. Update wallet display (three layers)
4. Fix YAM = $1 display issues
5. Test all changes

### Phase 2: Landing Page (New Feature)
1. Create HTML landing page (6 views)
2. Set up Discord server structure
3. Build API endpoints
4. Integrate QRtiger
5. Implement membership selection flow
6. Add spiritual messaging elements
7. Test QR code redirects

---

## KEY FILES TO MODIFY/CREATE

### Wallet System (Refinements)
- `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-functions.php`
- `wp-content/plugins/cpm-dongtrader/template-parts/content-detente-wallet.php`
- `wp-content/plugins/cpm-dongtrader/template-parts/content-redemption.php`
- `wp-content/plugins/cpm-twilio/assets/js/cpm-twilio-script.js`

### Landing Page (New)
- `humanblockchain.info` domain setup
- New landing page HTML/CSS/JS
- API endpoints for PoD/member management
- Discord bot integration
- QRtiger integration code

---

## CLIENT CONCERNS

### Current Problems:
- "System not functioning correctly with 1 YAM = $1"
- "Displayed values in wallet not understandable"
- Need clearer separation: Cash value vs Action-based points

### Client Preferences:
- Most people care about redemption value in fiat (Fiat or YAM)
- XP remains action-based reward system with leaderboards
- Need clearer distinction: redeemable vs accumulating vs dust

---

## NEXT STEPS

1. ✅ Document all requirements
2. ⏳ Implement wallet refinements (dust, three-layer display)
3. ⏳ Create landing page structure
4. ⏳ Set up Discord integration
5. ⏳ Build API endpoints
6. ⏳ Test complete flow
7. ⏳ Deploy to humanblockchain.info

---

## NOTES

- Client has provided complete HTML/CSS/JS for landing page (from ChatGPT)
- Client wants to discuss timeframe
- Landing page should be "nice visual UI" and "generic" (can redirect to Smallstreet/Megavoters/LLB)
- Spiritual elements are important (DeepSeek guidebook, Coach Tom messaging)

