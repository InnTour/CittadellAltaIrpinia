# MetaBorghi — Contesto Completo del Progetto

> Documento di onboarding per nuove AI e collaboratori.
> Ultimo aggiornamento: 2026-03-17 | Branch attivo: `claude/filter-lacedonia-municipality-azcGs`

---

## 1. Cos'è MetaBorghi

**MetaBorghi** è una piattaforma full-stack per promuovere e vendere prodotti, esperienze e servizi dei borghi dell'Alta Irpinia (Campania, Italia). Aggrega 25 comuni e permette a produttori locali, artigiani, ristoranti e strutture ricettive di avere visibilità digitale e vendita online.

Sviluppata da **InnTour** per i comuni dell'Alta Irpinia (area montana tra Avellino, Potenza, Foggia).

---

## 2. Stack Tecnologico

### Frontend
- **Framework:** React (via Vite)
- **Styling:** Tailwind CSS + CSS custom properties
- **Routing:** SPA client-side
- **Mappe:** Leaflet.js
- **Build output:** `/assets/*.js` (file JS minificati pre-compilati)

### Backend
- **Linguaggio:** PHP 8+
- **Database:** MySQL 8.0+ (charset utf8mb4)
- **Database access:** PDO con prepared statements
- **Hosting:** Hostinger (shared hosting)
- **Auth API:** Bearer token
- **Auth Admin:** Session PHP

### Pattern architetturale chiave
Il sistema usa la **data export strategy**: invece di query dinamiche al DB a ogni richiesta frontend, l'admin esporta i dati in file JS statici. Il frontend importa quei moduli. Questo ottimizza le performance su hosting condiviso.

**Flusso di pubblicazione:**
1. Admin modifica dati nel pannello PHP
2. Dati salvati su MySQL
3. Admin clicca "Pubblica"
4. `api/export/generate.php` chiama `api/export/_generate_functions.php`
5. Vengono rigenerati i file JS in `/assets/`
6. Il frontend rispecchia immediatamente le modifiche

---

## 3. Struttura Directory

```
MetaBorghi/
├── index.html                  # SPA entry point (colori, Tailwind, Leaflet)
├── .htaccess                   # Apache SPA routing (tutto → index.html)
├── favicon.svg
├── assets/                     # JS compilati da Vite + export DB
│   ├── boroughs-CXywHoot.js    # 25 borghi (dati statici dal DB)
│   ├── companies-DS8bqSy6.js   # 14 aziende
│   ├── experiences-C_0o8G74.js # 15 esperienze
│   ├── craft-products-CcLcqzAP.js  # 7 prodotti artigianali
│   └── *.js                    # Componenti React compilati
├── src/                        # Sorgenti React (non inclusi nel deploy diretto)
├── api/
│   ├── config/
│   │   └── db.php              # Config DB, helper functions, auth
│   ├── v1/                     # REST API endpoints (GET pubblico, POST/PUT/DELETE con token)
│   │   ├── boroughs.php
│   │   ├── companies.php
│   │   ├── experiences.php
│   │   ├── crafts.php
│   │   ├── food_products.php
│   │   ├── accommodations.php
│   │   └── restaurants.php
│   ├── admin/                  # Pannello admin (PHP server-rendered, Tailwind dark)
│   │   ├── _layout.php         # Template layout con sidebar
│   │   ├── _footer.php
│   │   ├── login.php / logout.php
│   │   ├── index.php           # Dashboard (stats + bottone Pubblica)
│   │   ├── borghi.php
│   │   ├── aziende.php
│   │   ├── esperienze.php
│   │   ├── artigianato.php
│   │   ├── prodotti.php        # Food products
│   │   ├── ospitalita.php
│   │   ├── ristorazione.php
│   │   ├── seed_lacedonia.php  # Seed dati esempio (1 borgo + entità correlate)
│   │   └── seed_all.php        # Seed TUTTI i dati frontend (25 borghi, 14 aziende, ecc.)
│   ├── export/
│   │   ├── generate.php        # Endpoint HTTP per il bottone "Pubblica"
│   │   └── _generate_functions.php  # Logica export DB → JS files
│   ├── schema.sql              # Schema principale tabelle
│   └── schema_migration.sql   # Migrazione tabelle food/accommodations/restaurants
├── MetaBorghi_DataTemplate.xlsx  # Template Excel per importazione dati
└── populate_excel.py           # Script Python per popolare il template
```

---

## 4. Database — Tabelle

