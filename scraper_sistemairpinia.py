#!/usr/bin/env python3
"""
Scraper per sistemairpinia.provincia.avellino.it
Estrae: borghi, eventi, itinerari

Dipendenze:
    pip install playwright beautifulsoup4 lxml
    playwright install chromium

Utilizzo:
    python scraper_sistemairpinia.py

Output:
    borghi.json, eventi.json, itinerari.json
"""

import asyncio
import json
import re
import time
import sys
from pathlib import Path
from urllib.parse import urljoin, urlparse

from playwright.async_api import async_playwright
from bs4 import BeautifulSoup

BASE_URL = "https://sistemairpinia.provincia.avellino.it"

# I 25 comuni dell'Alta Irpinia che ci interessano
ALTA_IRPINIA_COMUNI = {
    "andretta", "aquilonia", "bisaccia", "cairano", "calitri",
    "conza della campania", "conza-della-campania",
    "guardia lombardi", "guardia-lombardi", "guardia dei lombardi",
    "lacedonia", "lioni", "monteverde", "morra de sanctis", "morra-de-sanctis",
    "nusco", "rocca san felice", "rocca-san-felice",
    "sant'angelo dei lombardi", "sant-angelo-dei-lombardi",
    "sant'andrea di conza", "sant-andrea-di-conza",
    "torella dei lombardi", "torella-dei-lombardi",
    "villamaina", "gesualdo", "frigento", "flumeri",
    "greci", "zungoli", "savignano irpino", "vallata",
    "scampitella", "trevico", "vallesaccarda",
    "castelbaronia", "carife", "san sossio baronia",
    "chiusano san domenico", "paternopoli"
}

# ============================================================
# Utility
# ============================================================

def clean(text: str) -> str:
    if not text:
        return ""
    text = re.sub(r'\s+', ' ', text)
    return text.strip()

def save_json(data, filename: str):
    path = Path(filename)
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  → Salvato: {filename} ({len(data)} elementi)")

# ============================================================
# Browser setup
# ============================================================

async def get_page_html(page, url: str, wait_selector: str = None, delay: float = 1.5) -> str:
    """Carica una pagina e restituisce l'HTML dopo il rendering JS."""
    try:
        await page.goto(url, wait_until="networkidle", timeout=30000)
        if wait_selector:
            try:
                await page.wait_for_selector(wait_selector, timeout=5000)
            except:
                pass
        await asyncio.sleep(delay)
        return await page.content()
    except Exception as e:
        print(f"  ⚠️  Errore caricamento {url}: {e}")
        return ""

# ============================================================
# Scoperta URL listing
# ============================================================

async def discover_listing_urls(page) -> dict:
    """Trova le URL delle sezioni borghi, eventi, itinerari."""
    print("\n[1/4] Analisi navigazione sito...")
    html = await get_page_html(page, BASE_URL)
    soup = BeautifulSoup(html, 'lxml')

    nav_links = {}
    for a in soup.find_all('a', href=True):
        href = a['href'].lower()
        text = clean(a.get_text()).lower()
        full = urljoin(BASE_URL, a['href'])

        if any(kw in href or kw in text for kw in ['borg', 'villag', 'comu']):
            nav_links['borghi'] = full
        elif any(kw in href or kw in text for kw in ['event']):
            nav_links['eventi'] = full
        elif any(kw in href or kw in text for kw in ['itinerar', 'percors']):
            nav_links['itinerari'] = full

    # Fallback URL standard Drupal/Irpinia
    defaults = {
        'borghi':    [
            f"{BASE_URL}/it/borghi",
            f"{BASE_URL}/it/comuni",
            f"{BASE_URL}/en/villages",
            f"{BASE_URL}/en/cities",
            f"{BASE_URL}/it/borghi-e-centri-storici",
        ],
        'eventi':    [
            f"{BASE_URL}/it/eventi",
            f"{BASE_URL}/en/events",
            f"{BASE_URL}/it/calendar",
        ],
        'itinerari': [
            f"{BASE_URL}/it/itinerari",
            f"{BASE_URL}/en/itineraries",
            f"{BASE_URL}/it/percorsi",
        ],
    }

    result = {}
    for section, fallbacks in defaults.items():
        if section in nav_links:
            result[section] = nav_links[section]
        else:
            # Prova i fallback
            for url in fallbacks:
                try:
                    resp = await page.goto(url, timeout=10000)
                    if resp and resp.status < 400:
                        result[section] = url
                        break
                except:
                    continue
            if section not in result:
                result[section] = fallbacks[0]  # usa il primo come default

    print(f"  Borghi   → {result.get('borghi', 'N/D')}")
    print(f"  Eventi   → {result.get('eventi', 'N/D')}")
    print(f"  Itinerari→ {result.get('itinerari', 'N/D')}")
    return result

