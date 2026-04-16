<?php
// api/export/generate_poi.php
// Chiamata diretta (inclusa da punti-interesse-edit.php) o via GET con token.

if (!function_exists('getDB')) {
    require_once __DIR__ . '/../config/db.php';
}

/**
 * Genera il file HTML statico per un singolo POI.
 * Crea la directory borghi/[borough]/[slug]/ se non esiste.
 */
function exportPoiHtml(PDO $db, string $poiId): bool {
    $stmt = $db->prepare("SELECT * FROM points_of_interest WHERE id=?");
    $stmt->execute([$poiId]);
    $poi = $stmt->fetch();
    if (!$poi) return false;

    // Carica immagini galleria
    $imgStmt = $db->prepare(
        "SELECT src, alt FROM entity_images WHERE entity_type='poi' AND entity_id=? ORDER BY sort_order"
    );
    $imgStmt->execute([$poiId]);
    $images = $imgStmt->fetchAll();

    $html = renderPoiHtml($poi, $images);

    // Path: da public_html/api/export/ risaliamo a public_html/borghi/
    // dirname(__DIR__, 2) == public_html root
    $publicHtml = dirname(__DIR__, 2);
    $outDir = $publicHtml . '/borghi/' . $poi['borough_id'] . '/' . $poi['id'];
    if (!is_dir($outDir)) {
        if (!mkdir($outDir, 0755, true) && !is_dir($outDir)) {
            error_log("exportPoiHtml: impossibile creare directory $outDir");
            return false;
        }
    }

    return file_put_contents($outDir . '/index.html', $html) !== false;
}

/**
 * Genera tutti i POI di un borough (o tutti se $boroughId = null).
 * Restituisce il numero di file generati con successo.
 */
function exportAllPoi(PDO $db, ?string $boroughId = null): int {
    $sql = "SELECT id FROM points_of_interest";
    $params = [];
    if ($boroughId) {
        $sql .= " WHERE borough_id = ?";
        $params[] = $boroughId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $count = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
        if (exportPoiHtml($db, $id)) {
            $count++;
        }
    }
    return $count;
}

/**
 * Restituisce l'HTML completo della scheda POI — iframe-first, vanilla JS, design system MetaBorghi.
 */