### BORGHI
| Tabella | Descrizione |
|---------|-------------|
| `boroughs` | Entità principale. Colonne: id, slug, name, province, region, population, altitude_meters, area_km2, lat, lng, main_video_url, virtual_tour_url, description, companies_count, hero_image_index, hero_image_alt |
| `borough_highlights` | Array highlights (FK: borough_id) |
| `borough_notable_products` | Prodotti notevoli (FK: borough_id) |
| `borough_notable_experiences` | Esperienze notevoli (FK: borough_id) |
| `borough_notable_restaurants` | Ristoranti notevoli (FK: borough_id) |
| `borough_gallery_images` | Immagini galleria con source_index e alt |

### AZIENDE
| Tabella | Descrizione |
|---------|-------------|
| `companies` | Produttori, agriturismo, artigiani. Tipo: PRODUTTORE_FOOD, MISTO, AGRITURISMO, ecc. Tier: BASE, PREMIUM, PLATINUM |
| `company_certifications` | Certificazioni (Slow Food, DOP, IGP, ecc.) |
| `company_b2b_interests` | Interessi B2B (Distribuzione, Export, ecc.) |
| `company_awards` | Premi: company_id, year, title, entity |

### ESPERIENZE
| Tabella | Descrizione |
|---------|-------------|
| `experiences` | Visite, degustazioni, tour. Category: GASTRONOMIA, CULTURA, NATURA, ARTIGIANATO, BENESSERE, AVVENTURA |
| `experience_languages` | Lingue disponibili |
| `experience_includes` | Cosa include |
| `experience_excludes` | Cosa NON include |
| `experience_bring` | Cosa portare |
| `experience_seasonal_tags` | Tag stagionali (primavera, estate, autunno, inverno) |
| `experience_timeline` | Fasi con time_slot, title, description, sort_order |

### PRODOTTI ARTIGIANALI
| Tabella | Descrizione |
|---------|-------------|
| `craft_products` | Oggetti artigianali handmade |
| `craft_material_types` | Materiali usati |
| `craft_customization_options` | Opzioni personalizzazione con price_modifier |
| `craft_process_steps` | Fasi produzione |

### PRODOTTI FOOD
| Tabella | Descrizione |
|---------|-------------|
| `food_products` | Prodotti alimentari. Colonne: producer_id, category, price, unit, weight_grams, shelf_life_days, allergens, ingredients, origin_protected, pairing_suggestions |

### OSPITALITÀ
| Tabella | Descrizione |
|---------|-------------|
| `accommodations` | Strutture ricettive. Type ENUM: HOTEL, AGRITURISMO, MASSERIA, BED_AND_BREAKFAST, HOSTEL, APPARTAMENTO |

### RISTORAZIONE
| Tabella | Descrizione |
|---------|-------------|
| `restaurants` | Ristoranti. Type ENUM: RISTORANTE, TRATTORIA, PIZZERIA, AGRITURISMO, ENOTECA, BAR, OSTERIA. Price range: BUDGET, MEDIO, ALTO, GOURMET |

---

## 5. Helper Functions (api/config/db.php)

```php
getDB()                              // PDO singleton connection
requireAuth()                        // Verifica Bearer token (write API)
requireAdminSession()                // Verifica sessione admin (admin panel)
jsonHeaders()                        // Imposta CORS + Content-Type JSON
getJsonBody()                        // Legge body JSON request

fetchArray($db, $table, $fk, $id, $col)
// Legge 1-to-many: SELECT col FROM $table WHERE $fk = $id ORDER BY sort_order

replaceArray($db, $table, $fk, $id, $values, $col='value')
// Sostituisce 1-to-many: DELETE + INSERT con sort_order automatico
```

---

## 6. API Endpoints

### GET (pubblico, no auth)
```
GET /api/v1/boroughs.php              → lista borghi
GET /api/v1/boroughs.php?id={slug}    → singolo borgo
GET /api/v1/companies.php             → lista aziende
GET /api/v1/companies.php?borough={slug}  → aziende per borgo
GET /api/v1/experiences.php           → lista esperienze
GET /api/v1/experiences.php?category={cat}&borough={slug}
GET /api/v1/crafts.php                → prodotti artigianali
GET /api/v1/crafts.php?borough={slug}
GET /api/v1/food_products.php
GET /api/v1/accommodations.php
GET /api/v1/restaurants.php
```

### POST/PUT/DELETE (richiede Bearer token)
```
Authorization: Bearer {API_TOKEN}
```

---

## 7. Frontend — Pagine

