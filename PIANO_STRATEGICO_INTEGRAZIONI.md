# MetaBorghi — Piano Strategico Integrazioni v1.1
> InnTour S.R.L. | Data: 18 Marzo 2026 | Branch: `claude/metaborghi-strategic-plan-KHSDV`
> Basato su: Blueprint v3 DEFINITIVO + Analisi stack corrente
> **AGGIORNAMENTO v1.1**: Deployment su Hostinger Cloud (non Vercel)

---

## Executive Summary

Il progetto MetaBorghi dispone di una **base funzionante** (PHP 8+/MySQL/React-Vite, 25 borghi, admin panel CRUD, API REST) e di una **visione strategica completa** (Blueprint v3: Next.js 14, PostgreSQL, Payload CMS, AI Cicerone, Transport Layer, Blockchain). Questo piano definisce **come integrare progressivamente** le due realtà — senza riscrittura big-bang — seguendo una strategia di migrazione incrementale per fasi.

### Principio guida
**Non buttare, costruire sopra.** Il sistema PHP attuale rimane in produzione e genera valore. La nuova architettura cresce parallelamente e assorbe il legacy per moduli successivi, con feature flag per il cutover controllato.

### Decisione Deployment — Hostinger Cloud
La piattaforma Next.js viene deployata su **Hostinger Cloud Hosting** (non Vercel), con il seguente stack:
- **Runtime**: Node.js 20 LTS + PM2 (process manager)
- **Reverse proxy**: nginx (SSL + static cache + gzip)
- **Database**: MySQL esistente (shared hosting) → PostgreSQL in Fase V2 per pgvector/AI
- **CI/CD**: GitHub Actions → SSH deploy su Cloud
- **Legacy**: PHP su shared hosting resta attivo durante transizione (Strangler Fig)

| Aspetto | Vercel (Blueprint originale) | Hostinger Cloud (Piano adattato) |
|---|---|---|
| Deploy | Automatico su git push | GitHub Actions → SSH + PM2 reload |
| Edge Runtime | Nativo | Non disponibile — Node.js standard (sufficiente) |
| Preview deploys | Automatico per PR | Staging manuale o subdomain deploy |
| Image Optimization | CDN Vercel | `sharp` + nginx cache (equivalente) |
| Scaling | Serverless auto | Risorse fisse piano Cloud (adeguato per 25 borghi) |
| SSL | Automatico | Hostinger incluso / Let's Encrypt |
| ISR | Edge cache globale | Node.js cache + nginx proxy_cache |
| Costo | €0-20/mese | Incluso nel piano Cloud esistente |

---

## 1. Stato Attuale vs Target Architetturale

| Layer | Stato Attuale | Target (Blueprint v3) | Gap |
|-------|--------------|----------------------|-----|
| **Frontend** | React (Vite) SPA, dati JS statici | Next.js 14 App Router + TypeScript + ShadCN | Alto |
| **Backend** | PHP 8+ REST API + session admin | Next.js Server Actions + NestJS micro (Fase 3+) | Alto |
| **Database** | MySQL 8.0 (Hostinger shared) | MySQL 8.0 + Drizzle ORM (→ PostgreSQL in V2 per pgvector) | Basso |
| **CMS/Admin** | PHP server-rendered (Tailwind dark) | Payload CMS v3 (same-process Next.js) | Alto |
| **Auth** | Bearer token API + Session PHP admin | NextAuth v5 + JWT RBAC 4 livelli | Alto |
| **Mappe** | Leaflet.js | MapLibre GL JS + Protomaps PMTiles offline | Medio |
| **Deploy** | Hostinger shared hosting | Hostinger Cloud (Node.js + PM2 + nginx) | Medio |
| **Dati** | Export strategy: DB → file JS statici | SSR/ISR + Payload CMS Local API | Alto |
| **E-commerce** | Scheletro carrello (non operativo) | MedusaJS v2 + Mercur + Stripe Connect | Alto |
| **AI/Tour** | Non presente | 3DVista + geoxp + Cicerone RAG + ElevenLabs | Assente |
| **Transport** | Non presente | Amadeus + Omio + Rentalcars | Assente |
| **i18n** | Solo italiano | IT / EN / dialetto irpino (next-intl v4+) | Assente |
| **Testing** | Nessuno | Vitest + Playwright + Hardhat | Assente |
| **Analytics** | Nessuno | Umami cookieless + PostHog + Sentry | Assente |
| **Blockchain** | Non presente | Polygon Amoy heritage certification | Assente |
| **Governance** | Non presente | Decidim + Pol.is + SPID/CIE | Assente |