# ============================================================
# Scraping borghi
# ============================================================

async def scrape_borghi_list(page, listing_url: str) -> list:
    """Raccoglie tutti i link alle pagine dettaglio borghi."""
    print(f"\n  Listing borghi: {listing_url}")
    links = set()
    current_url = listing_url

    # Gestisce paginazione
    while current_url:
        html = await get_page_html(page, current_url, delay=1.0)
        soup = BeautifulSoup(html, 'lxml')

        # Cerca link a pagine comuni
        for a in soup.find_all('a', href=True):
            href = a['href']
            full = urljoin(BASE_URL, href)
            # Pattern tipici Drupal: /it/comuni/nome, /it/borghi/nome, /it/node/123
            if re.search(r'/(comuni|borgh|villag|cities|node)/[a-z0-9\-]+', href, re.I):
                if full.startswith(BASE_URL):
                    links.add(full)

        # Paginazione
        next_link = soup.find('a', {'rel': 'next'}) or soup.find('a', string=re.compile(r'›|next|succ', re.I))
        if next_link and next_link.get('href'):
            next_url = urljoin(BASE_URL, next_link['href'])
            if next_url != current_url and next_url not in links:
                current_url = next_url
            else:
                break
        else:
            break

    print(f"  Trovati {len(links)} link borghi")
    return sorted(links)

def parse_borgo_detail(html: str, url: str) -> dict:
    """Estrae tutti i dati da una pagina dettaglio borgo."""
    soup = BeautifulSoup(html, 'lxml')

    # Titolo
    title = ""
    for sel in ['h1.page-title', 'h1.node-title', 'h1', '.field-name-title']:
        el = soup.select_one(sel)
        if el:
            title = clean(el.get_text())
            break

    # Descrizione / corpo principale
    description = ""
    for sel in [
        '.field-name-body .field-item',
        '.field-name-field-descrizione .field-item',
        '.field-type-text-with-summary .field-item',
        'article .body',
        '.node-content p',
        'main p'
    ]:
        els = soup.select(sel)
        if els:
            description = clean(' '.join(e.get_text() for e in els))
            break

    # Immagini
    images = []
    for img in soup.select('img[src]'):
        src = img.get('src', '')
        if src and not src.endswith('.gif') and 'placeholder' not in src:
            full_src = urljoin(BASE_URL, src)
            alt = img.get('alt', '')
            images.append({'src': full_src, 'alt': clean(alt)})

    # Campi strutturati (field-name-* pattern Drupal)
    fields = {}
    for field_div in soup.select('[class*="field-name-"]'):
        classes = field_div.get('class', [])
        field_name = next((c.replace('field-name-', '') for c in classes if c.startswith('field-name-')), None)
        if field_name and field_name not in ('body', 'title'):
            label_el = field_div.select_one('.field-label')
            value_els = field_div.select('.field-item')
            if value_els:
                values = [clean(v.get_text()) for v in value_els if clean(v.get_text())]
                label = clean(label_el.get_text()).rstrip(':') if label_el else field_name
                if values:
                    fields[label or field_name] = values if len(values) > 1 else values[0]

    # Dati numerici da testo
    pop_match = re.search(r'popolazione[:\s]+([0-9.,]+)', description + ' '.join(str(v) for v in fields.values()), re.I)
    alt_match = re.search(r'altitudine[:\s]+([0-9]+)\s*m', description + ' '.join(str(v) for v in fields.values()), re.I)

    return {
        'url': url,
        'name': title,
        'description': description,
        'images': images[:10],
        'fields': fields,
        'population_text': pop_match.group(1) if pop_match else '',
        'altitude_text': alt_match.group(1) if alt_match else '',
        'raw_text': clean(soup.get_text())[:2000],  # per analisi
    }

async def scrape_borghi(page, listing_url: str) -> list:
    """Scraping completo di tutti i borghi."""
    print(f"\n[2/4] Scraping borghi...")
    detail_links = await scrape_borghi_list(page, listing_url)

    borghi = []
    total = len(detail_links)

    for i, url in enumerate(detail_links, 1):
        print(f"  [{i}/{total}] {url}")
        html = await get_page_html(page, url, delay=1.2)
        if html:
            data = parse_borgo_detail(html, url)
            if data['name']:
                borghi.append(data)
                print(f"    ✓ {data['name']}")
            else:
                print(f"    ✗ Nome non trovato")
        else:
            print(f"    ✗ Pagina non caricata")

    return borghi

# ============================================================
# Scraping eventi
# ============================================================