| Pagina | URL | Asset JS |
|--------|-----|---------|
| Home | `/` | HomePage |
| Borghi | `/borghi` | BoroughsPage |
| Comuni | `/comuni` | ComuniPage |
| Dettaglio Borgo | `/borghi/:slug` | BoroughDetailPage |
| Aziende | `/aziende` | CompaniesPage |
| Dettaglio Azienda | `/aziende/:slug` | CompanyDetailPage |
| Esperienze | `/esperienze` | ExperiencesPage |
| Dettaglio Esperienza | `/esperienze/:slug` | ExperienceDetailPage |
| Artigianato | `/artigianato` | CraftsPage |
| Dettaglio Artigianato | `/artigianato/:slug` | CraftDetailPage |
| Prodotti | `/prodotti` | ProductsPage / ProductDetailPage |
| Progetti / B2B | `/progetti` | ProgettoPage, B2BLandingPage |
| Carrello | `/carrello` | CartPage |
| Checkout | `/checkout` | CheckoutPage |
| Account | `/account` | AccountPage |
| FAQ | `/faq` | FaqPage |
| Contatti | `/contatti` | ContattiPage |
| Admin | `/api/admin/` | AdminPage (PHP + React) |

**Colori brand:**
- Verde primario: `#00D084`
- Ciano: `#00B4D8`
- Giallo neon: `#F0FF00`

---

## 8. Pannello Admin

**URL:** `/api/admin/`
**Auth:** Session PHP (username/password)

Il pannello usa un pattern preciso:
1. `require_once __DIR__ . '/../config/db.php';`
2. `requireAdminSession();`
3. Form HTML che fa POST a se stesso
4. Array fields: textarea con valori separati da `\n`
5. Layout: `require '_layout.php';` + `require '_footer.php';`
6. Dark theme Tailwind (slate-800/900 background)

---

## 9. Seed Files

### `seed_lacedonia.php`
Inserisce dati di esempio per il comune di Lacedonia:
- 1 Borgo
- 1 Azienda (Caciocavalleria De D.)
- 1 Esperienza (Tour Caseificio Podolico)
- 1 Artigianato (Cesto in Vimini)
- 1 Prodotto Food (Caciocavallo Podolico 18m)
- 1 Ospitalità (Masseria Santa Lucia)
- 1 Ristorazione (Trattoria del Borgo)

Include anche la **schema migration inline** (crea le tabelle se non esistono).

