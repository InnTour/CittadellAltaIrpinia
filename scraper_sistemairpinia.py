#!/usr/bin/env python3
"""
Scraper v2 per sistemairpinia.provincia.avellino.it
Cerca i 25 borghi dell'Alta Irpinia + eventi + itinerari.

Dipendenze:
    pip install playwright beautifulsoup4 lxml
    playwright install chromium

Utilizzo:
    python scraper_sistemairpinia.py

Output:
    borghi_raw.json, eventi_raw.json, itinerari_raw.json
"""

import asyncio
import json
import re
from pathlib import Path
from urllib.parse import urljoin, quote
from playwright.async_api import async_playwright
from bs4 import BeautifulSoup

BASE_URL = "https://sistemairpinia.provincia.avellino.it"

# I 25 comuni dell'Alta Irpinia nel nostro sistema
# slug → nomi da cercare (varie grafie)
ALTA_IRPINIA = {
    "andretta":                ["andretta"],
    "aquilonia":               ["aquilonia"],
    "bisaccia":                ["bisaccia"],
    "cairano":                 ["cairano"],
    "calitri":                 ["calitri"],
    "conza-della-campania":    ["conza della campania", "conza"],
    "guardia-dei-lombardi":    ["guardia lombardi", "guardia dei lombardi"],
    "lacedonia":               ["lacedonia"],
    "lioni":                   ["lioni"],
    "monteverde":              ["monteverde"],
    "morra-de-sanctis":        ["morra de sanctis", "morra"],
    "nusco":                   ["nusco"],
    "rocca-san-felice":        ["rocca san felice"],
    "sant-angelo-dei-lombardi":["sant angelo dei lombardi", "sant'angelo dei lombardi"],
    "sant-andrea-di-conza":    ["sant andrea di conza", "sant'andrea di conza"],
    "torella-dei-lombardi":    ["torella dei lombardi", "torella lombardi"],
    # Aggiungi altri se necessario
    "villamaina":              ["villamaina"],
    "gesualdo":                ["gesualdo"],
    "frigento":                ["frigento"],
    "flumeri":                 ["flumeri"],
    "greci":                   ["greci"],
    "zungoli":                 ["zungoli"],
    "vallata":                 ["vallata"],
    "castelbaronia":           ["castelbaronia"],
    "trevico":                 ["trevico"],
}

# Pattern URL da provare per ogni comune
URL_PATTERNS = [
    "{base}/it/comuni/{slug}",
    "{base}/it/borghi/{slug}",
    "{base}/it/borghi-e-centri-storici/{slug}",
    "{base}/it/{slug}",
    "{base}/en/cities/{slug}",
    "{base}/en/villages/{slug}",
    "{base}/en/comuni/{slug}",
]

def clean(text: str) -> str:
    if not text:
        return ""
    text = re.sub(r'\s+', ' ', text)
    return text.strip()

def save_json(data, filename: str):
    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  → Salvato: {filename} ({len(data)} elementi)")

async def get_html(page, url: str, delay: float = 1.2) -> str:
    try:
        await page.goto(url, wait_until="networkidle", timeout=25000)
        await asyncio.sleep(delay)
        return await page.content()
    except Exception as e:
        return ""

async def find_comune_url(page, slug: str, names: list):
    """Prova i pattern URL, poi fa una ricerca sul sito."""

    # 1. Prova URL dirette
    for pattern in URL_PATTERNS:
        url = pattern.format(base=BASE_URL, slug=slug)
        try:
            resp = await page.goto(url, timeout=10000, wait_until="domcontentloaded")
            if resp and resp.status == 200:
                text = (await page.content()).lower()
                if any(n.lower() in text for n in names):
                    print(f"    ✓ URL trovata: {url}")
                    return url
        except:
            pass
        await asyncio.sleep(0.3)

    # 2. Ricerca italiana
    for name in names:
        search_url = f"{BASE_URL}/it/search?search_api_fulltext={quote(name)}"
        try:
            html = await get_html(page, search_url, delay=0.8)
            if html:
                soup = BeautifulSoup(html, 'lxml')
                for a in soup.find_all('a', href=True):
                    href = a['href']
                    link_text = a.get_text(strip=True).lower()
                    if name.lower() in link_text or name.lower() in href.lower():
                        full = urljoin(BASE_URL, href)
                        if full.startswith(BASE_URL) and len(href) > 2:
                            print(f"    ✓ Trovata via ricerca: {full}")
                            return full
        except:
            pass

    # 3. Ricerca inglese
    for name in names:
        search_url = f"{BASE_URL}/en/search?search_api_fulltext={quote(name)}"
        try:
            html = await get_html(page, search_url, delay=0.8)
            if html:
                soup = BeautifulSoup(html, 'lxml')
                for a in soup.find_all('a', href=True):
                    href = a['href']
                    link_text = a.get_text(strip=True).lower()
                    if name.lower() in link_text or name.lower() in href.lower():
                        full = urljoin(BASE_URL, href)
                        if full.startswith(BASE_URL) and len(href) > 2:
                            print(f"    ✓ Trovata via ricerca EN: {full}")
                            return full
        except:
            pass

    print(f"    ✗ URL non trovata per: {slug}")
    return None

