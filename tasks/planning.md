# Laravel SaaS Subscription Starter Kit — Complete Implementation Plan

> **Stack**: Laravel 13 · Inertia.js 3 · React 19 · TypeScript · Tailwind CSS v4
> **Auth Base**: Laravel Fortify (Passkeys + 2FA already scaffolded in starter kit)
> **Session Driver**: Database (required for session-kill on suspension)

---

## Section 1 — All Locked Decisions

| # | Topic | Decision |
|---|---|---|
| 1 | OTP Channels | Email + WhatsApp + FCM + SMS — default channel configurable by admin in settings panel |
| 2 | Free Plan Trial | No trial on Free. Paid plans have configurable `trial_days`. User can start trial on plan selection page, then pay or fall back to Free |
| 3 | Seeded Plans | Free Starter · Pro Monthly · Pro Yearly · Enterprise Monthly · Enterprise Yearly · Lifetime |
| 4 | Lifetime Plan | One-time Stripe payment, `ends_at = null`, never expires, no renewal |
| 5 | Suspension Session Kill | Immediately delete from `sessions` table on suspend |
| 6 | Super Admin Seed | Credentials from `.env` (`ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`) |
| 7 | Currency | Configurable from admin settings panel |
| 8 | Stripe Coupons | Allow promo codes at Stripe Checkout |
| 9 | Cancel Behavior | Auto-downgrade to Free Starter plan at period end (data preserved, access maintained) |
| 10 | Admin Panel Access | Same login — role-based (Admin / Super Admin → `/admin` routes) |
| 11 | Multi-subscription | No — one active `main` subscription slot per user |
| 12 | Upgrade Proration | Prorate immediately via Stripe (`always_invoice`) |
| 13 | Notification Log Retention | Auto-delete logs older than 30 days via cron |
| 14 | WhatsApp | Optional — silently skip if Green API not configured |
| 15 | Queue Driver | Redis (Laravel Horizon for monitoring) |
| 16 | Invitations | Optional — admins and users can both send invitations |
| 17 | Phone Number Update | Requires OTP re-verification on new number |
| 18 | Pricing Page (subscribed) | Show current plan highlighted + upgrade/downgrade options |
| 19 | Audit Log | Every admin action logged with before/after diff, CSV export |
| 20 | Admin Impersonation | Login-as-user with role restrictions, 2hr expiry, activity logged |
| 21 | Failed Job Monitor | Retry/delete queue failures from admin panel |
| 22 | System Health Dashboard | Redis/DB/queue/disk/cache status |
| 23 | Maintenance Mode | Admin panel toggle with bypass secret |
| 24 | Announcement Banner | Admin-configurable, dismissible, 4 types |
| 25 | Webhook Event Log | Stripe events viewer with idempotency guard |
| 26 | Cache Management | Flush by category from admin UI |
| 27 | Social Login | Google + GitHub via Laravel Socialite |
| 28 | Magic Link Login | 15-min signed URL, one-time use, passwordless |
| 29 | Session Management | User views and revokes active sessions per device |
| 30 | Login History | Last 90 days, success and failed attempts |
| 31 | Avatar Upload | Image upload + Gravatar fallback |
| 32 | Account Deletion | GDPR — 30-day soft delete then hard delete via cron |
| 33 | Data Export | GDPR — JSON download via queued job, 24hr link |
| 34 | Terms of Service | Checkbox on register + re-accept modal when TOS version changes |
| 35 | Proration Preview | Show exact charge breakdown before plan change confirmation |
| 36 | Invoice PDF | DomPDF generated from Stripe invoice data |
| 37 | Credit / Wallet | Admin grants credits, applies to Stripe checkout |
| 38 | Coupon Generator | Admin creates real Stripe coupons from panel |
| 39 | Stripe Tax | Stripe Tax API, optional |
| 40 | Referral System | Per-user code, reward (credit or discount) on first paid conversion |
| 41 | MRR / ARR Dashboard | 12-month trend, Redis-cached 1hr |
| 42 | Churn Rate | Monthly calculation |
| 43 | Growth Charts | New subscriptions per day (30 days) |
| 44 | Revenue by Plan | Chart + table |
| 45 | Feature Usage Analytics | Usage heatmap table |
| 46 | User Funnel | Registered → Verified → Plan Selected → Paid |
| 47 | Analytics CSV Export | All data downloadable |
| 48 | Onboarding Checklist | 5 steps, confetti on 100% |
| 49 | In-app Notification Bell | Feed + unread badge in header |
| 50 | Email Template Editor | Admin edits body copy from panel, TipTap editor |
| 51 | Feature Flags | Per plan/role/user, 5-min Redis cache |
| 52 | IP Allowlist/Blocklist | CIDR support, Redis-cached rules |
| 53 | Multi-language / i18n | Laravel + react-i18next |
| 54 | Soft Deletes on Users | 30-day restore window, cron hard-deletes |
| 55 | DB Backup | spatie/laravel-backup, daily to local + optional S3 |
| 56 | 2FA Setup UI | QR code + recovery codes page (Fortify backend already in starter kit) |
| 57 | PWA Support | manifest.json + service worker + offline fallback |
| 58 | Account Lockout | After N failed password attempts (configurable in settings) |
| 59 | Password History | Prevent reuse of last N passwords |
| 60 | Password Strength Policy | Configurable uppercase/numbers/symbols requirements |
| 61 | Rate Limit Dashboard | Admin views and unlocks locked IPs/users |
| 62 | Admin User Notes | Private internal notes on user profiles |
| 63 | Bulk User Actions | Suspend/unsuspend/assign role/export multiple users |
| 64 | User CSV Export | Full user list with roles and plan data |
| 65 | User Segments | Visual filter builder, live count, target for notifications |
| 66 | Dunning Management | Day 1/3/7 payment retry schedule, auto-cancel after N days |
| 67 | SMS (Twilio) | 4th notification channel, silently skipped if not configured |
| 68 | Bulk Push Notifications | Target all/plan/role/segment, delivery stats |
| 69 | Scheduled Notifications | Datetime picker, cron dispatches at scheduled time |
| 70 | Email Bounce Handling | Hard bounces auto-disable email, admin reset button |
| 71 | Priority Queues | high (OTP/security) / default (billing) / low (bulk/exports) |
| 72 | Laravel Horizon | Redis queue monitoring, Super Admin gated |
| 73 | Admin Log Viewer | Tail last 500 lines, filter by level, download, clear |
| 74 | Custom Branding | Logo, favicon, app name, primary color from admin settings → CSS variables |

---

## Section 2 — Package Installation

### 2.1 Composer Packages

```bash
composer require spatie/laravel-permission
composer require revoltify/subscriptionify
composer require masterix21/laravel-entitlements
composer require offload-project/laravel-invite-only
composer require devkandil/notifire
composer require stripe/stripe-php
composer require predis/predis
composer require guzzlehttp/guzzle
composer require laravel/socialite
composer require barryvdh/laravel-dompdf
composer require spatie/laravel-backup
composer require twilio/sdk
composer require laravel/horizon
```

### 2.2 NPM Packages

```bash
npm install recharts
npm install react-i18next i18next
npm install @tiptap/react @tiptap/starter-kit @tiptap/extension-link @tiptap/extension-image
npm install dompurify @types/dompurify
```

### 2.3 Publish Vendor Resources

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Revoltify\Subscriptionify\SubscriptionifyServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="Revoltify\Subscriptionify\SubscriptionifyServiceProvider" --tag="config"
php artisan vendor:publish --provider="Masterix21\Entitlements\EntitlementsServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="OffloadProject\InviteOnly\InviteOnlyServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="DevKandil\NotiFire\NotiFireServiceProvider" --tag="config"
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
php artisan horizon:install
```

### 2.4 `.env` Additions

```env
# Queue (Redis for all environments)
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Admin seed credentials
ADMIN_NAME="Super Admin"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=password

# Stripe (overridable from DB settings panel)
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

---

## Section 3 — Complete Database Schema

All 27 custom tables below, in migration execution order.

---

### 3.1 `users` — Additional Columns (Migration)

**File**: `database/migrations/2026_06_18_000001_add_saas_columns_to_users_table.php`

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `phone_number` | `string(30)` | YES | — | `index` | WhatsApp/SMS OTP and alerts |
| `phone_verified_at` | `timestamp` | YES | — | — | Phone verification flag |
| `otp_code` | `string(255)` | YES | — | — | Bcrypt-hashed OTP |
| `otp_expires_at` | `timestamp` | YES | — | `index` | OTP expiry window |
| `otp_purpose` | `string(30)` | YES | — | — | `email_verify` / `login_2fa` / `phone_verify` / `password_reset` |
| `is_suspended` | `boolean` | NO | `false` | `index(['is_suspended','id'])` | Suspension gate |
| `suspended_at` | `timestamp` | YES | — | — | When suspended |
| `suspended_reason` | `string(500)` | YES | — | — | Admin-entered reason |
| `stripe_id` | `string(255)` | YES | — | `index` | Stripe Customer ID |
| `pm_type` | `string(50)` | YES | — | — | Card brand |
| `pm_last_four` | `string(4)` | YES | — | — | Last 4 digits |
| `last_login_at` | `timestamp` | YES | — | `index` | Security audit |
| `last_login_ip` | `string(45)` | YES | — | — | Security audit |
| `avatar_path` | `string(500)` | YES | — | — | Stored avatar |
| `referral_code` | `string(20)` | YES | — | `unique` | User's own referral code |
| `locale` | `string(10)` | NO | `en` | — | UI language preference |
| `terms_accepted_at` | `timestamp` | YES | — | — | ToS acceptance timestamp |
| `terms_version_accepted` | `string(20)` | YES | — | — | Which version accepted |
| `email_bounced_at` | `timestamp` | YES | — | — | Hard bounce detection |
| `email_bounce_type` | `enum('soft','hard')` | YES | — | — | Bounce severity |
| `deleted_at` | `timestamp` | YES | — | `index` | Soft delete (SoftDeletes trait) |

---

### 3.2 `plans` Table

**File**: `database/migrations/2026_06_18_000002_create_plans_table.php`

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `name` | `string(100)` | NO | — | — | "Pro Monthly" |
| `slug` | `string(100)` | NO | — | `unique` | "pro-monthly" |
| `description` | `text` | YES | — | — | Marketing copy |
| `price` | `decimal(10,2)` | NO | — | — | 0.00 for Free |
| `currency` | `string(3)` | NO | `USD` | — | ISO 4217 — overridden by `app_currency` setting |
| `billing_period` | `enum('month','year','lifetime')` | NO | `month` | `index` | Billing interval |
| `trial_days` | `smallint unsigned` | NO | `0` | — | 0 = no trial |
| `grace_days` | `smallint unsigned` | NO | `7` | — | Days after payment failure |
| `sort_order` | `smallint` | NO | `0` | `index` | Pricing page display order |
| `is_active` | `boolean` | NO | `true` | `index` | Show on pricing page |
| `stripe_monthly_price_id` | `string(100)` | YES | — | — | Stripe Price ID (monthly) |
| `stripe_yearly_price_id` | `string(100)` | YES | — | — | Stripe Price ID (yearly) |
| `stripe_product_id` | `string(100)` | YES | — | — | Stripe Product ID |
| `metadata` | `json` | YES | — | — | Badge text, highlights, badge_color |
| `timestamps` | — | — | — | — | — |

---

### 3.3 `features` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `name` | `string(100)` | NO | — | — | "Projects Limit" |
| `slug` | `string(100)` | NO | — | `unique` | "projects" |
| `type` | `enum('boolean','consumable','limit')` | NO | — | — | Feature category |
| `description` | `string(500)` | YES | — | — | Help text |
| `default_value` | `string(50)` | YES | — | — | Fallback if not in plan |
| `resettable_period` | `enum('none','day','week','month','year')` | NO | `none` | — | Usage reset interval |
| `timestamps` | — | — | — | — | — |

---

### 3.4 `plan_feature` Pivot Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `plan_id` | `bigint FK→plans CASCADE` | NO | — | `unique(['plan_id','feature_id'])` | — |
| `feature_id` | `bigint FK→features CASCADE` | NO | — | — | — |
| `value` | `string(100)` | NO | — | — | `true`, `10`, `unlimited` |
| `timestamps` | — | — | — | — | — |

---

### 3.5 `subscriptions` Table

**File**: `database/migrations/2026_06_18_000003_create_subscriptions_table.php`

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users CASCADE` | NO | — | `index(['user_id','name'])` | Primary lookup |
| `plan_id` | `bigint FK→plans RESTRICT` | NO | — | `index` | Current plan |
| `name` | `string(50)` | NO | `main` | — | Always "main" |
| `status` | `enum('active','trialing','grace','canceled','expired','paused')` | NO | `active` | `index(['status','ends_at'])` | Lifecycle |
| `stripe_id` | `string(255)` | YES | — | `unique` | Stripe Subscription ID |
| `stripe_status` | `string(50)` | YES | — | — | Raw Stripe status |
| `stripe_price_id` | `string(100)` | YES | — | — | Active Stripe Price |
| `trial_ends_at` | `timestamp` | YES | — | — | Trial end |
| `billing_starts_at` | `timestamp` | YES | — | — | Billing cycle start |
| `ends_at` | `timestamp` | YES | — | — | `null` for lifetime |
| `cancels_at` | `timestamp` | YES | — | — | Stripe scheduled cancel |
| `grace_ends_at` | `timestamp` | YES | — | — | Grace period end |
| `paused_at` | `timestamp` | YES | — | — | Pause timestamp |
| `auto_renew` | `boolean` | NO | `true` | `index(['auto_renew','ends_at'])` | Renewal flag |
| `canceled_at` | `timestamp` | YES | — | — | When cancel was requested |
| `previous_plan_id` | `bigint` | YES | — | — | Downgrade tracking |
| `coupon_id` | `string(100)` | YES | — | — | Applied Stripe coupon |
| `payment_failed_at` | `timestamp` | YES | — | — | First payment failure (dunning) |
| `retry_count` | `int unsigned` | NO | `0` | — | Dunning retry counter |
| `next_retry_at` | `timestamp` | YES | — | — | Scheduled Stripe retry |
| `metadata` | `json` | YES | — | — | Extra data |
| `timestamps` | — | — | — | — | — |

---

### 3.6 `subscription_usages` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `subscription_id` | `bigint FK→subscriptions CASCADE` | NO | — | `unique(['subscription_id','feature_slug'])` | — |
| `feature_slug` | `string(100)` | NO | — | — | References features.slug |
| `used` | `int unsigned` | NO | `0` | — | Current usage count |
| `reset_at` | `timestamp` | YES | — | `index` | Next counter reset date |
| `timestamps` | — | — | — | — | — |

---

### 3.7 `fcm_tokens` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users CASCADE` | NO | — | `index(['user_id','is_active'])` | — |
| `token` | `string(500)` | NO | — | `unique` | FCM registration token |
| `device_type` | `enum('web','ios','android')` | YES | — | — | Platform |
| `device_name` | `string(200)` | YES | — | — | Browser or device label |
| `last_used_at` | `timestamp` | YES | — | `index` | Stale token cleanup |
| `is_active` | `boolean` | NO | `true` | — | Soft-disable flag |
| `timestamps` | — | — | — | — | — |

