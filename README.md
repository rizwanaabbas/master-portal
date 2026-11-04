# Portal Master (unified multi-bank admin portal)

**Single master codebase for all bank admin portals (CIB, Monfi, and future banks).**  
Branding (logo, theme, emails, PDF statements) is configured per deployment via **Settings → Own Company** — see [docs/BANK_BRANDING.md](docs/BANK_BRANDING.md).

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Multi-bank configuration](#multi-bank-configuration)
3. [Tech Stack](#tech-stack)
4. [Project Structure](#project-structure)
5. [Modules](#modules)
6. [Third-Party API Integrations](#third-party-api-integrations)
7. [Security Flow](#security-flow)
8. [Authentication & Authorization](#authentication--authorization)
9. [Database Schema Overview](#database-schema-overview)
10. [Configuration](#configuration)
11. [Deployment](#deployment)
12. [Environment Variables](#environment-variables)
13. [Development Setup](#development-setup)
14. [Module Documentation](#module-documentation)

---

## 📚 Module Documentation

Module-specific docs may live outside this repo at `../docs/`. Root `README.md` plus `docs/BANK_BRANDING.md` stay in the project.

---

## 🏦 Overview

Portal Master is a comprehensive Laravel-based banking platform that provides:

- **Crypto Trading** — Withdrawal and deposit operations via Bitpace and Fireblocks custody
- **Foreign Exchange (FX)** — RFQ, order management, balance tracking through B2C2
- **Anti-Money Laundering (AML)** — KYC compliance rules, transaction monitoring, and NeroData integration
- **Digital Banking** — Account transactions (TT, Cheque, Internal transfers), exchange operations
- **Client Portal** — Role-based access for clients, directors, finance managers

This tree was formed by unifying `monfi-portal` (kept all Monfi fixes) with anything still only on `cib-portal`, then making CI and branding bank-agnostic.

---

## 🎛️ Multi-bank configuration

| Layer | What differs per bank |
|-------|------------------------|
| DB `own_companies` | Logo, favicon, theme CSS, stamp, watermark, legal name, emails, card/chart colors |
| DB `modules` | Feature flags / integration toggles |
| `.env` | `APP_NAME`, URLs, mail, S3, API keys |
| GitHub Actions vars | `ORG_FOLDER`, `ADDITIONAL_CHARTS` (optional Teams webhook secret) |

Do **not** maintain separate application forks for branding.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend Framework** | Laravel 8+ |
| **PHP Version** | ^7.3 / ^8.0 |
| **Frontend (SPA)** | Vue.js |
| **CSS Framework** | Tailwind CSS |
| **Asset Bundling** | Vite |
| **Database** | MySQL |
| **Object Storage** | Alibaba Cloud OSS |
| **Containerization** | Docker + Nginx |
| **API Client** | Guzzle HTTP (B2C2), Fireblocks PHP SDK, Laravel Http Facade (Bitpace) |

---

## 📁 Project Structure

```
portal-master/
├── app/
│   ├── Controllers/              # Core controllers (TRRequest, CryptoTransaction, Bitpace)
│   ├── Helpers/                  # Business logic helpers (BitpaceMerchantApis, EmailNotification, S3Helper, etc.)
│   ├── Http/
│   │   ├── Controllers/          # All HTTP request handlers
│   │   ├── Middleware/            # Custom middleware (APIKeyMiddleware, Check2FA, TwoFactorMiddleware)
│   │   └── Kernel.php            # Route & middleware definitions
│   ├── Models/                   # Core Eloquent models (User, Account, Currency, etc.)
│   ├── Modules/                  # Third-party module integrations
│   │   ├── Aml/                  # Anti-Money Laundering module
│   │   ├── B2c2/                 # Foreign Exchange trading module
│   │   └── Fireblocks/           # Digital asset custody module
│   └── Providers/                # Service providers
├── config/                       # Application configuration (fireblocks, b2c2, auth, cors)
├── database/
│   ├── migrations/               # Database schema & migrations
│   └── seeders/                  # Database seeding
├── public/
│   └── assets/js/                # Vue.js SPA bundles (statistics, fullcalendar, etc.)
├── resources/views/              # Blade templates
│   ├── modules/aml/             # AML views
│   └── client-portal/           # Client-facing views
├── routes/                       # Route definitions
│   ├── api.php                   # REST API routes (auth, KYC)
│   ├── b2c2.php                  # B2C2 FX trading routes
│   ├── fireblocks.php            # Fireblocks crypto custody routes
│   ├── web.php                   # Main web routes
│   └── channels.php              # Broadcast channels
├── Dockerfile                    # Container build config
├── docker-compose.yaml           # Multi-container orchestration
├── .env                          # Environment configuration
└── composer.json                 # PHP dependencies
```

---

## 🧩 Modules

### 1. 🔵 Bitpace (Crypto Trading)

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed

| Feature | Description |
|---------|-------------|
| Withdrawal Orders | Create withdrawal orders via `BitpaceMerchantApis` helper |
| Deposit Addresses | Generate deposit addresses through Bitpace API |
| Balance Check | Fetch currency balances from Bitpace |
| Order Price Lookup | Get pricing for withdraw/deposit operations |
| Travel Rules | Enable/disable travel rule compliance checks |

**Key Files:**
- `app/Helpers/BitpaceMerchantApis.php` — All Bitpace merchant API calls
- `routes/b2c2.php` — Routes: `/b2c2/balance`, `/b2c2/instrument`, `/b2c2/rfq`, `/b2c2/order`

**API Endpoints:**
```
GET    /balance/currency              → Get account balance
POST   /orders                        → Create withdrawal order
POST   /orders/prices                 → Get order pricing
GET    /customer/deposit/address      → Generate deposit address
GET    /customer/deposit/networks/{cc}→ Get supported networks for currency
```

### 2. 🔴 Fireblocks (Digital Asset Custody)

**Purpose:** Enterprise-grade digital asset custody, treasury management, and compliance.

| Feature | Description |
|---------|-------------|
| Deposit Addresses | Generate multi-sig deposit addresses with QR codes |
| Withdrawals | Execute crypto withdrawals through Fireblocks network |
| Webhook Verification | Validate incoming webhook signatures (V1 & V2) |
| Transaction Monitoring | Track on-chain transactions via webhooks |

**Key Files:**
- `app/Modules/Fireblocks/Controllers/DepositAddressController.php` — Deposit address management
- `app/Modules/Fireblocks/Controllers/WebhookController.php` — Webhook handling with signature validation
- `app/Modules/Fireblocks/Services/WithdrawalService.php` — Withdrawal execution logic
- `config/fireblocks.php` — Fireblocks SDK configuration

**API Endpoints:**
```
POST   /fireblocks/webhook            → Public webhook endpoint (signature verification)
GET    /fireblocks/deposit-addresses  → List deposit addresses
POST   /fireblocks/withdraw           → Initiate withdrawal
```

### 3. 🟢 B2C2 (Foreign Exchange Trading)

**Purpose:** Foreign exchange market data, RFQ (Request for Quote), and order management.

| Feature | Description |
|---------|-------------|
| RFQ (Request for Quote) | Request quotes from liquidity providers |
| Order Management | Create, update, cancel FX orders |
| Balance Tracking | View available FX balances |
| Instrument Lookup | Browse available currency pairs/instruments |

**Key Files:**
- `app/Modules/B2c2/Controllers/RFQController.php` — RFQ request handling
- `app/Modules/B2c2/Controllers/OrderController.php` — Order CRUD operations
- `app/Modules/B2c2/Controllers/BalanceController.php` — Balance queries (DB + API)
- `app/Modules/B2c2/Services/ClientService.php` — Guzzle-based HTTP client for B2C2 API
- `app/Modules/B2c2/Models/Rfq.php` — RFQ data model

**API Endpoints:**
```
GET    /b2c2/balance                  → Get balances (DB fallback)
GET    /b2c2/instrument               → List instruments/currency pairs
POST   /b2c2/rfq                      → Request quote
POST   /b2c2/order                    → Create/update order
```

### 4. 🟣 AML (Anti-Money Laundering)

**Purpose:** Compliance engine for KYC checks, transaction monitoring, and regulatory reporting.

| Feature | Description |
|---------|-------------|
| AML Rules Engine | Define and manage AML rule configurations |
| NeroData Integration | On-chain transaction analysis via NeroData API |
| Chainalysis Integration | Wallet address blockchain analytics |
| KYC Records | Manage client KYC documents and verification status |

**Key Files:**
- `app/Modules/Aml/Controllers/AmlRuleController.php` — AML rule management
- `app/Helpers/NeroDataHelper.php` — NeroData API integration for transaction analysis
- `app/Models/AmlData.php`, `AmlRule.php` — Data models

---

## 🔌 Third-Party API Integrations

### 1. Fireblocks (Digital Asset Custody)

| Detail | Value |
|--------|-------|
| **SDK** | [Credit-Investment-Bank/fireblocks-php-sdk](https://github.com/Credit-Investment-Bank/fireblocks-php-sdk) (VCS repo) |
| **Config File** | `config/fireblocks.php` |
| **Auth Method** | API Key + Secret from Fireblocks Console |
| **Base URL** | `https://api.fireblocks.io` |
| **Webhook Verification** | RSA V1 (raw base64 signature) and JWS V2 detached payload formats |

### 2. B2C2 (Foreign Exchange)

| Detail | Value |
|--------|-------|
| **Base URL** | `https://api.b2c2.com` |
| **Auth Method** | API Token (`B2C2_API_TOKEN`) |
| **HTTP Client** | Guzzle HTTP with retry logic (max 3 retries, 500ms delay) |
| **Timeout** | 30s connect / 10s timeout |

### 3. Bitpace (Crypto Merchant API)

| Detail | Value |
|--------|-------|
| **Auth Method** | Merchant Code + Password → JWT Token |
| **Config Source** | `module_configurations` table (`Bitpace` module) |
| **Environments** | Sandbox & Production URLs from config |
| **Callback Secret** | Used for KYC callback verification |

### 4. Alibaba Cloud OSS (Object Storage)

| Detail | Value |
|--------|-------|
| **Provider** | `BUCKET_PROVIDER=OSS` |
| **SDK** | `aliyuncs/oss-sdk-php` v2.7+ |
| **Credentials** | `OSS_ACCESS_KEY_ID`, `OSS_ACCESS_KEY_SECRET` from `.env` |

### 5. Chainalysis (Blockchain Analytics)

| Detail | Value |
|--------|-------|
| **API Endpoint** | `https://public.chainalysis.com/api/v1/address/{wallet_address}` |
| **Auth Method** | `X-API-Key` header with Chainalysis API key |
| **Use Case** | Wallet address risk scoring and transaction graph analysis |

### 6. NeroData (On-Chain Transaction Analysis)

| Detail | Value |
|--------|-------|
| **Integration** | Via `NeroDataHelper` service |
| **Use Case** | Real-time AML checks on crypto transactions, rule-based alerts |
| **Config** | Enabled/disabled per module via `module_configurations` table |

---

## 🔒 Security Flow

### Authentication Layers

```
┌─────────────────────────────────────────────────┐
│  Layer 1: API Key Validation                    │
│  (app/Http/Middleware/APIKeyMiddleware.php)      │
│  - Validates X-API-Key header for API routes     │
│  - Returns 403 if key is missing or invalid     │
└─────────────────────┬───────────────────────────┘
                      ▼
┌─────────────────────────────────────────────────┐
│  Layer 2: Laravel Sanctum Bearer Token          │
│  (auth:sanctum middleware)                       │
│  - Validates Authorization: Bearer {token}       │
│  - Generates tokens via /api/login POST          │
└─────────────────────┬───────────────────────────┘
                      ▼
┌─────────────────────────────────────────────────┐
│  Layer 3: Two-Factor Authentication (2FA)       │
│  (Check2FA + TwoFactorMiddleware)                │
│  - Google Authenticator for required roles      │
│  - Skips 2FA for basic clients / draft clients  │
│  - Session-based 2FA token validation           │
└─────────────────────┬───────────────────────────┘
                      ▼
┌─────────────────────────────────────────────────┐
│  Layer 4: Role-Based Authorization              │
│  (app/Models/User.php → roles, hasRole())       │
│  - Roles: admin, client, director, finance mgr  │
│  - IDOR protection via IDORHelper               │
│  - Client isolation per account                  │
└─────────────────────────────────────────────────┘
```

### Security Features

| Feature | Implementation |
|---------|---------------|
| **API Key Middleware** | Custom middleware validates `X-API-Key` header before route execution |
| **2FA (Google Authenticator)** | Optional 2FA enforced for specific roles via `Check2FA` and `TwoFactorMiddleware` |
| **Sanctum Bearer Tokens** | Token-based auth for API routes with rate limiting (`throttle:5,1`) |
| **Webhook Signature Verification** | Fireblocks webhooks validated against RSA V1 (base64) and JWS V2 detached payload formats |
| **IDOR Protection** | `IDORHelper::isAccountBelongToUser()` ensures users can only access their own accounts |
| **CORS Configuration** | Configurable CORS middleware (`config/cors.php`) for API endpoints |
| **Rate Limiting** | API routes use Laravel throttle (`throttle:5,1` on login) |
| **Input Validation** | Form requests validated with `Validator::make()` and custom rules (e.g., `SupportingDocumentsRule`) |
| **Error Logging** | All errors logged via Sentry integration (`config/sentry.php`) + Laravel log channel |

### Webhook Security (Fireblocks)

```
1. Fireblocks sends POST to /fireblocks/webhook
2. WebhookController extracts signature from request header
3. Validates against public key stored in DB (V1: raw base64 RSA, V2: JWS detached payload)
4. Verifies timestamp freshness (prevents replay attacks)
5. Only proceeds if signature is valid AND within time window
```

---

## 🔐 Authentication & Authorization

### User Roles

| Role | Description | 2FA Required? |
|------|-------------|---------------|
| **admin** | Full system access | Yes (if GOOGLE_AUTHENTICATOR enabled) |
| **client** | Client account operations | Conditional |
| **director** | Director-level approvals | Yes |
| **finance manager** | Finance management | Yes |
| **draft client** | Pending KYC clients | No |
| **basic client** | Basic access tier | No |

### Authentication Flow

1. User logs in via `POST /login` (API) or standard Laravel auth routes
2. Bearer token generated and stored in Sanctum storage
3. For API routes: `X-API-Key` header validated first, then Sanctum token
4. Role-based access controlled through `User::hasRole('roleName')`
5. 2FA enforced for admin/director/finance manager roles

### KYC Callback Integration

```
POST /api/vista-money/kyc-callback
→ Validates callback secret from Bitpace config
→ Updates KYC status in database
```

---

## 🗄️ Database Schema Overview

### Core Tables

| Table | Purpose |
|-------|---------|
| `users` | User accounts with roles, 2FA status, timestamps |
| `accounts` | Bank accounts linked to clients (TT, Cheque, Crypto) |
| `currencies` | Currency definitions (fiat + crypto), active/inactive flags |
| `clients` | Client company information |
| `client_kyc_records` | KYC documents and verification status |
| `b2c2_rfqs` | RFQ records for FX trading (`b2c2_rfqs` table) |
| `fireblocks_transactions` | On-chain transaction tracking |
| `aml_rules` | AML rule configurations |
| `aml_data` | AML analysis results |
| `module_configurations` | Per-module config (Bitpace, Fireblocks, etc.) |
| `module_configurations_configs` | Bit-specific module configuration key-value pairs |

---

## ⚙️ Configuration

### Key Config Files

| File | Purpose |
|------|---------|
| `config/fireblocks.php` | Fireblocks SDK base URL, API key/secret, webhook settings |
| `config/b2c2.php` | B2C2 FX API token, timeout, retry config |
| `config/auth.php` | Authentication guards (web + api), password reset |
| `config/cors.php` | CORS allowed origins for API endpoints |
| `config/sentry.php` | Error tracking with Sentry.io |

---

## 🚀 Deployment

### Docker Compose Setup

```yaml
# docker-compose.yaml
services:
  app:
    build: .
    ports:
      - "8000:80"
    environment:
      - APP_ENV=production
      - DB_CONNECTION=mysql
      # ... other env vars from .env
    depends_on:
      - db

  nginx:
    image: nginx:alpine
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/conf.d/default.conf
      - ./public:/var/www/html/public
```

### Dockerfile Highlights

- Multi-stage build (PHP + Nginx)
- Composer install, npm install, asset compilation via Vite
- Production-ready with `php artisan optimize`

---

## 📝 Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_NAME` | Application name | bank short name, e.g. `Monfi` or `CIB` |
| `BITPACE_APP_ENV` | Bitpace environment (local/production) | `local` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `OSS_ACCESS_KEY_ID` / `_SECRET` | Alibaba OSS credentials | — |
| `FIREBLOCKS_BASE_URL` | Fireblocks API endpoint | `https://api.fireblocks.io` |
| `B2C2_API_TOKEN` | B2C2 FX trading token | — |
| `GOOGLE_AUTHENTICATOR` | Enable 2FA for all roles | `true/false` |

---

## 🛠️ Development Setup

### Prerequisites

- PHP ^7.3 or ^8.0
- Composer
- Node.js + npm
- MySQL (or Docker)
- Docker & Docker Compose (for containerized setup)

### Quick Start

```bash
# Clone and install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env  # or edit existing .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Compile frontend assets (development)
npm run dev

# Start Docker containers
docker-compose up -d
```

### Useful Artisan Commands

```bash
php artisan serve                    # Start local development server
php artisan route:list               # List all registered routes
php artisan vendor:publish --tag=config   # Publish config files
php artisan optimize                  # Optimize for production
```

---

## 📊 Key Controllers Index

| Controller | File | Purpose |
|-----------|------|---------|
| `TRRequestController` | `app/Http/Controllers/TRRequestController.php` | Transaction requests (TT, Cheque, Internal) |
| `CryptoTransactionController` | `app/Http/Controllers/CryptoTransactionController.php` | Crypto deposit/withdrawal operations |
| `BitpaceController` | `app/Http/Controllers/BitpaceController.php` | Bitpace merchant API wrapper |
| `KYCController` | `app/Http/Controllers/API/KYCController.php` | KYC callback handling |
| `LogoutController` | `app/Http/Controllers/LogoutController.php` | Session cleanup on logout |
| `RFQController` | `app/Modules/B2c2/Controllers/RFQController.php` | FX quote requests |
| `OrderController` | `app/Modules/B2c2/Controllers/OrderController.php` | FX order management |
| `BalanceController` | `app/Modules/B2c2/Controllers/BalanceController.php` | Balance queries (DB + API) |
| `DepositAddressController` | `app/Modules/Fireblocks/Controllers/DepositAddressController.php` | Deposit address generation |
| `WebhookController` | `app/Modules/Fireblocks/Controllers/WebhookController.php` | Fireblocks webhook handling |

---

## 📄 License

Proprietary software. Same codebase; bank-specific Own Company + env per deploy.