def parse_event_detail(html: str, url: str) -> dict:
    """Estrae dati da una pagina evento."""
    soup = BeautifulSoup(html, 'lxml')

    title = ""
    for sel in ['h1.page-title', 'h1.node-title', 'h1']:
        el = soup.select_one(sel)
        if el:
            title = clean(el.get_text())
            break

    description = ""
    for sel in ['.field-name-body .field-item', '.field-type-text-with-summary .field-item', 'article p', 'main p']:
        els = soup.select(sel)
        if els:
            description = clean(' '.join(e.get_text() for e in els))
            break

    # Date
    date_start = ""
    date_end = ""
    for sel in ['.date-display-single', '.field-name-field-data', '.field-name-field-date', '.event-date', 'time']:
        el = soup.select_one(sel)
        if el:
            date_start = clean(el.get_text())
            dt = el.get('datetime', '')
            if dt:
                date_start = dt
            break

    # Luogo
    location = ""
    for sel in ['.field-name-field-luogo', '.field-name-field-location', '.field-name-field-comune', '.location']:
        el = soup.select_one(sel)
        if el:
            location = clean(el.get_text())
            break

    # Categoria
    category = ""
    for sel in ['.field-name-field-categoria', '.field-name-field-category', '.field-name-field-tipo']:
        el = soup.select_one(sel)
        if el:
            category = clean(el.get_text())
            break

    images = []
    for img in soup.select('img[src]'):
        src = img.get('src', '')
        if src and not src.endswith('.gif') and 'placeholder' not in src:
            images.append(urljoin(BASE_URL, src))

    return {
        'url': url,
        'title': title,
        'description': description,
        'date_start': date_start,
        'date_end': date_end,
        'location': location,
        'category': category,
        'images': images[:5],
    }

async def scrape_eventi(page, listing_url: str) -> list:
    """Scraping completo degli eventi."""
    print(f"\n[3/4] Scraping eventi...")
    html = await get_page_html(page, listing_url, delay=1.5)
    soup = BeautifulSoup(html, 'lxml')

    event_links = set()
    for a in soup.find_all('a', href=True):
        href = a['href']
        full = urljoin(BASE_URL, href)
        if re.search(r'/(event|eventi|node)/[a-z0-9\-]+', href, re.I) and full.startswith(BASE_URL):
            event_links.add(full)

    # Paginazione
    while True:
        next_link = soup.find('a', {'rel': 'next'}) or soup.find('a', string=re.compile(r'›|next|succ', re.I))
        if not next_link:
            break
        next_url = urljoin(BASE_URL, next_link['href'])
        html = await get_page_html(page, next_url, delay=1.0)
        soup = BeautifulSoup(html, 'lxml')
        prev_count = len(event_links)
        for a in soup.find_all('a', href=True):
            href = a['href']
            full = urljoin(BASE_URL, href)
            if re.search(r'/(event|eventi|node)/[a-z0-9\-]+', href, re.I) and full.startswith(BASE_URL):
                event_links.add(full)
        if len(event_links) == prev_count:
            break

    print(f"  Trovati {len(event_links)} eventi")
    eventi = []
    for i, url in enumerate(sorted(event_links), 1):
        print(f"  [{i}/{len(event_links)}] {url}")
        html = await get_page_html(page, url, delay=1.0)
        if html:
            data = parse_event_detail(html, url)
            if data['title']:
                eventi.append(data)

    return eventi

# ============================================================
# Scraping itinerari
# ============================================================

def parse_itinerary_detail(html: str, url: str) -> dict:
    """Estrae dati da una pagina itinerario."""
    soup = BeautifulSoup(html, 'lxml')

    title = ""
    for sel in ['h1.page-title', 'h1.node-title', 'h1']:
        el = soup.select_one(sel)
        if el:
            title = clean(el.get_text())
            break

    description = ""
    for sel in ['.field-name-body .field-item', '.field-type-text-with-summary .field-item', 'article p', 'main p']:
        els = soup.select(sel)
        if els:
            description = clean(' '.join(e.get_text() for e in els))
            break

    # Tappe / punti interesse
    tappe = []
    for sel in ['.field-name-field-tappe', '.views-row', '.stop', '.tappa', 'ol li', 'ul.tappe li']:
        els = soup.select(sel)
        if els:
            tappe = [clean(e.get_text()) for e in els if len(clean(e.get_text())) > 5]
            if tappe:
                break

    # Difficoltà
    difficulty = ""
    for sel in ['.field-name-field-difficolta', '.field-name-field-difficulty', '.difficulty']:
        el = soup.select_one(sel)
        if el:
            difficulty = clean(el.get_text())
            break

    # Lunghezza / durata
    length = ""
    duration = ""
    all_text = soup.get_text()
    km_m = re.search(r'(\d+(?:[.,]\d+)?)\s*km', all_text)
    if km_m:
        length = km_m.group(0)
    dur_m = re.search(r'(\d+(?:[.,]\d+)?)\s*(ore|h\b|minuti)', all_text, re.I)
    if dur_m:
        duration = dur_m.group(0)

    # Tipo (natura, cultura, enogastronomia...)
    tipo = ""
    for sel in ['.field-name-field-tipo', '.field-name-field-category', '.field-name-field-categoria']:
        el = soup.select_one(sel)
        if el:
            tipo = clean(el.get_text())
            break

    images = []
    for img in soup.select('img[src]'):
        src = img.get('src', '')
        if src and not src.endswith('.gif') and 'placeholder' not in src:
            images.append(urljoin(BASE_URL, src))

    return {
        'url': url,
        'title': title,
        'description': description,
        'tappe': tappe[:15],
        'difficulty': difficulty,
        'length': length,
        'duration': duration,
        'tipo': tipo,
        'images': images[:5],
    }

