# Bank branding via Own Company (portal-master)

This repository is the **unified admin portal** for every bank deployment (CIB, Monfi, and future banks).  
Do **not** fork the app for logos or colors. Configure each bank from the database.

## Where to configure

**Settings → Own Company** (`/settings/own-company-details`)  
Table: `own_companies` (single row per deployment)  
Helper: `App\Helpers\Common::getOwnCompanyData()` (shared to all Blade views as `$ownCompany`)

## Fields that drive branding

| Field | Used for |
|-------|----------|
| `name`, `short_name` | Page titles, footers, email sign-off, PDF headers |
| `address`, `country`, `license_number`, `contact_number` | PDF / legal / contact blocks |
| `logo` | Portal sidebar, header, login bar, email headers, PDF logos |
| `favicon` | Browser tab icon |
| `client_portal_light_logo`, `client_portal_dark_logo` | Client portal (separate app) light/dark themes |
| `stamp`, `watermark` | Balance certificates & statement PDFs |
| `login_page_image` | Auth/login page background (falls back to default art if empty) |
| `theme` | CSS file under `public/assets/css/themes/` (e.g. `cust-theme-14.css`) |
| `card_colors` | JSON array of Bootstrap utility classes for dashboard cards |
| `chart_color` | Hex color for dashboard charts |
| `poc_name`, `poc_email`, `support_team_emails`, `tech_email`, `chairperson_email` | Emails & support copy |
| `app_store_url`, `play_store_url`, `find_us`, `terms_and_conditions_url`, `client_manual` | Register / help / legal links |
| `account_number_prefix` | Generated account numbers |
| `show_introducer_field`, `maintenance_fee_job_enabled`, `max_file_size` | Behaviour toggles |

## Theme color presets

Set `theme` to one of:

| File | Primary |
|------|---------|
| `cust-theme-1.css` | `#b56a9f` |
| `cust-theme-3.css` | `#4679cc` |
| `cust-theme-4.css` | `#045AB5` |
| `cust-theme-14.css` | `#ffc800` (common default) |
| … | See `public/assets/css/themes/` |

## Per-bank deploy (not in Own Company)

Keep these **outside** git / per environment:

- `.env` — `APP_NAME`, `APP_URL`, `MAIL_*`, AWS/S3, DB, third-party keys
- Firebase / other JSON credentials under `storage/`
- GitHub Actions variables: `ORG_FOLDER`, `ADDITIONAL_CHARTS`
- Module enablement & module `configurations` rows in DB

## New bank checklist

1. Deploy this same `portal-master` tag/image.
2. Point `.env` at that bank’s DB, mail, and S3.
3. Create/update the single `own_companies` row (logos uploaded to that bank’s S3).
4. Set `theme` / `card_colors` / `chart_color`.
5. Enable modules required for that bank.
6. Set Actions `ORG_FOLDER` if using the release workflow with helm charts.
