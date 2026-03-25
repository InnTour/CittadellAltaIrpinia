# SITEMAP COMPLETA - MetaBorghi/CittadellAltaIrpinia

## PARTE FRONTEND - APPLICAZIONE WEB

### Home & Sezioni Principali

```
/ (HomePage)
├── Links to:
│   ├── /borghi (BoroughsPage)
│   ├── /aziende (CompaniesPage)
│   ├── /esperienze (ExperiencesPage)
│   ├── /artigianato (CraftsPage)
│   ├── /prodotti-food (ProductsPage)
│   ├── /ospitalita (AccommodationsPage)
│   ├── /ristorazione (RestaurantsPage)
│   ├── /comuni
│   ├── /contatti
│   ├── /b2b
│   └── /faq
```

### Sezione BORGHI (Piccoli Comuni Montani)

```
/borghi (BoroughsPage - List View)
├── Links to:
│   ├── /borghi/:slug (BoroughDetailPage)
│   ├── Individual borghi detail pages:
│   │   ├── /borghi/lacedonia
│   │   ├── /borghi/villarosa
│   │   └── [other borghi]
│   ├── Hero section references partner content:
│   │   ├── Companies in each borough
│   │   ├── Experiences in each borough
│   │   ├── Products from each borough
│   │   ├── Restaurants in each borough
│   │   └── Accommodations in each borough
│   └── Navigation back to /
```

### Sezione AZIENDE (Companies/Businesses)

```
/aziende (CompaniesPage - List View)
├── Shows:
│   ├── All companies with filters
│   ├── Company type/category filters
│   └── Search functionality
├── Links to:
│   ├── /aziende/:slug (CompanyDetailPage)
│   └── Related content:
│       ├── Products from company
│       ├── Experiences offered by company
│       └── Locations/Borghi where active
└── Navigation back to /
```

### Sezione ESPERIENZE (Activities/Experiences)

```
/esperienze (ExperiencesPage - List View)
├── Shows:
│   ├── All experiences with filters
│   ├── Category filters (trekking, cooking, etc.)
│   ├── Difficulty levels
│   └── Seasonal availability
├── Links to:
│   ├── /esperienze/:slug (ExperienceDetailPage)
│   └── Related content:
│       ├── Experience provider (company/azienda)
│       ├── Location (borough)
│       └── Associated products
└── Navigation back to /
```

### Sezione ARTIGIANATO (Crafts)

```
/artigianato (CraftsPage - List View)
├── Shows:
│   ├── All craft products with filters
│   ├── Craft type filters
│   └── Materials/techniques
├── Links to:
│   ├── /artigianato/:slug (CraftDetailPage)
│   └── Related content:
│       ├── Artisan/craftsperson info
│       ├── Production process
│       └── Purchase/contact info
└── Navigation back to /
```

### Sezione PRODOTTI FOOD (Food Products)

```
/prodotti-food (ProductsPage - List View)
├── Shows:
│   ├── All food products
│   ├── Category filters (cheeses, wines, etc.)
│   └── Producer filters
├── Links to:
│   ├── /prodotti-food/:slug (ProductDetailPage)
│   └── Related content:
│       ├── Producer/company info
│       ├── Origin borough
│       └── Purchase options
└── Navigation back to /
```

### Sezione OSPITALITÀ (Accommodations)

```
/ospitalita (AccommodationsPage - List View)
├── Shows:
│   ├── All accommodations
│   ├── Type filters (B&B, hotel, agriturismi, etc.)
│   ├── Rating/reviews
│   └── Availability
├── Links to:
│   ├── /ospitalita/:slug (AccommodationDetailPage)
│   └── Related content:
│       ├── Location (borough)
│       ├── Nearby experiences
│       ├── Nearby restaurants
│       └── Booking/contact
└── Navigation back to /
```

### Sezione RISTORAZIONE (Restaurants)

```
/ristorazione (RestaurantsPage - List View)
├── Shows:
│   ├── All restaurants
│   ├── Type filters (pizzeria, trattoria, etc.)
│   ├── Cuisine filters
│   ├── Rating/reviews
│   └── Hours/availability
├── Links to:
│   ├── /ristorazione/:slug (RestaurantDetailPage)
│   └── Related content:
│       ├── Location (borough)
│       ├── Menu preview
│       ├── Signature dishes
│       └── Reservation/contact
└── Navigation back to /
```