### `seed_all.php`
Inserisce TUTTI i dati estratti dai file JS del frontend:
- 25 Borghi (tutti i comuni dell'Alta Irpinia)
- 14 Aziende
- 15 Esperienze
- 7 Prodotti Artigianali

**Pattern del seed:**
```php
<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$results = []; $errors = [];

// Schema migration (CREATE TABLE IF NOT EXISTS)
// ...

function seedRun(PDO $db, string $label, callable $fn, array &$results, array &$errors): void {
    try { $fn($db); $results[] = "✅ $label"; }
    catch (PDOException $e) { $errors[] = "❌ $label: " . $e->getMessage(); }
}

seedRun($db, 'Label', function(PDO $db) {
    $db->prepare("INSERT INTO table (...) VALUES (...) ON DUPLICATE KEY UPDATE ...")->execute([...]);
    replaceArray($db, 'sub_table', 'fk_col', $id, ['val1', 'val2']);
}, $results, $errors);

// HTML render
$pageTitle = 'Seed ...';
require '_layout.php';
// ... HTML ...
require '_footer.php';
```

---

## 10. File JS Dati (Frontend Static Data)

I file JS seguenti contengono i dati "canonical" del frontend. Quando si aggiungono dati dal DB, questi file vanno rigenerati via il bottone "Pubblica".

| File | Contenuto |
|------|-----------|
| `assets/boroughs-CXywHoot.js` | 25 borghi con descrizioni, coordinate, video URLs |
| `assets/companies-DS8bqSy6.js` | 14 aziende con profili completi, awards, B2B |
| `assets/experiences-C_0o8G74.js` | 15 esperienze con timeline, includes, rating |
| `assets/craft-products-CcLcqzAP.js` | 7 prodotti artigianali con process steps |

**Note di parsing JS → PHP:**
- `!0` → `true` → `1` in PHP
- `!1` → `false` → `0` in PHP
- `hero_image_index` del borgo = indice N in `a.borghi[N]`

**Mapping indice → slug borgo:**
```
0=andretta, 1=aquilonia, 2=bagnoli-irpino, 3=bisaccia, 4=cairano,
5=calabritto, 6=calitri, 7=caposele, 8=cassano-irpino, 9=castelfranci,
10=conza-della-campania, 11=guardia-dei-lombardi, 12=lacedonia, 13=lioni,
14=montella, 15=monteverde, 16=morra-de-sanctis, 17=nusco,
18=rocca-san-felice, 19=sant-andrea-di-conza, 20=sant-angelo-dei-lombardi,
21=senerchia, 22=teora, 23=torella-dei-lombardi, 24=villamaina
```

---

## 11. Convenzioni di Codice

### ID e Slug
- Tutti gli ID sono `VARCHAR(100)` (stringhe custom, NON auto-increment)
- ID = slug (kebab-case, es: `lacedonia`, `caciocavalleria`, `tour-caseificio`)
- Apostrofi in PHP: usare single-quote con escape `\'` oppure double-quote

### PHP INSERT Pattern
```php
$db->prepare("INSERT INTO table (col1, col2) VALUES (?,?) ON DUPLICATE KEY UPDATE col1=VALUES(col1)")
   ->execute(['val1', 'val2']);
```

### PHP Arrays (1-to-many)
```php
replaceArray($db, 'borough_highlights', 'borough_id', $boroughId, [
    'Primo highlight',
    'Secondo highlight',
]);
```

### PHP Awards (company_awards)
```php
// Helper da definire inline o in db.php:
function replaceAwards(PDO $db, string $companyId, array $awards): void {
    $db->prepare("DELETE FROM company_awards WHERE company_id=?")->execute([$companyId]);
    $stmt = $db->prepare("INSERT INTO company_awards (company_id, year, title, entity) VALUES (?,?,?,?)");
    foreach ($awards as $a) {
        $stmt->execute([$companyId, $a['year'], $a['title'], $a['entity']]);
    }
}
```

---

## 12. Git

**Repository:** `InnTour/MetaBorghi`
**Branch attivo:** `claude/filter-lacedonia-municipality-azcGs`

### Ultimi commit
```
2ea762c fix: elimina loopback HTTP su Hostinger per il pulsante Pubblica
ae5de0c Fix testo spurio in alto a sinistra nel backend admin
e501618 Fix 500 on new admin pages + seed crea le tabelle automaticamente
54c9a0c Add Prodotti Food, Ospitalità, Ristorazione — schema, API, admin e seed Lacedonia
5e7539d Add ?borough= filter to experiences and crafts API endpoints
42ba075 feat: backend PHP/MySQL + 4 interventi frontend
6396e29 feat: aggiungi pagine /progetti e /comuni — riscrittura da zero
```

### Regole push
```bash
git push -u origin claude/filter-lacedonia-municipality-azcGs
```
- Branch DEVE iniziare con `claude/` e terminare con `azcGs`
- Non pushare mai su `main` senza conferma esplicita

---

## 13. Task Correnti / Prossimi Passi

- [x] Backend PHP + MySQL (borghi, aziende, esperienze, artigianato)
- [x] Admin panel CRUD per tutte le entità
- [x] Prodotti Food, Ospitalità, Ristorazione (schema + API + admin)
- [x] Seed dati Lacedonia (esempio completo)
- [ ] **seed_all.php** — Seed di tutti i 25 borghi + 14 aziende + 15 esperienze + 7 artigianato
- [ ] Completare API endpoints food_products, accommodations, restaurants
- [ ] Integrare i nuovi tipi di contenuto nel frontend

---

## 14. Domande Frequenti per Nuove AI

**D: Come aggiungo un nuovo borgo?**
R: Inserisci in `boroughs` con `replaceArray()` per le sotto-tabelle. Poi esegui il seed o usa l'admin panel. Poi clicca "Pubblica" per aggiornare i file JS.

**D: Come leggo i dati JS?**
R: I file in `assets/` sono minificati (1 riga). Usare `cat` o la read tool. `!0`=true, `!1`=false.

**D: Dove sono le credenziali DB?**
R: `api/config/db.php` (non committare mai credenziali in chiaro).

**D: Come funziona il bottone Pubblica?**
R: Fa una richiesta a `api/export/generate.php` che legge il DB e sovrascrive i file JS in `assets/`. Il fix recente (commit 2ea762c) elimina il loopback HTTP su Hostinger — ora viene chiamata direttamente la funzione PHP.

**D: Qual è la differenza tra seed_lacedonia.php e seed_all.php?**
R: `seed_lacedonia.php` è un seed minimale con 1 elemento per tipo, per test rapidi. `seed_all.php` contiene TUTTI i dati di produzione estratti dai file JS del frontend.