### Asset da preservare e migrare
- ✅ Schema dati consolidato (25 borghi, 14 aziende, 15 esperienze, 7 artigianato, food, ospitalità, ristorazione)
- ✅ API REST funzionanti (`/api/v1/*.php`)
- ✅ Admin panel CRUD completo per tutte le entità
- ✅ Seed files con dati di produzione (`seed_all.php`)
- ✅ Logica export DB → JS statici (riutilizzabile come ISR/on-demand revalidation in Next.js)
- ✅ Identità brand (verde `#00D084`, ciano `#00B4D8`, giallo neon `#F0FF00`)

---

## 2. Strategia di Migrazione — Approccio "Strangler Fig"

La migrazione segue il pattern **Strangler Fig**: la nuova piattaforma sostituisce progressivamente il sistema legacy route per route, con il sistema PHP che rimane il backend dati per i moduli non ancora migrati.

```
[Fase 0-1] PHP/MySQL → continua a servire i dati
           Next.js → proxy alcune route, nuove feature

[Fase 2-3] Migrazione dati MySQL → PostgreSQL (Neon)
           API PHP → Next.js Server Actions

[Fase 4+]  PHP legacy dismesso
           Sistema completamente su Next.js/Neon/Vercel
```

### Coesistenza temporanea via API proxy
Il frontend Next.js, nelle prime fasi, chiama le API PHP esistenti tramite Server Actions:

```typescript
// app/actions/boroughs.ts — chiama API PHP esistente
export async function getBoroughs() {
  const res = await fetch(process.env.PHP_API_BASE + '/api/v1/boroughs.php', {
    next: { revalidate: 3600 } // ISR ogni ora
  })
  return res.json()
}
```

Questo evita di dover migrare tutti i dati il giorno 1 e permette go-live rapido del frontend Next.js.

---

## 3. Roadmap di Integrazione — 15 Mesi

### FASE 0 — Infrastruttura Base (Mese 1)
**Obiettivo:** Scaffold Next.js operativo che convive con il sistema PHP attuale.

**Deliverable:**
- [ ] Monorepo setup: Next.js 14 App Router + TypeScript strict + TailwindCSS + ShadCN UI
- [ ] Scaffold da ARKA (pjborowiecki) come base architetturale
- [ ] next-intl v4+ configurato: `it` (default), `en`, `irp` (dialetto irpino)
- [ ] Routing: `/[locale]/borghi/[slug]`, `/[locale]/esperienze/[slug]`
- [ ] NextAuth v5 con 4 ruoli: Guest / Ospite Registrato / Operatore Borgo / Admin Comune
- [ ] Drizzle ORM + PostgreSQL Neon (schema parallelo a MySQL esistente)
- [ ] Payload CMS v3 (same-process) — admin panel moderno in sostituzione progressiva del PHP admin
- [ ] Arcjet + Nosecone CSP configurati
- [ ] Infisical per secrets management (rimpiazza credenziali hardcoded in `db.php`)
- [ ] Vitest setup + CI/CD Vercel (ESLint → TypeCheck → Vitest → Build → Deploy)
- [ ] Umami analytics self-host (zero cookie banner)
- [ ] Sentry setup automatico (`npx @sentry/wizard -i nextjs`)

**Effort stimato:** 60-80 ore
**Output:** Deploy su Vercel (preview), connesso a PHP backend via proxy

---