### Sezioni Informative

```
/comuni (ComuniPage)
├── Lists all municipalities
├── Information about each comune
└── Links back to /

/comuni-pa (ComuniPage variant)
├── Possibly regional/provincial info
└── Links back to /

/contatti (ContattiPage)
├── Contact form
├── Address/location
├── Business hours
├── Social media links
└── Links back to /

/faq (FaqPage)
├── Frequently asked questions
├── Help center
└── Links back to /

/progetti (ProgettoPage)
├── Project information
├── News/announcements
└── Links back to /
```

### Sezioni E-Commerce/B2B

```
/carrello (CartPage)
├── Shopping cart
├── Item management
└── Links to:
    └── /checkout (CheckoutPage)

/checkout (CheckoutPage)
├── Purchase/order confirmation
├── Payment processing
└── Links back to /account

/account (AccountPage)
├── User profile
├── Order history
├── Wishlist
└── Links to:
    ├── /
    └── Saved items/wishlist pages

/b2b (B2GPage)
├── B2B/B2G opportunities
├── Business partnerships
└── Links to:
    ├── /b2b-landing (B2BLandingPage)
    ├── /b2b-directory (B2BDirectoryPage)
    ├── /b2b-opportunities (B2BOpportunitiesPage)
    └── /b2b-opportunity/:id (B2BOpportunityDetailPage)

/404 (NotFoundPage)
├── 404 error page
└── Links back to /
```

### Admin/Protected Routes

```
/admin (AdminPage)
├── Requires authentication
├── Links to:
│   ├── /api/admin/borghi
│   ├── /api/admin/aziende
│   ├── /api/admin/esperienze
│   ├── /api/admin/artigianato
│   ├── /api/admin/prodotti
│   ├── /api/admin/ospitalita
│   ├── /api/admin/ristorazione
│   ├── /api/admin/comuni
│   ├── /api/admin/statistiche
│   └── /api/admin/utenti
```

---

## PARTE BACKEND - API & ADMIN

### API Endpoints (RESTful)

#### 1. BORGHI (Boroughs/Small Municipalities)
```
GET    /api/v1/borghi              → Get all borghi
GET    /api/v1/borghi/{id}         → Get single borgo
GET    /api/v1/borghi/{id}/images  → Get borgo images
GET    /api/v1/borghi/{id}/gallery → Get borgo gallery
```

#### 2. AZIENDE (Companies)
```
GET    /api/v1/companies           → Get all companies
GET    /api/v1/companies/{id}      → Get single company
GET    /api/v1/companies/{id}/images → Get company images
GET    /api/v1/companies/borough/{id} → Get companies by borough
```

#### 3. ESPERIENZE (Experiences)
```
GET    /api/v1/experiences         → Get all experiences
GET    /api/v1/experiences/{id}    → Get single experience
GET    /api/v1/experiences/{id}/images → Get experience images
GET    /api/v1/experiences/borough/{id} → Get experiences by borough
GET    /api/v1/experiences/company/{id} → Get company's experiences
```

#### 4. ARTIGIANATO (Crafts)
```
GET    /api/v1/crafts              → Get all craft products
GET    /api/v1/crafts/{id}         → Get single craft
GET    /api/v1/crafts/{id}/images  → Get craft images
GET    /api/v1/crafts/borough/{id} → Get crafts by borough
```

#### 5. PRODOTTI FOOD (Food Products)
```
GET    /api/v1/food_products       → Get all food products
GET    /api/v1/food_products/{id}  → Get single product
GET    /api/v1/food_products/{id}/images → Get product images
GET    /api/v1/food_products/borough/{id} → Get products by borough
```

#### 6. OSPITALITÀ (Accommodations)
```
GET    /api/v1/accommodations      → Get all accommodations
GET    /api/v1/accommodations/{id} → Get single accommodation
GET    /api/v1/accommodations/{id}/images → Get accommodation images
GET    /api/v1/accommodations/borough/{id} → Get by borough
GET    /api/v1/accommodations/type/{type} → Get by type
```