---

### 3.8 `settings` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `key` | `string(100)` | NO | — | `unique` | Setting key |
| `value` | `text` | YES | — | — | Plaintext or encrypted value |
| `group` | `string(50)` | NO | — | `index` | `smtp`/`stripe`/`green_api`/`twilio`/`firebase`/`app`/`otp`/`security`/`dunning`/`branding` |
| `type` | `enum('string','integer','boolean','json','secret')` | NO | `string` | — | Input type hint |
| `label` | `string(200)` | YES | — | — | Human-readable label |
| `is_encrypted` | `boolean` | NO | `false` | — | Auto encrypt/decrypt |
| `is_public` | `boolean` | NO | `false` | `index(['group','is_public'])` | Expose to frontend |
| `timestamps` | — | — | — | — | — |

---

### 3.9 `invitations` Table (Package + Custom Additions)

Package creates base table. Custom migration adds:

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `invited_by` | `bigint FK→users SET NULL` | YES | — | `index` | Who sent the invitation |
| `role` | `string(50)` | YES | `User (Free)` | — | Role to assign on accept |
| `message` | `text` | YES | — | — | Personal message to recipient |

---

### 3.10 `notification_logs` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users SET NULL` | YES | — | `index(['user_id','channel'])` | Target user |
| `channel` | `enum('email','whatsapp','fcm','sms')` | NO | — | `index` | Delivery channel |
| `type` | `string(100)` | NO | — | `index` | `otp_verify`, `trial_ending` etc. |
| `recipient` | `string(500)` | NO | — | — | Email / phone / FCM token |
| `status` | `enum('sent','failed','pending')` | NO | `pending` | `index(['status','created_at'])` | Delivery state |
| `error_message` | `text` | YES | — | — | Error detail on failure |
| `sent_at` | `timestamp` | YES | — | — | Delivery timestamp |
| `timestamps` | — | — | — | — | — |

---

### 3.11 `activity_logs` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users SET NULL` | YES | — | `index` | Actor |
| `subject_type` | `string(100)` | YES | — | `index(['subject_type','subject_id'])` | Polymorphic model |
| `subject_id` | `bigint` | YES | — | — | Polymorphic ID |
| `event` | `string(100)` | NO | — | `index` | `user.suspended`, `plan.changed` etc. |
| `description` | `text` | YES | — | — | Human-readable description |
| `old_values` | `json` | YES | — | — | Before state |
| `new_values` | `json` | YES | — | — | After state |
| `ip_address` | `string(45)` | YES | — | — | Actor's IP |
| `user_agent` | `string(500)` | YES | — | — | Browser/client |
| `timestamps` | — | — | — | — | — |

**Events to log**: user.created/suspended/unsuspended/deleted/role.assigned/plan.assigned, subscription.activated/canceled/expired/upgraded/downgraded/grace.entered, settings.smtp.updated/stripe.updated/green_api.updated/firebase.updated, plan.created/updated/deleted, admin.login/impersonation.started/impersonation.ended, log.cleared, rate_limit.cleared

---

### 3.12 `social_accounts` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users CASCADE` | NO | — | `index` | — |
| `provider` | `enum('google','github')` | NO | — | `unique(['provider','provider_id'])` | OAuth provider |
| `provider_id` | `string(100)` | NO | — | — | External user ID |
| `token` | `text` | YES | — | — | OAuth access token |
| `refresh_token` | `text` | YES | — | — | OAuth refresh token |
| `avatar_url` | `string(500)` | YES | — | — | Provider avatar URL |
| `timestamps` | — | — | — | — | — |

---

### 3.13 `magic_links` Table

| Column | Type | Nullable | Default | Purpose |
|---|---|---|---|---|
| `email` | `string PK` | NO | — | Target user email |
| `token` | `string(100) unique` | NO | — | Random signed token |
| `expires_at` | `timestamp` | NO | — | 15-minute window |
| `used_at` | `timestamp` | YES | — | One-time use guard |

---

### 3.14 `login_history` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users CASCADE` | NO | — | `index(['user_id','login_at'])` | — |
| `ip_address` | `string(45)` | NO | — | — | Login IP |
| `user_agent` | `string(500)` | YES | — | — | Browser string |
| `login_at` | `timestamp` | NO | — | — | Attempt timestamp |
| `status` | `enum('success','failed','blocked')` | NO | — | `index` | Outcome |
| `failure_reason` | `string(100)` | YES | — | — | Wrong password / suspended / 2FA |

---

### 3.15 `coupons` Table

| Column | Type | Nullable | Default | Purpose |
|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | — |
| `stripe_coupon_id` | `string(100) unique` | NO | — | Stripe coupon ID |
| `code` | `string(50) unique` | NO | — | Human-readable promo code |
| `discount_type` | `enum('percent','amount')` | NO | — | — |
| `discount_value` | `decimal(10,2)` | NO | — | — |
| `duration` | `enum('once','repeating','forever')` | NO | — | — |
| `max_redemptions` | `int` | YES | — | Usage cap |
| `times_redeemed` | `int` | NO | `0` | Usage counter |
| `valid_until` | `timestamp` | YES | — | Expiry |
| `is_active` | `boolean` | NO | `true` | — |
| `timestamps` | — | — | — | — |

---

### 3.16 `referrals` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `referrer_id` | `bigint FK→users CASCADE` | NO | — | `index` | Who referred |
| `referred_id` | `bigint FK→users SET NULL` | YES | — | `index` | Who was referred |
| `code` | `string(20) unique` | NO | — | `unique` | Referral code used |
| `status` | `enum('pending','converted','rewarded')` | NO | `pending` | `index` | Lifecycle |
| `reward_type` | `enum('discount','credit','none')` | NO | `none` | — | Reward kind |
| `reward_value` | `decimal(10,2)` | YES | — | — | Reward amount |
| `converted_at` | `timestamp` | YES | — | — | When referred user subscribed |
| `timestamps` | — | — | — | — | — |

---

### 3.17 `user_credits` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users CASCADE` | NO | — | `index` | — |
| `amount` | `decimal(10,2)` | NO | — | — | Credit value |
| `type` | `enum('admin_grant','referral','refund','purchase')` | NO | — | `index` | Source |
| `description` | `string(500)` | YES | — | — | Admin note |
| `expires_at` | `timestamp` | YES | — | `index` | Optional expiry |
| `used_at` | `timestamp` | YES | — | — | When consumed |
| `timestamps` | — | — | — | — | — |

---

### 3.18 `email_templates` Table

| Column | Type | Nullable | Default | Purpose |
|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | — |
| `key` | `string(100) unique` | NO | — | `otp`, `trial_ending` etc. |
| `subject` | `string(500)` | NO | — | Email subject line |
| `body_html` | `longtext` | NO | — | Admin-editable HTML body |
| `body_text` | `text` | YES | — | Plain text fallback |
| `variables` | `json` | NO | — | Available `{placeholders}` |
| `is_active` | `boolean` | NO | `true` | Use DB or fallback to Blade |
| `timestamps` | — | — | — | — |

---

### 3.19 `feature_flags` Table

| Column | Type | Nullable | Default | Purpose |
|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | — |
| `key` | `string(100) unique` | NO | — | `new_dashboard`, `beta_api` etc. |
| `description` | `string(500)` | YES | — | What it controls |
| `enabled_globally` | `boolean` | NO | `false` | On for all users |
| `enabled_for_plans` | `json` | YES | — | `["pro-monthly"]` |
| `enabled_for_roles` | `json` | YES | — | `["Super Admin"]` |
| `enabled_for_users` | `json` | YES | — | Specific user IDs |
| `timestamps` | — | — | — | — |

---

### 3.20 `ip_rules` Table

| Column | Type | Nullable | Default | Purpose |
|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | — |
| `ip` | `string(50)` | NO | — | Single IP or CIDR range |
| `type` | `enum('allow','block')` | NO | — | Rule type |
| `reason` | `string(500)` | YES | — | Admin note |
| `is_active` | `boolean` | NO | `true` | — |
| `timestamps` | — | — | — | — |

---

### 3.21 `webhook_logs` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `source` | `enum('stripe')` | NO | `stripe` | `index` | Origin |
| `event_id` | `string(100)` | NO | — | `unique` | Stripe event ID (idempotency) |
| `event_type` | `string(100)` | NO | — | `index` | `invoice.payment_failed` etc. |
| `payload` | `json` | NO | — | — | Full event payload |
| `processed` | `boolean` | NO | `false` | `index` | Successfully handled |
| `error` | `text` | YES | — | — | Processing error |
| `timestamps` | — | — | — | — | — |

---

### 3.22 `user_notifications` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users CASCADE` | NO | — | `index(['user_id','read_at'])` | — |
| `type` | `string(100)` | NO | — | — | Event type slug |
| `title` | `string(200)` | NO | — | — | Short notification title |
| `body` | `text` | NO | — | — | Full message |
| `action_url` | `string(500)` | YES | — | — | CTA link |
| `read_at` | `timestamp` | YES | — | — | NULL = unread |
| `data` | `json` | YES | — | — | Extra metadata |
| `timestamps` | — | — | — | — | — |

---

### 3.23 `onboarding_progress` Table

| Column | Type | Nullable | Default | Purpose |
|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | — |
| `user_id` | `bigint FK→users CASCADE unique` | NO | — | One row per user |
| `step_email_verified` | `boolean` | NO | `false` | Email OTP done |
| `step_plan_selected` | `boolean` | NO | `false` | Subscription created |
| `step_profile_completed` | `boolean` | NO | `false` | Name + phone + avatar set |
| `step_notifications_enabled` | `boolean` | NO | `false` | FCM token registered |
| `step_first_project` | `boolean` | NO | `false` | First consumeFeature call |
| `completed_at` | `timestamp` | YES | — | All steps done |
| `dismissed_at` | `timestamp` | YES | — | User dismissed checklist |

---

### 3.24 `password_history` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users CASCADE` | NO | — | `index` | — |
| `password` | `string(255)` | NO | — | — | Bcrypt hash of old password |
| `created_at` | `timestamp` | NO | — | `index` | For pruning oldest entries |

---

### 3.25 `user_notes` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `user_id` | `bigint FK→users CASCADE` | NO | — | `index` | Target user |
| `admin_id` | `bigint FK→users SET NULL` | YES | — | `index` | Who wrote the note |
| `content` | `text` | NO | — | — | Note body (max 2000 chars) |
| `timestamps` | — | — | — | — | — |

---

### 3.26 `user_segments` Table

| Column | Type | Nullable | Default | Purpose |
|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | — |
| `name` | `string(100)` | NO | — | "Monthly Pro Users" |
| `description` | `string(500)` | YES | — | Admin description |
| `filters` | `json` | NO | — | Filter rule set |
| `user_count` | `int` | NO | `0` | Cached count, refreshed hourly |
| `last_evaluated_at` | `timestamp` | YES | — | Last count refresh |
| `timestamps` | — | — | — | — |

**Segment filter JSON schema**:
```json
{
  "operator": "AND",
  "conditions": [
    {"field": "subscription_status", "operator": "in", "value": ["active", "trialing"]},
    {"field": "plan_slug", "operator": "eq", "value": "pro-monthly"},
    {"field": "created_at", "operator": "gte", "value": "2026-01-01"}
  ]
}
```

**Supported filter fields**: `subscription_status`, `plan_slug`, `role`, `is_suspended`, `created_at`, `email_verified_at`, `last_login_at`, `locale`

---

### 3.27 `broadcast_notifications` Table

| Column | Type | Nullable | Default | Index | Purpose |
|---|---|---|---|---|---|
| `id` | `bigint PK AI` | NO | — | PK | — |
| `admin_id` | `bigint FK→users SET NULL` | YES | — | `index` | Who created it |
| `title` | `string(200)` | NO | — | — | Notification title |
| `body` | `text` | NO | — | — | Message body |
| `channels` | `json` | NO | — | — | `["fcm","email","whatsapp","sms"]` |
| `target_type` | `enum('all','plan','role','segment')` | NO | `all` | `index` | Audience |
| `target_id` | `bigint` | YES | — | — | plan_id / role_id / segment_id |
| `status` | `enum('draft','scheduled','sending','sent','failed')` | NO | `draft` | `index` | Lifecycle |
| `scheduled_at` | `timestamp` | YES | — | `index` | Future send time |
| `sent_at` | `timestamp` | YES | — | — | When completed |
| `total_recipients` | `int` | NO | `0` | — | Cached recipient count |
| `sent_count` | `int` | NO | `0` | — | Successful deliveries |
| `failed_count` | `int` | NO | `0` | — | Failed deliveries |
| `data` | `json` | YES | — | — | Extra FCM payload |
| `timestamps` | — | — | — | — | — |

---

## Section 4 — All Models

### 4.1 `User` Model [MODIFY]

**Traits**: `HasFactory`, `Notifiable`, `PasskeyAuthenticatable`, `TwoFactorAuthenticatable`, `HasRoles` (Spatie), `HasSubscriptions` (custom), `SoftDeletes`

**Fillable**:
```php
['name', 'email', 'password', 'phone_number', 'stripe_id', 'pm_type', 'pm_last_four',
 'is_suspended', 'suspended_at', 'suspended_reason', 'otp_code', 'otp_expires_at',
 'otp_purpose', 'last_login_at', 'last_login_ip', 'avatar_path', 'referral_code',
 'locale', 'terms_accepted_at', 'terms_version_accepted', 'email_bounced_at', 'email_bounce_type']
```

**Casts**:
```php
'email_verified_at'       => 'datetime',
'phone_verified_at'       => 'datetime',
'otp_expires_at'          => 'datetime',
'two_factor_confirmed_at' => 'datetime',
'suspended_at'            => 'datetime',
'last_login_at'           => 'datetime',
'terms_accepted_at'       => 'datetime',
'email_bounced_at'        => 'datetime',
'is_suspended'            => 'boolean',
'password'                => 'hashed',
'deleted_at'              => 'datetime',
```

**Helper Methods**:
```php
isSuspended(): bool
isVerified(): bool              // email_verified_at !== null
hasVerifiedPhone(): bool        // phone_verified_at !== null
hasBouncedEmail(): bool         // email_bounced_at !== null && email_bounce_type === 'hard'
hasActiveOtp(string $purpose): bool
getAvatarUrlAttribute(): string // Storage URL or Gravatar fallback
```

**Relationships**:
```php
subscriptions(): HasMany<Subscription>
activeSubscription(): HasOne<Subscription>
fcmTokens(): HasMany<FcmToken>
notificationLogs(): HasMany<NotificationLog>
userNotifications(): HasMany<UserNotification>
sentInvitations(): HasMany<Invitation>
socialAccounts(): HasMany<SocialAccount>
loginHistory(): HasMany<LoginHistory>
referralsSent(): HasMany<Referral>      // as referrer
referralReceived(): HasOne<Referral>    // as referred
credits(): HasMany<UserCredit>
notes(): HasMany<UserNote>
onboarding(): HasOne<OnboardingProgress>
passwordHistory(): HasMany<PasswordHistory>
```

