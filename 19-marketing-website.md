# PROMPT 19 — Marketing website (full, bilingual Arabic + English)

> Re-paste `00-MASTER-CONTEXT.md` before running this prompt.
> This builds the **public-facing marketing website** that sells Wakeel and funnels
> visitors into the registration flow (prompt 02). It is bilingual from day one and
> reuses the localization engine from **prompt 18** (locale resolution, lang files,
> RTL/LTR). Build it after prompt 18 so the i18n plumbing already exists; if built
> earlier, implement a minimal `ar/en` session switcher inline and migrate to prompt
> 18's system later.

> **Decision (stated):** the site lives as **public routes in the same Laravel app**
> (Blade + Tailwind + Alpine), so "ابدأ مجاناً / Start free" CTAs go straight to
> `/register` with no cross-domain friction and the whole thing shares one deploy. (If
> you later want it fully decoupled/static, the same content/structure ports cleanly.)

---

**Goal:** A fast, modern, conversion-focused marketing site that explains the product to
a non-technical real-estate developer, builds trust, and drives sign-ups — fully Arabic
(RTL) and English (LTR), switchable, and SEO-ready in both languages.

---

## A. Audience & message

- **Primary visitor:** owner/marketing manager at an Egyptian/Arab real-estate developer.
  Not technical. Cares about: more qualified leads, faster replies, no missed WhatsApp
  messages at night, less wasted sales-rep time.
- **Core promise (hero):** "وكيل مبيعات بالذكاء الاصطناعي يرد على عملائك على واتساب،
  يأهّلهم، ويحجزلهم المعاينات — ٢٤ ساعة." / "An AI sales agent that answers your leads on
  WhatsApp, qualifies them, and books viewings — 24/7."
- Tone: confident, clear, benefit-led (outcomes, not tech jargon). Mention "AI Agent"
  and "WhatsApp" plainly; avoid hype.

---

## B. Pages & routes (all bilingual)

1. **Home `/`** — the main landing page (sections in §C).
2. **Features `/features`** — deeper dive on each capability (answers inquiries, sends
   unit media, books viewings, lead scoring, human handoff, dashboard & analytics,
   follow-ups).
3. **How it works `/how-it-works`** — the customer journey + the company onboarding
   ("live in 5 minutes"), shown as simple visual steps.
4. **Pricing `/pricing`** — the three plans from master-context (Starter / Growth /
   Enterprise) with a monthly/annual toggle, feature comparison table, and an FAQ on
   billing. CTA per plan → `/register?plan=...`.
5. **About `/about`** — short story, mission, who it's for.
6. **Contact `/contact`** — a contact form (name, company, email, phone, message) +ا
   WhatsApp "talk to us" link (dogfood: route it to a demo Wakeel number) + email. Form
   posts server-side, validated, stored and/or emailed; success/error states localized.
   Treat this as a normal contact form — do NOT auto-send anything on the visitor's
   behalf.
7. **Legal:** `/privacy` and `/terms` (bilingual, real placeholder copy with clear
   structure to fill in).
8. *(optional)* **Blog/Resources `/blog`** — simple index + article view, bilingual,
   for SEO/content marketing. Mark optional; scaffold only if quick.

All routes resolve locale via prompt-18 middleware; provide localized URL handling and a
visible `ar/en` switcher in the header that preserves the current page.

---

## C. Home page sections (in order)

1. **Sticky header** — logo (Wakeel / وكيل), nav (Features, How it works, Pricing,
   About, Contact), `ar/en` switcher, "تسجيل الدخول / Log in", and a primary
   "ابدأ مجاناً / Start free" button.
2. **Hero** — headline + subhead (the core promise), primary CTA (→ `/register`) +
   secondary "شوف عرض / See demo". A visual: a WhatsApp-style chat mockup showing the
   agent qualifying a buyer and offering a viewing (you can adapt the chat UI we
   designed). Trust line: "يرد في أقل من ٣٠ ثانية".
3. **Problem → Solution** — a short "before/after": missed messages, slow replies,
   wasted rep time → instant 24/7 AI agent that only escalates serious buyers.
4. **Key features grid** — 6 cards (smart catalog, viewing booking, lead qualification,
   media on WhatsApp, auto follow-up, handoff to sales) — icon + benefit headline +
   one line each.