#### 7. RISTORAZIONE (Restaurants)
```
GET    /api/v1/restaurants         → Get all restaurants
GET    /api/v1/restaurants/{id}    → Get single restaurant
GET    /api/v1/restaurants/{id}/images → Get restaurant images
GET    /api/v1/restaurants/borough/{id} → Get by borough
GET    /api/v1/restaurants/cuisine/{type} → Get by cuisine
```

#### 8. COMUNI (Municipalities)
```
GET    /api/v1/municipalities      → Get all comuni
GET    /api/v1/municipalities/{id} → Get single comune
```

#### 9. UTENTI (Users/Authentication)
```
POST   /api/v1/users/register      → User registration
POST   /api/v1/users/login         → User login
GET    /api/v1/users/{id}          → Get user profile
PUT    /api/v1/users/{id}          → Update user
DELETE /api/v1/users/{id}          → Delete user
GET    /api/v1/users/verify-email  → Email verification
```

#### 10. WISHLIST
```
GET    /api/v1/wishlist            → Get user's wishlist
POST   /api/v1/wishlist            → Add to wishlist
DELETE /api/v1/wishlist/{item_id}  → Remove from wishlist
```

#### 11. BOOKING
```
POST   /api/v1/bookings            → Create booking
GET    /api/v1/bookings/{id}       → Get booking details
PUT    /api/v1/bookings/{id}       → Update booking
DELETE /api/v1/bookings/{id}       → Cancel booking
GET    /api/v1/bookings/user/{uid} → Get user's bookings
```

#### 12. ANALYTICS
```
GET    /api/v1/analytics/views     → Page view stats
GET    /api/v1/analytics/searches  → Search analytics
GET    /api/v1/analytics/conversions → Conversion tracking
```

### Admin Panel Pages

```
/api/admin/
├── login.php          → Admin login
├── logout.php         → Admin logout
├── index.php          → Dashboard/main page
├── borghi.php         → Manage borghi
├── aziende.php        → Manage companies
├── esperienze.php     → Manage experiences
├── artigianato.php    → Manage crafts
├── prodotti.php       → Manage food products
├── ospitalita.php     → Manage accommodations
├── ristorazione.php   → Manage restaurants
├── comuni.php         → Manage municipalities
├── statistiche.php    → Statistics/analytics
├── utenti.php         → Manage users
├── seed_all.php       → Seed all data (development)
├── seed_lacedonia.php → Seed Lacedonia data (testing)
├── _layout.php        → Admin layout template
└── _footer.php        → Admin footer template
```

---

## DATABASE SCHEMA

### Core Content Tables

#### 1. BOROUGHS (Borghi)
```
boroughs
├── id (PK)
├── name
├── slug
├── description
├── location
├── coordinates (lat/lng)
├── population
├── elevation
├── hero_image
├── cover_image
├── cover_video_url
├── main_video_url
├── gallery_images
├── notable_products (FK → products)
├── notable_restaurants (FK → restaurants)
├── notable_experiences (FK → experiences)
├── notable_companies (FK → companies)
└── highlights/interesting_facts
```

#### 2. COMPANIES (Aziende)
```
companies
├── id (PK)
├── name
├── slug
├── description
├── type/category
├── borough_id (FK → boroughs)
├── website
├── phone
├── email
├── address
├── coordinates
├── hero_image
├── cover_image
├── cover_video_url
├── main_video_url
├── gallery_images
├── certifications (→ company_certifications)
├── awards (→ company_awards)
├── b2b_interests (→ company_b2b_interests)
├── is_verified
├── rating/reviews
└── created_at/updated_at
```

#### 3. EXPERIENCES (Esperienze)
```
experiences
├── id (PK)
├── name
├── slug
├── description
├── category
├── difficulty_level
├── duration (hours)
├── borough_id (FK → boroughs)
├── provider_id (FK → companies)
├── hero_image
├── cover_image
├── cover_video_url
├── main_video_url
├── gallery_images
├── price
├── max_participants
├── languages (→ experience_languages)
├── includes (→ experience_includes)
├── excludes (→ experience_excludes)
├── bring_items (→ experience_bring)
├── timeline (→ experience_timeline)
├── seasonal_tags (→ experience_seasonal_tags)
├── is_available
├── rating/reviews
└── created_at/updated_at
```