async def scrape_itinerari(page, listing_url: str) -> list:
    """Scraping completo degli itinerari."""
    print(f"\n[4/4] Scraping itinerari...")
    html = await get_page_html(page, listing_url, delay=1.5)
    soup = BeautifulSoup(html, 'lxml')

    itinerary_links = set()
    for a in soup.find_all('a', href=True):
        href = a['href']
        full = urljoin(BASE_URL, href)
        if re.search(r'/(itinerar|percors|route|node)/[a-z0-9\-]+', href, re.I) and full.startswith(BASE_URL):
            itinerary_links.add(full)

    # Paginazione
    while True:
        next_link = soup.find('a', {'rel': 'next'}) or soup.find('a', string=re.compile(r'›|next|succ', re.I))
        if not next_link:
            break
        next_url = urljoin(BASE_URL, next_link['href'])
        html = await get_page_html(page, next_url, delay=1.0)
        soup = BeautifulSoup(html, 'lxml')
        prev_count = len(itinerary_links)
        for a in soup.find_all('a', href=True):
            href = a['href']
            full = urljoin(BASE_URL, href)
            if re.search(r'/(itinerar|percors|route|node)/[a-z0-9\-]+', href, re.I) and full.startswith(BASE_URL):
                itinerary_links.add(full)
        if len(itinerary_links) == prev_count:
            break

    print(f"  Trovati {len(itinerary_links)} itinerari")
    itinerari = []
    for i, url in enumerate(sorted(itinerary_links), 1):
        print(f"  [{i}/{len(itinerary_links)}] {url}")
        html = await get_page_html(page, url, delay=1.0)
        if html:
            data = parse_itinerary_detail(html, url)
            if data['title']:
                itinerari.append(data)

    return itinerari

# ============================================================
# MAIN
# ============================================================

async def main():
    print("=" * 60)
    print("  Scraper — sistemairpinia.provincia.avellino.it")
    print("=" * 60)

    async with async_playwright() as p:
        browser = await p.chromium.launch(
            headless=True,
            args=['--no-sandbox', '--disable-setuid-sandbox']
        )
        context = await browser.new_context(
            viewport={"width": 1280, "height": 900},
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/120.0.0.0 Safari/537.36"
            ),
            locale="it-IT",
            extra_http_headers={
                "Accept-Language": "it-IT,it;q=0.9,en;q=0.8",
                "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
            }
        )
        page = await context.new_page()

        # 1. Scopri URL delle sezioni
        urls = await discover_listing_urls(page)

        # 2. Scraping borghi
        borghi = await scrape_borghi(page, urls.get('borghi', f"{BASE_URL}/it/borghi"))
        save_json(borghi, 'borghi_raw.json')

        # 3. Scraping eventi
        eventi = await scrape_eventi(page, urls.get('eventi', f"{BASE_URL}/it/eventi"))
        save_json(eventi, 'eventi_raw.json')

        # 4. Scraping itinerari
        itinerari = await scrape_itinerari(page, urls.get('itinerari', f"{BASE_URL}/it/itinerari"))
        save_json(itinerari, 'itinerari_raw.json')

        await browser.close()

    print("\n" + "=" * 60)
    print(f"  COMPLETATO")
    print(f"  Borghi:    {len(borghi)}")
    print(f"  Eventi:    {len(eventi)}")
    print(f"  Itinerari: {len(itinerari)}")
    print("=" * 60)
    print("\nFile generati:")
    print("  borghi_raw.json")
    print("  eventi_raw.json")
    print("  itinerari_raw.json")
    print("\nInvia questi 3 file e procediamo con l'integrazione nel DB.")

if __name__ == '__main__':
    asyncio.run(main())
