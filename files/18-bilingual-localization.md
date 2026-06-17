# PROMPT 18 — Bilingual platform (Arabic + English) with RTL/LTR

> Re-paste `00-MASTER-CONTEXT.md` before running this prompt.
> This prompt makes the **entire platform** bilingual: company dashboard, the WhatsApp
> bot's replies, the Filament super-admin, and all transactional emails. Language is
> resolved with a **two-level rule: a company default + a per-user override.**
> Run it after the surfaces it localizes exist (i.e. after prompts 10–16). It then
> applies retroactively to everything built so far.

> **Update the master context:** revise §9 — the dashboard, admin, emails, and bot
> output are **bilingual (ar / en)**, not Arabic-only. Code/identifiers stay English.
> Direction follows locale: `rtl` for Arabic, `ltr` for English.

---

**Goal:** Anyone using the platform — a company owner, a sales rep, the platform
super-admin, and the end customer on WhatsApp — experiences it in their language. Arabic
is RTL, English is LTR, and switching is seamless and remembered.

---

## A. Language resolution (the two-level rule)

1. **Company default locale** — add `default_locale` (enum `ar | en`, default `ar`) to
   `companies`. Set during onboarding (prompt 12) with an `ar / en` choice. This is the
   fallback for every user in that company and the default for that company's bot.
2. **Per-user override** — add `locale` (enum `ar | en`, nullable) to `users`. When
   null → inherit the company default. A language switcher in the dashboard header lets
   each user pick their own; the choice persists on their user record.
3. **Resolution order** at request time (a `SetLocale` middleware): authenticated user's
   `locale` → else company `default_locale` → else app fallback (`ar`). The middleware
   sets `App::setLocale()` and exposes the current `dir` (`rtl`/`ltr`) to all views.
4. Guests (login/registration/marketing) get a simple `ar/en` switcher that stores the
   choice in the session so the pre-auth screens are bilingual too.

---

## B. Translation infrastructure

1. Use Laravel's localization. Create parallel files under `lang/ar/` and `lang/en/`
   (e.g. `dashboard.php`, `leads.php`, `units.php`, `bot.php`, `billing.php`,
   `validation.php`, `auth.php`, `emails.php`, `admin.php`). Mirror keys exactly across
   both locales.
2. **Replace every hard-coded UI string** introduced in prompts 02–16 with a `__()` /
   `@lang` translation key. Audit each Livewire component, Blade view, Filament label,
   notification, and validation message. No literal user-facing text left in code.
3. Provide a key-coverage test: assert `lang/ar` and `lang/en` have identical key sets
   (fail the build if a key exists in one and not the other).
4. Localize formatting too: dates/times (Africa/Cairo display, localized month/day
   names), numbers, and EGP currency formatting per locale (Arabic-Indic vs Western
   digits — pick a documented convention; default to Western digits in both for
   consistency unless the company opts into Arabic-Indic).

---

## C. RTL / LTR layout

1. Drive direction from the resolved locale: `<html lang="{{ app()->getLocale() }}"
   dir="{{ $dir }}">`.
2. Use Tailwind's logical properties / RTL plugin so the same markup flips correctly
   (use `ms-*/me-*`, `ps-*/pe-*`, `text-start/text-end` instead of hard left/right).
   Audit the prompts 10–13 dashboards, the prompt-12 settings, and the prompt-10 chat
   panel for any hard-coded `left/right`.
3. Icons/chevrons that imply direction must mirror in RTL. Charts and tables read
   correctly in both directions.
4. The language switcher in the header swaps both translations and direction live
   without a broken layout.

---

## D. The WhatsApp bot — per-conversation language

The bot must answer each customer in the customer's language, independent of the
dashboard user's locale.

1. Add `locale` (`ar | en`, nullable) to `leads` (and/or the conversation). Default to
   the company's `default_locale`.
2. **Auto-detect** the customer's language from their first message(s) (simple, cheap
   heuristic: Arabic script vs Latin script; store the detection). If the customer
   writes in English, the bot replies in English; if Arabic (incl. Egyptian colloquial),
   it replies in Arabic. If mixed/ambiguous, fall back to company default.
3. Feed the resolved conversation locale into the **`SystemPromptBuilder`** (prompt 06):
   instruct the model to respond in that language and adopt the matching tone. Keep the
   persona/tone settings working in both languages (provide tone guidance for English
   too: friendly, formal).
4. Localize bot-side **fixed strings** (the prompt-08 error fallback, the prompt-10
   escalation/closing messages, prompt-14 follow-up templates) via the `bot.php` lang
   files, selected by the conversation locale.
5. WhatsApp **template messages** (prompt 14, outside the 24h window) must exist in both
   `ar` and `en` approved versions; pick the template language by the lead's locale.

---

## E. Emails & notifications

1. Localize all transactional emails (welcome/onboarding, billing receipts & dunning,
   quota warnings from prompt 15, team invites) using `lang/*/emails.php`. Render each
   email in the **recipient's** resolved locale with correct direction in the HTML.
2. In-app + broadcast notifications (handoff alerts, new-lead pings) localize to the
   receiving user's locale.

---

## F. Filament super-admin (prompt 16)

1. Make the `/admin` panel bilingual too: enable Filament's locale support, translate
   resource labels/columns/actions via `lang/*/admin.php`, and add an `ar/en` switcher.
   Super-admin direction follows the chosen locale.

---

**Tests:**
- Locale resolution: user override beats company default beats app fallback.
- Key parity: `ar` and `en` lang files have identical keys (build fails otherwise).
- A user switching language re-renders dashboard text and flips `dir` correctly.
- Bot replies in English to an English customer and Arabic to an Arabic customer in the
  same company, simultaneously (assert via `SystemPromptBuilder` output + faked Prism).
- Emails render in the recipient's locale.
- No hard-coded user-facing string remains (grep/audit test for literal Arabic/English
  in Blade/Livewire/Filament outside lang files).

**Done when:** every surface — dashboard, bot, admin, emails — is fully Arabic/English,
direction-correct, with company-default + per-user override working, and the customer
always hears the bot in their own language.
