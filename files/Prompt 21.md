# PROMPT 21 — Grounding & anti-hallucination (answers ONLY from company data)

> Re-paste `00-MASTER-CONTEXT.md` before running this prompt.
> This hardens the agent so **every factual claim about properties — units, prices,
> availability, areas, locations, payment plans — comes only from the company's own
> database**, never from the model's general knowledge or invention. It builds on the
> tools (prompt 07), the agent runner (prompt 08), and the system prompt (prompt 06).
> Run after the agent works end-to-end.

> **Update the master context:** add a "Grounding" rule to §3/§4 — the agent is
> **retrieval-grounded**: it must not state any property fact that wasn't returned by a
> tool, and unsupported claims are blocked/regenerated, not sent.

---

**Goal:** The bot is a faithful spokesperson for the company's inventory. If the data
isn't in `units` / `unit_media`, the bot does not assert it — it says it doesn't have
that info and offers to check or connect a human. No invented prices, no phantom units,
no guessed availability. This is enforced by **three layers working together**, not by
the prompt alone (prompts can be ignored by the model; layers 2 and 3 cannot).

---

## Layer 1 — Instruction (necessary, not sufficient)

Extend `SystemPromptBuilder` (prompt 06) with a strict, explicit **grounding policy** in
Arabic. The model must be told, unambiguously:

1. It may state property facts **only** from the JSON returned by `search_properties`
   (and the other tools). It must **call `search_properties` before** quoting any price,
   area, room count, location, availability, or payment plan.
2. It must **never invent or estimate** a unit, price, discount, delivery date, or
   availability. If asked about something not in the returned data, it says (in Egyptian
   Arabic) it doesn't have that unit/info right now and offers to check or connect a
   sales rep — it does NOT guess.
3. It must not rely on general/real-estate "world knowledge" for company-specific facts
   (e.g. typical market prices). Company facts come from tools only.
4. When it presents a unit, it should reference the unit by its returned identifier so
   later layers can verify what it claimed.
5. It must not promise anything not represented in the data (no "we can do a special
   discount" unless that's a real, returned field).

Provide a couple of in-prompt examples (good: "ماعنديش وحدة بالمواصفات دي حالياً، تحب
أوصّلك بأحد مستشارينا؟" / bad: inventing a price).

---

## Layer 2 — Retrieval discipline (the tools enforce truth)

Make the tools the **only** source of property facts and make their output the ground
truth the agent is bound to.

1. **`search_properties` returns structured, complete, ID-stamped records** (prompt 07):
   each unit as `{unit_id, title, rooms, area, price, location, down_payment,
   installment_years, status, has_media}`. Always include `unit_id`. Never return
   free-text the model could misread as license to embellish.
2. **Empty results are explicit:** when nothing matches, return a clear machine-readable
   "no_matches" signal (not an empty string), so the model is steered to the honest
   "I don't have that" path instead of filling the gap.
3. **Scope is absolute:** every tool query is tenant-scoped (`company_id`) and filtered to
   sellable inventory (`status = available`) for offers. A reserved/sold unit must not be
   presented as available (re-confirm the prompt-11 filter; add a test).
4. **No fact without a tool:** `calculate_installment` must use real `price`/terms (not a
   number the model typed); `send_unit_media` only sends media that exists for a real,
   company-owned `unit_id`. Reject tool calls referencing a `unit_id` outside the company
   (return an error string the model must relay honestly).
5. **Record what was retrieved:** in the agent run, capture the set of `unit_id`s and the
   exact field values returned by tools during this turn — this becomes the "allowed
   facts" set used by Layer 3.

---

## Layer 3 — Verification (catch hallucinations before they send)

A post-generation **grounding check** in `AgentRunner` (prompt 08), applied to the final
assistant text before it's dispatched to WhatsApp:

1. **Claim extraction & matching:** from the captured retrieved records (Layer 2), verify
   that any **specific property facts in the reply** are consistent with retrieved data:
   - Numeric facts (prices, monthly installments, areas, room counts, years) mentioned in
     the reply must match a retrieved value (allow formatting/rounding tolerance you
     define) or a `calculate_installment` result.
   - Any unit the reply describes must correspond to a retrieved `unit_id`.
   Implement deterministically where feasible (e.g. scan for numbers/prices and check
   them against the allowed-facts set). Keep it robust to Arabic/Western digits and EGP
   formatting.
2. **On mismatch (a likely hallucination):**
   - Do **not** send the unsupported reply.
   - **Regenerate once** with a corrective instruction ("استخدم فقط البيانات التي رجعت من
     الأدوات؛ لا تذكر أي سعر أو وحدة غير موجودة") and re-verify.
   - If it still fails → send a **safe grounded fallback** ("اسمحلي أتأكد من التفاصيل دي
     وأرجعلك" or hand to a rep) and **log a grounding violation** (company, conversation,
     model, the offending text, the allowed facts) for review.
3. **Optional LLM-as-judge (cheap, secondary):** for fuzzy/long replies where
   rule-based matching is weak, a small secondary check ("is every property fact in this
   reply supported by this JSON? yes/no + offending claim") can gate sending. Keep it
   off the hot path where deterministic checks suffice; make it config-toggleable for
   cost.

---

## Observability & admin

1. **Grounding violations log:** a table/record of every blocked or regenerated reply
   (company, conversation, model used, original text, matched/unmatched facts, action
   taken). Surface in the Filament admin (prompt 16): per-company hallucination rate,
   recent violations, and which model produced them (ties into prompt 20's model mix —
   useful to compare Claude vs GPT grounding quality).
2. **Metrics:** track grounding-violation rate as a quality KPI; alert if a company's
   rate spikes (often a sign of sparse inventory data pushing the model to improvise).

---

## Data-quality companion (reduce the pressure to hallucinate)

Hallucination often rises when inventory is thin or fields are empty. So:
1. In the units UI (prompt 11), encourage complete records (price, area, rooms, location,
   payment terms, media) — flag units missing key fields.
2. If `search_properties` returns sparse fields, the agent must still only state what's
   present and say "غير متاح حالياً" for the rest — never fill blanks.

---

**Tests:**
- **No-match honesty:** query with no matching units → tool returns `no_matches` → the
  reply offers to check / escalate and contains **no** invented unit or price (assert).
- **Fabricated-number block:** force a model reply that states a price not in retrieved
  data (faked Prism) → Layer 3 blocks it, regenerates, and either corrects or sends the
  safe fallback; a violation is logged.
- **Cross-tenant rejection:** a tool call / reply referencing another company's `unit_id`
  is rejected and not presented.
- **Reserved/sold exclusion:** a sold unit is never offered as available.
- **Installment integrity:** quoted monthly payment matches `calculate_installment` on
  real price/terms; a mismatched number is blocked.
- **Digit/format robustness:** verification matches values whether the model used
  Arabic-Indic or Western digits and regardless of EGP formatting.
- **Parity across models:** run the block/regenerate test on both Claude and GPT
  (prompt 20) — both are held to the same grounding bar.

**Done when:** the agent provably cannot send a property fact that isn't backed by the
company's data — unsupported claims are blocked and regenerated or replaced with an
honest fallback, every violation is logged and visible to the admin, and the behavior
holds across both models and across tenants.