---

### 4.2 `Plan` Model [NEW]

```php
// Relationships
features(): BelongsToMany<Feature>   // with pivot 'value'
subscriptions(): HasMany<Subscription>

// Helpers
isFree(): bool              // price == 0
isLifetime(): bool          // billing_period === 'lifetime'
getFeatureValue(string $slug): mixed  // Value from plan_feature pivot

// Scopes
scopeActive(Builder $q): Builder
scopeOrdered(Builder $q): Builder
```

---

### 4.3 `Feature` Model [NEW]

```php
plans(): BelongsToMany<Plan>  // via plan_feature
```

---

### 4.4 `Subscription` Model [NEW]

```php
// Status Helpers
isActive(): bool      // status='active' AND (ends_at IS NULL OR ends_at > now())
isTrialing(): bool    // status='trialing' AND trial_ends_at > now()
isInGrace(): bool     // status='grace' AND grace_ends_at > now()
isCanceled(): bool    // status='canceled'
isExpired(): bool     // status='expired' OR (ends_at NOT NULL AND ends_at < now())
isPaused(): bool      // status='paused'

// Master validity check — gate for dashboard access
isValid(): bool       // isActive() || isTrialing() || isInGrace() || (isCanceled() && ends_at > now())

// Display helpers
daysRemaining(): ?int
isLifetime(): bool   // plan.billing_period === 'lifetime'

// Relationships
user(): BelongsTo<User>
plan(): BelongsTo<Plan>
usages(): HasMany<SubscriptionUsage>
```

---

### 4.5 `SubscriptionUsage` Model [NEW]

```php
getRemaining(int|string $limit): int|string  // 'unlimited' passthrough
isExhausted(int|string $limit): bool
subscription(): BelongsTo<Subscription>
```

---

### 4.6 `Setting` Model [NEW]

**Auto-encrypt/decrypt accessor/mutator**:
```php
public function getValueAttribute(?string $value): ?string
{
    return ($this->is_encrypted && $value) ? decrypt($value) : $value;
}

public function setValueAttribute(?string $value): void
{
    $this->attributes['value'] = ($this->is_encrypted && $value) ? encrypt($value) : $value;
}

// Static helpers
static get(string $key, mixed $default = null): mixed
static getGroup(string $group): Collection
static set(string $key, mixed $value): bool
static flush(): void   // Clear settings cache
```

---

### 4.7 Other Models [NEW — brief specs]

| Model | Key Methods / Notes |
|---|---|
| `FcmToken` | `user()`, `scopeActive()` |
| `NotificationLog` | `user()`, `scopeChannel()`, `scopeRecent()` |
| `ActivityLog` | `user()`, `subject()` (morphTo) |
| `SocialAccount` | `user()` |
| `MagicLink` | `isExpired()`, `isUsed()` |
| `LoginHistory` | `user()`, cron cleanup after 90 days |
| `Coupon` | `isValid()` (active + not expired + under max_redemptions) |
| `Referral` | `referrer()`, `referred()` |
| `UserCredit` | `user()`, `scopeAvailable()` (not expired, not used) |
| `EmailTemplate` | `scopeActive()` |
| `FeatureFlag` | `isEnabled(?User $user)` |
| `IpRule` | `scopeActive()` |
| `WebhookLog` | `scopeUnprocessed()` |
| `UserNotification` | `user()`, `scopeUnread()` |
| `OnboardingProgress` | `user()`, `completionPercentage()`, `isComplete()` |
| `PasswordHistory` | `user()` |
| `UserNote` | `user()`, `admin()` |
| `UserSegment` | `buildQuery()` delegate to SegmentService |
| `BroadcastNotification` | `admin()`, `scopeScheduledDue()` |

---

## Section 5 — `HasSubscriptions` Trait

**File**: `app/Traits/HasSubscriptions.php`

**Redis Cache Key Schema**:
```
user:{id}:subscription       → Subscription model (TTL 3600s)
user:{id}:feature_limits     → array [slug => value] (TTL 3600s)
user:{id}:feature_usages     → array [slug => used_count] (TTL 900s)
```

**Full Implementation**:
```php
// ─── Read (Cache-First) ───────────────────────────────────────────────────

public function getActiveSubscription(): ?Subscription
{
    return Cache::remember("user:{$this->id}:subscription", 3600, fn() =>
        $this->subscriptions()->with(['plan.features'])->latest()->first()
    );
}

public function hasValidSubscription(): bool
{
    $sub = $this->getActiveSubscription();
    return $sub && $sub->isValid();
}

public function getFeatureLimit(string $slug): int|string
{
    $limits = Cache::remember("user:{$this->id}:feature_limits", 3600, function () {
        $sub = $this->getActiveSubscription();
        if (!$sub) return [];
        return $sub->plan->features->pluck('pivot.value', 'slug')->toArray();
    });
    return $limits[$slug] ?? 0;
}

public function getFeatureUsage(string $slug): int
{
    $usages = Cache::remember("user:{$this->id}:feature_usages", 900, function () {
        $sub = $this->getActiveSubscription();
        if (!$sub) return [];
        return $sub->usages->pluck('used', 'feature_slug')->toArray();
    });
    return $usages[$slug] ?? 0;
}

public function getFeatureRemaining(string $slug): int|string
{
    $limit = $this->getFeatureLimit($slug);
    if ($limit === 'unlimited') return 'unlimited';
    return max(0, (int)$limit - $this->getFeatureUsage($slug));
}

public function canUseFeature(string $slug): bool
{
    $limit = $this->getFeatureLimit($slug);
    if ($limit === 'unlimited') return true;
    if ($limit === 'false' || $limit === false) return false;
    if ($limit === 'true' || $limit === true) return true;
    return $this->getFeatureUsage($slug) < (int)$limit;
}

// ─── Write (DB + Cache Flush) ─────────────────────────────────────────────

public function consumeFeature(string $slug, int $amount = 1): void
{
    $sub = $this->getActiveSubscription();
    if (!$sub) return;

    SubscriptionUsage::where('subscription_id', $sub->id)
                     ->where('feature_slug', $slug)
                     ->increment('used', $amount);

    Cache::forget("user:{$this->id}:feature_usages");

    // Update onboarding step_first_project if applicable
    OnboardingProgress::where('user_id', $this->id)
        ->where('step_first_project', false)
        ->update(['step_first_project' => true]);
}

// ─── Cache Management ─────────────────────────────────────────────────────

public function flushSubscriptionCache(): void
{
    Cache::forget("user:{$this->id}:subscription");
    Cache::forget("user:{$this->id}:feature_limits");
    Cache::forget("user:{$this->id}:feature_usages");
}
```

---

## Section 6 — All Service Classes

### 6.1 `SubscriptionManager`

**File**: `app/Services/SubscriptionManager.php`

| Method | Signature | Business Logic |
|---|---|---|
| `subscribeTo` | `(User $user, Plan $plan, ?string $stripeSubId = null): Subscription` | Cancel existing → create subscription row → seed usage rows → assign role → flush cache → dispatch notifications |
| `changePlan` | `(User $user, Plan $newPlan): Subscription` | Update plan_id + previous_plan_id → Stripe proration → flush cache → notify |
| `cancelAtPeriodEnd` | `(User $user): Subscription` | Set auto_renew=false, status=canceled, canceled_at → keep ends_at → flush cache → notify |
| `cancelImmediately` | `(Subscription $sub): void` | Set status=expired, ends_at=now → flush cache |
| `enterGracePeriod` | `(Subscription $sub): void` | Set status=grace, grace_ends_at=now()+grace_days, payment_failed_at=now() → flush cache → notify |
| `resume` | `(User $user): Subscription` | Set auto_renew=true, status=active, canceled_at=null → flush cache |
| `downgradeToFree` | `(User $user): Subscription` | Find Free plan → cancelImmediately on current → subscribeTo Free → remove paid role → notify |
| `resetFeatureUsages` | `(Subscription $sub): void` | Zero out usages where reset_at < now(), update next reset_at → flush usages cache |
| `syncFromStripe` | `(object $stripeSubscription): void` | Update local subscription fields from Stripe object → flush cache |
| `scheduleDunningRetry` | `(Subscription $sub, int $dayOffset): void` | Set next_retry_at = payment_failed_at + $dayOffset days |

**Key business rules in `subscribeTo()`**:
1. If existing non-expired subscription exists → `cancelImmediately()` first
2. Compute `ends_at`: now()+1 month / now()+1 year / null for lifetime
3. Set `status = 'trialing'` if `plan->trial_days > 0`, else `'active'`
4. Seed one `subscription_usages` row per plan feature with `used = 0`
5. Set `reset_at` based on feature's `resettable_period`
6. Assign `User (Subscribed)` role if paid; keep `User (Free)` if free
7. Flush all cache keys
8. Dispatch `subscription_activated` notification

---

### 6.2 `OtpService`

**File**: `app/Services/OtpService.php`

```php
const EXPIRY_MINUTES   = 10;
const MAX_ATTEMPTS     = 5;
const LOCKOUT_MINUTES  = 15;

// Generate 6-digit OTP, store hashed in DB, dispatch via configured channels
generate(User $user, string $purpose): string

// Verify submitted code — returns true/false
// On fail: increment Cache('otp_fail:{user_id}')
// On 5th fail: lockout for LOCKOUT_MINUTES
verify(User $user, string $code, string $purpose): bool

isLockedOut(User $user): bool
lockoutSecondsRemaining(User $user): int

// Clear OTP fields in DB
clear(User $user): void

// Which channels to use for OTP (reads admin settings)
getChannels(): array   // subset of ['email','whatsapp','sms','fcm']
```

---

### 6.3 `GreenApiService`

**File**: `app/Services/GreenApiService.php`

```php
// All methods return bool — silently return false if not configured

sendMessage(string $phoneNumber, string $message): bool
// Sanitize phone to "14155550132@c.us"
// POST {url}/waInstance{id}/sendMessage/{token}
// Log to notification_logs

// Notification templates
sendOtp(string $phone, string $code): bool
sendTrialEnding(string $phone, string $planName, string $endsIn): bool
sendRenewalUpcoming(string $phone, string $planName, string $renewsOn, string $price): bool
sendGraceWarning(string $phone, int $graceDaysLeft): bool
sendSubscriptionExpired(string $phone): bool
sendSuspensionNotice(string $phone, ?string $reason): bool
sendInvitation(string $phone, string $inviteLink): bool
```

---

### 6.4 `TwilioService`

**File**: `app/Services/TwilioService.php`

```php
// Silently return false if not configured or disabled in settings

sendSms(string $phoneNumber, string $message): bool
// twilio/sdk → $client->messages->create()
// Log to notification_logs

sendOtp(string $phone, string $code): bool
// "Your verification code is: {code}. Valid for 10 minutes."
```

---

### 6.5 `FcmService`

**File**: `app/Services/FcmService.php`

```php
send(string|array $tokens, string $title, string $body, array $data = []): bool
// Uses DevKandil\NotiFire FCM facade
// Deactivates tokens that return "invalid_token" error

sendToUser(User $user, string $title, string $body, array $data = []): bool

registerToken(User $user, string $token, string $deviceType, ?string $deviceName): FcmToken
// Deactivates old token for same device_type before registering new one

deactivateToken(string $token): void
```

---

### 6.6 `NotificationDispatcher`

**File**: `app/Services/NotificationDispatcher.php`

**Event → Handler routing**:

| Event | Email | WhatsApp | FCM | SMS | In-App |
|---|---|---|---|---|---|
| `otp_email_verify` | ✅ | ✅ | ✅ | ✅ | ✗ |
| `otp_login_2fa` | ✅ | ✅ | ✅ | ✅ | ✗ |
| `otp_phone_verify` | ✗ | ✅ | ✅ | ✅ | ✗ |
| `otp_password_reset` | ✅ | ✅ | ✅ | ✅ | ✗ |
| `subscription_activated` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `subscription_trial_end` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `subscription_renewal` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `subscription_renewed` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `subscription_grace` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `subscription_expired` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `subscription_canceled` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `account_suspended` | ✅ | ✅ | ✅ | ✅ | ✗ |
| `invitation_sent` | ✅ | ✅ | ✗ | ✅ | ✗ |
| `dunning_retry` | ✅ | ✅ | ✅ | ✅ | ✅ |

**Channel logic**:
- Reads `otp_default_channels` from settings (json array)
- Checks `$user->hasBouncedEmail()` → skips email channel
- Checks `$user->phone_number` → skips WhatsApp/SMS if null
- Checks active FCM tokens → skips FCM if none
- Creates `UserNotification` record (in-app) for every subscribed event
- Logs every attempt to `notification_logs`

---

### 6.7 `AnalyticsService`

**File**: `app/Services/AnalyticsService.php`

All methods cache in Redis for 1 hour:
```php
getMrr(): float           // Monthly price subs + (yearly/12)
getArr(): float           // getMrr() * 12
getChurnRate(): float     // Expired+canceled last 30d / active at month start * 100
getArpu(): float          // getMrr() / activeCount
getActiveByPlan(): array          // [plan_slug => count]
getStatusDistribution(): array    // [status => count]
getGrowthChart(int $days): array  // [date => new_subs]
getFunnel(): array        // [registered, verified, plan_selected, paid, pct_each]
getFeatureUsageStats(): array     // [slug => {total, avg, max, pct_at_limit}]
getRevenueByPlan(): array         // [plan_name => monthly_revenue]
getMrrTrend(): array      // [month => mrr] last 12 months
getChurnTrend(): array    // [month => count] last 12 months
```

---

### 6.8 `SegmentService`

**File**: `app/Services/SegmentService.php`

```php
buildQuery(array $filters): Builder   // Convert filter JSON to Eloquent query
evaluate(UserSegment $segment): int   // Run query, cache count, update last_evaluated_at
refreshAllCounts(): void              // Called from daily cron
getUserIds(UserSegment $segment): array
```

---

### 6.9 `AuditLogger`

**File**: `app/Services/AuditLogger.php`

```php
static log(
    string $event,
    ?Model $subject = null,
    array $oldValues = [],
    array $newValues = [],
    ?string $description = null
): void
// Auto-captures: auth()->user(), request()->ip(), request()->userAgent()
// Inserts into activity_logs
```

---

### 6.10 `CreditService`

**File**: `app/Services/CreditService.php`

```php
getBalance(User $user): float          // Sum of non-expired, unused credits
grant(User $user, float $amount, string $type, string $description): void
apply(User $user, string $checkoutSessionId): void  // Apply to Stripe as customer balance
hasCredits(User $user): bool
```

---

### 6.11 `FeatureFlagService`

**File**: `app/Services/FeatureFlagService.php`

```php
isEnabled(string $key, ?User $user = null): bool
// 5-min Redis cache per flag key
// Check order: global → user_ids → role → plan → false
```

---

### 6.12 `PasswordHistoryService`

**File**: `app/Services/PasswordHistoryService.php`