### FASE 1 — Discovery & SEO (Mesi 2-3)
**Obiettivo:** Catalogo borghi live su Next.js, SEO ottimizzato, Lacedonia come borgo pilota.

**Integrazioni da PHP:**
- `GET /api/v1/boroughs.php` → consumato da Server Actions Next.js con ISR
- `GET /api/v1/experiences.php` → idem
- `GET /api/v1/companies.php` → idem

**Deliverable:**
- [ ] Pagine borghi con Listing Cards (pattern Chisfis — 8+ tipologie)
- [ ] Pagina dettaglio borgo: Lacedonia pilota completo
- [ ] MapLibre GL JS + Protomaps PMTiles (tile offline Alta Irpinia, ~€3/mese su R2)
- [ ] Schema.org JSON-LD: `TouristDestination`, `TouristAttraction`, `LodgingBusiness`, `Event`
- [ ] Sitemap dinamica multi-locale (25 borghi × 3 locali = 75 URL + esperienze)
- [ ] OG Image generation via `next/og` per ogni borgo (hero image + nome localizzato)
- [ ] Leaflet.js → MapLibre: migrazione mappa esistente
- [ ] Metadata API Next.js 14 (niente next-seo — use `generateMetadata()`)
- [ ] Core Web Vitals: LCP ≤2.5s, INP ≤200ms, CLS ≤0.1
- [ ] Playwright E2E: flusso navigazione base + accessibilità axe-core

**Effort stimato:** 80-100 ore
**Output:** Sito informativo live con SEO ottimizzato per Lacedonia

---

### FASE 2 — Engagement & Auth (Mesi 4-5)
**Obiettivo:** Utenti registrati, wishlist, prenotazioni base, Stripe Checkout iniziale.

**Migrazione dati (prima parziale):**
- Schema Drizzle per `borghi`, `esperienze`, `aziende` su PostgreSQL Neon
- Script di migrazione: MySQL → PostgreSQL (via `pg_dump`-equivalent o script Python)
- Feature toggle per-borgo: se `lacedonia.useNextDB = true` → legge da Neon, altrimenti da PHP API

```typescript
// lib/featureFlags.ts
export const FEATURE_FLAGS = {
  lacedonia: { useNextDB: true, useStripe: false },
  nusco: { useNextDB: false, useStripe: false },
  // ...altri borghi quando migrati
}
```

**Deliverable:**
- [ ] Auth utenti: registrazione, login, profilo con NextAuth v5
- [ ] Wishlist borghi e esperienze (salvate su Neon)
- [ ] Ricerca/filtri esperienze con URL-based state
- [ ] Recensioni verificate (post-prenotazione)
- [ ] Stepper prenotazione 6 step (pattern Golobe): borgo → esperienza → data → ospiti → pagamento → conferma
- [ ] Stripe Checkout base (senza Connect) per Lacedonia
- [ ] Resend + React Email: email conferma prenotazione
- [ ] Feature Toggles per onboarding borghi senza deploy
- [ ] PDF Ticket con QR code (pdfkit)
- [ ] PostHog cookieless per feature flag management

**Effort stimato:** 100-120 ore
**Output:** Primo utente registrato e prima prenotazione reale (Lacedonia + Nusco)

---

### FASE 3 — Transazioni Completo (Mesi 6-7)
**Obiettivo:** Marketplace booking multi-borgo operativo con pagamenti reali.

**Completamento migrazione dati:**
- Migrazione completa MySQL → PostgreSQL Neon per tutti i 25 borghi
- PHP API mantiene solo funzione di fallback (deprecation programmata)
- Admin PHP rimpiazzato completamente da Payload CMS v3

