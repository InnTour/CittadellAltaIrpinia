# SITEMAP COMPLETA - MetaBorghi/CittadellAltaIrpinia

**Platform:** React SPA (Single Page Application) con anchor-based navigation
**Backend:** PHP REST API v1 + MySQL + Admin CMS
**Company:** InnTour S.r.l. (Startup Innovativa)
**Architecture:** REST API, Static data export, Admin panel management

---

## PARTE FRONTEND - APPLICAZIONE WEB

### Home & Sezioni Principali (Anchor-Based Navigation)

L'intera applicazione è una **Single Page Application (SPA)** con navigazione tramite anchor links (#). La homepage contiene tutte le sezioni principali.

```
/ (HomePage - Anchor Navigation)
├── #borghi (Borghi Section) → Lists 9 boroughs
│   ├── Links to: Individual borgo details via API
│   ├── Shows: Maps view, highlights, related content
│   └── Cross-links to: Companies, Experiences, Products in each borough
│
├── #esperienze (Esperienze Section) → Lists 6 experiences
│   ├── Filterable by: GASTRONOMIA, CULTURA, NATURA, ARTIGIANATO, BENESSERE, AVVENTURA
│   ├── Links to: Experience details via API
│   └── Shows: Provider, location, difficulty, duration, price
│
├── #mappa (Map Section) → Interactive map with all borghi
│   ├── Displays: Geolocation of all content
│   └── Links to: Borgo details from map pins
│
├── #chi-siamo (About Us) → Platform information
├── #prenota (Call-to-Action) → Booking/engagement section
└── #contatti (Footer/Contacts) → Contact form, social links, hours

Navigation Menu:
├── Borghi → #borghi
├── Esperienze → #esperienze
├── Mappa → #mappa
├── Chi Siamo → #chi-siamo
├── Contatti → #contatti
└── Prenota (CTA Button) → #prenota
```

### Sezione BORGHI (9 Piccoli Comuni Montani)

**9 Borghi Available:**
1. **Lacedonia** - Population 2,200, Altitude 730m
2. **Calitri** - Population 4,500, Altitude 530m (Ceramic arts center)
3. **Bisaccia** - Population 3,800, Altitude 860m (Archaeological)
4. **Andretta** - Population 1,700, Altitude 850m (Artisan ceramics)
5. **Monteverde** - Population 750, Altitude 730m (Castle views)
6. **Aquilonia** - Population 1,600, Altitude 740m (Ethnographic museum)
7. **Cairano** - Population 350, Altitude 820m (Smallest, contemporary art)
8. **Conza della Campania** - Population 1,400, Altitude 580m (Lake oasis)
9. **Nusco** - Population 3,800, Altitude 914m (Highest, cathedral)

```
#borghi (BoroughsPage - Anchor Section)
├── Displays:
│   ├── All 9 borghi with highlights
│   ├── Population, altitude, province info
│   ├── Hero images and coordinates
│   └── Notable content per borough
│
├── Links to:
│   ├── Individual borgo details via API
│   ├── Related Companies (aziende)
│   ├── Related Experiences
│   ├── Related Products
│   ├── Related Restaurants
│   └── Related Accommodations
│
└── Navigation back to home (#)
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

### Sezione ESPERIENZE (6 Esperienze per Categoria)

**6 Experiences by Category:**
1. **ARTIGIANATO** - Ceramica Calitri (craft workshop, €45, 3 hours)
2. **NATURA** - Sentiero Ofanto (hiking, €25, 5 hours)
3. **GASTRONOMIA** - Sapori d'Irpinia (gastronomy tour, €65, 4 hours)
4. **CULTURA** - Notte al Castello (castle visit, €20, 2 hours)
5. **BENESSERE** - Yoga all'Alba (yoga session, €30, 2 hours)
6. **AVVENTURA** - Kayak Lago Conza (water sports, €40, 3 hours)

```
#esperienze (ExperiencesPage - Anchor Section)
├── Shows:
│   ├── All 6 experiences with filters
│   ├── Category filters (GASTRONOMIA, CULTURA, NATURA, ARTIGIANATO, BENESSERE, AVVENTURA)
│   ├── Difficulty levels (FACILE, MEDIO, DIFFICILE)
│   ├── Price, duration, max participants
│   ├── Rating and reviews
│   └── Seasonal availability tags
│
├── Links to:
│   ├── Individual experience details via API
│   ├── Experience provider/company info
│   ├── Location (specific borough)
│   ├── Cancellation policy
│   └── Languages available
│
└── Related content:
    ├── What's included/excluded
    ├── What to bring
    ├── Timeline steps/itinerary
    └── Similar experiences
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