function renderPoiHtml(array $poi, array $images): string {
    // ── Dati testo (htmlspecialchars su tutti i campi DB) ──────────────────
    $nameIt  = htmlspecialchars($poi['name_it']  ?? '', ENT_QUOTES, 'UTF-8');
    $nameEn  = htmlspecialchars($poi['name_en']  ?? $poi['name_it'] ?? '', ENT_QUOTES, 'UTF-8');
    $nameIrp = htmlspecialchars($poi['name_irp'] ?? $poi['name_it'] ?? '', ENT_QUOTES, 'UTF-8');

    $descIt  = nl2br(htmlspecialchars($poi['desc_it']  ?? '', ENT_QUOTES, 'UTF-8'));
    $descEn  = nl2br(htmlspecialchars($poi['desc_en']  ?? '', ENT_QUOTES, 'UTF-8'));
    $descIrp = nl2br(htmlspecialchars($poi['desc_irp'] ?? '', ENT_QUOTES, 'UTF-8'));

    $cat     = htmlspecialchars($poi['category']   ?? '', ENT_QUOTES, 'UTF-8');
    $borough = htmlspecialchars($poi['borough_id'] ?? '', ENT_QUOTES, 'UTF-8');
    $cover   = htmlspecialchars($poi['cover_image'] ?? '', ENT_QUOTES, 'UTF-8');

    // ── Tag pills ──────────────────────────────────────────────────────────
    $tagPills = '';
    $rawTags = $poi['tags'] ?? '';
    if ($rawTags !== '') {
        foreach (array_filter(array_map('trim', explode(',', $rawTags))) as $tag) {
            $tagPills .= '<span class="tag-pill">' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</span>';
        }
    }

    // ── Determina tab attive ───────────────────────────────────────────────
    $hasAudio  = !empty($poi['audio_it']) || !empty($poi['audio_en']) || !empty($poi['audio_irp']);
    $hasVideo  = !empty($poi['video_it']) || !empty($poi['video_en']);
    $hasImages = !empty($images) || !empty($poi['cover_image']);

    // ── Dati JSON per JS (non escapati ulteriormente — json_encode gestisce) ──
    $imgJson = json_encode(
        array_map(fn($i) => ['src' => $i['src'] ?? '', 'alt' => $i['alt'] ?? ''], $images),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $audioJson = json_encode([
        'it'  => ['url' => $poi['audio_it']      ?? '', 'transcript' => $poi['transcript_it']  ?? ''],
        'en'  => ['url' => $poi['audio_en']      ?? '', 'transcript' => $poi['transcript_en']  ?? ''],
        'irp' => ['url' => $poi['audio_irp']     ?? '', 'transcript' => $poi['transcript_irp'] ?? ''],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $videoJson = json_encode([
        'it' => $poi['video_it'] ?? '',
        'en' => $poi['video_en'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // ── Tab HTML condizionali (stringa vuota = tab nascosta) ───────────────
    $tabAudio  = $hasAudio  ? '<button class="tab" data-tab="audio">&#127911; Audio</button>'    : '';
    $tabVideo  = $hasVideo  ? '<button class="tab" data-tab="video">&#127909; Video</button>'    : '';
    $tabImages = $hasImages ? '<button class="tab" data-tab="immagini">&#128444; Immagini</button>' : '';

    // ── Hero style ─────────────────────────────────────────────────────────
    $heroStyle = $cover ? "background-image:url('" . addslashes($cover) . "');" : '';

    // ── HTML ───────────────────────────────────────────────────────────────
    return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"/>
<title>{$nameIt} &mdash; MetaBorghi</title>
<meta name="description" content="{$nameIt} &mdash; {$cat} &mdash; Alta Irpinia, MetaBorghi"/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<style>
/* ── Reset & Variables ─────────────────────────────────── */
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --green:#00D084;--cyan:#00B4D8;
  --notte:#1A1A2E;--notte-lt:#2D2D48;--notte-card:#1e2235;
  --surface:#FAFAF8;--surface-alt:#F0EDE8;
  --border:#E2DDD6;--text:#1C1917;--text-sec:#57534E;--text-muted:#A8A29E
}
html,body{height:100%;font-family:'Inter',sans-serif;background:var(--notte);color:var(--text);overflow-x:hidden}
/* ── POI Header ────────────────────────────────────────── */
.poi-header{
  background:linear-gradient(160deg,var(--notte) 0%,#1e1e38 60%,#0d2a1f 100%);
  padding:16px 18px 0;position:relative;overflow:hidden
}
.poi-header::before{
  content:'';position:absolute;top:-30px;right:-30px;
  width:140px;height:140px;border-radius:50%;
  background:radial-gradient(circle,rgba(0,208,132,.18) 0%,transparent 70%)
}
.header-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;position:relative}
.borough-chip{font-size:10px;font-weight:500;color:rgba(255,255,255,.5);letter-spacing:.08em;text-transform:uppercase}
/* ── Lang Toggle ────────────────────────────────────────── */
.lang-toggle{display:flex;gap:3px;background:rgba(255,255,255,.08);border-radius:20px;padding:3px}
.lang-btn{
  font-size:11px;font-weight:600;color:rgba(255,255,255,.5);
  padding:4px 9px;border-radius:16px;cursor:pointer;border:none;
  background:transparent;transition:.15s
}
.lang-btn.active{background:var(--green);color:#fff}
/* ── POI Name & Category ─────────────────────────────────── */
.poi-category{
  display:inline-flex;align-items:center;gap:5px;
  font-size:10px;font-weight:600;letter-spacing:.1em;
  text-transform:uppercase;color:var(--green);margin-bottom:6px;position:relative
}
.poi-category::before{content:'';width:16px;height:1px;background:var(--green)}
.poi-name{
  font-family:'Playfair Display',serif;font-size:24px;font-weight:700;
  color:#fff;line-height:1.15;margin-bottom:10px;position:relative
}
/* ── Hero Image ─────────────────────────────────────────── */
.hero{
  margin:0 -18px;height:140px;
  background:#0d2a1f;
  background-size:cover;background-position:center;
  position:relative
}
.hero-overlay{
  position:absolute;bottom:0;left:0;right:0;height:50px;
  background:linear-gradient(0deg,var(--notte) 0%,transparent 100%)
}
/* ── Tab Bar ─────────────────────────────────────────────── */
.tab-bar{
  display:flex;background:var(--notte-lt);
  border-bottom:1px solid rgba(255,255,255,.06);overflow-x:auto;
  scrollbar-width:none
}
.tab-bar::-webkit-scrollbar{display:none}
.tab{
  flex:1;min-width:70px;text-align:center;
  padding:11px 6px 9px;
  font-size:10px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;
  color:rgba(255,255,255,.35);cursor:pointer;
  border:none;border-bottom:2px solid transparent;
  background:transparent;transition:.15s;white-space:nowrap
}
.tab.active{color:var(--green);border-bottom-color:var(--green)}
/* ── Content Panels ──────────────────────────────────────── */
.content{min-height:200px;padding:16px}
.section-label{
  font-size:10px;font-weight:600;letter-spacing:.12em;
  text-transform:uppercase;color:var(--text-muted);margin-bottom:8px
}
.panel{display:none}.panel.active{display:block}
/* ── Info Tab ────────────────────────────────────────────── */
.info-content{background:var(--surface)}
.desc-text{font-size:13.5px;line-height:1.75;color:var(--text-sec);margin-bottom:16px}
.tag-row{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
.tag-pill{
  font-size:10px;font-weight:500;padding:4px 10px;border-radius:20px;
  background:rgba(0,208,132,.1);color:var(--green);border:1px solid rgba(0,208,132,.25)
}
/* ── Audio Tab ───────────────────────────────────────────── */
.audio-content{background:var(--notte)}
.audio-card{background:var(--notte-card);border-radius:12px;padding:14px;margin-bottom:10px}
.audio-top{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.play-btn{
  width:38px;height:38px;border-radius:50%;background:var(--green);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;flex-shrink:0;border:none
}
.triangle{
  width:0;height:0;
  border-top:6px solid transparent;border-bottom:6px solid transparent;
  border-left:10px solid #fff;margin-left:2px
}
.audio-meta h4{font-family:'Playfair Display',serif;font-size:13px;color:#fff;margin-bottom:2px}
.audio-meta p{font-size:10px;color:rgba(255,255,255,.4)}
.audio-player{width:100%;height:32px;margin-top:6px;accent-color:var(--green)}
.transcript-btn{
  font-size:10px;color:rgba(255,255,255,.4);background:none;
  border:none;cursor:pointer;padding:6px 0;display:block
}
.transcript-box{
  display:none;font-size:12px;line-height:1.65;
  color:rgba(255,255,255,.5);font-style:italic;
  background:rgba(0,0,0,.2);border-radius:8px;padding:10px;margin-top:6px
}
.audio-lang-btns{display:flex;gap:6px;margin-top:10px}
.audio-lang-btn{
  flex:1;text-align:center;padding:7px;border-radius:9px;
  border:1px solid rgba(255,255,255,.1);
  font-size:10px;font-weight:600;color:rgba(255,255,255,.4);
  cursor:pointer;background:none;transition:.15s
}
.audio-lang-btn.active{
  background:rgba(0,208,132,.15);border-color:rgba(0,208,132,.4);color:var(--green)
}
/* ── Video Tab ───────────────────────────────────────────── */
.video-content{background:var(--notte)}
.video-card{border-radius:12px;overflow:hidden;margin-bottom:10px}
.video-wrap{position:relative;width:100%;padding-bottom:56.25%}
.video-wrap video{position:absolute;inset:0;width:100%;height:100%;background:#000}
.video-info{background:var(--notte-card);padding:10px 13px}
.video-info h4{font-family:'Playfair Display',serif;font-size:13px;color:#fff}
.video-info p{font-size:10px;color:rgba(255,255,255,.4);margin-top:2px}
.video-empty{text-align:center;padding:40px 16px;color:rgba(255,255,255,.25)}
/* ── Immagini Tab ─────────────────────────────────────────── */
.img-content{background:var(--surface)}
.img-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-bottom:10px}
.img-card{border-radius:9px;overflow:hidden;cursor:pointer;position:relative}
.img-card-full{grid-column:1/-1}
.img-card img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block;transition:.2s}
.img-card-full img{aspect-ratio:16/7}
.img-card:hover img{transform:scale(1.03)}
/* ── Lightbox ─────────────────────────────────────────────── */
.lightbox{
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);
  z-index:1000;align-items:center;justify-content:center;flex-direction:column
}
.lightbox.open{display:flex}
.lightbox img{max-width:95vw;max-height:88vh;border-radius:8px;object-fit:contain}
.lightbox-close{
  position:absolute;top:14px;right:16px;
  font-size:28px;color:#fff;cursor:pointer;background:none;border:none;line-height:1
}
/* ── CTA Footer ───────────────────────────────────────────── */
.cta-footer{
  background:linear-gradient(135deg,var(--notte),#0d2a1f);
  padding:12px 18px;display:flex;align-items:center;
  justify-content:space-between;flex-shrink:0
}
.cta-text{font-size:10px;color:rgba(255,255,255,.4)}
.cta-link{
  font-size:11px;font-weight:600;color:var(--green);
  text-decoration:none;letter-spacing:.04em
}
.cta-link:hover{color:#fff}
</style>
</head>
<body>

<!-- ── HEADER ─────────────────────────────────────────────── -->
<div class="poi-header">
  <div class="header-row">
    <span class="borough-chip">{$borough} &middot; Alta Irpinia</span>
    <div class="lang-toggle">
      <button class="lang-btn active" onclick="setLang('it')">IT</button>
      <button class="lang-btn" onclick="setLang('en')">EN</button>
      <button class="lang-btn" onclick="setLang('irp')">IRP</button>
    </div>
  </div>
  <div class="poi-category">{$cat}</div>
  <div class="poi-name"
       id="poi-name"
       data-it="{$nameIt}"
       data-en="{$nameEn}"
       data-irp="{$nameIrp}">{$nameIt}</div>
  <div class="hero" style="{$heroStyle}">
    <div class="hero-overlay"></div>
  </div>
</div>

<!-- ── TAB BAR ────────────────────────────────────────────── -->
<div class="tab-bar">
  <button class="tab active" data-tab="info">&#128214; Info</button>
  {$tabAudio}
  {$tabVideo}
  {$tabImages}
</div>

<!-- ── INFO PANEL ─────────────────────────────────────────── -->
<div class="panel info-content content active" id="panel-info">
  <div class="section-label">Descrizione</div>
  <p class="desc-text lang-text"
     data-it="{$descIt}"
     data-en="{$descEn}"
     data-irp="{$descIrp}">{$descIt}</p>
  <div class="section-label">Categorie</div>
  <div class="tag-row">{$tagPills}</div>
</div>

<!-- ── AUDIO PANEL ────────────────────────────────────────── -->
<div class="panel audio-content content" id="panel-audio">
  <div class="section-label" style="color:rgba(255,255,255,.3)">Narrazione audio</div>
  <div class="audio-card" id="audio-card">
    <div class="audio-top">
      <button class="play-btn" onclick="toggleAudio()" aria-label="Play/Pausa">
        <div class="triangle" id="play-triangle"></div>
      </button>
      <div class="audio-meta">
        <h4 id="audio-title">Narrazione &middot; Italiano</h4>
        <p>Cicerone Digitale &middot; MetaBorghi</p>
      </div>
    </div>
    <audio id="audio-player" class="audio-player" controls preload="none"></audio>
    <button class="transcript-btn" onclick="toggleTranscript()">
      &#128196; Mostra trascrizione &#9660;
    </button>
    <div class="transcript-box" id="transcript-box"></div>
    <div class="audio-lang-btns">
      <button class="audio-lang-btn active" onclick="switchAudioLang('it')">IT</button>
      <button class="audio-lang-btn" onclick="switchAudioLang('en')">EN</button>
      <button class="audio-lang-btn" onclick="switchAudioLang('irp')">IRP</button>
    </div>
  </div>
</div>

<!-- ── VIDEO PANEL ────────────────────────────────────────── -->
<div class="panel video-content content" id="panel-video">
  <div class="section-label" style="color:rgba(255,255,255,.3)">Video</div>
  <div id="video-container"></div>
</div>

<!-- ── IMMAGINI PANEL ─────────────────────────────────────── -->
<div class="panel img-content content" id="panel-immagini">
  <div class="section-label">Galleria fotografica</div>
  <div class="img-grid" id="img-grid"></div>
</div>

<!-- ── LIGHTBOX ───────────────────────────────────────────── -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <button class="lightbox-close" onclick="closeLightbox()" aria-label="Chiudi">&#215;</button>
  <img id="lightbox-img" src="" alt=""/>
</div>

<!-- ── CTA FOOTER ─────────────────────────────────────────── -->
<div class="cta-footer">
  <span class="cta-text">MetaBorghi &middot; Alta Irpinia</span>
  <a class="cta-link" href="https://metaborghi.org" target="_blank" rel="noopener">metaborghi.org &#8594;</a>
</div>

<script>
/* ── Dati dal PHP ──────────────────────────────────────── */
var AUDIO  = {$audioJson};
var VIDEO  = {$videoJson};
var IMAGES = {$imgJson};
var COVER  = '{$cover}';

var currentLang      = 'it';
var currentAudioLang = 'it';

/* ── TABS ──────────────────────────────────────────────── */
document.querySelectorAll('.tab').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.panel').forEach(function(p) { p.classList.remove('active'); });
    btn.classList.add('active');
    var panel = document.getElementById('panel-' + btn.dataset.tab);
    if (panel) panel.classList.add('active');
    if (btn.dataset.tab === 'video')    renderVideo();
    if (btn.dataset.tab === 'immagini') renderImages();
  });
});

/* ── LINGUA GLOBALE ────────────────────────────────────── */
function setLang(lang) {
  currentLang = lang;

  // Aggiorna bottoni lang
  document.querySelectorAll('.lang-btn').forEach(function(b) { b.classList.remove('active'); });
  var activeBtn = document.querySelector('.lang-btn[onclick="setLang(\'' + lang + '\')"]');
  if (activeBtn) activeBtn.classList.add('active');

  // Nome POI
  var nm = document.getElementById('poi-name');
  if (nm) nm.textContent = nm.dataset[lang] || nm.dataset.it;

  // Testi descrizione (innerHTML per mantenere <br>)
  document.querySelectorAll('.lang-text').forEach(function(el) {
    el.innerHTML = el.dataset[lang] || el.dataset.it;
  });

  // html lang attribute
  document.documentElement.lang = lang === 'irp' ? 'it' : lang;
}

/* ── AUDIO ─────────────────────────────────────────────── */
function switchAudioLang(lang) {
  currentAudioLang = lang;

  document.querySelectorAll('.audio-lang-btn').forEach(function(b) { b.classList.remove('active'); });
  var activeBtn = document.querySelector('.audio-lang-btn[onclick="switchAudioLang(\'' + lang + '\')"]');
  if (activeBtn) activeBtn.classList.add('active');

  var data   = AUDIO[lang] || {};
  var player = document.getElementById('audio-player');
  if (data.url) {
    player.src = data.url;
    var labels = { it: 'Narrazione \u00b7 Italiano', en: 'Narration \u00b7 English', irp: 'Narrazione \u00b7 Cerugn\u00e9s' };
    var title  = document.getElementById('audio-title');
    if (title) title.textContent = labels[lang] || labels.it;
  }

  // Reset trascrizione
  var box = document.getElementById('transcript-box');
  var btn = document.querySelector('.transcript-btn');
  if (box) { box.innerHTML = ''; box.style.display = 'none'; }
  if (btn) btn.textContent = '\u{1F4C4} Mostra trascrizione \u25be';
}

function toggleAudio() {
  var p = document.getElementById('audio-player');
  if (!p.src) switchAudioLang(currentAudioLang);
  if (p.paused) {
    p.play().catch(function() {});
  } else {
    p.pause();
  }
}

function toggleTranscript() {
  var box = document.getElementById('transcript-box');
  var btn = document.querySelector('.transcript-btn');
  if (!box || !btn) return;
  var open = box.style.display === 'block';
  if (!open && !box.innerHTML) {
    var data = AUDIO[currentAudioLang] || {};
    box.innerHTML = data.transcript
      ? data.transcript
      : 'Trascrizione non disponibile.';
  }
  box.style.display = open ? 'none' : 'block';
  btn.textContent = open
    ? '\u{1F4C4} Mostra trascrizione \u25be'
    : '\u{1F4C4} Nascondi trascrizione \u25b4';
}

/* Init audio: nasconde bottoni senza file, seleziona prima lingua disponibile */
(function initAudio() {
  var langs = ['it', 'en', 'irp'];
  langs.forEach(function(l) {
    var btn = document.querySelector('.audio-lang-btn[onclick="switchAudioLang(\'' + l + '\')"]');
    if (btn && !(AUDIO[l] && AUDIO[l].url)) btn.style.display = 'none';
  });
  var first = langs.find(function(l) { return AUDIO[l] && AUDIO[l].url; });
  if (first) switchAudioLang(first);
}());

/* ── VIDEO ─────────────────────────────────────────────── */
function renderVideo() {
  var container = document.getElementById('video-container');
  if (!container || container.dataset.rendered) return;
  container.dataset.rendered = '1';

  var langs = [['it', 'Italiano'], ['en', 'English']];
  var html  = '';
  langs.forEach(function(pair) {
    var lang  = pair[0];
    var label = pair[1];
    if (!VIDEO[lang]) return;
    html += '<div class="video-card">'
          + '<div class="video-wrap"><video controls preload="none" playsinline>'
          + '<source src="' + escHtml(VIDEO[lang]) + '" type="video/mp4"/>'
          + '</video></div>'
          + '<div class="video-info">'
          + '<h4>' + escHtml(label) + '</h4>'
          + '<p>Cicerone Digitale &middot; MetaBorghi</p>'
          + '</div></div>';
  });
  container.innerHTML = html || '<div class="video-empty"><p>Nessun video disponibile</p></div>';
}

/* ── IMMAGINI ───────────────────────────────────────────── */
function renderImages() {
  var grid = document.getElementById('img-grid');
  if (!grid || grid.dataset.rendered) return;
  grid.dataset.rendered = '1';

  var all  = COVER ? [{ src: COVER, alt: 'Cover' }].concat(IMAGES) : IMAGES;
  var html = '';
  all.forEach(function(img, i) {
    var cls = i === 0 ? 'img-card img-card-full' : 'img-card';
    html += '<div class="' + cls + '" onclick="openLightbox(\'' + escJs(img.src) + '\')">'
          + '<img src="' + escHtml(img.src) + '" alt="' + escHtml(img.alt || '') + '" loading="lazy"/>'
          + '</div>';
  });
  grid.innerHTML = html
    || '<p style="color:var(--text-muted);font-size:13px">Nessuna immagine disponibile</p>';
}

/* ── LIGHTBOX ───────────────────────────────────────────── */
function openLightbox(src) {
  var lbImg = document.getElementById('lightbox-img');
  var lb    = document.getElementById('lightbox');
  if (!lbImg || !lb) return;
  lbImg.src = src;
  lb.classList.add('open');
}
function closeLightbox() {
  var lb = document.getElementById('lightbox');
  if (lb) lb.classList.remove('open');
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeLightbox();
});

/* ── Utility: escape HTML e JS ─────────────────────────── */
function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
function escJs(str) {
  return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}
</script>
</body>
</html>
HTML;
}

/* ── Chiamata diretta via GET con token (export massivo da browser/cron) ── */
if (isset($_GET['token'])) {
    jsonHeaders();
    $token = $_GET['token'] ?? '';
    if (!defined('API_TOKEN') || $token !== API_TOKEN) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $db        = getDB();
    $boroughId = isset($_GET['borough']) ? trim($_GET['borough']) : null;
    $count     = exportAllPoi($db, $boroughId ?: null);
    echo json_encode(['ok' => true, 'exported' => $count]);
    exit;
}