**Deliverable:**
- [ ] Stripe Connect Express: KYC automatico per operatori (Codice Fiscale, IBAN)
- [ ] Satispay Business API (5M utenti IT) — integrazione via Stripe APM poi REST diretta
- [ ] Stripe Payment Links per operatori non tecnici (Day 1 gratuito)
- [ ] SEPA Direct Debit per abbonamenti B2B comuni
- [ ] WhatsApp Business API: conferme prenotazione e promemoria
- [ ] Novu: orchestrazione multi-canale (email + WhatsApp + push + in-app)
- [ ] Feature Toggle: onboarding automatico nuovo borgo in 2 click da Payload CMS
- [ ] Sentry error monitoring + alerting su Slack
- [ ] Klaro: consent management GDPR (solo se integrati servizi con cookie terze parti)
- [ ] Playwright E2E completo: prenotazione end-to-end + webhook Stripe

**Effort stimato:** 120-160 ore
**Output:** Revenue reale da prenotazioni (Lacedonia + Nusco + borghi in onboarding)

---

### V1 — Marketplace Prodotti Territoriali (Mesi 8-9)
**Obiettivo:** E-commerce multi-vendor per prodotti fisici (artigianato + enogastronomia irpina).

**Integrazione con dati esistenti:**
- `craft_products`, `food_products` da MySQL → migrazione su MedusaJS v2 product catalog
- `companies` (produttori) → diventano vendor MedusaJS con dashboard dedicata

**Deliverable:**
- [ ] MedusaJS v2 (Railway, €5-20/mese) come backend marketplace
- [ ] Mercur starter multi-vendor: dashboard vendor, commissioni, Stripe Connect split
- [ ] FattureInCloud API: fatturazione italiana automatica (regime forfettario RF19, XML SDI)
- [ ] ShippyPro: spedizioni con 190+ carrier (BRT, SDA, Poste, GLS, DHL)
- [ ] Cart unificato: `LineItem.type: 'physical'` vs `'digital_experience'`
- [ ] Scalapay BNPL (per ordini >€50)
- [ ] Onboarding vendor: ogni artigiano/produttore ottiene dashboard autonoma
- [ ] E2E Playwright: flusso acquisto prodotto fisico completo

**Alta Irpinia (5+ borghi) operativi sul marketplace**
**Effort stimato:** 280-400 ore (considerare Mercur starter per ridurre del 60-70%)

---

### V2 — Heritage AI & Immersività (Mesi 10-11)
**Obiettivo:** USP differenziante — virtual tour, AI Cicerone geolocalizzato, tour multimediali GPS.

**Deliverable:**
- [ ] 3DVista integration: embed virtual tour 3D per ogni borgo (MAVI Lacedonia già esistente)
- [ ] geoxp (mezzo-forte): GPS trigger engine per Cicerone AI mobile
  - Mappatura hotspot GPS per ogni borgo (POI, castello, chiesa, palazzo)
  - Raggio configurabile (default 30m per aree urbane, 100m per percorsi naturalistici)
  - Playback automatico narrazione AI senza interazione utente
- [ ] AI Cicerone — stack RAG:
  - LangChain.js: chunking contenuti heritage → embedding
  - pgvector su Neon PostgreSQL: `CREATE EXTENSION IF NOT EXISTS vector`
  - Vercel AI SDK: streaming risposte + UI chat
  - ElevenLabs TTS: narrazione italiana di alta qualità
  - Starter: `langchain-ai/langchain-nextjs-template`
- [ ] matterport-dl: self-hosting tour offline (resilienza connettività borghi entroterra)
- [ ] Payload CMS multilingua: contenuti IT/EN/irp per ogni borgo
- [ ] PWA: Service Worker per caching tour e mappe offline
- [ ] Totem 32" WCAG: layout responsive dedicato (già in produzione su Guardia Lombardi)

**25 borghi con virtual tour + AI narratore mobile operativi**
**Effort stimato:** 160-200 ore

---

### V3 — Transport Layer Porta-a-Porta (Mese 12)
**Obiettivo:** Integrazione completa multimodale per raggiungere i borghi da qualsiasi punto.

**Deliverable:**
- [ ] Amadeus Node SDK v9+: ricerca e prenotazione voli (hub: NAP, FCO, MXP)
  ```typescript
  // Scaffolding: Rahul-9211/flight-booking-system-app
  GET /api/transport/flights → Amadeus FlightOffersSearch
  POST /api/transport/flights/book → Amadeus FlightOrder
  ```
