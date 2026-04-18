<?php
// api/export/generate_poi.php
// Chiamata diretta (inclusa da punti-interesse-edit.php) o via GET con token.

if (!function_exists('getDB')) {
    require_once __DIR__ . '/../config/db.php';
}

function exportPoiHtml(PDO $db, string $poiId): bool {
    $stmt = $db->prepare("SELECT * FROM points_of_interest WHERE id=?");
    $stmt->execute([$poiId]);
    $poi = $stmt->fetch();
    if (!$poi) return false;

    $imgStmt = $db->prepare(
        "SELECT src, alt FROM entity_images WHERE entity_type='poi' AND entity_id=? ORDER BY sort_order"
    );
    $imgStmt->execute([$poiId]);
    $images = $imgStmt->fetchAll();

    $html = renderPoiHtml($poi, $images);

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

function renderPoiHtml(array $poi, array $images): string {
    $nameIt = htmlspecialchars($poi['name_it'] ?? '', ENT_QUOTES, 'UTF-8');
    $nameEn = htmlspecialchars($poi['name_en'] ?: ($poi['name_it'] ?? ''), ENT_QUOTES, 'UTF-8');

    $descIt = htmlspecialchars($poi['desc_it'] ?? '', ENT_QUOTES, 'UTF-8');
    $descEn = htmlspecialchars($poi['desc_en'] ?: ($poi['desc_it'] ?? ''), ENT_QUOTES, 'UTF-8');

    $cat     = htmlspecialchars($poi['category']    ?? '', ENT_QUOTES, 'UTF-8');
    $borough = htmlspecialchars($poi['borough_id']  ?? '', ENT_QUOTES, 'UTF-8');
    $boroughLabel = htmlspecialchars(
        ucwords(str_replace('-', ' ', $poi['borough_id'] ?? '')),
        ENT_QUOTES, 'UTF-8'
    );

    $tagPills = '';
    $rawTags = $poi['tags'] ?? '';
    if ($rawTags !== '') {
        foreach (array_filter(array_map('trim', explode(',', $rawTags))) as $tag) {
            $tagPills .= '<span class="tag-pill">' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</span>';
        }
    }

    $cover = $poi['cover_image'] ?? '';
    $coverEsc = htmlspecialchars($cover, ENT_QUOTES, 'UTF-8');

    // Costruisci gallery: cover come prima immagine, poi entity_images
    $galleryAll = [];
    if ($cover) {
        $galleryAll[] = ['src' => $cover, 'alt' => $poi['name_it'] ?? 'Cover'];
    }
    foreach ($images as $img) {
        $galleryAll[] = ['src' => $img['src'] ?? '', 'alt' => $img['alt'] ?? ''];
    }

    // Flags presenza media (solo IT/EN — no IRP)
    $hasAudio = !empty($poi['audio_it']) || !empty($poi['audio_en']);
    $hasVideo = !empty($poi['video_it']) || !empty($poi['video_en']);
    $hasGallery = !empty($galleryAll);
    $hasStory = !empty($poi['desc_it']) || !empty($poi['desc_en']);

    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    $audioJson = json_encode([
        'it' => ['url' => $poi['audio_it'] ?? '', 'transcript' => $poi['transcript_it'] ?? ''],
        'en' => ['url' => $poi['audio_en'] ?? '', 'transcript' => $poi['transcript_en'] ?? ''],
    ], $jsonFlags) ?: '{}';

    $videoJson = json_encode([
        'it' => $poi['video_it'] ?? '',
        'en' => $poi['video_en'] ?? '',
    ], $jsonFlags) ?: '{}';

    $galleryJson = json_encode($galleryAll, $jsonFlags) ?: '[]';

    $textJson = json_encode([
        'it' => ['name' => $poi['name_it'] ?? '', 'desc' => $poi['desc_it'] ?? ''],
        'en' => ['name' => $poi['name_en'] ?: ($poi['name_it'] ?? ''), 'desc' => $poi['desc_en'] ?: ($poi['desc_it'] ?? '')],
    ], $jsonFlags) ?: '{}';

    $heroBg = $cover ? "background-image:url('" . addslashes($cover) . "');" : '';

    $mediaCards = '';
    $mediaCards .= $hasStory
        ? '<button type="button" class="media-card card-story" data-open="story">
             <div class="card-icon">&#128216;</div>
             <div class="card-title" data-it="Storia" data-en="Story">Storia</div>
             <div class="card-sub" data-it="Leggi il racconto" data-en="Read the story">Leggi il racconto</div>
             <div class="card-arrow">&#8599;</div>
           </button>'
        : '';

    $audioLangCount = ($poi['audio_it'] ? 1 : 0) + ($poi['audio_en'] ? 1 : 0);
    $mediaCards .= $hasAudio
        ? '<button type="button" class="media-card card-audio" data-open="audio">
             <div class="card-icon">&#127911;</div>
             <div class="card-title" data-it="Ascolta" data-en="Listen">Ascolta</div>
             <div class="card-sub" data-it="Narrazione audio" data-en="Audio narration">Narrazione audio</div>
             <div class="card-arrow">&#8599;</div>
           </button>'
        : '';

    $videoLangCount = ($poi['video_it'] ? 1 : 0) + ($poi['video_en'] ? 1 : 0);
    $mediaCards .= $hasVideo
        ? '<button type="button" class="media-card card-video" data-open="video">
             <div class="card-icon">&#127909;</div>
             <div class="card-title" data-it="Guarda" data-en="Watch">Guarda</div>
             <div class="card-sub" data-it="Video immersivo" data-en="Immersive video">Video immersivo</div>
             <div class="card-arrow">&#8599;</div>
           </button>'
        : '';

    $galleryCount = count($galleryAll);
    $mediaCards .= $hasGallery
        ? '<button type="button" class="media-card card-gallery" data-open="gallery">
             <div class="card-icon">&#128247;</div>
             <div class="card-title" data-it="Galleria" data-en="Gallery">Galleria</div>
             <div class="card-sub"><span data-it="' . $galleryCount . ' foto" data-en="' . $galleryCount . ' photos">' . $galleryCount . ' foto</span></div>
             <div class="card-arrow">&#8599;</div>
           </button>'
        : '';

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
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
:root{
  --green:#00D084;--cyan:#00B4D8;--yellow:#F0FF00;
  --notte:#0B0B18;--notte-lt:#1A1A2E;--notte-card:#141428;
  --surface:#FAFAF8;
  --text:#1C1917;--text-sec:#57534E;--text-muted:#A8A29E;
  --grad-notte:linear-gradient(135deg,#0B0B18 0%,#1A1A2E 50%,#0d2a1f 100%);
  --grad-brand:linear-gradient(135deg,#00D084 0%,#00B4D8 100%);
  --ease-spring:cubic-bezier(.34,1.56,.64,1)
}
html,body{font-family:'Inter',sans-serif;background:var(--notte);color:var(--text);overflow-x:hidden}
body{min-height:100vh;display:flex;flex-direction:column}
main{flex:1;display:flex;flex-direction:column}

/* ══ HERO ══ */
.hero{
  position:relative;
  min-height:58vh;
  display:flex;flex-direction:column;justify-content:space-between;
  padding:18px 20px 28px;
  color:#fff;overflow:hidden;
  background:var(--notte)
}
.hero-bg{
  position:absolute;inset:0;
  background-size:cover;background-position:center;
  filter:saturate(1.1) brightness(.85);
  transform:scale(1.05);
  transition:transform 12s ease-out
}
.hero:hover .hero-bg{transform:scale(1.08)}
.hero-overlay{
  position:absolute;inset:0;
  background:
    radial-gradient(ellipse at top right,rgba(0,208,132,.18) 0%,transparent 50%),
    linear-gradient(180deg,rgba(11,11,24,.5) 0%,rgba(11,11,24,.2) 40%,rgba(11,11,24,.95) 100%)
}
.hero-grain{
  position:absolute;inset:0;opacity:.15;pointer-events:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence baseFrequency='.9'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.5'/%3E%3C/svg%3E")
}
.hero > *{position:relative;z-index:2}

.hero-top{display:flex;justify-content:space-between;align-items:center;gap:12px}
.borough-chip{
  display:inline-flex;align-items:center;gap:7px;
  font-size:10.5px;font-weight:600;color:rgba(255,255,255,.85);
  letter-spacing:.12em;text-transform:uppercase;
  background:rgba(255,255,255,.08);backdrop-filter:blur(10px);
  border:1px solid rgba(255,255,255,.12);
  padding:6px 12px;border-radius:100px
}
.borough-chip::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 10px var(--green)}

.lang-toggle{
  display:flex;gap:2px;
  background:rgba(0,0,0,.35);backdrop-filter:blur(10px);
  border-radius:100px;padding:3px;
  border:1px solid rgba(255,255,255,.12)
}
.lang-btn{
  font-size:11px;font-weight:700;color:rgba(255,255,255,.55);
  padding:6px 13px;border-radius:100px;cursor:pointer;border:none;
  background:transparent;transition:all .25s;font-family:inherit;letter-spacing:.05em
}
.lang-btn.active{background:var(--grad-brand);color:#fff;box-shadow:0 2px 12px rgba(0,208,132,.4)}

.hero-bottom{margin-top:auto;padding-top:60px}
.poi-category{
  display:inline-flex;align-items:center;gap:10px;
  font-size:11px;font-weight:700;letter-spacing:.18em;
  text-transform:uppercase;color:var(--green);margin-bottom:12px;
  text-shadow:0 2px 14px rgba(0,208,132,.3)
}
.poi-category::before{content:'';width:26px;height:2px;background:var(--green);border-radius:2px;box-shadow:0 0 10px var(--green)}
.poi-name{
  font-family:'Playfair Display',serif;font-size:36px;font-weight:700;
  color:#fff;line-height:1.08;margin-bottom:16px;
  letter-spacing:-.015em;
  text-shadow:0 4px 30px rgba(0,0,0,.5)
}
.tag-row{display:flex;flex-wrap:wrap;gap:6px}
.tag-pill{
  font-size:10.5px;font-weight:600;padding:5px 11px;border-radius:100px;
  background:rgba(255,255,255,.08);backdrop-filter:blur(8px);
  color:rgba(255,255,255,.9);
  border:1px solid rgba(255,255,255,.15)
}

/* ══ MEDIA GRID ══ */
.media-section{
  background:var(--notte);
  padding:28px 20px 20px;
  position:relative
}
.section-eyebrow{
  font-size:10.5px;font-weight:700;letter-spacing:.2em;
  text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:14px;
  display:flex;align-items:center;gap:10px
}
.section-eyebrow::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,rgba(255,255,255,.15),transparent)}

.media-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:10px
}
.media-card{
  position:relative;
  background:var(--notte-card);
  border:1px solid rgba(255,255,255,.08);
  border-radius:20px;
  padding:20px 18px;
  min-height:130px;
  display:flex;flex-direction:column;justify-content:space-between;
  color:#fff;text-align:left;cursor:pointer;
  font-family:inherit;
  transition:transform .25s var(--ease-spring), border-color .25s, box-shadow .25s;
  overflow:hidden
}
.media-card::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,transparent 50%,rgba(0,208,132,.08) 100%);
  opacity:0;transition:opacity .3s;pointer-events:none
}
.media-card:hover{transform:translateY(-3px);border-color:rgba(0,208,132,.35);box-shadow:0 10px 30px rgba(0,0,0,.3),0 0 0 1px rgba(0,208,132,.15)}
.media-card:hover::before{opacity:1}
.media-card:active{transform:translateY(-1px)}
.card-icon{
  font-size:26px;line-height:1;margin-bottom:auto;
  filter:drop-shadow(0 2px 8px rgba(0,208,132,.4))
}
.card-title{
  font-family:'Playfair Display',serif;font-size:19px;font-weight:600;
  color:#fff;margin-bottom:3px
}
.card-sub{font-size:11px;color:rgba(255,255,255,.45);letter-spacing:.02em}
.card-arrow{
  position:absolute;top:18px;right:18px;
  font-size:18px;color:rgba(255,255,255,.3);
  transition:transform .25s, color .25s
}
.media-card:hover .card-arrow{color:var(--green);transform:translate(2px,-2px)}

