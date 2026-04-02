#!/usr/bin/env python3
"""
Script diagnostico per sistemairpinia.provincia.avellino.it
Stampa struttura del sito: tutti i link, menu, URL pattern.

Utilizzo:
    pip install playwright beautifulsoup4 lxml
    playwright install chromium
    python scraper_debug.py
"""

import asyncio
import json
import re
from urllib.parse import urljoin
from playwright.async_api import async_playwright
from bs4 import BeautifulSoup

BASE_URL = "https://sistemairpinia.provincia.avellino.it"

CANDIDATE_URLS = [
    f"{BASE_URL}/",
    f"{BASE_URL}/it",
    f"{BASE_URL}/en",
    f"{BASE_URL}/it/borghi",
    f"{BASE_URL}/it/borghi-e-centri-storici",
    f"{BASE_URL}/it/borghi-piu-belli-ditalia",
    f"{BASE_URL}/it/comuni",
    f"{BASE_URL}/en/villages",
    f"{BASE_URL}/en/cities",
    f"{BASE_URL}/it/luoghi",
    f"{BASE_URL}/it/cosa-vedere",
    f"{BASE_URL}/it/eventi",
    f"{BASE_URL}/it/calendario",
    f"{BASE_URL}/en/events",
    f"{BASE_URL}/it/manifestazioni",
    f"{BASE_URL}/it/itinerari",
    f"{BASE_URL}/it/percorsi",
    f"{BASE_URL}/en/itineraries",
    f"{BASE_URL}/it/esperienze",
    f"{BASE_URL}/sitemap.xml",
    f"{BASE_URL}/sitemap",
]

async def main():
    print("=" * 70)
    print("  DIAGNOSTICA sistemairpinia.provincia.avellino.it")
    print("=" * 70)

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/120.0.0.0 Safari/537.36"
            ),
            locale="it-IT",
        )
        page = await context.new_page()

        print("\n[1] Verifica URL candidate...\n")
        working = []
        for url in CANDIDATE_URLS:
            try:
                resp = await page.goto(url, timeout=15000, wait_until="domcontentloaded")
                status = resp.status if resp else 0
                marker = "✓" if status < 400 else "✗"
                print(f"  {marker} [{status}] {url}")
                if status < 400:
                    working.append(url)
            except Exception as e:
                print(f"  ✗ [ERR] {url}  ({e})")
            await asyncio.sleep(0.5)

        print("\n[2] Analisi menu/navigazione homepage...\n")
        await page.goto(BASE_URL, wait_until="networkidle", timeout=30000)
        await asyncio.sleep(2)
        html = await page.content()
        soup = BeautifulSoup(html, 'lxml')

        print("  Link nel menu principale:")
        for nav in soup.select('nav a, .menu a, .navbar a, header a, .navigation a, #menu a'):
            href = nav.get('href', '')
            text = nav.get_text(strip=True)
            if href and text and len(text) > 1:
                full = urljoin(BASE_URL, href)
                if BASE_URL in full or href.startswith('/'):
                    print(f"    [{text}] → {full}")

        if working:
            borghi_url = next((u for u in working if any(k in u for k in ['borg', 'comu', 'villag', 'cities'])), working[0])
            print(f"\n[3] Analisi pagina: {borghi_url}\n")
            await page.goto(borghi_url, wait_until="networkidle", timeout=30000)
            await asyncio.sleep(2)
            html = await page.content()
            soup = BeautifulSoup(html, 'lxml')

            internal_links = {}
            for a in soup.find_all('a', href=True):
                href = a['href']
                text = a.get_text(strip=True)[:60]
                full = urljoin(BASE_URL, href)
                if full.startswith(BASE_URL) and len(href) > 1:
                    internal_links[full] = text

            print(f"  Trovati {len(internal_links)} link interni:")
            for url, text in sorted(internal_links.items())[:80]:
                print(f"    [{text}] → {url}")

            print("\n[4] Classi CSS principali nella pagina...")
            all_classes = []
            for el in soup.find_all(class_=True):
                for cls in el.get('class', []):
                    all_classes.append(cls)
            from collections import Counter
            top_classes = Counter(all_classes).most_common(40)
            print("  Top classi CSS:")
            for cls, count in top_classes:
                print(f"    {count:4d}x  .{cls}")

            print("\n[5] Primo elemento lista (HTML grezzo)...")
            for sel in ['.views-row', '.node-teaser', '.card', '.item', 'article', '.view-content > div:first-child']:
                first = soup.select_one(sel)
                if first:
                    print(f"  Selettore: {sel}")
                    print(f"  HTML:\n{str(first)[:1000]}\n")
                    break

        await page.goto(BASE_URL, wait_until="networkidle", timeout=30000)
        await asyncio.sleep(2)
        html = await page.content()
        with open('homepage_debug.html', 'w', encoding='utf-8') as f:
            f.write(html)
        print("\n[6] Homepage salvata in: homepage_debug.html")

        if working:
            borghi_url = next((u for u in working if any(k in u for k in ['borg', 'comu', 'villag', 'cities'])), None)
            if borghi_url:
                await page.goto(borghi_url, wait_until="networkidle", timeout=30000)
                await asyncio.sleep(2)
                html = await page.content()
                with open('borghi_listing_debug.html', 'w', encoding='utf-8') as f:
                    f.write(html)
                print(f"[7] Pagina borghi salvata in: borghi_listing_debug.html")

        await browser.close()

    print("\n" + "=" * 70)
    print("Invia questi file per analisi:")
    print("  - homepage_debug.html")
    print("  - borghi_listing_debug.html (se presente)")
    print("=" * 70)

if __name__ == '__main__':
    asyncio.run(main())