- [ ] Omio API: treni (Italo, Trenitalia, Frecciarossa) + bus (FlixBus, BlaBlaBus)
  - Unica API per multimodalità ferroviaria+bus in Italia/Europa
  - Stepper UI: pattern Golobe
- [ ] Bus locali Alta Irpinia: piattaforma MetaBorghi (pattern dhan-gaadi)
  - Cooperative e navette comunali registrate come operatori
  - Scheduling stagionale/per-evento
  - Biglietti PDF con QR + NFC offline per conducenti
- [ ] Rentalcars API: noleggio auto (Hertz, Avis, Europcar, Sixt)
  - Pickup: aeroporti NAP/FCO/MXP e centri Avellino/Benevento/Salerno
- [ ] Auto locali: Vendor Module (pattern Rent-a-Ride) per autonoleggi e taxi Alta Irpinia
- [ ] Planner viaggio completo: volo + treno + bus cooperativa + noleggio → checkout unico

**Tutte le destinazioni con trasporto porta-a-porta**
**Effort stimato:** 160-200 ore

---

### FASE 2 — Governance Partecipativa (Mesi 13-15)
**Obiettivo:** Ecosistema digitale civico completo con democrazia partecipativa e identità digitale PA.

**Deliverable:**
- [ ] Decidim: deploy separato (Ruby on Rails) + query via GraphQL API in Next.js
  - ParteciPA-compatible (governo italiano)
  - Feature partecipative leggere (proposte, votazioni) native in Next.js per piccoli comuni
- [ ] Pol.is: clustering opinioni via embed iframe
- [ ] SPID/CIE OIDC: `italia/spid-cie-oidc-nodejs` per autenticazione civica
  - **AVVIARE SUBITO accreditamento AgID** (4-12 settimane burocrazia)
  - Riferimento architetturale: `pagopa/io-web-profile`
- [ ] Blockchain Polygon Amoy → mainnet:
  - Smart contract certificazione heritage (pattern DArt — La Sapienza Roma)
  - Tokenizzazione: reperti MAVI Lacedonia, Oggetti Narranti Guardia Lombardi
  - Audit sicurezza obbligatorio pre-deploy mainnet
  - Hardhat test coverage 100% (immutabili dopo deploy)
- [ ] Community contribution: pattern nosilha per autenticità contenuti da nativi e diaspora irpina
- [ ] Developers Italia: `design-react-kit`, `bootstrap-italia` per componenti PA conformi

**Ecosistema completo partecipativo**
**Effort stimato:** 200-280 ore (+ 4-12 settimane burocrazia AgID — avviare in parallelo)

---

## 4. Piano di Migrazione Dati MySQL → PostgreSQL

### Schema di equivalenza (MySQL → Drizzle ORM)

```typescript
// drizzle/schema/boroughs.ts
import { pgTable, varchar, integer, decimal, text, timestamp } from 'drizzle-orm/pg-core'

export const boroughs = pgTable('boroughs', {
  id: varchar('id', { length: 100 }).primaryKey(), // slug (es: 'lacedonia')
  slug: varchar('slug', { length: 100 }).notNull(),
  name: varchar('name', { length: 200 }).notNull(),
  province: varchar('province', { length: 100 }),
  region: varchar('region', { length: 100 }),
  population: integer('population'),
  altitude_meters: integer('altitude_meters'),
  area_km2: decimal('area_km2', { precision: 8, scale: 2 }),
  lat: decimal('lat', { precision: 10, scale: 8 }),
  lng: decimal('lng', { precision: 11, scale: 8 }),
  main_video_url: text('main_video_url'),
  virtual_tour_url: text('virtual_tour_url'),
  description: text('description'),
  // ... altri campi
  created_at: timestamp('created_at').defaultNow(),
  updated_at: timestamp('updated_at').defaultNow(),
})

// pgvector per AI Cicerone
export const boroughEmbeddings = pgTable('borough_embeddings', {
  id: varchar('id', { length: 100 }).primaryKey(),
  borough_id: varchar('borough_id', { length: 100 }).references(() => boroughs.id),
  content: text('content').notNull(),
  embedding: vector('embedding', { dimensions: 1536 }), // OpenAI text-embedding-3-small
})
```