.card-story  .card-icon{filter:drop-shadow(0 2px 10px rgba(240,255,0,.4))}
.card-audio  .card-icon{filter:drop-shadow(0 2px 10px rgba(0,208,132,.5))}
.card-video  .card-icon{filter:drop-shadow(0 2px 10px rgba(0,180,216,.5))}
.card-gallery .card-icon{filter:drop-shadow(0 2px 10px rgba(168,85,247,.4))}

/* ══ MODAL ══ */
.modal{
  display:none;
  position:fixed;inset:0;z-index:100;
  background:rgba(11,11,24,.92);backdrop-filter:blur(12px);
  align-items:flex-end;justify-content:center;
  animation:fadeIn .2s ease
}
.modal.open{display:flex}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}

.modal-panel{
  width:100%;max-width:620px;max-height:92vh;overflow-y:auto;
  background:var(--notte-lt);
  border-top:1px solid rgba(255,255,255,.1);
  border-radius:24px 24px 0 0;
  animation:slideUp .3s var(--ease-spring);
  color:#fff;
  position:relative
}
@media (min-width:720px){
  .modal{align-items:center;padding:20px}
  .modal-panel{border-radius:24px;border:1px solid rgba(255,255,255,.1)}
}
.modal-header{
  position:sticky;top:0;z-index:5;
  background:rgba(26,26,46,.95);backdrop-filter:blur(16px);
  padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.08);
  display:flex;align-items:center;justify-content:space-between;gap:12px
}
.modal-title{
  font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:#fff;
  display:flex;align-items:center;gap:10px
}
.modal-title .dot{width:8px;height:8px;border-radius:50%;background:var(--green);box-shadow:0 0 10px var(--green)}
.modal-close{
  width:36px;height:36px;border-radius:50%;
  background:rgba(255,255,255,.08);border:none;color:#fff;cursor:pointer;
  font-size:20px;line-height:1;
  display:flex;align-items:center;justify-content:center;
  transition:background .2s
}
.modal-close:hover{background:rgba(255,255,255,.16)}
.modal-body{padding:22px 20px 28px}