def parse_comune(html: str, url: str, slug: str) -> dict:
    soup = BeautifulSoup(html, 'lxml')

    name = ""
    for sel in ['h1.page-title', 'h1.node-title', 'h1', '.field-name-title h1']:
        el = soup.select_one(sel)
        if el:
            name = clean(el.get_text())
            break

    description_parts = []
    for sel in [
        '.field-name-body .field-item',
        '.field-name-field-descrizione .field-item',
        '.field-type-text-with-summary .field-item',
        'article .content p',
        '.node-content p',
        '.region-content p',
        'main article p',
    ]:
        els = soup.select(sel)
        if els:
            texts = [clean(e.get_text()) for e in els if len(clean(e.get_text())) > 30]
            if texts:
                description_parts = texts
                break

    description = ' '.join(description_parts)
    if not description:
        paras = [clean(p.get_text()) for p in soup.find_all('p') if len(clean(p.get_text())) > 40]
        description = ' '.join(paras[:8])

    images = []
    for img in soup.select('img[src]'):
        src = img.get('src', '')
        if src and not any(x in src for x in ['.gif', 'placeholder', 'logo', 'icon', 'banner']):
            full = urljoin(BASE_URL, src)
            alt = clean(img.get('alt', ''))
            if full not in [i['src'] for i in images]:
                images.append({'src': full, 'alt': alt})

    fields = {}
    for div in soup.select('[class*="field-name-"]'):
        cls_list = div.get('class', [])
        field_name = next((c.replace('field-name-', '') for c in cls_list if c.startswith('field-name-')), None)
        if not field_name or field_name in ('body', 'title'):
            continue
        label_el = div.select_one('.field-label')
        val_els = div.select('.field-item')
        if val_els:
            vals = [clean(v.get_text()) for v in val_els if clean(v.get_text())]
            label = clean(label_el.get_text()).rstrip(':') if label_el else field_name
            if vals:
                fields[label or field_name] = vals if len(vals) > 1 else vals[0]

    full_text = soup.get_text()
    pop = re.search(r'(?:popolazione|abitanti)[:\s]+([0-9][0-9.,]+)', full_text, re.I)
    alt = re.search(r'(?:altitudine|altezza|quota)[:\s]+([0-9]+)\s*m', full_text, re.I)
    area = re.search(r'(?:superficie|area)[:\s]+([0-9]+[.,]?[0-9]*)\s*km', full_text, re.I)

    lists = []
    for ul in soup.find_all(['ul', 'ol']):
        items = [clean(li.get_text()) for li in ul.find_all('li') if len(clean(li.get_text())) > 5]
        if 2 <= len(items) <= 20:
            lists.extend(items)

    return {
        'slug': slug,
        'url': url,
        'name': name,
        'description': description,
        'fields': fields,
        'images': images[:12],
        'population_raw': pop.group(1) if pop else '',
        'altitude_raw': alt.group(1) if alt else '',
        'area_raw': area.group(1) if area else '',
        'list_items': lists[:20],
        'raw_text': clean(full_text)[:3000],
    }