```php
// Check if $plainPassword matches any of last N hashed passwords
check(User $user, string $plainPassword): bool

// Store new password hash, prune oldest to keep only last N
record(User $user, string $hashedPassword): void
// N = Setting::get('password_history_count', 5)
```

---

## Section 7 — All Middleware

### 7.1 Middleware Stack in `bootstrap/app.php`

**Global (web requests)**:
1. `SetSecurityHeaders`
2. `SanitizeInput`
3. `IpFirewall`
4. `LoadSettingsFromDatabase`
5. `SetLocale`
6. `EnsureNotSuspended`
7. `TrackImpersonation`

**Named Aliases**:
```php
'subscribed'   => VerifyActiveSubscription::class
'feature'      => CheckFeatureLimit::class
'role'         => RoleMiddleware::class          // Spatie
'permission'   => PermissionMiddleware::class    // Spatie
```

**CSRF Exceptions**: `stripe/webhook`, `webhooks/email/bounce`

---

### 7.2 `SetSecurityHeaders`

Sets: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`, `HSTS: max-age=31536000; includeSubDomains; preload`, `Permissions-Policy: geolocation=(), camera=(), microphone=()`, `Content-Security-Policy` (self + Stripe JS + Google Fonts)

---

### 7.3 `SanitizeInput`

Recursively `strip_tags()` all inputs. Skip: `password`, `password_confirmation`, `current_password`, `value` (settings)

---

### 7.4 `IpFirewall`

Load `ip_rules` from Redis (1hr cache). Check block list first via CIDR matching → `abort(403)` if matched. Supports single IP and CIDR ranges.

---

### 7.5 `LoadSettingsFromDatabase`

```php
Cache::remember('app_settings', 86400, fn() => Setting::all()->keyBy('key'))
```

Maps to Laravel config paths:

| Setting Key | Config Path |
|---|---|
| `mail_host` | `mail.mailers.smtp.host` |
| `mail_port` | `mail.mailers.smtp.port` |
| `mail_username` | `mail.mailers.smtp.username` |
| `mail_password` | `mail.mailers.smtp.password` |
| `mail_encryption` | `mail.mailers.smtp.encryption` |
| `mail_from_address` | `mail.from.address` |
| `mail_from_name` | `mail.from.name` |
| `green_api_url` | `services.green_api.url` |
| `green_api_id_instance` | `services.green_api.id_instance` |
| `green_api_token_instance` | `services.green_api.token_instance` |
| `stripe_key` | `services.stripe.key` |
| `stripe_secret` | `services.stripe.secret` |
| `stripe_webhook_secret` | `services.stripe.webhook_secret` |
| `firebase_project_id` | `notifire.project_id` |
| `firebase_private_key` | `notifire.private_key` |
| `firebase_client_email` | `notifire.client_email` |
| `twilio_account_sid` | `services.twilio.sid` |
| `twilio_auth_token` | `services.twilio.token` |
| `twilio_from_number` | `services.twilio.from` |

---

### 7.6 `SetLocale`

```php
App::setLocale(auth()->user()?->locale ?? Setting::get('app_locale', 'en'));
```

---

### 7.7 `EnsureNotSuspended`

If `Auth::check() && Auth::user()->is_suspended`: logout, invalidate session, regenerate CSRF, redirect to login with error and suspension reason.

---

### 7.8 `TrackImpersonation`

Reads `session('impersonating_admin_id')` → passes `is_impersonating: true`, `impersonating_as: $user->name` to Inertia shared props. Expires after 2 hours.

---

### 7.9 `VerifyTosAccepted`

If `Setting::get('tos_version') !== $user->terms_version_accepted` → pass `tos_acceptance_required: true` to Inertia → modal rendered client-side.

---

### 7.10 `VerifyActiveSubscription`

- Skip for Admins / Super Admins
- If `!$user->email_verified_at` → redirect to `/verify/otp?purpose=email_verify`
- If `!$user->hasValidSubscription()` → redirect to `/pricing`

---

### 7.11 `CheckFeatureLimit`

Route usage: `middleware('feature:projects')`

```php
if (!$user->canUseFeature($featureSlug)) {
    return $request->wantsJson()
        ? response()->json(['message' => 'Feature limit reached', 'feature' => $featureSlug, 'limit' => $limit, 'used' => $used], 403)
        : back()->with('error', "You've reached your {$featureSlug} limit.");
}
```

---

## Section 8 — All Controllers

### 8.1 Auth — `RegisteredUserController::store()` [MODIFY]

1. Validate: `name`, `email` (unique), `password` (confirmed, min:8, `StrongPassword` rule), `phone_number` (nullable, regex), `terms` (required, accepted)
2. Check invitation token in query param → mark as `accepted` after creation
3. `User::create([...])`
4. Assign role: `$user->assignRole('User (Free)')`
5. Create `OnboardingProgress` record
6. Generate referral code (Str::random(8) unique)
7. Record in `password_history`
8. Generate OTP via `OtpService::generate($user, 'email_verify')`
9. `NotificationDispatcher::dispatch($user, 'otp_email_verify')`
10. `Auth::login($user)`
11. `AuditLogger::log('user.created', $user)`
12. Redirect to `/verify/otp?purpose=email_verify`

**Edge cases**:
- Duplicate unverified email → resend OTP, don't create duplicate
- Valid invitation token → mark `accepted`, record `invited_by`
- Expired/cancelled invitation → allow registration freely (invitations are optional)

---

### 8.2 Auth — `OtpController` [NEW]

**`show()`**: Render `auth/otp-verify` with purpose, masked contact, expiry seconds

**`verifyOtp()`**:
1. Validate: `code` (digits:6), `purpose` (in allowed list)
2. Check `OtpService::isLockedOut()` → 429 with seconds remaining
3. `OtpService::verify()` → on fail: 422 + lockout status
4. On success dispatch by purpose:
   - `email_verify` → set `email_verified_at = now()`, update onboarding step → redirect `/pricing`
   - `phone_verify` → set `phone_verified_at = now()` → 200
   - `login_2fa` → complete session auth → redirect `/dashboard`
   - `password_reset` → generate one-time reset URL → redirect to reset form

**`sendOtp()`** (resend, `throttle:5,1`):
1. Check lockout → block if locked
2. `OtpService::clear()` → `OtpService::generate()`
3. Dispatch notifications
4. Return `{message, expires_in_seconds: 600}`

---

### 8.3 Auth — `LoginController::store()` [MODIFY]

1. Validate: email, password
2. Check `is_suspended` BEFORE auth → return 403 with reason
3. Check login lockout via `RateLimiter`
4. `Auth::attempt()` → on fail: `RateLimiter::hit()`, log to `login_history` (failed)
5. On success:
   - `RateLimiter::clear()`
   - Update `last_login_at`, `last_login_ip`
   - Log to `login_history` (success)
   - `AuditLogger::log('admin.login')` if admin role
   - If 2FA enabled → generate OTP → redirect `/verify/otp?purpose=login_2fa`
   - Check `hasValidSubscription()` → redirect to `/pricing` if false
   - Redirect to `/dashboard`

---

### 8.4 Auth — `SocialAuthController` [NEW]

**`redirect(string $provider)`**: `Socialite::driver($provider)->redirect()`

**`callback(string $provider)`**:
1. `$socialUser = Socialite::driver($provider)->user()`
2. Find or create `SocialAccount` record by `provider + provider_id`
3. If new user: create `User`, assign `User (Free)`, skip OTP (email already verified by provider), create `OnboardingProgress`
4. If existing user: link social account
5. `Auth::login($user)` → subscription check → `/pricing` or `/dashboard`

---

### 8.5 Auth — `MagicLinkController` [NEW]

**`send()`**: Rate limit 3/hr. Find user. Generate token. Store in `magic_links`. Dispatch email with signed URL. Return generic success.

**`login()`**: Validate signature. Check `used_at IS NULL` and not expired. Set `used_at = now()`. `Auth::login()`. Subscription check.

---

### 8.6 Auth — `PasswordResetController` [MODIFY]

**`forgotPassword()`**: Rate limit `3,60`. Generate OTP with purpose `password_reset`. Dispatch via `NotificationDispatcher`. Generic success response.

**`resetPassword()`**:
1. Validate: email, code (6 digits), password (confirmed, `StrongPassword` rule)
2. Verify OTP
3. Check `PasswordHistoryService::check()` → reject if reused
4. On success: update password, record in `password_history`, delete all sessions, redirect to login

---

### 8.7 Auth — `SessionController` [NEW]

**`index()`**: Load sessions for user from `sessions` table. Parse user_agent. Flag current session. Return with device info.

**`destroy(string $sessionId)`**: Verify ownership → delete from `sessions`.

**`destroyOthers()`**: Delete all sessions except current session ID.

---

### 8.8 Profile — `ProfileController` [MODIFY]

**`updateAvatar()`**: Validate image (max:2MB, mimes:jpg,jpeg,png,webp). Delete old. Store new. Update `avatar_path`. Return avatar URL.

**`deleteAvatar()`**: Storage::delete + null out `avatar_path`.

**`updatePhone()`**: Validate phone (unique except self). Store new number, set `phone_verified_at = null`. Generate OTP for `phone_verify`. Dispatch WhatsApp/SMS.

**`update()`** (existing): Include `StrongPassword` rule + `PasswordHistoryService::check()` on password change.

**`requestDeletion()`**: Validate password. Generate signed URL (24hr TTL). Dispatch confirmation email.

**`confirmDeletion()`**: Validate signature. Soft-delete user. Cancel Stripe subscription. Deactivate FCM tokens. Log to `activity_logs`.

**`requestExport()`**: Rate limit 1/24hr. Dispatch `ExportUserDataJob`. Return success message.

---

### 8.9 Billing — `PlanController` [NEW]

**`pricing()`**: Load active ordered plans with features. Load `$user->getActiveSubscription()` if auth. Render `billing/pricing`.

**`dashboard()`** (requires `subscribed`): Load subscription + plan + features + usages. Calculate usage percentages. Load last 12 Stripe invoices. Render `billing/dashboard`.

---

### 8.10 Billing — `StripeBillingController` [NEW]

**`checkoutSession()`**:
1. Validate: `plan_id`, `billing_cycle`, `coupon` (optional)
2. If Free plan → call `SubscriptionManager::subscribeTo()` directly → `{redirect: '/dashboard'}`
3. Create/get Stripe Customer
4. Select Stripe Price ID by billing_cycle
5. Build Stripe Checkout Session with: `allow_promotion_codes: true`, `trial_period_days`, `automatic_tax` (if enabled), `metadata: {user_id, plan_id, billing_cycle}`
6. Return `{checkout_url}`

**`previewProration()`**: Call Stripe `invoices.retrieveUpcoming` → return `{credit_applied, new_charge, total_due_today, next_billing_date}`

**`billingPortal()`**: Create Stripe Billing Portal session → return `{portal_url}`

**`cancelSubscription()`**: Cancel in Stripe (cancel_at_period_end). `SubscriptionManager::cancelAtPeriodEnd()`. Dispatch notification.

**`resumeSubscription()`**: Update Stripe to resume. `SubscriptionManager::resume()`.

**`changePlan()`**: Validate. Show proration preview first. Update Stripe subscription with proration. `SubscriptionManager::changePlan()`.

**`checkoutSuccess()`**: Retrieve Stripe session. If already processed → redirect dashboard. Else show processing page.

**`handleWebhook()`** — Event routing:

| Stripe Event | Handler |
|---|---|
| `checkout.session.completed` | `handleCheckoutCompleted()` — find user/plan from metadata → `subscribeTo()` → notify |
| `customer.subscription.updated` | `syncFromStripe()` → flush cache |
| `customer.subscription.deleted` | `handleCancellation()` → `cancelImmediately()` → downgrade |
| `invoice.payment_succeeded` | `handlePaymentSucceeded()` → active, reset grace, reset usage, notify |
| `invoice.payment_failed` | `handlePaymentFailed()` → `enterGracePeriod()` → schedule dunning → notify |
| `customer.subscription.paused` | `handlePaused()` → status=paused |
| `customer.subscription.resumed` | `handleResumed()` → status=active |

**Idempotency**: Every webhook creates/finds a `WebhookLog` record by `event_id`. Skip if `processed = true`.

---

### 8.11 Billing — `InvoiceController` [NEW]

**`download(string $invoiceId)`**: Verify invoice belongs to user (customer_id match). Retrieve from Stripe. Generate PDF via DomPDF. Stream as download.

---

### 8.12 Admin — `AdminUserController` [NEW]

| Method | Action |
|---|---|
| `index()` | Paginate(20) with `['roles','activeSubscription.plan']`. Search by name/email. Filter by role/status/plan/suspended |
| `show(User $user)` | Full profile: roles, subscription history, login audit, notification logs, FCM tokens, user notes, credits, login history |
| `suspend(User $user)` | Set suspended flags. Delete sessions. Dispatch notification. Log to activity_logs |
| `unsuspend(User $user)` | Clear suspended flags. Log to activity_logs |
| `assignRole(User $user)` | `syncRoles()`. Flush permission cache. Log to activity_logs |
| `assignPlan(User $user)` | `SubscriptionManager::changePlan()` (admin bypass, no Stripe). Log to activity_logs |
| `destroy(User $user)` | Soft delete. Cancel Stripe. Deactivate FCM. Log to activity_logs |
| `restore(User $user)` | Restore soft-deleted user |
| `impersonate(User $user)` | Validate role restrictions. `session(['impersonating_admin_id' => auth()->id()])`. `Auth::login($user)`. Log impersonation.started |
| `stopImpersonation()` | `Auth::loginUsingId(session('impersonating_admin_id'))`. Clear session key. Log impersonation.ended |
| `storeNote(User $user)` | Create `UserNote` record |
| `destroyNote(UserNote $note)` | Authorize: own note or Super Admin |
| `bulkAction()` | suspend/unsuspend/assign_role/export for multiple user_ids |
| `export()` | Dispatch `BulkUserExportJob` |
| `trashed()` | List soft-deleted users |

---

### 8.13 Admin — `AdminRoleController` [NEW]

| Method | Action |
|---|---|
| `index()` | List roles with permissions count + user count |
| `store()` | Create role (guard: web) |
| `update(Role)` | Rename role |
| `syncPermissions(Role)` | `$role->syncPermissions($permissions)`. Flush Spatie cache |
| `destroy(Role)` | Delete if 0 users assigned |

---

### 8.14 Admin — `AdminPlanController` [NEW]

| Method | Action |
|---|---|
| `index()` | All plans + features + subscriber counts |
| `store()` | Create plan + sync features |
| `update(Plan)` | Update details, sync Stripe product/price if needed |
| `syncFeatures(Plan)` | Update `plan_feature` pivot values |
| `toggleActive(Plan)` | Flip `is_active` |
| `destroy(Plan)` | Only if 0 active subscribers |

---

### 8.15 Admin — `SettingController` [NEW]

**`index()`**: Load all settings grouped by group. Mask `type=secret` values as `'••••••••'` for display.

**`update()`**: Loop submitted key-value pairs. `Setting::set()`. Clear settings cache + branding cache. If SMTP changed → test connection.

---

### 8.16 Admin — `InvitationController` [NEW]

| Route | Method | Action |
|---|---|---|
| `GET /admin/invitations` | `index()` | Paginated list with status, expiry, invited_by |
| `POST /admin/invitations` | `store()` | Validate email. Create invitation with token. Dispatch email + WhatsApp |
| `POST /admin/invitations/{id}/resend` | `resend()` | Regenerate token, reset expiry, resend |
| `DELETE /admin/invitations/{id}` | `cancel()` | Set status=cancelled |
| `POST /invite` | `userInvite()` | Any auth user sends invitation |

---

### 8.17 Admin — `ActivityLogController` [NEW]

**`index()`**: Paginate with filters: user, event type, date range, subject type

**`export()`**: Download filtered logs as CSV

---

### 8.18 Admin — `AnalyticsController` [NEW]

**`index()`**: Load all analytics data from `AnalyticsService`. Render `admin/analytics`.

**`exportCsv()`**: Download all analytics data as CSV.

---

### 8.19 Admin — `BroadcastNotificationController` [NEW]

| Method | Action |
|---|---|
| `index()` | List all broadcasts with status + delivery stats |
| `store()` | Validate + create draft/immediate broadcast |
| `send(BroadcastNotification)` | Dispatch `SendBroadcastNotificationJob` |
| `preview()` | Return estimated recipient count |
| `destroy(BroadcastNotification)` | Only if draft or scheduled |

---

### 8.20 Admin — `UserSegmentController` [NEW]

| Method | Action |
|---|---|
| `index()` | List segments with cached counts |
| `store()` | Create segment with filter JSON |
| `update(UserSegment)` | Edit filters |
| `preview()` | Live count from `SegmentService::buildQuery()` |
| `destroy(UserSegment)` | Delete segment |
| `export(UserSegment)` | Export segment users as CSV |
| `notify(UserSegment)` | Create draft broadcast targeting this segment |

---

### 8.21 Admin — Other Controllers [NEW]

| Controller | Route Prefix | Key Methods |
|---|---|---|
| `CouponController` | `/admin/coupons` | CRUD, Stripe API sync, toggle |
| `EmailTemplateController` | `/admin/email-templates` | index, edit, update, preview, sendTest |
| `FailedJobController` | `/admin/failed-jobs` | index, retry, retryAll, destroy, flush |
| `FeatureFlagController` | `/admin/feature-flags` | index, store, update, destroy |
| `IpRuleController` | `/admin/ip-rules` | index, store, update, destroy |
| `MaintenanceController` | `/admin/maintenance` | enable, disable |
| `SystemHealthController` | `/admin/system-health` | status (Redis/DB/queue/disk/cache) |
| `WebhookLogController` | `/admin/webhook-logs` | index, show, reprocess |
| `CacheController` | `/admin/cache` | flush (by category) |
| `RateLimitController` | `/admin/rate-limits` | index, unlock |
| `LogViewerController` | `/admin/logs` | index, show, download, clear (Super Admin) |

---

### 8.22 Admin — Notification / Diagnostic Controllers [NEW]

| Controller | Routes | Methods |
|---|---|---|
| `FcmNotificationController` | `/fcm/register`, `/admin/diagnostics/fcm`, `/admin/fcm/broadcast` | register, send, sendBroadcast |
| `WhatsappNotificationController` | `/admin/diagnostics/whatsapp` | send |
| `AdminMailController` | `/admin/diagnostics/email`, `/admin/diagnostics/email/preview/{template}` | sendTestEmail, previewTemplate |
| `SmsNotificationController` | `/admin/diagnostics/sms` | send (test SMS) |

---

### 8.23 Notification Bell — `NotificationController` [NEW]

| Method | Action |
|---|---|
| `index()` | Latest 20 `user_notifications` for auth user |
| `markRead(int $id)` | Set `read_at = now()` |
| `markAllRead()` | Update all unread for user |
| `unreadCount()` | Redis-cached unread count for badge |

---

### 8.24 Webhooks — `EmailBounceController` [NEW]

**Route**: `POST /webhooks/email/bounce` (no CSRF, no auth)

Parse bounce payload based on `mail_provider` setting format. On hard bounce: set `email_bounced_at`, `email_bounce_type = 'hard'`. Create admin in-app notification. Log to `notification_logs`.

---

## Section 9 — Complete Route File

```php
// ─── PUBLIC ──────────────────────────────────────────────────────────────
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/pricing', [PlanController::class, 'pricing'])->name('pricing');
});