### Script migrazione

```bash
# 1. Export da MySQL (su Hostinger)
mysqldump -u user -p metaborghi_db > metaborghi_mysql_backup.sql

# 2. Conversione MySQL → PostgreSQL syntax
pgloader mysql://user:pass@hostinger-host/metaborghi_db \
         postgresql://user:pass@neon-host/metaborghi_neon

# 3. Verifica conteggi
psql -c "SELECT COUNT(*) FROM boroughs;"  # Atteso: 25
psql -c "SELECT COUNT(*) FROM companies;" # Atteso: 14
```

### Strategia cutover per borgo
1. Attivare feature flag: `borgo.useNeonDB = true`
2. Verificare parità dati via test automatico
3. Disattivare scritture su MySQL per quel borgo
4. Dopo 30 giorni senza incidenti → rimuovere flag, MySQL deprecato per quel borgo

---

## 5. Integrazioni Prioritarie Immediate

### 5.1 Da fare subito (parallelamente allo sviluppo)

| Azione | Perché adesso | Responsabile |
|--------|--------------|-------------|
| **Avviare accreditamento AgID SPID/CIE** | 4-12 settimane burocrazia — ogni settimana di ritardo ritarda la Fase 2 | InnTour (non tecnico) |
| **Aprire account Amadeus for Developers** | Free tier 2.000 call/giorno — necessario per sviluppo V3 | InnTour |
| **Contattare Omio Partner API** | Contratto B2B richiesto — sandbox disponibile per sviluppo | InnTour |
| **Contattare Rentalcars Partners** | Programma partner, REST API sandbox | InnTour |
| **Aprire account Linear (Startup Program)** | 6 mesi gratuiti — avviare subito per gestione roadmap | Dev Team |
| **Setup Infisical** | Rimpiazza credenziali in `db.php` dal giorno 0 | Dev Team |
| **Registrare dominio MetaBorghi.it** | SEO e branding — se non già fatto | InnTour |

### 5.2 Moduli del sistema PHP da preservare durante la transizione

| Modulo PHP | Strategia | Timeline |
|-----------|-----------|----------|
| `api/v1/*.php` (GET pubblico) | Proxy Next.js Server Actions → continua a funzionare | Fino a Fase 3 |
| `api/admin/` (pannello CRUD) | Sostituito da Payload CMS v3 — Fase 1 | Fase 1-2 |
| `api/export/generate.php` | Sostituito da ISR + on-demand revalidation Next.js | Fase 1 |
| `api/config/db.php` | Migrato a Drizzle ORM su Neon | Fase 2-3 |
| `seed_all.php` | Diventa script migrazione MySQL → Neon | Fase 2 |

---

## 6. Architettura Target Definitiva