async def scrape_borghi(page) -> list:
    print("\n[2/4] Scraping borghi Alta Irpinia...")
    borghi = []
    for slug, names in ALTA_IRPINIA.items():
        print(f"\n  • {slug}")
        url = await find_comune_url(page, slug, names)
        if not url:
            continue
        html = await get_html(page, url, delay=1.0)
        if not html:
            continue
        data = parse_comune(html, url, slug)
        borghi.append(data)
        print(f"    nome={data['name']} | desc={len(data['description'])} chars | img={len(data['images'])}")
    return borghi

EVENTI_LISTING_URLS = [
    f"{BASE_URL}/it/eventi",
    f"{BASE_URL}/it/calendario-eventi",
    f"{BASE_URL}/it/manifestazioni",
    f"{BASE_URL}/en/events",
    f"{BASE_URL}/it/agenda",
]

def parse_event(html: str, url: str) -> dict:
    soup = BeautifulSoup(html, 'lxml')
    title = clean(soup.select_one('h1').get_text()) if soup.select_one('h1') else ''
    desc_els = soup.select('.field-name-body .field-item') or soup.select('article p') or soup.select('main p')
    description = clean(' '.join(e.get_text() for e in desc_els[:5]))
    full_text = soup.get_text()
    date_m = re.search(r'\d{1,2}[/\-\.]\d{1,2}[/\-\.]\d{2,4}', full_text)
    location_el = soup.select_one('.field-name-field-luogo, .field-name-field-comune, .location')
    cat_el = soup.select_one('.field-name-field-categoria, .field-name-field-tipo, .field-name-field-category')
    images = [urljoin(BASE_URL, img['src']) for img in soup.select('img[src]')
              if img['src'] and 'placeholder' not in img['src'] and not img['src'].endswith('.gif')]
    return {
        'url': url,
        'title': title,
        'description': description,
        'date': date_m.group(0) if date_m else '',
        'location': clean(location_el.get_text()) if location_el else '',
        'category': clean(cat_el.get_text()) if cat_el else '',
        'images': images[:3],
    }

async def scrape_eventi(page) -> list:
    print("\n[3/4] Scraping eventi...")
    listing_url = None
    for url in EVENTI_LISTING_URLS:
        try:
            resp = await page.goto(url, timeout=10000, wait_until="domcontentloaded")
            if resp and resp.status == 200:
                listing_url = url
                print(f"  Listing eventi: {url}")
                break
        except:
            pass
    if not listing_url:
        print("  ✗ Pagina eventi non trovata")
        return []
    event_links = set()
    current = listing_url
    visited = set()
    while current and current not in visited:
        visited.add(current)
        html = await get_html(page, current, delay=1.0)
        if not html:
            break
        soup = BeautifulSoup(html, 'lxml')
        for a in soup.find_all('a', href=True):
            href = a['href']
            full = urljoin(BASE_URL, href)
            if re.search(r'/(event|eventi|nodo|node)/[a-z0-9\-]+', href, re.I):
                if full.startswith(BASE_URL) and full != listing_url:
                    event_links.add(full)
        next_a = soup.find('a', {'rel': 'next'}) or soup.find('a', title=re.compile(r'pag|next|succ', re.I))
        current = urljoin(BASE_URL, next_a['href']) if next_a else None
    print(f"  Trovati {len(event_links)} link eventi")
    eventi = []
    for i, url in enumerate(sorted(event_links), 1):
        print(f"  [{i}/{len(event_links)}] {url}")
        html = await get_html(page, url, delay=0.8)
        if html:
            data = parse_event(html, url)
            if data['title']:
                eventi.append(data)
    return eventi

ITINERARIO_LISTING_URLS = [
    f"{BASE_URL}/it/itinerari",
    f"{BASE_URL}/it/percorsi",
    f"{BASE_URL}/it/esperienze",
    f"{BASE_URL}/en/itineraries",
    f"{BASE_URL}/en/experiences",
    f"{BASE_URL}/it/tour",
]