// Stripe & Email bounce webhooks (no CSRF)
Route::post('/stripe/webhook', [StripeBillingController::class, 'handleWebhook'])
    ->withoutMiddleware(VerifyCsrfToken::class)->name('stripe.webhook');
Route::post('/webhooks/email/bounce', [EmailBounceController::class, 'handle'])
    ->withoutMiddleware(VerifyCsrfToken::class)->name('webhooks.email.bounce');

// Social OAuth
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

// Magic Link
Route::get('/auth/magic-link', [MagicLinkController::class, 'send'])->name('magic-link.form');
Route::post('/auth/magic-link', [MagicLinkController::class, 'send'])->name('magic-link.send');
Route::get('/auth/magic-link/login', [MagicLinkController::class, 'login'])->name('magic-link.login');

// ─── AUTH (no subscription check yet) ───────────────────────────────────
Route::middleware(['auth'])->group(function () {
    // OTP Verification
    Route::get('/verify/otp', [OtpController::class, 'show'])->name('verification.otp');
    Route::post('/verify/otp', [OtpController::class, 'verifyOtp'])->name('verification.otp.verify');
    Route::post('/verify/otp/resend', [OtpController::class, 'sendOtp'])
        ->middleware('throttle:5,1')->name('verification.otp.resend');

    // Plan selection / checkout (pre-subscription)
    Route::get('/billing/success', [StripeBillingController::class, 'checkoutSuccess'])->name('billing.success');
    Route::post('/billing/checkout', [StripeBillingController::class, 'checkoutSession'])
        ->middleware('throttle:10,1')->name('billing.checkout');
    Route::post('/billing/proration-preview', [StripeBillingController::class, 'previewProration'])->name('billing.proration-preview');

    // Send invitation (any auth user)
    Route::post('/invite', [InvitationController::class, 'userInvite'])
        ->middleware('throttle:10,60')->name('invite.send');

    // FCM token registration
    Route::post('/fcm/register', [FcmNotificationController::class, 'register'])->name('fcm.register');

    // In-app notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.count');

    // ToS acceptance
    Route::post('/terms/accept', fn(Request $r) => tap(
        auth()->user()->update(['terms_accepted_at' => now(), 'terms_version_accepted' => Setting::get('tos_version')]),
        fn() => redirect()->back()
    ))->name('terms.accept');
});

// ─── AUTH + SUBSCRIPTION REQUIRED ────────────────────────────────────────
Route::middleware(['auth', 'subscribed', 'verified-tos'])->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('dashboard'))->name('dashboard');
    Route::get('/pricing', [PlanController::class, 'pricing'])->name('pricing.subscribed');

    // Billing management
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [PlanController::class, 'dashboard'])->name('dashboard');
        Route::post('/portal', [StripeBillingController::class, 'billingPortal'])->name('portal');
        Route::post('/cancel', [StripeBillingController::class, 'cancelSubscription'])->name('cancel');
        Route::post('/resume', [StripeBillingController::class, 'resumeSubscription'])->name('resume');
        Route::post('/change-plan', [StripeBillingController::class, 'changePlan'])
            ->middleware('throttle:5,1')->name('change-plan');
        Route::get('/invoices/{invoiceId}/download', [InvoiceController::class, 'download'])->name('invoice.download');
    });

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::post('/phone', [ProfileController::class, 'updatePhone'])->name('phone.update');
        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar'])->name('avatar.delete');
        Route::post('/request-deletion', [ProfileController::class, 'requestDeletion'])->name('deletion.request');
        Route::get('/confirm-deletion', [ProfileController::class, 'confirmDeletion'])->name('deletion.confirm');
        Route::post('/export', [ProfileController::class, 'requestExport'])->name('export');
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy'])->name('sessions.destroy');
        Route::delete('/sessions', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');
        Route::get('/login-history', fn() => Inertia::render('profile/login-history', [
            'history' => auth()->user()->loginHistory()->latest('login_at')->paginate(20)
        ]))->name('login-history');
    });

    // ─── ADMIN ───────────────────────────────────────────────────────────
    Route::middleware('role:Admin|Super Admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', fn() => Inertia::render('admin/dashboard'))->name('dashboard');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('analytics.export');

        // Users
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/trashed', [AdminUserController::class, 'trashed'])->name('trashed');
            Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
            Route::post('/{user}/suspend', [AdminUserController::class, 'suspend'])->name('suspend');
            Route::post('/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('unsuspend');
            Route::post('/{user}/assign-role', [AdminUserController::class, 'assignRole'])->name('assign-role');
            Route::post('/{user}/assign-plan', [AdminUserController::class, 'assignPlan'])->name('assign-plan');
            Route::post('/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('impersonate');
            Route::post('/{user}/notes', [AdminUserController::class, 'storeNote'])->name('notes.store');
            Route::delete('/{user}/notes/{note}', [AdminUserController::class, 'destroyNote'])->name('notes.destroy');
            Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/restore', [AdminUserController::class, 'restore'])->name('restore');
            Route::post('/bulk', [AdminUserController::class, 'bulkAction'])->name('bulk');
            Route::post('/export', [AdminUserController::class, 'export'])->name('export');
        });
        Route::post('/impersonation/stop', [AdminUserController::class, 'stopImpersonation'])->name('impersonation.stop');

        // Roles & Permissions
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [AdminRoleController::class, 'index'])->name('index');
            Route::post('/', [AdminRoleController::class, 'store'])->name('store');
            Route::put('/{role}', [AdminRoleController::class, 'update'])->name('update');
            Route::post('/{role}/sync-permissions', [AdminRoleController::class, 'syncPermissions'])->name('sync');
            Route::delete('/{role}', [AdminRoleController::class, 'destroy'])->name('destroy');
        });

        // Plans & Features
        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [AdminPlanController::class, 'index'])->name('index');
            Route::post('/', [AdminPlanController::class, 'store'])->name('store');
            Route::put('/{plan}', [AdminPlanController::class, 'update'])->name('update');
            Route::post('/{plan}/features', [AdminPlanController::class, 'syncFeatures'])->name('features.sync');
            Route::post('/{plan}/toggle', [AdminPlanController::class, 'toggleActive'])->name('toggle');
            Route::delete('/{plan}', [AdminPlanController::class, 'destroy'])->name('destroy');
        });

        // Invitations
        Route::prefix('invitations')->name('invitations.')->group(function () {
            Route::get('/', [InvitationController::class, 'index'])->name('index');
            Route::post('/', [InvitationController::class, 'store'])->name('store');
            Route::post('/{invitation}/resend', [InvitationController::class, 'resend'])->name('resend');
            Route::delete('/{invitation}', [InvitationController::class, 'cancel'])->name('cancel');
        });

        // Coupons
        Route::apiResource('coupons', CouponController::class)->names('coupons');
        Route::post('/coupons/{coupon}/toggle', [CouponController::class, 'toggle'])->name('coupons.toggle');

        // Segments
        Route::prefix('segments')->name('segments.')->group(function () {
            Route::get('/', [UserSegmentController::class, 'index'])->name('index');
            Route::post('/', [UserSegmentController::class, 'store'])->name('store');
            Route::put('/{segment}', [UserSegmentController::class, 'update'])->name('update');
            Route::post('/preview', [UserSegmentController::class, 'preview'])->name('preview');
            Route::delete('/{segment}', [UserSegmentController::class, 'destroy'])->name('destroy');
            Route::get('/{segment}/export', [UserSegmentController::class, 'export'])->name('export');
            Route::post('/{segment}/notify', [UserSegmentController::class, 'notify'])->name('notify');
        });

        // Broadcast Notifications
        Route::prefix('broadcasts')->name('broadcasts.')->group(function () {
            Route::get('/', [BroadcastNotificationController::class, 'index'])->name('index');
            Route::post('/', [BroadcastNotificationController::class, 'store'])->name('store');
            Route::post('/{broadcast}/send', [BroadcastNotificationController::class, 'send'])->name('send');
            Route::post('/preview', [BroadcastNotificationController::class, 'preview'])->name('preview');
            Route::delete('/{broadcast}', [BroadcastNotificationController::class, 'destroy'])->name('destroy');
        });

        // Email Templates
        Route::prefix('email-templates')->name('email-templates.')->group(function () {
            Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
            Route::get('/{template}/edit', [EmailTemplateController::class, 'edit'])->name('edit');
            Route::put('/{template}', [EmailTemplateController::class, 'update'])->name('update');
            Route::get('/{template}/preview', [EmailTemplateController::class, 'preview'])->name('preview');
            Route::post('/{template}/test', [EmailTemplateController::class, 'sendTest'])->name('test');
        });

        // Feature Flags
        Route::apiResource('feature-flags', FeatureFlagController::class)->names('feature-flags');

        // IP Rules
        Route::apiResource('ip-rules', IpRuleController::class)->names('ip-rules');

        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

        // Maintenance Mode
        Route::post('/maintenance/enable', [MaintenanceController::class, 'enable'])->name('maintenance.enable');
        Route::post('/maintenance/disable', [MaintenanceController::class, 'disable'])->name('maintenance.disable');

        // System Operations
        Route::get('/system-health', [SystemHealthController::class, 'status'])->name('system-health');
        Route::post('/cache/flush', [CacheController::class, 'flush'])->name('cache.flush');
        Route::get('/failed-jobs', [FailedJobController::class, 'index'])->name('failed-jobs.index');
        Route::post('/failed-jobs/{uuid}/retry', [FailedJobController::class, 'retry'])->name('failed-jobs.retry');
        Route::post('/failed-jobs/retry-all', [FailedJobController::class, 'retryAll'])->name('failed-jobs.retry-all');
        Route::delete('/failed-jobs/{uuid}', [FailedJobController::class, 'destroy'])->name('failed-jobs.destroy');
        Route::delete('/failed-jobs', [FailedJobController::class, 'flush'])->name('failed-jobs.flush');
        Route::get('/rate-limits', [RateLimitController::class, 'index'])->name('rate-limits.index');
        Route::post('/rate-limits/unlock', [RateLimitController::class, 'unlock'])->name('rate-limits.unlock');

        // Webhook Logs
        Route::get('/webhook-logs', [WebhookLogController::class, 'index'])->name('webhook-logs.index');
        Route::get('/webhook-logs/{log}', [WebhookLogController::class, 'show'])->name('webhook-logs.show');
        Route::post('/webhook-logs/{log}/reprocess', [WebhookLogController::class, 'reprocess'])->name('webhook-logs.reprocess');

        // Logs
        Route::get('/logs', [LogViewerController::class, 'index'])->name('logs.index');
        Route::get('/logs/{filename}', [LogViewerController::class, 'show'])->name('logs.show');
        Route::get('/logs/{filename}/download', [LogViewerController::class, 'download'])->name('logs.download');
        Route::delete('/logs/{filename}', [LogViewerController::class, 'clear'])->middleware('role:Super Admin')->name('logs.clear');

        // Activity Log
        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
        Route::get('/activity/export', [ActivityLogController::class, 'export'])->name('activity.export');

        // Diagnostics
        Route::prefix('diagnostics')->name('diagnostics.')->group(function () {
            Route::post('/email', [AdminMailController::class, 'sendTestEmail'])->name('email');
            Route::get('/email/preview/{template}', [AdminMailController::class, 'previewTemplate'])->name('email.preview');
            Route::post('/fcm', [FcmNotificationController::class, 'send'])->name('fcm');
            Route::post('/fcm/broadcast', [FcmNotificationController::class, 'sendBroadcast'])->name('fcm.broadcast');
            Route::post('/whatsapp', [WhatsappNotificationController::class, 'send'])->name('whatsapp');
            Route::post('/sms', [SmsNotificationController::class, 'send'])->name('sms');
        });
    });
});
```

---

## Section 10 — Jobs & Commands

### 10.1 `SendSubscriptionReminders` Artisan Command

**Schedule**: Daily at `08:00` in `routes/console.php`

**All tasks executed in one run**:

1. **Trial ending** → subscriptions `status=trialing` AND `trial_ends_at` within 3 days → dispatch `ProcessSubscriptionReminderJob($sub, 'subscription_trial_end')`
2. **Renewal upcoming** → `status=active`, `auto_renew=true`, `ends_at` within 3 days → dispatch `'subscription_renewal'`
3. **Grace expiring** → `status=grace`, `grace_ends_at` within 1 day → dispatch `'subscription_grace'`
4. **Dunning retries** → `status=grace` AND `next_retry_at <= now()` → dispatch `DunningRetryJob`
5. **Expire overdue** → `status IN (canceled,grace)` AND `ends_at < now()` → `status=expired` → `downgradeToFree()` → dispatch `'subscription_expired'`
6. **Scheduled broadcasts** → `BroadcastNotification::scheduledDue()` → dispatch `SendBroadcastNotificationJob`
7. **Refresh segment counts** → `SegmentService::refreshAllCounts()`
8. **Reset feature usages** → `subscription_usages.reset_at < now()` → zero `used`, update `reset_at`
9. **Cleanup OTP codes** → `otp_expires_at < now()-1hr` → nullify OTP fields
10. **Cleanup notification logs** → `created_at < now()-30days` → hard delete
11. **Cleanup login history** → `login_at < now()-90days` → hard delete
12. **Deactivate stale FCM tokens** → `last_used_at < now()-30days` → `is_active=false`
13. **Hard-delete soft-deleted users** → `deleted_at < now()-30days` → cancel Stripe + cascade delete

All queries use `.lazy(1000)` — constant memory at any scale.

---

### 10.2 Queue Jobs

| Job | Queue | Tries | Timeout | Purpose |
|---|---|---|---|---|
| `ProcessSubscriptionReminderJob` | `default` | 3 | 30s | Dispatch a single subscription notification |
| `DunningRetryJob` | `default` | 1 | 60s | Attempt Stripe invoice retry |
| `SendBroadcastNotificationJob` | `low` | 1 | 300s | Fan out broadcast to all recipients |
| `ExportUserDataJob` | `low` | 1 | 120s | Generate and email user data export |
| `BulkUserExportJob` | `low` | 1 | 120s | Generate and email admin user CSV export |

**Queue priority order**: `high` (OTP, security alerts) → `default` (billing events) → `low` (bulk, exports) → `emails`

**All Mail classes use `implements ShouldQueue` with `$queue = 'emails'`**

---

### 10.3 Horizon Configuration

**File**: `config/horizon.php`

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection'   => 'redis',
            'queue'        => ['high', 'default', 'low', 'emails'],
            'balance'      => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 10,
            'tries'        => 3,
            'timeout'      => 60,
        ],
    ],
    'local' => [
        'supervisor-1' => [
            'queue'    => ['high', 'default', 'low', 'emails'],
            'balance'  => 'simple',
            'processes'=> 3,
        ],
    ],
],
```

