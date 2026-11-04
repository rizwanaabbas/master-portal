# Unification record

| Source | Remote | `dev` tip used |
|--------|--------|----------------|
| Base (kept all Monfi fixes) | `Monfi-Bank/monfi-portal` | `1cab8fe0` |
| Gap-fill from CIB | `Credit-Investment-Bank/cib-portal` | `9c778f9` |

## What was kept from Monfi

All application diffs vs CIB at unify time, including:

- Fireblocks external-wallet withdraw / whitelist resolution
- Transaction monitoring enhancements
- Webhook API `created_by` / beneficiary validation
- Crypto management route permission cleanup
- Login logo sizing (`public.blade.php`)
- DataTables beneficiary search fix
- EmailNotification null-safe sender name

## What was brought from CIB

- `.env` / secret ignore patterns in `.gitignore`
- Optional Teams notify on staging workflow (when `TEAMS_WEBHOOK_URL` is set)
- Bank-agnostic CI image tagging (same idea as CIB/Monfi workflows, without hardcoded org)

## Intentionally not bank-forked

Own Company branding, theme CSS, PDF/email templates, OwnCompany controller — shared code for every bank.