def parse_itinerary(html: str, url: str) -> dict:
    soup = BeautifulSoup(html, 'lxml')
    title = clean(soup.select_one('h1').get_text()) if soup.select_one('h1') else ''
    desc_els = soup.select('.field-name-body .field-item') or soup.select('article p') or soup.select('main p')
    description = clean(' '.join(e.get_text() for e in desc_els[:6]))
    full_text = soup.get_text()
    km_m = re.search(r'(\d+(?:[.,]\d+)?)\s*km', full_text)
    dur_m = re.search(r'(\d+(?:[.,]\d+)?)\s*(ore|h\b|minuti|min\b)', full_text, re.I)
    diff_el = soup.select_one('.field-name-field-difficolta, .field-name-field-difficulty')
    tappe = []
    for sel in ['.field-name-field-tappe li', '.tappe li', 'ol li', 'ul.steps li']:
        items = soup.select(sel)
        if items:
            tappe = [clean(i.get_text()) for i in items if len(clean(i.get_text())) > 5]
            break
    tipo_el = soup.select_one('.field-name-field-tipo, .field-name-field-category, .field-name-field-categoria')
    images = [urljoin(BASE_URL, img['src']) for img in soup.select('img[src]')
              if img['src'] and 'placeholder' not in img['src'] and not img['src'].endswith('.gif')]
    return {
        'url': url,
        'title': title,
        'description': description,
        'tappe': tappe[:15],
        'length_km': km_m.group(0) if km_m else '',
        'duration': dur_m.group(0) if dur_m else '',
        'difficulty': clean(diff_el.get_text()) if diff_el else '',
        'tipo': clean(tipo_el.get_text()) if tipo_el else '',
        'images': images[:4],
    }

async def scrape_itinerari(page) -> list:
    print("\n[4/4] Scraping itinerari...")
    listing_url = None
    for url in ITINERARIO_LISTING_URLS:
        try:
            resp = await page.goto(url, timeout=10000, wait_until="domcontentloaded")
            if resp and resp.status == 200:
                listing_url = url
                print(f"  Listing itinerari: {url}")
                break
        except:
            pass
    if not listing_url:
        print("  ✗ Pagina itinerari non trovata")
        return []
    itinerary_links = set()
    current = listing_url
    visited = set()
    while current and current not in visited:
        visited.add(current)
        html = await get_html(page, current, delay=1.0)
        if not html:
            break
        soup = BeautifulSoup(html, 'lxml')
        for a in soup.find_all('a', href=True):
            href = a['href']
            full = urljoin(BASE_URL, href)
            if re.search(r'/(itinerar|percors|esperienz|tour|node)/[a-z0-9\-]+', href, re.I):
                if full.startswith(BASE_URL) and full != listing_url:
                    itinerary_links.add(full)
        next_a = soup.find('a', {'rel': 'next'}) or soup.find('a', title=re.compile(r'pag|next|succ', re.I))
        current = urljoin(BASE_URL, next_a['href']) if next_a else None
    print(f"  Trovati {len(itinerary_links)} link itinerari")
    itinerari = []
    for i, url in enumerate(sorted(itinerary_links), 1):
        print(f"  [{i}/{len(itinerary_links)}] {url}")
        html = await get_html(page, url, delay=0.8)
        if html:
            data = parse_itinerary(html, url)
            if data['title']:
                itinerari.append(data)
    return itinerari

async def main():
    print("=" * 60)
    print("  Scraper v2 — sistemairpinia.provincia.avellino.it")
    print("=" * 60)
    async with async_playwright() as p:
        browser = await p.chromium.launch(
            headless=True,
            args=['--no-sandbox', '--disable-blink-features=AutomationControlled']
        )
        context = await browser.new_context(
            viewport={"width": 1280, "height": 900},
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/120.0.0.0 Safari/537.36"
            ),
            locale="it-IT",
            extra_http_headers={"Accept-Language": "it-IT,it;q=0.9"},
        )
        page = await context.new_page()
        print("\n[1/4] Apertura sito...")
        await get_html(page, BASE_URL, delay=2.0)
        borghi    = await scrape_borghi(page)
        eventi    = await scrape_eventi(page)
        itinerari = await scrape_itinerari(page)
        await browser.close()
    save_json(borghi,    'borghi_raw.json')
    save_json(eventi,    'eventi_raw.json')
    save_json(itinerari, 'itinerari_raw.json')
    print("\n" + "=" * 60)
    print(f"  COMPLETATO")
    print(f"  Borghi:    {len(borghi)}")
    print(f"  Eventi:    {len(eventi)}")
    print(f"  Itinerari: {len(itinerari)}")
    print("=" * 60)
    print("\nInvia i 3 file JSON e procediamo con l'integrazione nel DB.")

if __name__ == '__main__':
    asyncio.run(main())