**Horizon auth gate** in `AppServiceProvider`:
```php
Horizon::auth(fn($request) => auth()->check() && auth()->user()->hasRole('Super Admin'));
```

---

## Section 11 — Email Templates

All under `resources/views/emails/` with shared `layout.blade.php`.

All Mail classes: `implements ShouldQueue` · `$queue = 'emails'` · Read from `EmailTemplate` table first; fallback to Blade file.

| Key | Mail Class | Trigger |
|---|---|---|
| `otp` | `OtpMail` | All OTP purposes |
| `subscription_activated` | `SubscriptionActivatedMail` | Plan activated |
| `trial_ending` | `TrialEndingMail` | 3 days before trial expires |
| `renewal_upcoming` | `SubscriptionRenewalUpcomingMail` | 3 days before renewal |
| `grace_warning` | `SubscriptionGraceWarningMail` | Payment failed, grace started |
| `subscription_renewed` | `SubscriptionRenewedMail` | Renewal payment succeeded |
| `subscription_expired` | `SubscriptionExpiredMail` | Access expired |
| `subscription_canceled` | `SubscriptionCanceledMail` | User canceled |
| `account_suspended` | `AccountSuspendedMail` | Admin suspended account |
| `invitation` | `InvitationMail` | Admin or user invitation |
| `magic_link` | `MagicLinkMail` | Passwordless login |
| `data_export` | `DataExportMail` | GDPR data export ready |
| `account_deletion_confirm` | `AccountDeletionConfirmMail` | Deletion confirmation link |
| `dunning_retry` | `DunningRetryMail` | Payment retry scheduled |
| `referral_reward` | `ReferralRewardMail` | Referrer earned a reward |

**`layout.blade.php`** reads from branding settings: app name, logo URL, support email, footer text.

---

## Section 12 — Seeders

### 12.1 `PermissionSeeder`

**13 Permissions**:
`view_admin_dashboard`, `manage_users`, `suspend_users`, `delete_users`, `manage_roles`, `manage_plans`, `manage_features`, `manage_invitations`, `manage_settings`, `send_fcm_notifications`, `send_test_emails`, `send_test_whatsapp`, `view_notification_logs`

**4 Roles**:

| Role | Permissions |
|---|---|
| `Super Admin` | ALL 13 |
| `Admin` | ALL except `delete_users` |
| `User (Subscribed)` | none |
| `User (Free)` | none |

---

### 12.2 `PlanSeeder` — 6 Plans

| Plan | Billing | Price | Trial | Grace | projects | api_calls | premium_support | team_members |
|---|---|---|---|---|---|---|---|---|
| Free Starter | month | $0.00 | 0d | 0d | 1 | 100/mo | false | 1 |
| Pro Monthly | month | $19.99 | 7d | 5d | 10 | 10,000/mo | true | 5 |
| Pro Yearly | year | $179.99 | 14d | 7d | 10 | 10,000/mo | true | 5 |
| Enterprise Monthly | month | $79.99 | 14d | 7d | 100 | unlimited | true | unlimited |
| Enterprise Yearly | year | $719.99 | 14d | 10d | 100 | unlimited | true | unlimited |
| Lifetime | lifetime | $399.00 | 0d | 0d | 100 | unlimited | true | unlimited |

**4 Features seeded**:
- `projects` — consumable, reset: none
- `api_calls` — consumable, reset: month
- `premium_support` — boolean, reset: none
- `team_members` — limit, reset: none

---

### 12.3 `SettingSeeder` — All Keys

| Key | Group | Type | Encrypted | Default |
|---|---|---|---|---|
| `mail_host` | smtp | string | No | `sandbox.smtp.mailtrap.io` |
| `mail_port` | smtp | integer | No | `2525` |
| `mail_username` | smtp | string | No | `` |
| `mail_password` | smtp | secret | **Yes** | `` |
| `mail_encryption` | smtp | string | No | `tls` |
| `mail_from_address` | smtp | string | No | `no-reply@example.com` |
| `mail_from_name` | smtp | string | No | `SaaS App` |
| `mail_provider` | smtp | string | No | `mailtrap` |
| `green_api_url` | green_api | string | No | `https://api.green-api.com` |
| `green_api_id_instance` | green_api | string | No | `` |
| `green_api_token_instance` | green_api | secret | **Yes** | `` |
| `twilio_account_sid` | twilio | string | No | `` |
| `twilio_auth_token` | twilio | secret | **Yes** | `` |
| `twilio_from_number` | twilio | string | No | `` |
| `twilio_enabled` | twilio | boolean | No | `false` |
| `stripe_key` | stripe | string | No | `` |
| `stripe_secret` | stripe | secret | **Yes** | `` |
| `stripe_webhook_secret` | stripe | secret | **Yes** | `` |
| `stripe_tax_enabled` | stripe | boolean | No | `false` |
| `stripe_tax_id_collection` | stripe | boolean | No | `false` |
| `firebase_project_id` | firebase | string | No | `` |
| `firebase_private_key_id` | firebase | string | No | `` |
| `firebase_private_key` | firebase | secret | **Yes** | `` |
| `firebase_client_email` | firebase | string | No | `` |
| `app_name` | app | string | No | `SaaS App` |
| `app_support_email` | app | string | No | `support@example.com` |
| `app_currency` | app | string | No | `USD` |
| `app_locale` | app | string | No | `en` |
| `app_supported_locales` | app | json | No | `["en"]` |
| `tos_url` | app | string | No | `` |
| `privacy_url` | app | string | No | `` |
| `tos_version` | app | string | No | `v1.0` |
| `announcement_text` | app | string | No | `` |
| `announcement_type` | app | string | No | `info` |
| `announcement_active` | app | boolean | No | `false` |
| `announcement_dismissible` | app | boolean | No | `true` |
| `otp_default_channels` | otp | json | No | `["email"]` |
| `otp_expiry_minutes` | otp | integer | No | `10` |
| `otp_max_attempts` | otp | integer | No | `5` |
| `otp_lockout_minutes` | otp | integer | No | `15` |
| `login_max_attempts` | security | integer | No | `5` |
| `login_lockout_minutes` | security | integer | No | `30` |
| `login_decay_minutes` | security | integer | No | `1` |
| `password_min_length` | security | integer | No | `8` |
| `password_require_uppercase` | security | boolean | No | `false` |
| `password_require_numbers` | security | boolean | No | `false` |
| `password_require_symbols` | security | boolean | No | `false` |
| `password_history_count` | security | integer | No | `5` |
| `dunning_enabled` | dunning | boolean | No | `true` |
| `dunning_retry_day_1` | dunning | boolean | No | `true` |
| `dunning_retry_day_3` | dunning | boolean | No | `true` |
| `dunning_retry_day_7` | dunning | boolean | No | `true` |
| `dunning_retry_day_14` | dunning | boolean | No | `false` |
| `dunning_cancel_after_days` | dunning | integer | No | `7` |
| `referral_reward_type` | app | string | No | `credit` |
| `referral_reward_value` | app | string | No | `10` |
| `referral_reward_duration` | app | string | No | `once` |
| `brand_app_name` | branding | string | No | `SaaS App` |
| `brand_logo_url` | branding | string | No | `/images/logo.png` |
| `brand_favicon_url` | branding | string | No | `/favicon.ico` |
| `brand_primary_color` | branding | string | No | `#4F46E5` |
| `brand_primary_color_dark` | branding | string | No | `#6366F1` |
| `brand_support_email` | branding | string | No | `support@example.com` |
| `brand_support_url` | branding | string | No | `` |
| `brand_twitter_url` | branding | string | No | `` |
| `brand_linkedin_url` | branding | string | No | `` |
| `brand_footer_text` | branding | string | No | `` |

---

### 12.4 `AdminSeeder`

```php
$admin = User::firstOrCreate(
    ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
    [
        'name'              => env('ADMIN_NAME', 'Super Admin'),
        'password'          => env('ADMIN_PASSWORD', 'password'),
        'email_verified_at' => now(),
        'terms_accepted_at' => now(),
        'terms_version_accepted' => 'v1.0',
    ]
);
$admin->assignRole('Super Admin');
// Create onboarding progress (mark all steps done for admin)
OnboardingProgress::firstOrCreate(['user_id' => $admin->id], [
    'step_email_verified'        => true,
    'step_plan_selected'         => true,
    'step_profile_completed'     => true,
    'step_notifications_enabled' => true,
    'step_first_project'         => true,
    'completed_at'               => now(),
]);
```

---

### 12.5 `EmailTemplateSeeder`

Seeds default template records for all 15 mail types. `is_active = false` by default — admin enables them once customized.

---

### 12.6 `DatabaseSeeder` execution order

```php
$this->call([
    PermissionSeeder::class,
    PlanSeeder::class,
    SettingSeeder::class,
    EmailTemplateSeeder::class,
    AdminSeeder::class,
]);
```

---

## Section 13 — Frontend

### 13.1 TypeScript Types (`resources/js/types/models.ts`)

```typescript
interface User {
  id: number; name: string; email: string;
  phone_number: string | null;
  email_verified_at: string | null;
  phone_verified_at: string | null;
  is_suspended: boolean;
  avatar_url: string;
  referral_code: string | null;
  locale: string;
  terms_accepted_at: string | null;
  email_bounced_at: string | null;
  roles: Role[];
  active_subscription: Subscription | null;
  last_login_at: string | null;
}

interface Plan {
  id: number; name: string; slug: string;
  price: number; currency: string;
  billing_period: 'month' | 'year' | 'lifetime';
  trial_days: number; grace_days: number;
  is_active: boolean; sort_order: number;
  features: Feature[]; metadata: Record<string, string> | null;
}

interface Subscription {
  id: number; status: SubscriptionStatus;
  plan: Plan; stripe_id: string | null;
  trial_ends_at: string | null; ends_at: string | null;
  grace_ends_at: string | null; cancels_at: string | null;
  auto_renew: boolean; canceled_at: string | null;
  is_valid: boolean; is_lifetime: boolean;
  days_remaining: number | null;
}

type SubscriptionStatus = 'active' | 'trialing' | 'grace' | 'canceled' | 'expired' | 'paused';

interface FeatureUsage {
  slug: string; name: string; used: number;
  limit: number | 'unlimited';
  percentage: number;
  remaining: number | 'unlimited';
  resettable_period: string;
}

interface UserNotification {
  id: number; type: string; title: string;
  body: string; action_url: string | null;
  read_at: string | null; created_at: string;
}

interface OnboardingStep {
  key: string; label: string; done: boolean; action_url: string;
}

interface Branding {
  appName: string; logoUrl: string; faviconUrl: string;
  primaryColor: string; supportEmail: string | null; footerText: string | null;
}
```

---

### 13.2 Inertia Shared Props (`HandleInertiaRequests`)