5. **How it works** — 3–4 steps for the company ("سجّل → أضف وحداتك → شارك رقمك →
   البوت يشتغل") and a peek at the customer chat experience.
6. **Live conversation showcase** — an animated/illustrative WhatsApp chat demonstrating
   a real qualify→media→book flow (Arabic and English variants).
7. **Dashboard preview** — a clean screenshot/illustration of the dashboard (leads, hot
   tiers, viewings, analytics) with 3 supporting captions.
8. **Outcomes / stats band** — "٨٥٪ من الاستفسارات يردها البوت", "رد < ٣٠ث", "٣× معاينات
   محجوزة", "٢٤/٧" (frame as illustrative/typical, not guaranteed).
9. **Pricing teaser** — 3 plan cards summarized → link to `/pricing`.
10. **FAQ** — accordion: Do I need WhatsApp Business API? (no — we provide the number),
    Does it speak Egyptian Arabic? (yes), Can a human take over? (yes), Is my data
    private/secure?, How fast to go live? (~5 min), Can it handle English-speaking
    buyers? (yes).
11. **Final CTA band** — strong close + "ابدأ مجاناً" button.
12. **Footer** — nav, legal links, contact, social, language switcher, copyright.

---

## D. Bilingual + RTL/LTR (reuse prompt 18)

1. All copy lives in `lang/ar/marketing.php` and `lang/en/marketing.php` (identical
   keys; key-parity test as in prompt 18). No hard-coded strings.
2. `<html lang dir>` flips per locale; build every section with Tailwind **logical
   properties** so the layout mirrors cleanly in RTL. Directional icons mirror.
3. The Arabic and English versions are **equally polished** — Arabic is not a
   second-class translation. Use a strong Arabic web font (e.g. an IBM Plex Arabic / Cairo
   / Tajawal class) and a clean Latin font for English; load both, switch per locale.
4. Language switcher keeps the visitor on the same page and remembers the choice.

---

## E. Design direction

- Modern SaaS aesthetic, generous whitespace, clear hierarchy, strong CTAs. Mobile-first
  and fully responsive (most Egyptian traffic is mobile).
- A defined visual system: primary brand color, accent, neutral scale, consistent
  spacing/radius/typography scale. Reuse the WhatsApp-green association tastefully
  without copying WhatsApp's brand.
- Subtle, performant motion only (scroll-reveal, the chat demo animation). No heavy
  libraries; prefer CSS/Alpine. Respect `prefers-reduced-motion`.
- Accessibility: semantic HTML, alt text (localized), keyboard-navigable nav/accordion/
  switcher, sufficient contrast in both themes.

> If a frontend-design skill/system is available in the environment, follow it for
> tokens, type scale, and component styling rather than ad-hoc values.

---

## F. SEO, performance, analytics

1. **Bilingual SEO:** per-locale `<title>`/meta description (from lang files),
   Open Graph/Twitter cards (localized), `hreflang` tags linking `ar`/`en` versions,
   canonical URLs, a localized `sitemap.xml`, and `robots.txt`. JSON-LD structured data
   (Organization, Product/SoftwareApplication, FAQ).
2. **Performance:** optimized/responsive images (WebP, lazy-load), minimal JS, cached
   marketing routes, Lighthouse target ≥ 90 on mobile for Performance/SEO/Accessibility.
3. **Analytics & conversion:** pluggable analytics (env-configurable), track CTA clicks
   and the register funnel entry; consent-aware (privacy-friendly default).
4. Every CTA path to `/register` works and (where relevant) preselects the plan via
   query param consumed by prompt 02's registration.

---

## G. Wiring to the app

- Header CTAs → `/register` (and `/login`). Pricing CTAs pass `?plan=`.
- Contact form: validated, rate-limited, stored (a `contact_messages` table) and/or
  emailed to the platform owner via the prompt-18 localized mail; show localized
  success. No third-party sends on the visitor's behalf.
- The site is publicly cached and must NOT leak any tenant data — it's 100% public,
  no `company_id` scope involved.

---

**Tests:**
- Each route renders 200 in both locales; switcher flips text + `dir` and stays on page.
- `marketing` lang files have identical `ar`/`en` keys (build fails otherwise).
- Hero/pricing CTAs land on `/register` (with plan param where set).
- Contact form: validation, persistence/email, localized success/error, rate limiting.
- `hreflang`, canonical, localized titles/meta, and sitemap present and correct.
- Basic responsive/RTL smoke (no horizontal overflow; nav/accordion work via keyboard).

**Done when:** a polished, fast, bilingual marketing site is live on public routes,
mirrors correctly in Arabic RTL and English LTR, ranks-ready (SEO/hreflang/structured
data), and reliably funnels visitors into registration.