#### 4. CRAFT PRODUCTS (Artigianato)
```
craft_products
├── id (PK)
├── name
├── slug
├── description
├── artisan_name/business_name
├── borough_id (FK → boroughs)
├── hero_image
├── cover_image
├── cover_video_url (removed for e-commerce)
├── gallery_images
├── material_types (→ craft_material_types)
├── process_steps (→ craft_process_steps)
├── customization_options (→ craft_customization_options)
├── price
├── available_stock
├── rating/reviews
└── created_at/updated_at
```

#### 5. FOOD PRODUCTS (Prodotti Food)
```
food_products
├── id (PK)
├── name
├── slug
├── description
├── producer/company
├── borough_id (FK → boroughs)
├── category (cheese, wine, pasta, etc.)
├── hero_image
├── cover_image
├── cover_video_url (removed for e-commerce)
├── gallery_images
├── ingredients
├── certifications (DOP, IGT, etc.)
├── price
├── available_stock
├── rating/reviews
└── created_at/updated_at
```

#### 6. ACCOMMODATIONS (Ospitalità)
```
accommodations
├── id (PK)
├── name
├── slug
├── description
├── type (B&B, hotel, agriturismi, etc.)
├── borough_id (FK → boroughs)
├── hero_image
├── cover_image
├── cover_video_url
├── main_video_url
├── gallery_images
├── address
├── coordinates
├── phone
├── email
├── website
├── rooms_count
├── price_per_night
├── amenities
├── rating/reviews
├── availability_calendar
└── created_at/updated_at
```

#### 7. RESTAURANTS (Ristorazione)
```
restaurants
├── id (PK)
├── name
├── slug
├── description
├── cuisine_type
├── borough_id (FK → boroughs)
├── hero_image
├── cover_image
├── cover_video_url
├── main_video_url
├── gallery_images
├── address
├── coordinates
├── phone
├── email
├── website
├── hours_of_operation
├── price_range
├── menu_items
├── rating/reviews
├── delivery_available
└── created_at/updated_at
```

#### 8. MUNICIPALITIES (Comuni)
```
municipalities
├── id (PK)
├── name
├── slug
├── province
├── region
├── description
├── population
├── area
├── coordinates
├── image
└── related_borghi (FK → boroughs)
```

### User & Transaction Tables

#### 9. USERS
```
users
├── id (PK)
├── email (UNIQUE)
├── password (hashed)
├── first_name
├── last_name
├── phone
├── address
├── city
├── postal_code
├── country
├── is_verified (email)
├── is_admin
├── wishlist_ids (→ wishlist)
├── bookings (→ bookings)
├── orders (→ orders)
├── created_at
└── updated_at
```

#### 10. WISHLIST
```
wishlist
├── id (PK)
├── user_id (FK → users)
├── item_id
├── item_type (experience, product, accommodation, etc.)
├── added_at
└── updated_at
```

#### 11. BOOKINGS
```
bookings
├── id (PK)
├── user_id (FK → users)
├── experience_id (FK → experiences)
├── accommodation_id (FK → accommodations)
├── restaurant_id (FK → restaurants)
├── booking_date
├── start_date
├── end_date
├── participants_count
├── status (confirmed, pending, cancelled)
├── price
├── payment_status
├── created_at
└── updated_at
```

### Asset/Media Tables

#### 12. GALLERY IMAGES (per entity type)
```
borough_gallery_images
experience_images
craft_images
restaurant_images
accommodation_images
product_images
(and similar for other entities)

Schema per image record:
├── id (PK)
├── entity_id (FK)
├── image_path
├── alt_text
├── display_order
├── created_at
└── updated_at
```

---

## ANALISI DEI LINK - STRUTTURA DI NAVIGAZIONE

### Well-Linked Pages (Buona Copertura)

✅ **HOME** → Links to all 7 main categories + secondary sections
✅ **Borghi List** → Links to detail pages + related categories
✅ **Detail Pages** (Borghi, Aziende, Esperienze, etc.) → Cross-link to related content
✅ **Category Lists** → Link to detail pages + back to home
✅ **Navigation Bar** → Consistent access to all main sections