```
┌─────────────────────────────────────────────────────────────────┐
│                    METABORGHI ECOSYSTEM                         │
├─────────────────────────────────────────────────────────────────┤
│  FRONTEND (Vercel)                                              │
│  Next.js 14 App Router + TypeScript + TailwindCSS + ShadCN UI   │
│  next-intl: /it/borghi/lacedonia | /en/villages/lacedonia       │
│  MapLibre GL JS + Protomaps PMTiles (offline)                   │
│  Vercel AI SDK streaming (Cicerone AI chat)                     │
├─────────────────────────────────────────────────────────────────┤
│  CMS/ADMIN (same-process, Vercel)                               │
│  Payload CMS v3 — Local API, multilingual, live preview         │
├─────────────────────────────────────────────────────────────────┤
│  DATABASE (Neon serverless)                                     │
│  PostgreSQL + Drizzle ORM + pgvector (embeddings AI)            │
├─────────────────────────────────────────────────────────────────┤
│  E-COMMERCE (Railway)                                           │
│  MedusaJS v2 + Mercur (marketplace multi-vendor)                │
│  FattureInCloud (fatturazione IT) + ShippyPro (spedizioni)      │
├─────────────────────────────────────────────────────────────────┤
│  PAGAMENTI                                                      │
│  Stripe Connect Express (split vendor) + SEPA                   │
│  Satispay Business API (mercato IT)                             │
│  Stripe Payment Links (operatori non tecnici)                   │
├─────────────────────────────────────────────────────────────────┤
│  AI / HERITAGE                                                  │
│  LangChain.js RAG + pgvector + ElevenLabs TTS                   │
│  geoxp GPS trigger + 3DVista virtual tour + matterport offline  │
│  Polygon Amoy → mainnet (certificazione blockchain heritage)    │
├─────────────────────────────────────────────────────────────────┤
│  TRANSPORT LAYER                                                │
│  Amadeus (voli) + Omio (treni+bus EU) + Rentalcars (auto)       │
│  dhan-gaadi pattern (cooperative locali Alta Irpinia)           │
├─────────────────────────────────────────────────────────────────┤
│  GOVERNANCE                                                     │
│  Decidim (Ruby on Rails, deploy separato) + Pol.is              │
│  SPID/CIE OIDC (italia/spid-cie-oidc-nodejs)                    │
├─────────────────────────────────────────────────────────────────┤
│  OSSERVABILITÀ & SICUREZZA                                      │
│  Umami cookieless + PostHog + Sentry + Arcjet WAF               │
│  Infisical (secrets) + Klaro GDPR + UptimeRobot                 │
│  Vitest + Playwright E2E + Hardhat (smart contract)             │
└─────────────────────────────────────────────────────────────────┘
         ↕ (fase transizione) ↕
┌─────────────────────────────────────────────────────────────────┐
│  LEGACY (Hostinger — deprecation programmata)                   │
│  PHP 8+ REST API (/api/v1/*.php) — proxy fino a Fase 3          │
│  MySQL 8.0 — migrazione progressiva per borgo                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. KPI e Metriche di Successo

| Milestone | KPI | Target |
|-----------|-----|--------|
| MVP 0 | Deploy Vercel operativo, CI/CD verde | Giorno 30 |
| MVP 1 | Core Web Vitals, Lighthouse ≥0.9, Lacedonia live | Giorno 90 |
| MVP 2 | Prima prenotazione reale, 1+ utente registrato | Giorno 150 |
| MVP 3 | Revenue €/mese da prenotazioni, Nusco + 1 borgo | Giorno 210 |
| V1 | Marketplace attivo, 3+ vendor onboarded | Giorno 270 |
| V2 | Virtual tour live, Cicerone AI attivo | Giorno 330 |
| V3 | Transport Layer completo (voli+treni+auto) | Giorno 365 |
| Fase 2 | SPID attivo, Decidim live, blockchain heritage | Mese 15 |

### Metriche tecniche continue
- Test coverage: ≥80% unit, ≥70% integration, 15-20 flussi E2E Playwright
- Smart contract: 100% coverage Hardhat (immutabili dopo deploy)
- Accessibilità: Lighthouse a11y ≥0.9 su ogni PR
- Performance: LCP ≤2.5s, INP ≤200ms, CLS ≤0.1
- Uptime: ≥99.5% (UptimeRobot monitoring)

---

## 8. Gestione Metodologica

### Shape Up Modificato (cicli 4 settimane + 1 cooldown)
- Nessuno Scrum/cerimonie — team 2-3 persone
- WIP limit: 2 task per persona
- Sync settimanale: 30 minuti
- Demo + mini-retro fine ciclo
- 20% tempo cooldown: debito tecnico + ADR (docs/adr/)

### PM Tool: Linear (Startup Program — 6 mesi gratuiti)

### CI/CD Pipeline (GitHub Actions + Vercel)
```yaml
steps:
  1. ESLint + Prettier check
  2. TypeScript tsc --noEmit
  3. Vitest unit + integration
  4. next build
  5. Playwright E2E su Vercel preview URL
  6. axe-core a11y + Lighthouse CI (≥0.9)
  7. Deploy preview (PR) / produzione (merge main)