## API ENDPOINTS (REST v1)

### Base URL: `/api/v1/`

All endpoints use standard HTTP methods: **GET** (read), **POST** (create), **PUT** (update), **DELETE** (destroy).
Authentication required for mutations (POST/PUT/DELETE) via JWT token.

#### Borghi (Towns)
```
GET    /api/v1/boroughs.php              → List all boroughs
GET    /api/v1/boroughs.php?id={id}     → Single borough by ID
GET    /api/v1/boroughs.php?slug={slug} → Single borough by slug
POST   /api/v1/boroughs.php             → Create borough (auth required)
PUT    /api/v1/boroughs.php?id={id}     → Update borough (auth required)
DELETE /api/v1/boroughs.php?id={id}     → Delete borough with cascading (auth required)

Response includes: highlights, notable_products, notable_experiences,
                  notable_restaurants, gallery_images, coordinates,
                  hero_image, cover_video_url, main_video_url
```

#### Aziende (Companies)
```
GET    /api/v1/companies.php                      → List all companies
GET    /api/v1/companies.php?id={id}             → Single company
GET    /api/v1/companies.php?slug={slug}         → Single company by slug
GET    /api/v1/companies.php?borough={borough_id} → Companies in borough
POST   /api/v1/companies.php                     → Create (auth required)
PUT    /api/v1/companies.php?id={id}             → Update (auth required)
DELETE /api/v1/companies.php?id={id}             → Delete (auth required)

Response includes: certifications, b2b_interests, awards, social_links,
                  coordinates, gallery_images, tier (BASE/PREMIUM/PLATINUM),
                  founder info, cover_video_url
```

#### Esperienze (Experiences)
```
GET    /api/v1/experiences.php                              → List all
GET    /api/v1/experiences.php?id={id}                     → Single
GET    /api/v1/experiences.php?slug={slug}                 → By slug
GET    /api/v1/experiences.php?category={GASTRONOMIA|...}  → By category
GET    /api/v1/experiences.php?borough={borough_id}        → By borough
GET    /api/v1/experiences.php?category={CAT}&borough={ID} → Combined
POST   /api/v1/experiences.php                             → Create
PUT    /api/v1/experiences.php?id={id}                     → Update
DELETE /api/v1/experiences.php?id={id}                     → Delete

Categories: GASTRONOMIA, CULTURA, NATURA, ARTIGIANATO, BENESSERE, AVVENTURA
Response includes: languages_available, includes, excludes, what_to_bring,
                  seasonal_tags, timeline_steps, difficulty_level, ratings
```

#### Artigianato (Craft Products)
```
GET    /api/v1/crafts.php                    → List all crafts
GET    /api/v1/crafts.php?id={id}           → Single craft
GET    /api/v1/crafts.php?slug={slug}       → By slug
GET    /api/v1/crafts.php?borough={id}      → By borough
POST   /api/v1/crafts.php                   → Create
PUT    /api/v1/crafts.php?id={id}           → Update
DELETE /api/v1/crafts.php?id={id}           → Delete

Response includes: material_types, customization_options, process_steps,
                  gallery_images, dimensions, price, lead_time_days
```