```php
'auth'         => ['user' => auth()->user()?->load('roles')],
'branding'     => Cache::remember('app_branding', 86400, fn() => [...]),
'announcement' => ['text' => ..., 'type' => ..., 'active' => ..., 'dismissible' => ...],
'tos'          => ['acceptance_required' => ..., 'tos_url' => ..., 'privacy_url' => ...],
'flash'        => ['success' => session('success'), 'error' => session('error')],
'is_impersonating'  => (bool) session('impersonating_admin_id'),
'impersonating_as'  => session('impersonating_admin_id')
    ? User::find(session('impersonating_admin_id'))?->name
    : null,
'unread_notifications' => auth()->check()
    ? Cache::remember("user:".auth()->id().":unread_count", 60, fn() =>
        UserNotification::where('user_id', auth()->id())->whereNull('read_at')->count()
    ) : 0,
```

---

### 13.3 All Frontend Pages

**Auth Pages**:
- `auth/otp-verify.tsx` — 6-digit input, countdown timer, resend button
- `auth/magic-link.tsx` — email input form for magic link request

**Profile Pages**:
- `profile/edit.tsx` — name, email, password (with strength meter), avatar, locale
- `profile/phone.tsx` — phone update with OTP flow
- `profile/two-factor.tsx` — 2FA enable/disable, QR code, recovery codes
- `profile/sessions.tsx` — active sessions table with device info, revoke buttons
- `profile/login-history.tsx` — login attempt history
- `profile/notifications.tsx` — notification preferences
- `profile/referrals.tsx` — user's referral code, referral stats, reward history
- `profile/security.tsx` — password change, account deletion, data export

**Billing Pages**:
- `billing/pricing.tsx` — plan cards, billing toggle (monthly/yearly), current plan highlighted, proration preview modal
- `billing/dashboard.tsx` — subscription card, feature usage bars, invoices list, cancel/resume/change actions

**Dashboard**:
- `dashboard.tsx` — with embedded `OnboardingChecklist` component

**Admin Pages**:
- `admin/dashboard.tsx` — KPI cards, recent activity, quick links
- `admin/analytics.tsx` — MRR/ARR/Churn cards + 7 charts (Recharts)
- `admin/users-manager.tsx` — searchable table, bulk actions, role/plan/suspension filters
- `admin/user-detail.tsx` — full user profile: subscription, usage, notes, login history, FCM tokens, notification logs
- `admin/roles-manager.tsx` — role list + permission matrix
- `admin/plans-manager.tsx` — plan cards + feature value editor
- `admin/invitations-manager.tsx` — invitation table with send form
- `admin/coupons-manager.tsx` — coupon list + create form
- `admin/user-segments.tsx` — visual filter builder + segment list
- `admin/broadcast-notifications.tsx` — campaign creator + history
- `admin/email-templates.tsx` — template list + TipTap editor + preview
- `admin/feature-flags.tsx` — flag toggle list with scope selectors
- `admin/ip-rules.tsx` — IP rule list + add form
- `admin/settings-manager.tsx` — tabbed (SMTP / Green API / Twilio / Stripe / Firebase / App / OTP / Security / Dunning / Branding)
- `admin/activity-log.tsx` — timeline view with filters
- `admin/failed-jobs.tsx` — failed queue jobs table with retry actions
- `admin/system-health.tsx` — service status cards + cache flush buttons
- `admin/log-viewer.tsx` — log file viewer with level filter
- `admin/webhook-logs.tsx` — Stripe event log table
- `admin/rate-limits.tsx` — rate-limited users/IPs with unlock button
- `admin/diagnostics/email.tsx` — test email sender + template previewer
- `admin/diagnostics/fcm.tsx` — test push notification
- `admin/diagnostics/whatsapp.tsx` — test WhatsApp message
- `admin/diagnostics/sms.tsx` — test SMS

---

### 13.4 All Global Components

| Component | Purpose |
|---|---|
| `announcement-banner.tsx` | Dismissible site-wide banner (reads Inertia shared props) |
| `notification-bell.tsx` | Header bell icon with unread badge + dropdown feed |
| `onboarding-checklist.tsx` | Dashboard floating checklist with progress + confetti |
| `notification-prompt.tsx` | FCM permission request + token registration on mount |
| `impersonation-bar.tsx` | Top bar shown when admin is impersonating a user |
| `tos-modal.tsx` | Modal shown when ToS acceptance is required |
| `password-strength-meter.tsx` | Real-time strength indicator for password inputs |
| `branding-header.tsx` | Dynamic logo + app name from branding settings |

---

### 13.5 PWA Files

- `public/manifest.json` — `name`, `short_name`, `start_url: /dashboard`, `display: standalone`, `theme_color`, 2 icons
- `public/sw.js` — cache-first for static, network-first for API, offline fallback page
- `public/icons/icon-192.png`, `icon-512.png`

**`app.blade.php` additions**:
```html
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="{{ $branding['primaryColor'] ?? '#4F46E5' }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<style>:root { --color-primary: {{ $branding['primaryColor'] }}; }</style>
```

---

## Section 14 — Custom Validation Rule

**File**: `app/Rules/StrongPassword.php`

```php
public function validate(string $attribute, mixed $value, Closure $fail): void
{
    $minLength  = (int) Setting::get('password_min_length', 8);
    $reqUpper   = (bool) Setting::get('password_require_uppercase', false);
    $reqNumbers = (bool) Setting::get('password_require_numbers', false);
    $reqSymbols = (bool) Setting::get('password_require_symbols', false);

    if (strlen($value) < $minLength)
        $fail("Password must be at least {$minLength} characters.");
    if ($reqUpper && !preg_match('/[A-Z]/', $value))
        $fail('Password must contain at least one uppercase letter.');
    if ($reqNumbers && !preg_match('/[0-9]/', $value))
        $fail('Password must contain at least one number.');
    if ($reqSymbols && !preg_match('/[\W_]/', $value))
        $fail('Password must contain at least one special character.');
}
```

---

## Section 15 — Test Suite

### 15.1 `UserRegistrationTest`
- Valid registration → `User (Free)` role assigned, OTP queued ✓
- Duplicate email (unverified) → resend OTP, no duplicate user ✓
- Correct OTP → `email_verified_at` set → redirect `/pricing` ✓
- Wrong OTP → 422, fail counter incremented ✓
- 5 wrong OTPs → lockout 15 min ✓
- Expired OTP → 422 ✓
- Invitation token present → invitation `accepted` ✓
- Terms checkbox missing → 422 ✓
- Weak password (if rules enabled) → 422 ✓
- Reused password on profile change → 422 ✓

### 15.2 `LoginTest`
- Suspended user blocked → 403 with reason ✓
- Login updates `last_login_at` and `last_login_ip` ✓
- Login logged to `login_history` ✓
- Too many failed attempts → rate limited → 429 ✓
- 2FA enabled → redirect to OTP page ✓
- Verified, no subscription → redirect `/pricing` ✓
- Active subscription → redirect `/dashboard` ✓
- Social login (Google) → creates user + social_account ✓
- Magic link → one-time use, expired link rejected ✓

### 15.3 `SubscriptionTest`
- Free plan → no Stripe, DB record created, usage rows seeded ✓
- Paid plan → Stripe checkout session URL returned ✓
- Webhook `checkout.session.completed` → subscription activated, notification sent ✓
- Webhook `invoice.payment_failed` → grace period entered, dunning retry scheduled ✓
- Webhook `invoice.payment_succeeded` → active restored, monthly usage reset ✓
- Dunning retry job → calls Stripe `invoices.pay` ✓
- Grace period end (cron) → expired, downgraded to Free ✓
- Cancel → `canceled` status, access valid until `ends_at` ✓
- Period end (cron) → auto-downgrade to Free ✓
- Plan upgrade → proration preview returns amounts ✓
- Plan upgrade confirmed → Stripe updated, plan changed ✓
- Lifetime plan → `ends_at = null`, never expires ✓
- Trial plan → `status = trialing`, access granted ✓
- Coupon applied → Stripe coupon used, discount shown ✓

### 15.4 `FeatureLimitTest`
- `canUseFeature()` true when under limit ✓
- `canUseFeature()` false when at limit ✓
- `canUseFeature()` always true for `unlimited` ✓
- `consumeFeature()` increments + flushes cache ✓
- Monthly feature usage resets after `reset_at` ✓
- `feature:projects` middleware → 403 when exhausted ✓

### 15.5 `AdminTest`
- Users list paginated (≤3 queries via eager loading) ✓
- Suspend → sessions deleted, notification sent, activity logged ✓
- Unsuspend → user can login ✓
- Bulk suspend → all selected users suspended ✓
- Impersonate → auth switches to target user ✓
- Stop impersonation → admin session restored ✓
- Impersonation logged to activity_logs ✓
- Assign role → Spatie role updated ✓
- Assign plan → plan changed (no Stripe) ✓
- Update settings → encrypted secrets stored encrypted ✓
- Read encrypted setting → decrypted value returned ✓
- Test email dispatched ✓
- Test FCM push dispatched ✓
- Test WhatsApp dispatched ✓
- Test SMS dispatched ✓
- Analytics data returns cached values ✓
- Segment preview returns correct count ✓
- Broadcast created → dispatched to correct target ✓

### 15.6 `SecurityTest`
- Hard bounce set → email channel skipped in dispatcher ✓
- IP blocked rule → request aborted 403 ✓
- Password reuse rejected ✓
- Password strength policy enforced ✓
- Rate limit dashboard shows locked user ✓
- Unlock clears rate limiter ✓
- Webhook log idempotency → same event_id processed once ✓

### 15.7 `PerformanceTest`
- Subscription check = 0 DB queries when cached ✓
- Feature limit check = 0 DB queries when cached ✓
- Feature usage check = 0 DB queries when cached ✓
- Admin user list ≤ 3 queries regardless of user count ✓
- Analytics service returns cached result without DB query ✓

---

## Section 16 — Real-World Scenario Walkthroughs

### Scenario A: New User → Free Plan
1. POST `/register` → user created, `User (Free)` role, `OnboardingProgress` row created, OTP dispatched (email + WhatsApp if phone given)
2. POST `/verify/otp` → `email_verified_at = now()`, onboarding `step_email_verified = true` → redirect `/pricing`
3. GET `/pricing` → all plans shown, Free plan highlighted as "Start Free"
4. POST `/billing/checkout` with Free plan → `SubscriptionManager::subscribeTo()` → subscription + usage rows → onboarding `step_plan_selected = true` → redirect `/dashboard`
5. Dashboard shows onboarding checklist (2/5 steps done)
6. User creates first project → `consumeFeature('projects')` → onboarding `step_first_project = true`
7. User tries to create 2nd project → `canUseFeature('projects') = false` → 403 blocked

---

### Scenario B: New User → Pro Monthly with Trial
1–2: Same as A (register → verify email)
3. On `/pricing` → "Start 7-day Free Trial" button on Pro Monthly
4. POST `/billing/checkout` with `trial=true` → Stripe Checkout with `trial_period_days: 7`
5. User completes Stripe checkout (no charge yet)
6. Webhook `checkout.session.completed` → `subscribeTo()` with `status=trialing`, `trial_ends_at = now()+7d`
7. Day 4: cron fires → `trial_ends_at` within 3 days → `ProcessSubscriptionReminderJob` → email + WhatsApp + FCM "Trial ends in 3 days"
8. Day 7: Stripe auto-charges → `invoice.payment_succeeded` → `status=active`
9. If user cancels before day 7 → webhook → `cancelAtPeriodEnd()` → cron auto-downgrades to Free at `ends_at`

---

### Scenario C: Payment Failure → Dunning → Recovery
1. Renewal date → Stripe charges → card declined
2. `invoice.payment_failed` webhook → `enterGracePeriod()` → `status=grace`, `grace_ends_at=+5d`, `payment_failed_at=now()`, `retry_count=0`
3. Notifications sent: email + WhatsApp + FCM + SMS "Payment failed, update your card"
4. Cron Day 1: `DunningRetryJob` dispatched → Stripe `invoices.pay()` → fails → `retry_count=1`, `next_retry_at=+2d`
5. Cron Day 3: retry again → fails → `retry_count=2`, `next_retry_at=+4d`
6. User updates card via Stripe Billing Portal
7. Cron Day 7: final retry → succeeds → `invoice.payment_succeeded` → `status=active`, reset grace + dunning fields, reset usage, notify "Payment successful"
8. Alternative: Day 7 without payment → `dunning_cancel_after_days` reached → `downgradeToFree()` → notify

---

### Scenario D: Plan Downgrade (Enterprise → Free)
1. User clicks "Downgrade to Free" → `previewProration()` modal shows credit amount
2. Confirms → POST `/billing/change-plan` → Stripe proration credit issued
3. `changePlan($user, $freePlan)` → `previous_plan_id = Enterprise ID`, `plan_id = Free`
4. User had 50 projects (Free limit = 1)
5. Existing projects remain — NOT deleted
6. `canUseFeature('projects') = false` → cannot create project 51
7. As user deletes projects down to 0 → `canUseFeature('projects') = true`

---

### Scenario E: Admin Suspends User
1. Admin → `/admin/users/{user}` → clicks "Suspend" → enters reason
2. POST `/admin/users/{user}/suspend` → flags set, sessions deleted, user force-logged-out
3. Notifications dispatched: email + WhatsApp + FCM + SMS
4. Activity logged: `user.suspended` with old/new values diff
5. User tries to login → `EnsureNotSuspended` fires → blocked before auth attempt
6. Admin clicks "Unsuspend" → flags cleared → user can login again

---

### Scenario F: Invitation Flow
1. **Admin sends invite**: POST `/admin/invitations` → invitation record, email + WhatsApp sent
2. **User sends invite**: POST `/invite` → same flow, `invited_by = $user->id`
3. Recipient gets email with `/register?invitation_token=abc123`
4. Registration page: email pre-filled (read-only), token in hidden field
5. On register: invitation `status = accepted`, `invited_by` stored
6. When referred user subscribes to paid plan → referral `status = converted` → reward issued → notify referrer

---

### Scenario G: OTP Lockout
1. User enters wrong OTP 5 times → fail counter hits MAX_ATTEMPTS
2. `Cache::put("otp_fail:{user_id}", 5, 900)` → 15-min lockout
3. Next attempt → `OtpService::isLockedOut() = true` → 429 with countdown
4. Resend attempt → also blocked during lockout
5. After 15 min → cache key expires → user can try again

---

### Scenario H: Phone Number Change
1. User enters new phone → POST `/profile/phone`
2. `phone_verified_at = null`, new number stored
3. OTP dispatched to NEW number via WhatsApp + SMS
4. OTP form shown with `purpose=phone_verify`
5. On verification → `phone_verified_at = now()`

---

### Scenario I: Settings Change (SMTP Update)
1. Admin opens Settings → SMTP tab
2. Enters new credentials → saves
3. POST `/admin/settings` → `Setting::set()` (password auto-encrypted) → `Cache::forget('app_settings')`
4. Next request: `LoadSettingsFromDatabase` rebuilds cache with new values, bootstraps `mail.*` config
5. Admin clicks "Send Test Email" → test mail sent via new SMTP → result shown

---