```

### ADR — Architecture Decision Records
Documentare in `docs/adr/` ogni scelta architetturale rilevante:
- ADR-001: MySQL → PostgreSQL Neon (strategia migrazione)
- ADR-002: PHP → Next.js (strategia strangler fig)
- ADR-003: MedusaJS vs Saleor vs Vendure (scelta e-commerce)
- ADR-004: Amadeus vs Trainline (scelta transport voli)
- ADR-005: Payload CMS v3 vs Strapi (scelta CMS)

---

## 9. Budget Infrastrutturale Stimato

| Servizio | Piano | Costo/mese |
|---------|-------|-----------|
| Vercel (frontend + CMS) | Hobby → Pro | €0 → €20 |
| Neon PostgreSQL | Free → Launch | €0 → €19 |
| Railway (MedusaJS) | Starter | €5-20 |
| Protomaps R2 (tile offline) | Cloudflare R2 | ~€3 |
| Umami analytics | Self-host su Railway | ~€5 |
| ElevenLabs TTS | Starter | €5-22 |
| Infisical secrets | Team (5 utenti) | €0 |
| UptimeRobot | 50 monitor | €0 |
| Sentry | Developer | €0 |
| **TOTALE MVP** | | **~€10-70/mese** |

*Escluso: Amadeus (contratto B2B production), Omio (contratto B2B), Rentalcars (partner), Stripe fees (0.25-0.5% transazioni), Satispay fees, Decidim hosting (Fase 2)*

---

## 10. Rischi e Mitigazioni

| Rischio | Probabilità | Impatto | Mitigazione |
|---------|-------------|---------|-------------|
| Connettività borghi per AI/virtual tour | Alta | Alto | matterport-dl offline + PWA Service Worker + PMTiles offline |
| Burocrazia AgID SPID (4-12 settimane) | Certa | Medio | Avviare subito; sistema funziona senza SPID fino alla Fase 2 |
| Omio/Rentalcars contratto B2B lento | Media | Alto | UI/UX scaffolding completato con mock data; API live quando contratto firma |
| Migrazione dati MySQL → Neon | Media | Alto | Feature flag per-borgo + rollback MySQL sempre disponibile |
| Polygon mainnet gas fees | Bassa | Medio | Amoy testnet per sviluppo; mainnet solo dopo audit sicurezza completo |
| Smart contract bug post-deploy | Bassa | Critico | Hardhat 100% coverage + audit esterno obbligatorio + proxy upgradeable pattern |
| CVE Next.js (CVE-2025-66478, CVE-2025-29927) | Media | Alto | Pinning versione Next.js + Dependabot automatico + Sentry alerting |

---

## 11. Prossimi Passi Immediati (Settimana 1-2)

### Dev Team
1. `git checkout -b feature/nextjs-scaffold` — inizio Fase 0
2. Installare scaffold ARKA come base
3. Configurare Infisical per secrets (migrare da `db.php`)
4. Setup CI/CD GitHub Actions + Vercel preview
5. Setup Vitest + primo test di sanità

### InnTour (non tecnico, in parallelo)
1. **Avviare accreditamento AgID SPID/CIE** — ogni settimana conta
2. Aprire account Amadeus for Developers (free tier immediato)
3. Contattare Omio Partner API (omio.com/partner-api)
4. Contattare Rentalcars Partners (rentalcars.com/partners)
5. Attivare Linear Startup Program
6. Aprire account MedusaJS Cloud o configurare Railway per MedusaJS

---

*MetaBorghi Piano Strategico Integrazioni v1.0 — InnTour S.R.L. — 18 Marzo 2026*
*Basato su Blueprint v3 DEFINITIVO (Claude Sonnet 4.6 + Perplexity AI + InnTour)*