### Partially Linked Pages (Media Coverage)

⚠️ **Admin Pages** → Accessible only via admin routes, no public links
⚠️ **Account Page** → Only accessible to logged-in users
⚠️ **Cart/Checkout** → Accessible via CTA buttons, not from main nav
⚠️ **B2B Pages** → Separate section, not integrated into main navigation

### Orphan/Missing Links

❌ **ProgettoPage** → Shows at `/progetti` but unclear linking
❌ **NotFoundPage** → Only reachable on actual 404 errors
❌ **Admin dashboard** → Not accessible from frontend
❌ **Cross-borough links** → Detail pages could link to nearby borghi

### Missing/Broken Cross-Links

- Accommodation detail pages should link to "nearby restaurants"
- Restaurant detail pages should link to "nearby accommodations"
- Experience detail pages should link to "nearby accommodations"
- Product detail pages could link to "producers in the area"
- Company detail pages could show "all products by this company"

---

## RACCOMANDAZIONI SEO/UX

### 1. NAVIGATION IMPROVEMENTS
```
Add breadcrumbs on detail pages:
/ > Borghi > Lacedonia > (current page)
/ > Aziende > Company Name > (current page)

Add "Related Content" sections:
- Detail page shows "Other experiences in same borough"
- Restaurant page shows "Nearby accommodations"
- Product page shows "From the same producer"
```

### 2. INTERNAL LINKING STRATEGY
```
Strong links needed between:
- Borghi ↔ All content in that borgo
- Companies ↔ Their experiences and products
- Accommodations ↔ Nearby restaurants
- Experiences ↔ Required accommodations
```

### 3. MISSING FUNCTIONALITY
```
Add:
- Sitemap.xml generation (/sitemap.xml)
- robots.txt configuration
- Structured data (Schema.org) for content
- Open Graph meta tags for sharing
```

### 4. CONTENT GAPS
```
Check completeness:
- Do all borghi have detail pages?
- Are all companies verified and linked?
- Do experiences have all required info?
- Are product images optimized?
```

### 5. PERFORMANCE ISSUES
```
- Images need optimization (WebP format)
- Lazy loading for gallery images
- Code splitting for detail pages
- API response caching
```

---

## STATISTICHE

| Category | Count | Links To | Linked From |
|----------|-------|----------|-------------|
| Borghi | ~15 | Detail pages, Experiences, Companies, Products | Home, Categories |
| Aziende | ~50+ | Detail pages, Products, Experiences | Home, Borghi details |
| Esperienze | ~30+ | Detail pages, Providers | Home, Borghi details, Company pages |
| Artigianato | ~40+ | Detail pages, Borghi | Home, Category list |
| Prodotti Food | ~50+ | Detail pages, Borghi, Producers | Home, Category list |
| Ospitalità | ~25+ | Detail pages, Borghi | Home, Experiences, Restaurants |
| Ristorazione | ~35+ | Detail pages, Borghi | Home, Accommodations, Experiences |
| Comuni | 5 | Informational | Home, Footer |
| Pagine Info | 4 | Footer links | Home, Multiple |
| **TOTALE** | **254+** | **Cross-referenced** | **Interconnected** |

---

## CONCLUSIONI

### 🟢 Punti Forti
- Struttura modellata bene con 7 categorie principali
- Backend API ben organizzato per ogni categoria
- Database relazionale completo
- Admin panel centralizzato

### 🟡 Aree da Migliorare
- Cross-linking tra categorie potrebbe essere più robusto
- Alcune pagine (progetti, admin) potrebbero integrarsi meglio
- Manca sitemap.xml e robots.txt
- SEO structure metadata mancante

### 🔴 Problemi Critici
- Nessuno al momento post-fix sintassi

### ✅ Azioni Consigliate
1. Generare `sitemap.xml` dinamicamente
2. Aggiungere Schema.org per rich snippets
3. Migliorare cross-linking tra categorie
4. Ottimizzare immagini (WebP, lazy loading)
5. Aggiungere breadcrumbs a tutte le detail pages