/* STORY modal */
.story-text{font-size:15px;line-height:1.8;color:rgba(255,255,255,.85);font-weight:300}

/* AUDIO modal */
.audio-big{
  background:var(--notte-card);border:1px solid rgba(255,255,255,.08);
  border-radius:18px;padding:22px 18px
}
.audio-hero{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.audio-playbtn{
  width:56px;height:56px;border-radius:50%;background:var(--grad-brand);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;border:none;flex-shrink:0;
  box-shadow:0 6px 20px rgba(0,208,132,.45);transition:transform .15s
}
.audio-playbtn:active{transform:scale(.94)}
.triangle{width:0;height:0;border-top:9px solid transparent;border-bottom:9px solid transparent;border-left:14px solid #fff;margin-left:3px}
.audio-title-big{font-family:'Playfair Display',serif;font-size:17px;color:#fff;margin-bottom:3px}
.audio-sub-big{font-size:11.5px;color:rgba(255,255,255,.5)}
.audio-player-big{width:100%;height:36px;accent-color:var(--green);margin-bottom:14px}
.audio-warn{
  font-size:12px;color:#ffb4b4;background:rgba(255,80,80,.08);
  border:1px solid rgba(255,80,80,.25);border-radius:10px;
  padding:10px 12px;margin-bottom:12px;line-height:1.5
}
.transcript-toggle{
  font-size:11px;color:rgba(255,255,255,.55);background:none;
  border:none;cursor:pointer;padding:6px 0;font-family:inherit
}
.transcript-toggle:hover{color:var(--green)}
.transcript-body{
  display:none;font-size:13px;line-height:1.7;
  color:rgba(255,255,255,.7);font-style:italic;
  background:rgba(0,0,0,.3);border-radius:12px;padding:14px;margin-top:10px;
  border-left:2px solid var(--green)
}

/* VIDEO modal */
.video-card-big{border-radius:14px;overflow:hidden;margin-bottom:14px;background:#000}
.video-wrap{position:relative;width:100%;padding-bottom:56.25%}
.video-wrap video{position:absolute;inset:0;width:100%;height:100%}
.video-label{font-size:11px;color:rgba(255,255,255,.45);padding:10px 4px 0;font-weight:600;letter-spacing:.1em;text-transform:uppercase}

/* GALLERY modal */
.gallery-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.gallery-grid-item{
  border-radius:12px;overflow:hidden;cursor:pointer;position:relative;
  background:rgba(255,255,255,.05);aspect-ratio:4/3
}
.gallery-grid-item.full{grid-column:1/-1;aspect-ratio:16/9}
.gallery-grid-item img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.gallery-grid-item:hover img{transform:scale(1.04)}

/* LIGHTBOX (zoom immagine) */
.lightbox{
  display:none;position:fixed;inset:0;z-index:200;
  background:rgba(0,0,0,.96);backdrop-filter:blur(8px);
  align-items:center;justify-content:center;padding:16px
}
.lightbox.open{display:flex}
.lightbox img{max-width:100%;max-height:92vh;border-radius:10px;object-fit:contain}
.lightbox-close{
  position:absolute;top:16px;right:16px;
  width:44px;height:44px;border-radius:50%;
  background:rgba(255,255,255,.12);border:none;color:#fff;cursor:pointer;
  font-size:24px;line-height:1;
  display:flex;align-items:center;justify-content:center
}

/* ══ FOOTER ══ */
.mb-footer{
  background:var(--grad-notte);padding:28px 20px 22px;
  position:relative;overflow:hidden;border-top:1px solid rgba(0,208,132,.15)
}
.mb-footer::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:radial-gradient(circle at 30% 50%,rgba(0,208,132,.08) 0%,transparent 60%),
             radial-gradient(circle at 80% 20%,rgba(0,180,216,.06) 0%,transparent 50%)
}
.footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:14px;position:relative}
.footer-logo{
  width:36px;height:36px;border-radius:11px;background:var(--grad-brand);
  display:flex;align-items:center;justify-content:center;
  font-family:'Playfair Display',serif;font-weight:700;color:#fff;font-size:17px;
  box-shadow:0 4px 16px rgba(0,208,132,.4)
}
.footer-brand-text{font-family:'Playfair Display',serif;font-size:17px;font-weight:700;color:#fff}
.footer-brand-text span{color:var(--green)}
.footer-tagline{
  font-size:11.5px;color:rgba(255,255,255,.55);margin-bottom:16px;
  line-height:1.65;position:relative;max-width:480px
}
.footer-links{display:flex;flex-wrap:wrap;gap:16px;margin-bottom:16px;position:relative}
.footer-link{
  font-size:11.5px;font-weight:600;color:rgba(255,255,255,.7);
  text-decoration:none;transition:color .15s
}
.footer-link:hover{color:var(--green)}
.footer-bottom{
  display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;
  padding-top:14px;border-top:1px solid rgba(255,255,255,.08);
  font-size:10.5px;color:rgba(255,255,255,.4);position:relative
}
.footer-bottom a{color:var(--green);text-decoration:none;font-weight:700}
.footer-bottom a:hover{color:#fff}

@media (min-width:720px){
  .hero{padding:26px 36px 40px;min-height:62vh}
  .poi-name{font-size:52px}
  .media-section{padding:36px 36px 28px}
  .media-grid{grid-template-columns:repeat(4,1fr);gap:14px}
  .media-card{min-height:160px;padding:24px 22px}
  .card-title{font-size:20px}
  .mb-footer{padding:36px 36px 26px}
}
</style>
</head>
<body>

<main>
<!-- HERO -->
<section class="hero">
  <div class="hero-bg" style="{$heroBg}"></div>
  <div class="hero-overlay"></div>
  <div class="hero-grain"></div>

  <div class="hero-top">
    <span class="borough-chip">{$boroughLabel} &middot; Alta Irpinia</span>
    <div class="lang-toggle" id="lang-toggle">
      <button type="button" class="lang-btn active" data-lang="it">IT</button>
      <button type="button" class="lang-btn" data-lang="en">EN</button>
    </div>
  </div>

  <div class="hero-bottom">
    <div class="poi-category">{$cat}</div>
    <h1 class="poi-name"
        id="poi-name"
        data-it="{$nameIt}"
        data-en="{$nameEn}">{$nameIt}</h1>
    <div class="tag-row">{$tagPills}</div>
  </div>
</section>

<!-- MEDIA GRID -->
<section class="media-section">
  <div class="section-eyebrow" data-it="Esplora" data-en="Explore">Esplora</div>
  <div class="media-grid">
    {$mediaCards}
  </div>
</section>
</main>

<!-- STORY MODAL -->
<div class="modal" id="modal-story" data-modal="story">
  <div class="modal-panel" role="dialog">
    <div class="modal-header">
      <div class="modal-title"><span class="dot"></span><span data-it="Storia" data-en="Story">Storia</span></div>
      <button type="button" class="modal-close" data-close aria-label="Chiudi">&#215;</button>
    </div>
    <div class="modal-body">
      <p class="story-text" id="story-body"></p>
    </div>
  </div>
</div>

<!-- AUDIO MODAL -->
<div class="modal" id="modal-audio" data-modal="audio">
  <div class="modal-panel" role="dialog">
    <div class="modal-header">
      <div class="modal-title"><span class="dot"></span><span data-it="Ascolta" data-en="Listen">Ascolta</span></div>
      <button type="button" class="modal-close" data-close aria-label="Chiudi">&#215;</button>
    </div>
    <div class="modal-body">
      <div class="audio-big">
        <div class="audio-hero">
          <button type="button" class="audio-playbtn" id="audio-playbtn" aria-label="Play">
            <div class="triangle"></div>
          </button>
          <div>
            <div class="audio-title-big" id="audio-title">Narrazione</div>
            <div class="audio-sub-big">Cicerone Digitale &middot; MetaBorghi</div>
          </div>
        </div>
        <audio id="audio-player" class="audio-player-big" controls preload="metadata"></audio>
        <div class="audio-warn" id="audio-warn" style="display:none"></div>
        <button type="button" class="transcript-toggle" id="transcript-toggle">&#128196; <span data-it="Mostra trascrizione" data-en="Show transcript">Mostra trascrizione</span> &#9662;</button>
        <div class="transcript-body" id="transcript-body"></div>
      </div>
    </div>
  </div>
</div>

<!-- VIDEO MODAL -->
<div class="modal" id="modal-video" data-modal="video">
  <div class="modal-panel" role="dialog">
    <div class="modal-header">
      <div class="modal-title"><span class="dot"></span><span data-it="Guarda" data-en="Watch">Guarda</span></div>
      <button type="button" class="modal-close" data-close aria-label="Chiudi">&#215;</button>
    </div>
    <div class="modal-body" id="video-body"></div>
  </div>
</div>

<!-- GALLERY MODAL -->
<div class="modal" id="modal-gallery" data-modal="gallery">
  <div class="modal-panel" role="dialog">
    <div class="modal-header">
      <div class="modal-title"><span class="dot"></span><span data-it="Galleria" data-en="Gallery">Galleria</span></div>
      <button type="button" class="modal-close" data-close aria-label="Chiudi">&#215;</button>
    </div>
    <div class="modal-body">
      <div class="gallery-grid" id="gallery-grid"></div>
    </div>
  </div>
</div>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox">
  <button type="button" class="lightbox-close" id="lightbox-close" aria-label="Chiudi">&#215;</button>
  <img id="lightbox-img" src="" alt=""/>
</div>

<!-- FOOTER -->
<footer class="mb-footer">
  <div class="footer-brand">
    <div class="footer-logo">M</div>
    <div class="footer-brand-text">Meta<span>Borghi</span></div>
  </div>
  <div class="footer-tagline" data-it="Piattaforma dei 25 borghi dell&rsquo;Alta Irpinia. Scopri, vivi, acquista esperienze autentiche del territorio."
       data-en="The platform of the 25 villages of Alta Irpinia. Discover, experience, buy authentic territory experiences.">
    Piattaforma dei 25 borghi dell&rsquo;Alta Irpinia. Scopri, vivi, acquista esperienze autentiche del territorio.
  </div>
  <div class="footer-links">
    <a class="footer-link" href="https://metaborghi.org/" target="_top" data-it="Home" data-en="Home">Home</a>
    <a class="footer-link" href="https://metaborghi.org/borghi/{$borough}/" target="_top" data-it="Torna a {$boroughLabel}" data-en="Back to {$boroughLabel}">Torna a {$boroughLabel}</a>
    <a class="footer-link" href="https://metaborghi.org/borghi/" target="_top" data-it="Tutti i borghi" data-en="All villages">Tutti i borghi</a>
    <a class="footer-link" href="https://metaborghi.org/chi-siamo" target="_top" data-it="Chi siamo" data-en="About">Chi siamo</a>
  </div>
  <div class="footer-bottom">
    <span>&copy; InnTour S.R.L. &middot; Alta Irpinia</span>
    <a href="https://metaborghi.org" target="_top">metaborghi.org &#8594;</a>
  </div>
</footer>

<script>
(function(){
  var AUDIO, VIDEO, GALLERY, TEXTS;
  try { AUDIO   = {$audioJson};   } catch(e) { AUDIO   = {it:{url:'',transcript:''},en:{url:'',transcript:''}}; }
  try { VIDEO   = {$videoJson};   } catch(e) { VIDEO   = {it:'',en:''}; }
  try { GALLERY = {$galleryJson}; } catch(e) { GALLERY = []; }
  try { TEXTS   = {$textJson};    } catch(e) { TEXTS   = {it:{name:'',desc:''},en:{name:'',desc:''}}; }

  var currentLang = 'it';

  /* ── LANG TOGGLE ── */
  var langToggle = document.getElementById('lang-toggle');
  if (langToggle) {
    langToggle.addEventListener('click', function(e){
      var btn = e.target.closest('.lang-btn');
      if (!btn) return;
      var l = btn.getAttribute('data-lang');
      if (l) setLang(l);
    });
  }

  function setLang(lang){
    currentLang = lang;
    var btns = document.querySelectorAll('.lang-btn');
    for (var i=0;i<btns.length;i++){
      btns[i].classList.toggle('active', btns[i].getAttribute('data-lang') === lang);
    }
    // Aggiorna tutti gli elementi con data-it / data-en
    var els = document.querySelectorAll('[data-it]');
    for (var j=0;j<els.length;j++){
      var v = els[j].getAttribute('data-' + lang);
      if (v !== null) els[j].textContent = v;
    }
    document.documentElement.lang = lang;
    // Aggiorna contenuti dinamici se modal aperti
    renderStory();
    syncAudioToLang();
  }

  /* ── MODAL OPEN/CLOSE ── */
  function openModal(name){
    var m = document.getElementById('modal-' + name);
    if (!m) return;
    m.classList.add('open');
    document.body.style.overflow = 'hidden';
    if (name === 'story')   renderStory();
    if (name === 'audio')   renderAudio();
    if (name === 'video')   renderVideo();
    if (name === 'gallery') renderGallery();
  }
  function closeModal(el){
    var m = el ? el.closest('.modal') : document.querySelector('.modal.open');
    if (m) m.classList.remove('open');
    if (!document.querySelector('.modal.open')) document.body.style.overflow = '';
    // Stop audio on close
    var p = document.getElementById('audio-player');
    if (p && !p.paused) p.pause();
  }

  document.body.addEventListener('click', function(e){
    var opener = e.target.closest('[data-open]');
    if (opener) { openModal(opener.getAttribute('data-open')); return; }
    var closer = e.target.closest('[data-close]');
    if (closer) { closeModal(closer); return; }
    // Click su backdrop (fuori dal panel) chiude
    var modal = e.target.closest('.modal');
    if (modal && e.target === modal) closeModal(modal);
  });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });

  /* ── STORY ── */
  function renderStory(){
    var body = document.getElementById('story-body');
    if (!body) return;
    var t = TEXTS[currentLang] || TEXTS.it || {desc:''};
    body.textContent = t.desc || (TEXTS.it && TEXTS.it.desc) || '';
  }

  /* ── AUDIO ── audio segue currentLang, no picker ── */
  function renderAudio(){
    syncAudioToLang();
  }

  function syncAudioToLang(){
    var player = document.getElementById('audio-player');
    var warn   = document.getElementById('audio-warn');
    var title  = document.getElementById('audio-title');
    var data = AUDIO[currentLang] || {};
    var labels = { it:'Narrazione \u00b7 Italiano', en:'Narration \u00b7 English' };
    if (title) title.textContent = labels[currentLang] || labels.it;

    // Reset trascrizione e warning
    var tBody = document.getElementById('transcript-body');
    var tTog  = document.getElementById('transcript-toggle');
    if (tBody) { tBody.textContent = ''; tBody.style.display = 'none'; }
    if (tTog)  { tTog.innerHTML = '\u{1F4C4} <span>' + (currentLang === 'en' ? 'Show transcript' : 'Mostra trascrizione') + '</span> \u25be'; }
    if (warn) { warn.style.display = 'none'; warn.textContent = ''; }

    if (player) {
      if (!player.paused) player.pause();
      if (data.url) {
        player.src = data.url;
        player.load();
        // Una sola volta: listener errore per mostrare warning UI
        if (!player.dataset.errBound) {
          player.dataset.errBound = '1';
          player.addEventListener('error', function(){
            var w = document.getElementById('audio-warn');
            if (w) {
              w.textContent = currentLang === 'en'
                ? 'Audio not available at the moment. Please try again later.'
                : 'Audio non disponibile al momento. Riprova piu tardi.';
              w.style.display = 'block';
            }
          });
        }
      } else {
        player.removeAttribute('src');
        if (warn) {
          warn.textContent = currentLang === 'en'
            ? 'Audio not available in English for this point.'
            : 'Audio non disponibile in italiano per questo punto.';
          warn.style.display = 'block';
        }
      }
    }
  }

  var playBtn = document.getElementById('audio-playbtn');
  if (playBtn) {
    playBtn.addEventListener('click', function(){
      var p = document.getElementById('audio-player');
      if (!p) return;
      var data = AUDIO[currentLang] || {};
      if (!p.src && data.url) { p.src = data.url; p.load(); }
      if (p.src && !p.paused) { p.pause(); return; }
      p.play().catch(function(err){
        console.warn('Audio play blocked:', err);
        var w = document.getElementById('audio-warn');
        if (w) {
          w.textContent = currentLang === 'en'
            ? 'Audio not available at the moment. Please try again later.'
            : 'Audio non disponibile al momento. Riprova piu tardi.';
          w.style.display = 'block';
        }
      });
    });
  }

  var trBtn = document.getElementById('transcript-toggle');
  if (trBtn) {
    trBtn.addEventListener('click', function(){
      var body = document.getElementById('transcript-body');
      if (!body) return;
      var open = body.style.display === 'block';
      if (!open && !body.textContent) {
        var data = AUDIO[currentLang] || {};
        body.textContent = data.transcript || (currentLang === 'en' ? 'Transcript not available.' : 'Trascrizione non disponibile.');
      }
      body.style.display = open ? 'none' : 'block';
      var label = currentLang === 'en' ? (open ? 'Show transcript' : 'Hide transcript') : (open ? 'Mostra trascrizione' : 'Nascondi trascrizione');
      trBtn.innerHTML = '\u{1F4C4} <span>' + label + '</span> ' + (open ? '\u25be' : '\u25b4');
    });
  }

  /* ── VIDEO ── */
  function renderVideo(){
    var body = document.getElementById('video-body');
    if (!body || body.dataset.built) return;
    body.dataset.built = '1';
    var pairs = [['it','Italiano'],['en','English']];
    var html = '';
    for (var i=0;i<pairs.length;i++){
      var lang = pairs[i][0], label = pairs[i][1];
      if (!VIDEO[lang]) continue;
      html += '<div class="video-card-big">'
            + '<div class="video-wrap"><video controls preload="metadata" playsinline>'
            + '<source src="' + escHtml(VIDEO[lang]) + '"/>'
            + '</video></div>'
            + '</div>'
            + '<div class="video-label">' + escHtml(label) + '</div>';
    }
    body.innerHTML = html || '<p style="color:rgba(255,255,255,.4);font-size:13px">Video non disponibile</p>';
  }

  /* ── GALLERY ── */
  function renderGallery(){
    var grid = document.getElementById('gallery-grid');
    if (!grid || grid.dataset.built) return;
    grid.dataset.built = '1';
    var html = '';
    for (var i=0;i<GALLERY.length;i++){
      var cls = i === 0 ? 'gallery-grid-item full' : 'gallery-grid-item';
      html += '<div class="' + cls + '" data-src="' + escHtml(GALLERY[i].src) + '">'
            + '<img src="' + escHtml(GALLERY[i].src) + '" alt="' + escHtml(GALLERY[i].alt || '') + '" loading="lazy"/>'
            + '</div>';
    }
    grid.innerHTML = html || '<p style="color:rgba(255,255,255,.4);font-size:13px">Nessuna immagine disponibile</p>';
    grid.addEventListener('click', function(e){
      var card = e.target.closest('.gallery-grid-item');
      if (card) openLightbox(card.getAttribute('data-src'));
    });
  }

  /* ── LIGHTBOX ── */
  function openLightbox(src){
    var img = document.getElementById('lightbox-img');
    var lb  = document.getElementById('lightbox');
    if (!img || !lb) return;
    img.src = src;
    lb.classList.add('open');
  }
  function closeLightbox(){
    var lb = document.getElementById('lightbox');
    if (lb) lb.classList.remove('open');
  }
  var lb = document.getElementById('lightbox');
  if (lb) lb.addEventListener('click', function(e){
    if (e.target === lb || e.target.id === 'lightbox-close' || e.target.closest('#lightbox-close')) closeLightbox();
  });

  function escHtml(str){
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
})();
</script>
</body>
</html>
HTML;
}

/* Chiamata diretta via GET con token (export massivo da browser/cron) */
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