### Scenario J: Branding Change
1. Admin opens Settings → Branding tab
2. Changes primary color to `#0EA5E9`, uploads new logo URL
3. POST `/admin/settings` → saved → `Cache::forget('app_branding')`
4. Next page load: `HandleInertiaRequests` reads new branding → passes to all pages
5. `app.blade.php` injects new CSS variable: `--color-primary: #0EA5E9`
6. All Tailwind `bg-primary` and `text-primary` classes use new color instantly
7. All email templates render with new logo and brand name

---

### Scenario K: Broadcast Notification
1. Admin creates broadcast: "New feature: Dark mode" → channels: FCM + Email → target: all Pro/Enterprise users → scheduled: tomorrow 10am
2. Broadcast record created with `status=scheduled`, `scheduled_at=tomorrow 10am`
3. Cron fires at 10am → `BroadcastNotification::scheduledDue()` → dispatches `SendBroadcastNotificationJob`
4. Job fans out: queries all eligible users → batches FCM tokens → sends FCM → queues `BroadcastMail` for each user
5. `sent_count` and `failed_count` updated in real time
6. Admin sees delivery stats: "12,543 sent / 23 failed"

---

## Section 17 — Complete File Directory

```
laravel-subscription/
├── app/
│   ├── Console/Commands/
│   │   └── SendSubscriptionReminders.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── ActivityLogController.php
│   │   │   │   ├── AdminMailController.php
│   │   │   │   ├── AdminPlanController.php
│   │   │   │   ├── AdminRoleController.php
│   │   │   │   ├── AdminUserController.php
│   │   │   │   ├── AnalyticsController.php
│   │   │   │   ├── BroadcastNotificationController.php
│   │   │   │   ├── CacheController.php
│   │   │   │   ├── CouponController.php
│   │   │   │   ├── EmailTemplateController.php
│   │   │   │   ├── FailedJobController.php
│   │   │   │   ├── FeatureFlagController.php
│   │   │   │   ├── FcmNotificationController.php
│   │   │   │   ├── InvitationController.php
│   │   │   │   ├── IpRuleController.php
│   │   │   │   ├── LogViewerController.php
│   │   │   │   ├── MaintenanceController.php
│   │   │   │   ├── RateLimitController.php
│   │   │   │   ├── SettingController.php
│   │   │   │   ├── SmsNotificationController.php
│   │   │   │   ├── SystemHealthController.php
│   │   │   │   ├── UserSegmentController.php
│   │   │   │   ├── WebhookLogController.php
│   │   │   │   └── WhatsappNotificationController.php
│   │   │   ├── Auth/
│   │   │   │   ├── DataExportController.php
│   │   │   │   ├── MagicLinkController.php
│   │   │   │   ├── OtpController.php
│   │   │   │   ├── SessionController.php
│   │   │   │   └── SocialAuthController.php
│   │   │   ├── Billing/
│   │   │   │   ├── InvoiceController.php
│   │   │   │   ├── PlanController.php
│   │   │   │   └── StripeBillingController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── ProfileController.php            [MODIFY]
│   │   │   └── Webhooks/
│   │   │       └── EmailBounceController.php
│   │   ├── Middleware/
│   │   │   ├── CheckFeatureLimit.php
│   │   │   ├── EnsureNotSuspended.php
│   │   │   ├── IpFirewall.php
│   │   │   ├── LoadSettingsFromDatabase.php
│   │   │   ├── SanitizeInput.php
│   │   │   ├── SetLocale.php
│   │   │   ├── SetSecurityHeaders.php
│   │   │   ├── TrackImpersonation.php
│   │   │   ├── VerifyActiveSubscription.php
│   │   │   └── VerifyTosAccepted.php
│   │   └── Requests/
│   │       ├── AssignPlanRequest.php
│   │       ├── AssignRoleRequest.php
│   │       ├── StorePlanRequest.php
│   │       ├── SyncPermissionsRequest.php
│   │       └── UpdateSettingsRequest.php
│   ├── Jobs/
│   │   ├── BulkUserExportJob.php
│   │   ├── DunningRetryJob.php
│   │   ├── ExportUserDataJob.php
│   │   ├── ProcessSubscriptionReminderJob.php
│   │   └── SendBroadcastNotificationJob.php
│   ├── Mail/
│   │   ├── AccountDeletionConfirmMail.php
│   │   ├── AccountSuspendedMail.php
│   │   ├── DataExportMail.php
│   │   ├── DunningRetryMail.php
│   │   ├── InvitationMail.php
│   │   ├── MagicLinkMail.php
│   │   ├── OtpMail.php
│   │   ├── ReferralRewardMail.php
│   │   ├── SubscriptionActivatedMail.php
│   │   ├── SubscriptionCanceledMail.php
│   │   ├── SubscriptionExpiredMail.php
│   │   ├── SubscriptionGraceWarningMail.php
│   │   ├── SubscriptionRenewedMail.php
│   │   ├── SubscriptionRenewalUpcomingMail.php
│   │   └── TrialEndingMail.php
│   ├── Models/
│   │   ├── ActivityLog.php
│   │   ├── BroadcastNotification.php
│   │   ├── Coupon.php
│   │   ├── EmailTemplate.php
│   │   ├── Feature.php
│   │   ├── FeatureFlag.php
│   │   ├── FcmToken.php
│   │   ├── IpRule.php
│   │   ├── LoginHistory.php
│   │   ├── MagicLink.php
│   │   ├── NotificationLog.php
│   │   ├── OnboardingProgress.php
│   │   ├── PasswordHistory.php
│   │   ├── Plan.php
│   │   ├── Referral.php
│   │   ├── Setting.php
│   │   ├── SocialAccount.php
│   │   ├── Subscription.php
│   │   ├── SubscriptionUsage.php
│   │   ├── User.php                             [MODIFY]
│   │   ├── UserCredit.php
│   │   ├── UserNote.php
│   │   ├── UserNotification.php
│   │   ├── UserSegment.php
│   │   └── WebhookLog.php
│   ├── Rules/
│   │   └── StrongPassword.php
│   ├── Services/
│   │   ├── AnalyticsService.php
│   │   ├── AuditLogger.php
│   │   ├── CreditService.php
│   │   ├── FcmService.php
│   │   ├── FeatureFlagService.php
│   │   ├── GreenApiService.php
│   │   ├── NotificationDispatcher.php
│   │   ├── OtpService.php
│   │   ├── PasswordHistoryService.php
│   │   ├── SegmentService.php
│   │   ├── SubscriptionManager.php
│   │   └── TwilioService.php
│   └── Traits/
│       └── HasSubscriptions.php
├── bootstrap/
│   └── app.php                                  [MODIFY]
├── config/
│   └── horizon.php                              [MODIFY]
├── database/
│   ├── migrations/
│   │   ├── 2026_06_18_000001_add_saas_columns_to_users_table.php
│   │   ├── 2026_06_18_000002_create_plans_table.php
│   │   ├── 2026_06_18_000003_create_subscriptions_table.php
│   │   ├── 2026_06_18_000004_create_fcm_tokens_table.php
│   │   ├── 2026_06_18_000005_create_settings_table.php
│   │   ├── 2026_06_18_000006_add_fields_to_invitations_table.php
│   │   ├── 2026_06_18_000007_create_notification_logs_table.php
│   │   ├── 2026_06_18_000008_create_activity_logs_table.php
│   │   ├── 2026_06_18_000009_create_social_accounts_table.php
│   │   ├── 2026_06_18_000010_create_magic_links_table.php
│   │   ├── 2026_06_18_000011_create_login_history_table.php
│   │   ├── 2026_06_18_000012_create_coupons_table.php
│   │   ├── 2026_06_18_000013_create_referrals_table.php
│   │   ├── 2026_06_18_000014_create_user_credits_table.php
│   │   ├── 2026_06_18_000015_create_email_templates_table.php
│   │   ├── 2026_06_18_000016_create_feature_flags_table.php
│   │   ├── 2026_06_18_000017_create_ip_rules_table.php
│   │   ├── 2026_06_18_000018_create_webhook_logs_table.php
│   │   ├── 2026_06_18_000019_create_user_notifications_table.php
│   │   ├── 2026_06_18_000020_create_onboarding_progress_table.php
│   │   ├── 2026_06_18_000021_create_password_history_table.php
│   │   ├── 2026_06_18_000022_create_user_notes_table.php
│   │   ├── 2026_06_18_000023_create_user_segments_table.php
│   │   └── 2026_06_18_000024_create_broadcast_notifications_table.php
│   └── seeders/
│       ├── AdminSeeder.php
│       ├── DatabaseSeeder.php                   [MODIFY]
│       ├── EmailTemplateSeeder.php
│       ├── PermissionSeeder.php
│       ├── PlanSeeder.php
│       └── SettingSeeder.php
├── public/
│   ├── icons/
│   │   ├── icon-192.png
│   │   └── icon-512.png
│   ├── manifest.json
│   └── sw.js
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   ├── announcement-banner.tsx
│   │   │   ├── branding-header.tsx
│   │   │   ├── impersonation-bar.tsx
│   │   │   ├── notification-bell.tsx
│   │   │   ├── notification-prompt.tsx
│   │   │   ├── onboarding-checklist.tsx
│   │   │   ├── password-strength-meter.tsx
│   │   │   └── tos-modal.tsx
│   │   ├── layouts/
│   │   │   └── admin-layout.tsx
│   │   ├── locales/
│   │   │   └── en/
│   │   │       └── translation.json
│   │   ├── pages/
│   │   │   ├── admin/
│   │   │   │   ├── activity-log.tsx
│   │   │   │   ├── analytics.tsx
│   │   │   │   ├── broadcast-notifications.tsx
│   │   │   │   ├── coupons-manager.tsx
│   │   │   │   ├── dashboard.tsx
│   │   │   │   ├── diagnostics/
│   │   │   │   │   ├── email.tsx
│   │   │   │   │   ├── fcm.tsx
│   │   │   │   │   ├── sms.tsx
│   │   │   │   │   └── whatsapp.tsx
│   │   │   │   ├── email-templates.tsx
│   │   │   │   ├── failed-jobs.tsx
│   │   │   │   ├── feature-flags.tsx
│   │   │   │   ├── invitations-manager.tsx
│   │   │   │   ├── ip-rules.tsx
│   │   │   │   ├── log-viewer.tsx
│   │   │   │   ├── plans-manager.tsx
│   │   │   │   ├── rate-limits.tsx
│   │   │   │   ├── roles-manager.tsx
│   │   │   │   ├── settings-manager.tsx
│   │   │   │   ├── system-health.tsx
│   │   │   │   ├── user-detail.tsx
│   │   │   │   ├── user-segments.tsx
│   │   │   │   ├── users-manager.tsx
│   │   │   │   └── webhook-logs.tsx
│   │   │   ├── auth/
│   │   │   │   ├── magic-link.tsx
│   │   │   │   └── otp-verify.tsx
│   │   │   ├── billing/
│   │   │   │   ├── dashboard.tsx
│   │   │   │   └── pricing.tsx
│   │   │   ├── dashboard.tsx
│   │   │   └── profile/
│   │   │       ├── edit.tsx
│   │   │       ├── login-history.tsx
│   │   │       ├── notifications.tsx
│   │   │       ├── phone.tsx
│   │   │       ├── referrals.tsx
│   │   │       ├── security.tsx
│   │   │       ├── sessions.tsx
│   │   │       └── two-factor.tsx
│   │   └── types/
│   │       └── models.ts
│   └── views/
│       ├── emails/
│       │   ├── layout.blade.php
│       │   ├── account_deletion_confirm.blade.php
│       │   ├── account_suspended.blade.php
│       │   ├── data_export.blade.php
│       │   ├── dunning_retry.blade.php
│       │   ├── grace_warning.blade.php
│       │   ├── invitation.blade.php
│       │   ├── magic_link.blade.php
│       │   ├── otp.blade.php
│       │   ├── referral_reward.blade.php
│       │   ├── renewal_upcoming.blade.php
│       │   ├── subscription_activated.blade.php
│       │   ├── subscription_canceled.blade.php
│       │   ├── subscription_expired.blade.php
│       │   ├── subscription_renewed.blade.php
│       │   └── trial_ending.blade.php
│       ├── errors/
│       │   └── 503.blade.php                   (maintenance mode)
│       └── pdf/
│           └── invoice.blade.php
├── routes/
│   ├── console.php                              [MODIFY]
│   └── web.php                                  [MODIFY]
└── tests/Feature/
    ├── AdminTest.php
    ├── FeatureLimitTest.php
    ├── LoginTest.php
    ├── PerformanceTest.php
    ├── SecurityTest.php
    ├── SubscriptionTest.php
    └── UserRegistrationTest.php
```

---

## Section 18 — Implementation Execution Order

Execute tasks in this order to respect dependencies:

```
1.  Install Composer + NPM packages
2.  Publish vendor files
3.  Run migrations (Section 3 — all 24 migration files)
4.  Run: php artisan db:seed
5.  Build Traits: HasSubscriptions
6.  Build Services: OtpService, GreenApiService, TwilioService, FcmService, SubscriptionManager
7.  Build Services: NotificationDispatcher, AnalyticsService, SegmentService, AuditLogger, CreditService, FeatureFlagService, PasswordHistoryService
8.  Build Middleware (all 10)
9.  Build Custom Rule: StrongPassword
10. Build Auth Controllers: Register, Login, OTP, Social, MagicLink, PasswordReset
11. Build Profile Controllers: ProfileController, SessionController, DataExportController
12. Build Billing Controllers: PlanController, StripeBillingController, InvoiceController
13. Build Notification Controller
14. Build Admin Controllers: AdminUserController, AdminRoleController, AdminPlanController
15. Build Admin Controllers: SettingController, InvitationController, ActivityLogController, AnalyticsController
16. Build Admin Controllers: BroadcastNotificationController, UserSegmentController, CouponController
17. Build Admin Controllers: EmailTemplateController, FeatureFlagController, IpRuleController
18. Build Admin Controllers: FailedJobController, SystemHealthController, MaintenanceController, CacheController
19. Build Admin Controllers: RateLimitController, WebhookLogController, LogViewerController
20. Build Admin Notification Controllers: FcmNotificationController, WhatsappNotificationController, SmsNotificationController, AdminMailController
21. Build Webhook Controller: EmailBounceController
22. Build Jobs: all 5 queue jobs
23. Build Command: SendSubscriptionReminders
24. Configure Horizon
25. Build Mail classes (15) + Email Blade templates
26. Build PDF template: invoice.blade.php
27. Update bootstrap/app.php + routes/web.php + routes/console.php
28. Update HandleInertiaRequests with all shared props
29. Build TypeScript types: models.ts
30. Build Frontend Components: announcement-banner, notification-bell, onboarding-checklist, notification-prompt, impersonation-bar, tos-modal, password-strength-meter, branding-header
31. Build Auth pages: otp-verify, magic-link
32. Build Billing pages: pricing, dashboard
33. Build Profile pages: edit, phone, two-factor, sessions, login-history, security, referrals
34. Build Dashboard page (with onboarding checklist)
35. Build Admin Layout
36. Build Admin pages (all 20+)
37. Build PWA files: manifest.json, sw.js, icons
38. Write Tests (7 test classes)
39. Run: php artisan test
40. Run: php artisan horizon
```