#### Prodotti Food (Food Products)
```
GET    /api/v1/food_products.php                    → List all
GET    /api/v1/food_products.php?id={id}           → Single product
GET    /api/v1/food_products.php?slug={slug}       → By slug
GET    /api/v1/food_products.php?borough={id}      → By borough
GET    /api/v1/food_products.php?category={cat}    → By category
POST   /api/v1/food_products.php                   → Create
PUT    /api/v1/food_products.php?id={id}           → Update
DELETE /api/v1/food_products.php?id={id}           → Delete

Response includes: producer info, certifications (DOP, IGP), ingredients,
                  storage_instructions, price, stock_qty
```

#### Ospitalità (Accommodations)
```
GET    /api/v1/accommodations.php                    → List all
GET    /api/v1/accommodations.php?id={id}           → Single
GET    /api/v1/accommodations.php?slug={slug}       → By slug
GET    /api/v1/accommodations.php?borough={id}      → By borough
POST   /api/v1/accommodations.php                   → Create
PUT    /api/v1/accommodations.php?id={id}           → Update
DELETE /api/v1/accommodations.php?id={id}           → Delete

Response includes: rooms_count, beds_count, amenities, price_per_night,
                  certifications, founder_info, contact info, social links
```

#### Ristorazione (Restaurants)
```
GET    /api/v1/restaurants.php                    → List all
GET    /api/v1/restaurants.php?id={id}           → Single
GET    /api/v1/restaurants.php?slug={slug}       → By slug
GET    /api/v1/restaurants.php?borough={id}      → By borough
POST   /api/v1/restaurants.php                   → Create
PUT    /api/v1/restaurants.php?id={id}           → Update
DELETE /api/v1/restaurants.php?id={id}           → Delete

Response includes: cuisines, dietary_options, certifications, tier,
                  booking_url, social_links, cover_video_url
```

#### Comuni (Municipalities B2G)
```
GET    /api/v1/municipalities.php              → List all
GET    /api/v1/municipalities.php?id={id}     → Single
GET    /api/v1/municipalities.php?status={st} → By status
POST   /api/v1/municipalities.php              → Create
PUT    /api/v1/municipalities.php?id={id}     → Update
DELETE /api/v1/municipalities.php?id={id}     → Delete
```

#### Authentication & Users
```
POST   /api/v1/auth.php?action=register       → Register new user
POST   /api/v1/auth.php?action=login          → Login (returns JWT)
GET    /api/v1/auth.php?action=me             → Get current user
POST   /api/v1/auth.php?action=refresh        → Refresh JWT token
GET    /api/v1/users.php                      → List users (admin)
GET    /api/v1/users.php?id={id}              → Single user profile
PUT    /api/v1/users.php?id={id}              → Update profile
PUT    /api/v1/users.php?action=password      → Change password
DELETE /api/v1/users.php?id={id}              → Deactivate user
```

#### Prenotazioni (Bookings)
```
GET    /api/v1/bookings.php                      → List user's bookings
GET    /api/v1/bookings.php?id={id}              → Single booking
GET    /api/v1/bookings.php?status={status}     → Filter by status
POST   /api/v1/bookings.php                      → Create booking
PUT    /api/v1/bookings.php?id={id}              → Update booking
DELETE /api/v1/bookings.php?id={id}              → Cancel booking

Status values: PENDING, CONFIRMED, CANCELLED, COMPLETED
```

#### Wishlist
```
GET    /api/v1/wishlist.php                                      → Get wishlist
GET    /api/v1/wishlist.php?item_type={type}                    → Filter by type
POST   /api/v1/wishlist.php                                      → Add to wishlist
DELETE /api/v1/wishlist.php?item_type={type}&item_id={id}       → Remove
```

#### Analytics
```
POST   /api/v1/analytics.php                    → Track page view (public)
GET    /api/v1/analytics.php                    → Get stats (auth required)
GET    /api/v1/analytics.php?period={days}     → Stats for N days
GET    /api/v1/analytics.php?type={type}       → Stats by entity type
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
