import { useState, useEffect, useRef, useCallback } from "react";

/* ═══════════════════════════════════════════════════════════════════
   METABORGHI — FRONTEND AD ALTO IMPATTO VISIVO
   InnTour S.r.l. — Startup Innovativa, Alta Irpinia

   Stack: React + Tailwind + Framer-Motion-like CSS animations
   Design System: Trilogia Verde/Ciano/Giallo + Palette Materica
   Features: Parallax, Glassmorphism, Morphing Gradients, Scroll Reveal
   ═══════════════════════════════════════════════════════════════════ */

// ─── BRAND TOKENS ────────────────────────────────────────────────
const COLORS = {
  green: "#00D084", greenDark: "#00A86B",
  cyan: "#00B4D8", cyanDark: "#0090AF",
  yellow: "#F0FF00", yellowDark: "#C8D400",
  orange: "#F5A623",
  notte: "#1A1A2E", notteLt: "#2D2D48",
  terracotta: "#C4622D", ocra: "#D4A855",
  bosco: "#2D5A27", cielo: "#4A90C4",
  surface: "#FAFAF8", surfaceAlt: "#F0EDE8",
  border: "#E2DDD6", nebbia: "#F5F5F0",
  textPrimary: "#1C1917", textSecondary: "#57534E", textMuted: "#A8A29E",
};

// ─── BORGHI DATA ────────────────────────────────────────────────
const BORGHI = [
  { slug: "lacedonia", name: "Lacedonia", province: "AV", pop: 2200, alt: 730, lat: 41.052, lng: 15.567, desc: "Borgo millenario affacciato sulla valle del Calaggio, custode di tradizioni ancestrali e panorami che abbracciano tre regioni.", highlights: ["Cattedrale romanica", "Castello ducale", "Festa di San Gerardo"] },
  { slug: "calitri", name: "Calitri", province: "AV", pop: 4500, alt: 530, desc: "La perla della ceramica irpina, dove l'arte si fonde con i vicoli in pietra e i sapori della tradizione contadina.", highlights: ["Ceramica artistica", "Rione Castello", "Carnevale"] },
  { slug: "bisaccia", name: "Bisaccia", province: "AV", pop: 3800, alt: 860, desc: "Borgo dalla storia millenaria, la tomba principesca osca ne testimonia l'importanza strategica tra Irpinia e Daunia.", highlights: ["Parco archeologico", "Museo civico", "Boschi del Vulture"] },
  { slug: "andretta", name: "Andretta", province: "AV", pop: 1700, alt: 850, desc: "Il borgo degli artisti, dove la ceramica d'autore incontra il paesaggio appenninico in un connubio unico.", highlights: ["Museo della Ceramica", "Centro storico", "Artigianato"] },
  { slug: "monteverde", name: "Monteverde", province: "AV", pop: 750, alt: 730, desc: "Arroccato su uno sperone roccioso, offre viste mozzafiato sulla valle dell'Ofanto e conserva un castello normanno.", highlights: ["Castello normanno", "Panorama Ofanto", "Borgo medievale"] },
  { slug: "aquilonia", name: "Aquilonia", province: "AV", pop: 1600, alt: 740, desc: "Il borgo del Museo Etnografico più grande del Sud, dove la memoria contadina rivive in migliaia di oggetti.", highlights: ["Museo Etnografico", "Centro storico", "Tradizioni"] },
  { slug: "cairano", name: "Cairano", province: "AV", pop: 350, alt: 820, desc: "Il borgo più piccolo dell'Alta Irpinia, un gioiello sospeso nel tempo con vista sulla diga di Conza.", highlights: ["Cairano 7x", "Vista lago", "Arte contemporanea"] },
  { slug: "conza-della-campania", name: "Conza della Campania", province: "AV", pop: 1400, alt: 580, desc: "L'antica Compsa romana, oggi oasi naturalistica con il lago artificiale più grande della Campania.", highlights: ["Oasi WWF", "Parco archeologico", "Lago di Conza"] },
  { slug: "nusco", name: "Nusco", province: "AV", pop: 3800, alt: 914, desc: "Il balcone dell'Irpinia, uno dei borghi più alti della Campania con una cattedrale romanica di rara bellezza.", highlights: ["Cattedrale romanica", "Panorama 360°", "Gastronomia"] },
];

// ─── ESPERIENZE DATA ────────────────────────────────────────────
const ESPERIENZE = [
  { slug: "ceramica-calitri", title: "L'Arte della Ceramica", cat: "ARTIGIANATO", borgo: "Calitri", hours: 3, price: 45, rating: 4.8, reviews: 42, desc: "Modella l'argilla nelle botteghe storiche di Calitri, guidato dai maestri ceramisti che custodiscono secoli di tradizione." },
  { slug: "sentiero-ofanto", title: "Sentiero dell'Ofanto", cat: "NATURA", borgo: "Monteverde", hours: 5, price: 25, rating: 4.9, reviews: 67, desc: "Un trekking panoramico lungo le sponde del fiume Ofanto, tra boschi appenninici e panorami che spaziano fino al Vulture." },
  { slug: "degustazione-irpinia", title: "Sapori d'Irpinia", cat: "GASTRONOMIA", borgo: "Nusco", hours: 4, price: 65, rating: 4.7, reviews: 38, desc: "Un viaggio gastronomico tra formaggi di grotta, salumi artigianali e vini autoctoni nelle cantine storiche dell'Alta Irpinia." },
  { slug: "notte-castello", title: "Notte al Castello", cat: "CULTURA", borgo: "Bisaccia", hours: 2, price: 20, rating: 4.6, reviews: 55, desc: "Visita guidata notturna al castello ducale di Bisaccia, tra leggende medievali e proiezioni immersive sulla storia osca." },
  { slug: "yoga-alba", title: "Yoga all'Alba", cat: "BENESSERE", borgo: "Cairano", hours: 2, price: 30, rating: 4.9, reviews: 24, desc: "Sessioni di yoga all'alba sul belvedere di Cairano, con vista sul lago di Conza e le montagne dell'Appennino." },
  { slug: "avventura-diga", title: "Kayak al Lago di Conza", cat: "AVVENTURA", borgo: "Conza della Campania", hours: 3, price: 40, rating: 4.8, reviews: 31, desc: "Esplora in kayak l'oasi WWF del Lago di Conza, tra aironi, falchi e paesaggi lacustri unici nel cuore della Campania." },
];

const CAT_COLORS = {
  GASTRONOMIA: { bg: "#C4622D", label: "Gastronomia", icon: "🍷" },
  CULTURA: { bg: "#4A90C4", label: "Cultura", icon: "🏛️" },
  NATURA: { bg: "#2D5A27", label: "Natura", icon: "🌿" },
  ARTIGIANATO: { bg: "#D4A855", label: "Artigianato", icon: "🏺" },
  BENESSERE: { bg: "#7C3AED", label: "Benessere", icon: "💆" },
  AVVENTURA: { bg: "#DC2626", label: "Avventura", icon: "⛰️" },
};

// ─── CUSTOM HOOKS ────────────────────────────────────────────────

function useScrollY() {
  const [scrollY, setScrollY] = useState(0);
  useEffect(() => {
    const handler = () => setScrollY(window.scrollY);
    window.addEventListener("scroll", handler, { passive: true });
    return () => window.removeEventListener("scroll", handler);
  }, []);
  return scrollY;
}

function useInView(threshold = 0.15) {
  const ref = useRef(null);
  const [isVisible, setIsVisible] = useState(false);
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const obs = new IntersectionObserver(
      ([e]) => { if (e.isIntersecting) { setIsVisible(true); obs.disconnect(); } },
      { threshold }
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, [threshold]);
  return [ref, isVisible];
}

function useMouseGlow() {
  const [pos, setPos] = useState({ x: 0, y: 0 });
  const handler = useCallback((e) => setPos({ x: e.clientX, y: e.clientY }), []);
  useEffect(() => {
    window.addEventListener("mousemove", handler, { passive: true });
    return () => window.removeEventListener("mousemove", handler);
  }, [handler]);
  return pos;
}

// ─── CSS-IN-JS STYLES ───────────────────────────────────────────

const globalStyles = `
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --mb-green: #00D084; --mb-cyan: #00B4D8; --mb-yellow: #F0FF00;
  --mb-orange: #F5A623; --mb-notte: #1A1A2E;
  --font-display: 'Playfair Display', Georgia, serif;
  --font-body: 'Inter', system-ui, sans-serif;
}

html { scroll-behavior: smooth; }
body { font-family: var(--font-body); color: #1C1917; background: #FAFAF8; overflow-x: hidden; }

/* MORPHING GRADIENT ANIMATION */
@keyframes morphColors {
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
@keyframes floatSlow { 0%,100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-20px) rotate(3deg); } }
@keyframes spinSlow { to { transform: rotate(360deg); } }
@keyframes shimmer { from { background-position: -200% 0; } to { background-position: 200% 0; } }
@keyframes pulseRing { 0% { transform: scale(1); opacity: 0.5; } 100% { transform: scale(2.8); opacity: 0; } }
@keyframes revealUp { from { opacity: 0; transform: translateY(50px); } to { opacity: 1; transform: translateY(0); } }
@keyframes revealScale { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }
@keyframes slideInLeft { from { opacity: 0; transform: translateX(-40px); } to { opacity: 1; transform: translateX(0); } }
@keyframes dotDrop { 0% { transform: translateY(-20px); opacity: 0; } 60% { transform: translateY(4px); opacity: 1; } 100% { transform: translateY(0); opacity: 1; } }
@keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
@keyframes breathe { 0%,100% { opacity: 0.4; transform: scale(1); } 50% { opacity: 0.7; transform: scale(1.05); } }

/* SWIRL TEXT */
.swirl-text {
  background: linear-gradient(90deg, #F5A623, #00D084, #00B4D8, #F0FF00, #F5A623);
  background-size: 300% 100%;
  -webkit-background-clip: text; background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: morphColors 5s ease infinite;
}

/* GLASS CARD */
.glass-card {
  background: rgba(255,255,255,0.04);
  backdrop-filter: blur(24px) saturate(180%);
  -webkit-backdrop-filter: blur(24px) saturate(180%);
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 8px 32px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.06);
}

/* GRADIENT BORDER ANIMATED */
.gradient-border-anim {
  position: relative; background: #0F1724; border-radius: 20px;
}
.gradient-border-anim::before {
  content: ''; position: absolute; inset: -2px;
  background: linear-gradient(135deg, #F5A623, #00D084, #00B4D8, #F0FF00);
  border-radius: 22px; z-index: -1;
  background-size: 300% 300%; animation: morphColors 4s ease infinite;
}

/* NOISE OVERLAY */
.noise::after {
  content: ''; position: absolute; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none; opacity: 0.06; mix-blend-mode: overlay;
}

/* GLOW EFFECTS */
.glow-green { box-shadow: 0 0 30px rgba(0,208,132,0.4), 0 0 80px rgba(0,208,132,0.15); }
.glow-cyan { box-shadow: 0 0 30px rgba(0,180,216,0.4), 0 0 80px rgba(0,180,216,0.15); }

/* SCROLL REVEAL BASE */
.reveal-hidden { opacity: 0; transform: translateY(50px); transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
.reveal-visible { opacity: 1; transform: translateY(0); }
.reveal-scale-hidden { opacity: 0; transform: scale(0.9); transition: all 0.7s cubic-bezier(0.34, 1.56, 0.64, 1); }
.reveal-scale-visible { opacity: 1; transform: scale(1); }

/* CUSTOM SCROLLBAR */
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #1A1A2E; }
::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #00D084, #00B4D8); border-radius: 4px; }

/* REDUCED MOTION */
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
`;

// ─── INNTOUR LOGO COMPONENT ─────────────────────────────────────

function Logo({ variant = "full", size = 40 }) {
  const isDark = variant === "dark" || variant === "white";
  const textColor = isDark ? "#FAFAF8" : "#1A1A2E";
  const scale = size / 40;
  return (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 80" width={320 * scale * 0.5} height={80 * scale * 0.5} role="img" aria-label="InnTour - Startup Innovativa">
      <defs>
        <linearGradient id="it-grad" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stopColor="#00D084"/><stop offset="50%" stopColor="#00B4D8"/><stop offset="100%" stopColor="#F0FF00"/>
        </linearGradient>
        <linearGradient id="it-icon-grad" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stopColor="#00D084"/><stop offset="100%" stopColor="#00B4D8"/>
        </linearGradient>
      </defs>
      <g transform="translate(0,8)">
        <rect x="10" y="20" width="10" height="36" rx="2" fill="url(#it-icon-grad)"/>
        <rect x="6" y="13" width="18" height="10" rx="3" fill="url(#it-icon-grad)"/>
        <circle cx="15" cy="10" r="4" fill="#F0FF00"/>
        <rect x="22" y="28" width="14" height="3" rx="1.5" fill="#00B4D8" opacity="0.7"/>
        <rect x="22" y="36" width="10" height="3" rx="1.5" fill="#00D084" opacity="0.5"/>
      </g>
      <text x="48" y="48" fontFamily="'Playfair Display', Georgia, serif" fontSize="28" fontWeight="700" letterSpacing="-0.5" fill="url(#it-grad)">Inn</text>
      <text x="104" y="48" fontFamily="'Inter', system-ui, sans-serif" fontSize="28" fontWeight="300" fill={textColor}>Tour</text>
      <text x="48" y="64" fontFamily="'Inter', system-ui, sans-serif" fontSize="9" fontWeight="500" letterSpacing="2.5" fill="#6B7280">STARTUP INNOVATIVA</text>
      <rect x="48" y="68" width="14" height="2" rx="1" fill="#00D084"/>
      <rect x="64" y="68" width="14" height="2" rx="1" fill="#00B4D8"/>
      <rect x="80" y="68" width="14" height="2" rx="1" fill="#F0FF00"/>
    </svg>
  );
}

// ─── ANIMATED ORB / BLOB COMPONENT ───────────────────────────────

function FloatingOrb({ color, size, top, left, delay = 0 }) {
  return (
    <div style={{
      position: "absolute", top, left, width: size, height: size,
      borderRadius: "50%",
      background: `radial-gradient(circle at 30% 30%, ${color}40, ${color}10, transparent 70%)`,
      filter: "blur(40px)",
      animation: `float 8s ease-in-out ${delay}s infinite`,
      pointerEvents: "none",
    }} />
  );
}

// ─── PARALLAX DOTS GRID (logo-inspired) ──────────────────────────

function DotsGrid({ scrollY }) {
  const colors = [COLORS.orange, COLORS.green, COLORS.cyan, COLORS.yellow];
  return (
    <div style={{ position: "absolute", top: 40, right: 60, display: "grid", gridTemplateColumns: "repeat(4,1fr)", gap: 8, opacity: 0.5, transform: `translateY(${scrollY * 0.05}px)` }}>
      {Array.from({ length: 16 }).map((_, i) => (
        <div key={i} style={{
          width: 6, height: 6, borderRadius: "50%",
          backgroundColor: colors[i % 4],
          animation: `dotDrop 0.6s ${i * 0.08}s both`,
        }} />
      ))}
    </div>
  );
}

// ─── NAVBAR ──────────────────────────────────────────────────────

function Navbar({ scrollY }) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const isScrolled = scrollY > 80;
  const links = [
    { label: "Borghi", href: "#borghi" }, { label: "Esperienze", href: "#esperienze" },
    { label: "Mappa", href: "#mappa" }, { label: "Chi Siamo", href: "#chi-siamo" },
    { label: "Contatti", href: "#contatti" },
  ];

  return (
    <nav style={{
      position: "fixed", top: 0, left: 0, right: 0, zIndex: 100,
      padding: isScrolled ? "12px 24px" : "20px 24px",
      background: isScrolled ? "rgba(26,26,46,0.85)" : "transparent",
      backdropFilter: isScrolled ? "blur(24px) saturate(180%)" : "none",
      borderBottom: isScrolled ? "1px solid rgba(255,255,255,0.06)" : "none",
      transition: "all 0.4s cubic-bezier(0.25,0.46,0.45,0.94)",
    }}>
      <div style={{ maxWidth: 1280, margin: "0 auto", display: "flex", alignItems: "center", justifyContent: "space-between" }}>
        <Logo variant="dark" size={isScrolled ? 32 : 40} />

        {/* Desktop links */}
        <div style={{ display: "flex", gap: 32, alignItems: "center" }}>
          {links.map((l) => (
            <a key={l.href} href={l.href} style={{
              color: "rgba(250,250,248,0.8)", fontFamily: "var(--font-body)", fontSize: 14,
              fontWeight: 500, textDecoration: "none", letterSpacing: 0.3,
              transition: "color 0.2s", position: "relative",
            }}
            onMouseEnter={(e) => e.target.style.color = "#00D084"}
            onMouseLeave={(e) => e.target.style.color = "rgba(250,250,248,0.8)"}
            >
              {l.label}
            </a>
          ))}
          <a href="#prenota" style={{
            background: "linear-gradient(135deg, #00D084, #00B4D8)",
            color: "#fff", fontFamily: "var(--font-body)", fontSize: 14,
            fontWeight: 600, padding: "10px 24px", borderRadius: 9999,
            textDecoration: "none", boxShadow: "0 4px 20px rgba(0,208,132,0.35)",
            transition: "all 0.3s cubic-bezier(0.34,1.56,0.64,1)",
          }}
          onMouseEnter={(e) => { e.target.style.transform = "translateY(-2px) scale(1.03)"; e.target.style.boxShadow = "0 8px 32px rgba(0,208,132,0.5)"; }}
          onMouseLeave={(e) => { e.target.style.transform = ""; e.target.style.boxShadow = "0 4px 20px rgba(0,208,132,0.35)"; }}
          >
            Prenota Ora
          </a>
        </div>
      </div>
    </nav>
  );
}

// ─── HERO SECTION (Parallax + Morphing) ──────────────────────────

function HeroSection({ scrollY }) {
  const parallaxBg = scrollY * 0.35;
  const parallaxContent = scrollY * -0.15;
  const opacity = Math.max(0, 1 - scrollY / 700);
  const scale = 1 + scrollY * 0.0003;

  return (
    <section style={{ position: "relative", height: "100vh", minHeight: 700, overflow: "hidden", background: "#0A0A0A" }}>
      {/* Parallax background image simulation */}
      <div className="noise" style={{
        position: "absolute", inset: 0,
        background: `linear-gradient(135deg, #0A1628 0%, #1A1A2E 30%, #0F1724 70%, #0A0A0A 100%)`,
        transform: `translateY(${parallaxBg}px) scale(${scale})`,
        transition: "transform 0.05s linear",
      }}>
        {/* Animated gradient overlay */}
        <div style={{
          position: "absolute", inset: 0, opacity: 0.6,
          background: "linear-gradient(135deg, #F5A623 0%, #00D084 25%, #00B4D8 50%, #F0FF00 75%, #F5A623 100%)",
          backgroundSize: "400% 400%",
          animation: "gradientShift 12s ease infinite",
          mixBlendMode: "overlay",
        }} />
      </div>

      {/* Floating orbs */}
      <FloatingOrb color={COLORS.green} size="400px" top="-10%" left="60%" delay={0} />
      <FloatingOrb color={COLORS.cyan} size="350px" top="50%" left="-5%" delay={2} />
      <FloatingOrb color={COLORS.orange} size="300px" top="20%" left="80%" delay={4} />
      <FloatingOrb color={COLORS.yellow} size="250px" top="70%" left="40%" delay={1} />

      {/* Concentric rings */}
      <div style={{ position: "absolute", top: "50%", left: "50%", transform: "translate(-50%,-50%)", pointerEvents: "none" }}>
        {[300, 500, 700].map((s, i) => (
          <div key={s} style={{
            position: "absolute", width: s, height: s,
            top: -s / 2, left: -s / 2,
            border: `1px solid rgba(0,208,132,${0.12 - i * 0.03})`,
            borderRadius: "50%",
            animation: `spinSlow ${30 + i * 10}s linear infinite ${i % 2 === 0 ? "" : "reverse"}`,
          }} />
        ))}
      </div>

      {/* Dots grid from logo */}
      <DotsGrid scrollY={scrollY} />

      {/* Pulse ring center */}
      <div style={{ position: "absolute", top: "50%", left: "50%", transform: "translate(-50%,-50%)", pointerEvents: "none" }}>
        <div style={{ width: 120, height: 120, borderRadius: "50%", border: "2px solid rgba(0,208,132,0.3)", animation: "pulseRing 3s ease-out infinite" }} />
        <div style={{ width: 120, height: 120, borderRadius: "50%", border: "2px solid rgba(0,180,216,0.2)", animation: "pulseRing 3s ease-out 1s infinite", position: "absolute", top: 0, left: 0 }} />
      </div>

      {/* Hero Content */}
      <div style={{
        position: "relative", zIndex: 10, height: "100%",
        display: "flex", flexDirection: "column", justifyContent: "center",
        padding: "0 clamp(24px, 6vw, 96px)",
        maxWidth: 1280, margin: "0 auto",
        transform: `translateY(${parallaxContent}px)`,
        opacity,
      }}>
        {/* Badge */}
        <div style={{
          display: "inline-flex", alignItems: "center", gap: 8,
          background: "rgba(0,208,132,0.12)", border: "1px solid rgba(0,208,132,0.25)",
          backdropFilter: "blur(12px)", borderRadius: 9999,
          padding: "8px 20px", marginBottom: 28, width: "fit-content",
          animation: "revealUp 0.8s 0.2s both",
        }}>
          <div style={{ width: 8, height: 8, borderRadius: "50%", background: COLORS.green, animation: "breathe 2s ease infinite" }} />
          <span style={{ color: COLORS.green, fontFamily: "var(--font-body)", fontSize: 13, fontWeight: 600, letterSpacing: 0.5 }}>
            25 Borghi · Alta Irpinia
          </span>
        </div>

        {/* Title */}
        <h1 style={{
          fontFamily: "var(--font-display)", fontWeight: 800,
          fontSize: "clamp(2.8rem, 2rem + 4vw, 5.5rem)",
          lineHeight: 1.02, letterSpacing: "-0.03em",
          color: "#FAFAF8", marginBottom: 24, maxWidth: 800,
          animation: "revealUp 0.8s 0.4s both",
        }}>
          Riscopri il <span className="swirl-text">Cuore</span> dell'Italia che non ti aspetti
        </h1>

        {/* Subtitle */}
        <p style={{
          fontFamily: "var(--font-body)", fontSize: "clamp(1rem, 0.9rem + 0.5vw, 1.25rem)",
          color: "rgba(250,250,248,0.7)", lineHeight: 1.7,
          maxWidth: 560, marginBottom: 40,
          animation: "revealUp 0.8s 0.6s both",
        }}>
          Esperienze autentiche nei borghi dell'Alta Irpinia — tra Campania, Basilicata e Puglia.
          Cultura, natura, artigianato e sapori in un viaggio che ti cambia.
        </p>

        {/* CTA Buttons */}
        <div style={{ display: "flex", flexWrap: "wrap", gap: 16, animation: "revealUp 0.8s 0.8s both" }}>
          <a href="#borghi" style={{
            background: "linear-gradient(135deg, #00D084, #00B4D8)",
            color: "#fff", fontFamily: "var(--font-body)", fontSize: 16, fontWeight: 600,
            padding: "16px 36px", borderRadius: 9999, textDecoration: "none",
            boxShadow: "0 8px 32px rgba(0,208,132,0.4)",
            transition: "all 0.35s cubic-bezier(0.34,1.56,0.64,1)",
          }}
          onMouseEnter={(e) => { e.target.style.transform = "translateY(-3px) scale(1.04)"; e.target.style.boxShadow = "0 16px 48px rgba(0,208,132,0.5)"; }}
          onMouseLeave={(e) => { e.target.style.transform = ""; e.target.style.boxShadow = "0 8px 32px rgba(0,208,132,0.4)"; }}
          >
            Esplora i Borghi
          </a>
          <a href="#esperienze" style={{
            background: "rgba(255,255,255,0.06)", backdropFilter: "blur(12px)",
            color: "#fff", fontFamily: "var(--font-body)", fontSize: 16, fontWeight: 600,
            padding: "16px 36px", borderRadius: 9999, textDecoration: "none",
            border: "1px solid rgba(255,255,255,0.15)",
            transition: "all 0.35s cubic-bezier(0.34,1.56,0.64,1)",
          }}
          onMouseEnter={(e) => { e.target.style.background = "rgba(255,255,255,0.12)"; e.target.style.transform = "translateY(-2px)"; }}
          onMouseLeave={(e) => { e.target.style.background = "rgba(255,255,255,0.06)"; e.target.style.transform = ""; }}
          >
            Vivi un'Esperienza
          </a>
        </div>

        {/* Scroll indicator */}
        <div style={{
          position: "absolute", bottom: 40, left: "50%", transform: "translateX(-50%)",
          display: "flex", flexDirection: "column", alignItems: "center", gap: 8,
          animation: "float 3s ease-in-out infinite",
        }}>
          <span style={{ color: "rgba(250,250,248,0.4)", fontFamily: "var(--font-body)", fontSize: 11, letterSpacing: 2, textTransform: "uppercase" }}>Scorri</span>
          <div style={{ width: 2, height: 40, background: "linear-gradient(180deg, rgba(0,208,132,0.6), transparent)", borderRadius: 1 }} />
        </div>
      </div>

      {/* Bottom gradient fade */}
      <div style={{ position: "absolute", bottom: 0, left: 0, right: 0, height: 200, background: "linear-gradient(to top, #FAFAF8, transparent)", zIndex: 5 }} />
    </section>
  );
}

// ─── SCROLL REVEAL WRAPPER ───────────────────────────────────────

function Reveal({ children, delay = 0, scale: useScale = false, style = {} }) {
  const [ref, isVisible] = useInView(0.12);
  const cls = useScale
    ? (isVisible ? "reveal-scale-visible" : "reveal-scale-hidden")
    : (isVisible ? "reveal-visible" : "reveal-hidden");
  return (
    <div ref={ref} className={cls} style={{ transitionDelay: `${delay}ms`, ...style }}>
      {children}
    </div>
  );
}

// ─── STATS RIBBON ────────────────────────────────────────────────

function StatsRibbon() {
  const stats = [
    { value: "25", label: "Borghi" }, { value: "6", label: "Categorie" },
    { value: "15+", label: "Esperienze" }, { value: "14", label: "Aziende locali" },
  ];
  return (
    <section style={{ padding: "60px 24px", background: COLORS.surface }}>
      <div style={{
        maxWidth: 1100, margin: "0 auto",
        display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 24,
      }}>
        {stats.map((s, i) => (
          <Reveal key={i} delay={i * 100}>
            <div style={{ textAlign: "center", padding: 24 }}>
              <div style={{
                fontFamily: "var(--font-display)", fontWeight: 800,
                fontSize: "clamp(2.5rem, 2rem + 2vw, 4rem)",
                background: "linear-gradient(135deg, #00D084, #00B4D8)",
                WebkitBackgroundClip: "text", WebkitTextFillColor: "transparent",
                backgroundClip: "text", lineHeight: 1.1,
              }}>{s.value}</div>
              <div style={{
                fontFamily: "var(--font-body)", fontSize: 14, color: COLORS.textSecondary,
                fontWeight: 500, marginTop: 8, letterSpacing: 0.5,
              }}>{s.label}</div>
            </div>
          </Reveal>
        ))}
      </div>
    </section>
  );
}

// ─── BORGHI SECTION ──────────────────────────────────────────────

function BorghiSection() {
  const [hovered, setHovered] = useState(null);

  return (
    <section id="borghi" style={{
      padding: "clamp(60px, 8vw, 120px) 24px",
      background: COLORS.surface, position: "relative",
    }}>
      {/* Decorative orb */}
      <FloatingOrb color={COLORS.green} size="500px" top="-15%" left="-10%" delay={0} />

      <div style={{ maxWidth: 1280, margin: "0 auto", position: "relative", zIndex: 1 }}>
        {/* Section header */}
        <Reveal>
          <div style={{ textAlign: "center", marginBottom: 64 }}>
            <span style={{
              display: "inline-block", fontFamily: "var(--font-body)",
              fontSize: 13, fontWeight: 600, color: COLORS.green,
              letterSpacing: 3, textTransform: "uppercase", marginBottom: 16,
            }}>
              Esplora il Territorio
            </span>
            <h2 style={{
              fontFamily: "var(--font-display)", fontWeight: 800,
              fontSize: "clamp(2rem, 1.5rem + 2.5vw, 3.5rem)",
              color: COLORS.textPrimary, lineHeight: 1.1, letterSpacing: "-0.02em",
            }}>
              I Borghi dell'Alta Irpinia
            </h2>
            <p style={{
              fontFamily: "var(--font-body)", fontSize: "clamp(1rem, 0.9rem + 0.3vw, 1.125rem)",
              color: COLORS.textSecondary, maxWidth: 600, margin: "16px auto 0",
              lineHeight: 1.7,
            }}>
              Venticinque gemme nascoste tra Campania, Basilicata e Puglia — ciascuna con una storia millenaria tutta da vivere.
            </p>
          </div>
        </Reveal>

        {/* Borough grid */}
        <div style={{
          display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(340px, 1fr))",
          gap: 28,
        }}>
          {BORGHI.map((borgo, i) => (
            <Reveal key={borgo.slug} delay={i * 80} scale>
              <div
                onMouseEnter={() => setHovered(borgo.slug)}
                onMouseLeave={() => setHovered(null)}
                style={{
                  background: "#fff", borderRadius: 20,
                  overflow: "hidden", cursor: "pointer",
                  boxShadow: hovered === borgo.slug
                    ? "0 24px 56px rgba(0,208,132,0.2), 0 8px 16px rgba(0,0,0,0.08)"
                    : "0 4px 16px rgba(0,0,0,0.08)",
                  transform: hovered === borgo.slug ? "translateY(-6px) scale(1.015)" : "translateY(0) scale(1)",
                  transition: "all 0.4s cubic-bezier(0.34,1.56,0.64,1)",
                }}
              >
                {/* Image */}
                <div style={{ position: "relative", height: 220, overflow: "hidden" }}>
                  <div style={{
                    width: "100%", height: "100%",
                    background: `linear-gradient(135deg, ${COLORS.notte} 0%, ${COLORS.bosco} 50%, ${COLORS.cielo} 100%)`,
                    transform: hovered === borgo.slug ? "scale(1.08)" : "scale(1)",
                    transition: "transform 0.8s cubic-bezier(0.25,0.46,0.45,0.94)",
                  }} />
                  <div style={{
                    position: "absolute", inset: 0,
                    background: "linear-gradient(180deg, transparent 40%, rgba(26,26,46,0.8) 100%)",
                  }} />
                  {/* Altitude badge */}
                  <div style={{
                    position: "absolute", top: 12, left: 12,
                    background: "rgba(26,26,46,0.75)", backdropFilter: "blur(8px)",
                    color: "#fff", fontFamily: "var(--font-body)", fontSize: 12, fontWeight: 500,
                    borderRadius: 9999, padding: "5px 14px",
                  }}>
                    {borgo.alt}m slm
                  </div>
                  {/* Province badge */}
                  <div style={{
                    position: "absolute", top: 12, right: 12,
                    background: "rgba(0,208,132,0.2)", backdropFilter: "blur(8px)",
                    border: "1px solid rgba(0,208,132,0.3)",
                    color: COLORS.green, fontFamily: "var(--font-body)", fontSize: 11, fontWeight: 600,
                    borderRadius: 9999, padding: "4px 12px",
                  }}>
                    {borgo.province}
                  </div>
                  {/* Borough name on image */}
                  <div style={{ position: "absolute", bottom: 16, left: 16, right: 16 }}>
                    <h3 style={{
                      fontFamily: "var(--font-display)", fontWeight: 700,
                      fontSize: 26, color: "#fff", letterSpacing: "-0.02em",
                      lineHeight: 1.15, textShadow: "0 2px 8px rgba(0,0,0,0.4)",
                    }}>
                      {borgo.name}
                    </h3>
                  </div>
                </div>

                {/* Content */}
                <div style={{ padding: 20 }}>
                  <div style={{ display: "flex", gap: 16, marginBottom: 12, color: COLORS.textMuted, fontFamily: "var(--font-body)", fontSize: 13 }}>
                    <span>📍 {borgo.province}</span>
                    <span>👥 {borgo.pop.toLocaleString("it-IT")} ab.</span>
                  </div>
                  <p style={{
                    fontFamily: "var(--font-body)", fontSize: 14, color: COLORS.textSecondary,
                    lineHeight: 1.65, marginBottom: 16,
                    display: "-webkit-box", WebkitLineClamp: 2, WebkitBoxOrient: "vertical", overflow: "hidden",
                  }}>
                    {borgo.desc}
                  </p>

                  {/* Highlights */}
                  <div style={{ display: "flex", flexWrap: "wrap", gap: 8, marginBottom: 16 }}>
                    {borgo.highlights.slice(0, 3).map((h, j) => (
                      <span key={j} style={{
                        background: COLORS.surfaceAlt, color: COLORS.textSecondary,
                        fontFamily: "var(--font-body)", fontSize: 12, borderRadius: 9999,
                        padding: "4px 12px",
                      }}>
                        {h}
                      </span>
                    ))}
                  </div>

                  {/* CTA */}
                  <div style={{ borderTop: `1px solid ${COLORS.border}`, paddingTop: 16 }}>
                    <span style={{
                      color: COLORS.green, fontFamily: "var(--font-body)",
                      fontSize: 14, fontWeight: 600, display: "flex", alignItems: "center", gap: 6,
                      transition: "gap 0.2s",
                    }}>
                      Scopri {borgo.name}
                      <span style={{
                        display: "inline-block",
                        transform: hovered === borgo.slug ? "translateX(4px)" : "translateX(0)",
                        transition: "transform 0.3s cubic-bezier(0.34,1.56,0.64,1)",
                      }}>→</span>
                    </span>
                  </div>
                </div>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

// ─── ESPERIENZE SECTION ──────────────────────────────────────────

function EsperienzeSection() {
  const [activeCat, setActiveCat] = useState(null);
  const [hoveredExp, setHoveredExp] = useState(null);
  const filtered = activeCat ? ESPERIENZE.filter(e => e.cat === activeCat) : ESPERIENZE;

  return (
    <section id="esperienze" style={{
      padding: "clamp(60px, 8vw, 120px) 24px",
      background: "linear-gradient(180deg, #FAFAF8, #F0EDE8)",
      position: "relative",
    }}>
      <FloatingOrb color={COLORS.cyan} size="400px" top="10%" left="85%" delay={1} />

      <div style={{ maxWidth: 1280, margin: "0 auto", position: "relative", zIndex: 1 }}>
        <Reveal>
          <div style={{ textAlign: "center", marginBottom: 48 }}>
            <span style={{
              fontFamily: "var(--font-body)", fontSize: 13, fontWeight: 600,
              color: COLORS.cyan, letterSpacing: 3, textTransform: "uppercase", marginBottom: 16,
              display: "inline-block",
            }}>Vivi il Territorio</span>
            <h2 style={{
              fontFamily: "var(--font-display)", fontWeight: 800,
              fontSize: "clamp(2rem, 1.5rem + 2.5vw, 3.5rem)",
              color: COLORS.textPrimary, lineHeight: 1.1, letterSpacing: "-0.02em",
            }}>Esperienze Autentiche</h2>
            <p style={{
              fontFamily: "var(--font-body)", fontSize: "clamp(1rem, 0.9rem + 0.3vw, 1.125rem)",
              color: COLORS.textSecondary, maxWidth: 600, margin: "16px auto 0", lineHeight: 1.7,
            }}>
              Sei categorie, infinite emozioni. Dalla ceramica al kayak, ogni esperienza è un racconto vivo del territorio.
            </p>
          </div>
        </Reveal>

        {/* Category filters */}
        <Reveal delay={100}>
          <div style={{ display: "flex", flexWrap: "wrap", justifyContent: "center", gap: 10, marginBottom: 48 }}>
            <button onClick={() => setActiveCat(null)} style={{
              padding: "10px 22px", borderRadius: 9999, border: "none", cursor: "pointer",
              fontFamily: "var(--font-body)", fontSize: 13, fontWeight: 600,
              background: !activeCat ? COLORS.notte : COLORS.surfaceAlt,
              color: !activeCat ? "#fff" : COLORS.textSecondary,
              transition: "all 0.3s", boxShadow: !activeCat ? "0 4px 16px rgba(26,26,46,0.3)" : "none",
            }}>Tutte</button>
            {Object.entries(CAT_COLORS).map(([key, val]) => (
              <button key={key} onClick={() => setActiveCat(activeCat === key ? null : key)} style={{
                padding: "10px 22px", borderRadius: 9999, border: "none", cursor: "pointer",
                fontFamily: "var(--font-body)", fontSize: 13, fontWeight: 600,
                background: activeCat === key ? val.bg : COLORS.surfaceAlt,
                color: activeCat === key ? "#fff" : COLORS.textSecondary,
                transition: "all 0.3s cubic-bezier(0.34,1.56,0.64,1)",
                transform: activeCat === key ? "scale(1.05)" : "scale(1)",
              }}>
                {val.icon} {val.label}
              </button>
            ))}
          </div>
        </Reveal>

        {/* Experiences grid */}
        <div style={{
          display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(360px, 1fr))", gap: 28,
        }}>
          {filtered.map((exp, i) => (
            <Reveal key={exp.slug} delay={i * 80} scale>
              <div
                onMouseEnter={() => setHoveredExp(exp.slug)}
                onMouseLeave={() => setHoveredExp(null)}
                style={{
                  background: "#fff", borderRadius: 20, overflow: "hidden",
                  boxShadow: hoveredExp === exp.slug
                    ? `0 20px 48px ${CAT_COLORS[exp.cat].bg}25, 0 8px 16px rgba(0,0,0,0.06)`
                    : "0 4px 16px rgba(0,0,0,0.06)",
                  transform: hoveredExp === exp.slug ? "translateY(-6px)" : "translateY(0)",
                  transition: "all 0.4s cubic-bezier(0.34,1.56,0.64,1)",
                  cursor: "pointer",
                }}
              >
                {/* Image */}
                <div style={{ position: "relative", aspectRatio: "16/9", overflow: "hidden" }}>
                  <div style={{
                    width: "100%", height: "100%",
                    background: `linear-gradient(135deg, ${CAT_COLORS[exp.cat].bg}90, ${COLORS.notte})`,
                    transform: hoveredExp === exp.slug ? "scale(1.06)" : "scale(1)",
                    transition: "transform 0.8s cubic-bezier(0.25,0.46,0.45,0.94)",
                  }} />
                  <div style={{ position: "absolute", inset: 0, background: "linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.5) 100%)" }} />
                  {/* Category badge */}
                  <div style={{
                    position: "absolute", top: 12, left: 12,
                    background: CAT_COLORS[exp.cat].bg, color: "#fff",
                    fontFamily: "var(--font-body)", fontSize: 12, fontWeight: 600,
                    borderRadius: 9999, padding: "5px 14px",
                    backdropFilter: "blur(8px)",
                  }}>
                    {CAT_COLORS[exp.cat].icon} {CAT_COLORS[exp.cat].label}
                  </div>
                  {/* Price */}
                  <div style={{
                    position: "absolute", bottom: 12, right: 12,
                    background: COLORS.green, color: "#fff",
                    fontFamily: "var(--font-body)", fontSize: 15, fontWeight: 700,
                    borderRadius: 9999, padding: "6px 18px",
                  }}>
                    €{exp.price}<span style={{ fontSize: 11, fontWeight: 400, opacity: 0.8 }}>/pers</span>
                  </div>
                </div>

                {/* Content */}
                <div style={{ padding: 20 }}>
                  <h3 style={{
                    fontFamily: "var(--font-display)", fontWeight: 700, fontSize: 22,
                    color: COLORS.textPrimary, letterSpacing: "-0.02em", lineHeight: 1.2, marginBottom: 4,
                  }}>{exp.title}</h3>
                  <p style={{ fontFamily: "var(--font-body)", fontSize: 13, color: COLORS.textMuted, marginBottom: 12 }}>{exp.borgo}</p>
                  <p style={{
                    fontFamily: "var(--font-body)", fontSize: 14, color: COLORS.textSecondary,
                    lineHeight: 1.65, marginBottom: 16,
                    display: "-webkit-box", WebkitLineClamp: 2, WebkitBoxOrient: "vertical", overflow: "hidden",
                  }}>{exp.desc}</p>

                  {/* Meta */}
                  <div style={{
                    display: "flex", justifyContent: "space-between", alignItems: "center",
                    fontFamily: "var(--font-body)", fontSize: 12, color: COLORS.textMuted,
                  }}>
                    <div style={{ display: "flex", gap: 16 }}>
                      <span>🕐 {exp.hours}h</span>
                      <span>⭐ {exp.rating} ({exp.reviews})</span>
                    </div>
                    <span style={{ color: COLORS.green, fontWeight: 600, fontSize: 13 }}>Scopri →</span>
                  </div>
                </div>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

// ─── IMMERSIVE PARALLAX DIVIDER ──────────────────────────────────

function ParallaxDivider({ scrollY }) {
  const offset = scrollY * 0.15;
  return (
    <section style={{
      position: "relative", height: 500, overflow: "hidden",
      background: `linear-gradient(135deg, ${COLORS.notte}, #0A1628)`,
    }}>
      <div className="noise" style={{ position: "absolute", inset: 0 }}>
        <div style={{
          position: "absolute", inset: 0,
          background: "linear-gradient(135deg, #F5A623 0%, #00D084 25%, #00B4D8 50%, #F0FF00 75%, #F5A623 100%)",
          backgroundSize: "400% 400%",
          animation: "gradientShift 10s ease infinite",
          opacity: 0.15,
          transform: `translateY(${-offset}px)`,
        }} />
      </div>

      <FloatingOrb color={COLORS.green} size="350px" top="20%" left="10%" delay={0} />
      <FloatingOrb color={COLORS.yellow} size="280px" top="40%" left="70%" delay={2} />

      <div style={{
        position: "relative", zIndex: 10, height: "100%",
        display: "flex", flexDirection: "column", justifyContent: "center", alignItems: "center",
        textAlign: "center", padding: "0 24px",
      }}>
        <Reveal>
          <h2 style={{
            fontFamily: "var(--font-display)", fontWeight: 800,
            fontSize: "clamp(1.8rem, 1.2rem + 3vw, 3.5rem)",
            color: "#FAFAF8", lineHeight: 1.15, letterSpacing: "-0.02em", maxWidth: 700,
            marginBottom: 20,
          }}>
            Ogni borgo è un <span className="swirl-text">universo</span> da esplorare
          </h2>
        </Reveal>
        <Reveal delay={200}>
          <p style={{
            fontFamily: "var(--font-body)", fontSize: 18, color: "rgba(250,250,248,0.65)",
            maxWidth: 500, lineHeight: 1.7, marginBottom: 32,
          }}>
            Storie millenarie, sapori unici, paesaggi che tolgono il fiato — tutto a portata di click.
          </p>
        </Reveal>
        <Reveal delay={400}>
          <a href="#prenota" style={{
            background: "linear-gradient(135deg, #00D084, #00B4D8)",
            color: "#fff", fontFamily: "var(--font-body)", fontSize: 16, fontWeight: 600,
            padding: "16px 40px", borderRadius: 9999, textDecoration: "none",
            boxShadow: "0 8px 32px rgba(0,208,132,0.5)",
            transition: "all 0.35s cubic-bezier(0.34,1.56,0.64,1)",
          }}
          onMouseEnter={(e) => { e.target.style.transform = "translateY(-3px) scale(1.05)"; }}
          onMouseLeave={(e) => { e.target.style.transform = ""; }}
          >
            Prenota la Tua Esperienza
          </a>
        </Reveal>
      </div>
    </section>
  );
}

// ─── MAPPA SEZIONE ───────────────────────────────────────────────

function MapSection() {
  const [selectedBorgo, setSelectedBorgo] = useState(null);

  return (
    <section id="mappa" style={{
      padding: "clamp(60px, 8vw, 120px) 24px",
      background: COLORS.surface, position: "relative",
    }}>
      <div style={{ maxWidth: 1280, margin: "0 auto" }}>
        <Reveal>
          <div style={{ textAlign: "center", marginBottom: 48 }}>
            <span style={{
              fontFamily: "var(--font-body)", fontSize: 13, fontWeight: 600,
              color: COLORS.orange, letterSpacing: 3, textTransform: "uppercase",
              display: "inline-block", marginBottom: 16,
            }}>Naviga la Mappa</span>
            <h2 style={{
              fontFamily: "var(--font-display)", fontWeight: 800,
              fontSize: "clamp(2rem, 1.5rem + 2.5vw, 3.5rem)",
              color: COLORS.textPrimary, lineHeight: 1.1, letterSpacing: "-0.02em",
            }}>25 Borghi, Un Solo Cuore</h2>
          </div>
        </Reveal>

        <Reveal delay={200}>
          <div style={{
            display: "grid", gridTemplateColumns: "1fr 360px", gap: 32,
            minHeight: 500,
          }}>
            {/* Map placeholder with interactive dots */}
            <div style={{
              background: `linear-gradient(135deg, ${COLORS.notte}, #0F1724)`,
              borderRadius: 20, position: "relative", overflow: "hidden",
              boxShadow: "0 12px 40px rgba(0,0,0,0.15)",
            }}>
              <div className="noise" style={{ position: "absolute", inset: 0 }} />
              {/* Simplified visual map */}
              <svg viewBox="0 0 600 500" style={{ width: "100%", height: "100%" }}>
                {/* Region outline suggestion */}
                <ellipse cx="300" cy="250" rx="240" ry="200" fill="none" stroke="rgba(0,208,132,0.12)" strokeWidth="1" />
                <ellipse cx="300" cy="250" rx="180" ry="150" fill="none" stroke="rgba(0,180,216,0.08)" strokeWidth="1" />
                {/* Borough dots */}
                {BORGHI.map((b, i) => {
                  const angle = (i / BORGHI.length) * Math.PI * 2 - Math.PI / 2;
                  const rx = 120 + Math.random() * 80;
                  const ry = 100 + Math.random() * 60;
                  const cx = 300 + Math.cos(angle) * rx;
                  const cy = 250 + Math.sin(angle) * ry;
                  const isActive = selectedBorgo === b.slug;
                  return (
                    <g key={b.slug} onClick={() => setSelectedBorgo(b.slug)} style={{ cursor: "pointer" }}>
                      {isActive && <circle cx={cx} cy={cy} r="20" fill="rgba(0,208,132,0.15)" stroke="rgba(0,208,132,0.3)" strokeWidth="1">
                        <animate attributeName="r" values="15;25;15" dur="2s" repeatCount="indefinite" />
                        <animate attributeName="opacity" values="0.5;0.2;0.5" dur="2s" repeatCount="indefinite" />
                      </circle>}
                      <circle cx={cx} cy={cy} r={isActive ? 7 : 5}
                        fill={isActive ? COLORS.green : COLORS.cyan}
                        stroke="#fff" strokeWidth="2"
                        style={{ transition: "all 0.3s" }}
                      />
                      <text x={cx} y={cy - 12} textAnchor="middle"
                        fill="rgba(250,250,248,0.7)" fontSize="10"
                        fontFamily="Inter, sans-serif" fontWeight="500"
                        style={{ opacity: isActive ? 1 : 0.5, transition: "opacity 0.3s" }}>
                        {b.name}
                      </text>
                    </g>
                  );
                })}
                {/* Label */}
                <text x="300" y="470" textAnchor="middle" fill="rgba(250,250,248,0.3)" fontSize="12" fontFamily="Inter, sans-serif" letterSpacing="3">ALTA IRPINIA</text>
              </svg>
            </div>

            {/* Sidebar with borough list */}
            <div style={{
              display: "flex", flexDirection: "column", gap: 8,
              maxHeight: 500, overflowY: "auto", paddingRight: 8,
            }}>
              {BORGHI.map((b) => (
                <div key={b.slug} onClick={() => setSelectedBorgo(b.slug)} style={{
                  padding: "14px 16px", borderRadius: 14, cursor: "pointer",
                  background: selectedBorgo === b.slug ? "linear-gradient(135deg, rgba(0,208,132,0.1), rgba(0,180,216,0.08))" : "rgba(0,0,0,0.02)",
                  border: selectedBorgo === b.slug ? "1px solid rgba(0,208,132,0.25)" : "1px solid transparent",
                  transition: "all 0.3s",
                }}>
                  <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                    <div>
                      <h4 style={{
                        fontFamily: "var(--font-display)", fontSize: 16, fontWeight: 600,
                        color: selectedBorgo === b.slug ? COLORS.green : COLORS.textPrimary,
                        transition: "color 0.3s",
                      }}>{b.name}</h4>
                      <p style={{ fontFamily: "var(--font-body)", fontSize: 12, color: COLORS.textMuted, marginTop: 2 }}>
                        {b.alt}m · {b.pop.toLocaleString("it-IT")} ab.
                      </p>
                    </div>
                    <span style={{
                      fontFamily: "var(--font-body)", fontSize: 18,
                      opacity: selectedBorgo === b.slug ? 1 : 0.3,
                      transform: selectedBorgo === b.slug ? "translateX(0)" : "translateX(-4px)",
                      transition: "all 0.3s",
                      color: COLORS.green,
                    }}>→</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  );
}

// ─── CHI SIAMO (About InnTour) ───────────────────────────────────

function ChiSiamoSection() {
  const features = [
    { icon: "🌍", title: "Turismo Sostenibile", desc: "Promuoviamo un turismo lento, rispettoso delle comunità e dell'ambiente dei borghi." },
    { icon: "💡", title: "Innovazione Digitale", desc: "Tecnologia al servizio della tradizione: piattaforma smart, totem interattivi, esperienze phygital." },
    { icon: "🤝", title: "Comunità Locale", desc: "Ogni esperienza coinvolge direttamente artigiani, ristoratori e guide locali dell'Alta Irpinia." },
    { icon: "🏛️", title: "Patrimonio Culturale", desc: "Valorizziamo castelli, chiese romaniche, musei e tradizioni che rischiano di scomparire." },
  ];

  return (
    <section id="chi-siamo" style={{
      padding: "clamp(60px, 8vw, 120px) 24px",
      background: `linear-gradient(135deg, ${COLORS.notte}, #0F1724)`,
      position: "relative", overflow: "hidden",
    }}>
      <div className="noise" style={{ position: "absolute", inset: 0 }} />
      <FloatingOrb color={COLORS.green} size="500px" top="10%" left="70%" delay={0} />
      <FloatingOrb color={COLORS.orange} size="300px" top="60%" left="-5%" delay={3} />

      <div style={{ maxWidth: 1280, margin: "0 auto", position: "relative", zIndex: 1 }}>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 64, alignItems: "center" }}>
          {/* Left: text */}
          <div>
            <Reveal>
              <span style={{
                fontFamily: "var(--font-body)", fontSize: 13, fontWeight: 600,
                color: COLORS.yellow, letterSpacing: 3, textTransform: "uppercase",
                display: "inline-block", marginBottom: 16,
              }}>Chi Siamo</span>
              <h2 style={{
                fontFamily: "var(--font-display)", fontWeight: 800,
                fontSize: "clamp(2rem, 1.5rem + 2.5vw, 3.5rem)",
                color: "#FAFAF8", lineHeight: 1.1, letterSpacing: "-0.02em", marginBottom: 20,
              }}>
                InnTour — <span className="swirl-text">Startup Innovativa</span>
              </h2>
            </Reveal>
            <Reveal delay={200}>
              <p style={{
                fontFamily: "var(--font-body)", fontSize: 16, color: "rgba(250,250,248,0.7)",
                lineHeight: 1.8, marginBottom: 16,
              }}>
                InnTour S.r.l. nasce con una missione chiara: portare il futuro digitale nei borghi dell'entroterra italiano, partendo dall'Alta Irpinia. La piattaforma MetaBorghi connette viaggiatori curiosi con esperienze autentiche, creando un ponte tra tradizione millenaria e innovazione tecnologica.
              </p>
            </Reveal>
            <Reveal delay={300}>
              <p style={{
                fontFamily: "var(--font-body)", fontSize: 16, color: "rgba(250,250,248,0.55)",
                lineHeight: 1.8,
              }}>
                Attraverso la nostra rete di 25 borghi, 14 aziende locali e oltre 15 esperienze curate, trasformiamo ogni visita in un racconto da vivere — dal laboratorio di ceramica al sentiero dell'Ofanto, dalla degustazione in cantina alla notte sotto le stelle.
              </p>
            </Reveal>
          </div>

          {/* Right: feature cards */}
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16 }}>
            {features.map((f, i) => (
              <Reveal key={i} delay={i * 120} scale>
                <div className="glass-card" style={{
                  padding: 24, borderRadius: 20,
                  transition: "all 0.4s cubic-bezier(0.34,1.56,0.64,1)",
                }}
                onMouseEnter={(e) => { e.currentTarget.style.transform = "translateY(-4px)"; e.currentTarget.style.borderColor = "rgba(0,208,132,0.2)"; }}
                onMouseLeave={(e) => { e.currentTarget.style.transform = ""; e.currentTarget.style.borderColor = "rgba(255,255,255,0.08)"; }}
                >
                  <div style={{ fontSize: 32, marginBottom: 12 }}>{f.icon}</div>
                  <h3 style={{
                    fontFamily: "var(--font-display)", fontSize: 18, fontWeight: 600,
                    color: "#FAFAF8", marginBottom: 8,
                  }}>{f.title}</h3>
                  <p style={{
                    fontFamily: "var(--font-body)", fontSize: 13, color: "rgba(250,250,248,0.55)",
                    lineHeight: 1.65,
                  }}>{f.desc}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

// ─── CTA SECTION (Full-width gradient) ───────────────────────────

function CTASection() {
  return (
    <section id="prenota" style={{
      padding: "clamp(80px, 10vw, 140px) 24px",
      background: "linear-gradient(135deg, #00D084 0%, #00B4D8 50%, #F0FF00 100%)",
      position: "relative", overflow: "hidden", textAlign: "center",
    }}>
      {/* Animated pattern */}
      <div style={{
        position: "absolute", inset: 0,
        background: "radial-gradient(circle at 20% 50%, rgba(255,255,255,0.15) 0%, transparent 50%), radial-gradient(circle at 80% 50%, rgba(255,255,255,0.1) 0%, transparent 40%)",
        animation: "breathe 6s ease-in-out infinite",
      }} />

      <div style={{ position: "relative", zIndex: 1, maxWidth: 700, margin: "0 auto" }}>
        <Reveal>
          <h2 style={{
            fontFamily: "var(--font-display)", fontWeight: 800,
            fontSize: "clamp(2rem, 1.5rem + 3vw, 3.8rem)",
            color: "#fff", lineHeight: 1.1, letterSpacing: "-0.02em", marginBottom: 20,
            textShadow: "0 2px 16px rgba(0,0,0,0.15)",
          }}>
            Pronto a vivere l'Alta Irpinia?
          </h2>
        </Reveal>
        <Reveal delay={200}>
          <p style={{
            fontFamily: "var(--font-body)", fontSize: 18, color: "rgba(255,255,255,0.85)",
            lineHeight: 1.7, marginBottom: 40, maxWidth: 500, margin: "0 auto 40px",
          }}>
            Scegli il tuo borgo, prenota un'esperienza e lasciati sorprendere dall'Italia più autentica.
          </p>
        </Reveal>
        <Reveal delay={400}>
          <div style={{ display: "flex", justifyContent: "center", gap: 16, flexWrap: "wrap" }}>
            <a href="#borghi" style={{
              background: "#fff", color: COLORS.green,
              fontFamily: "var(--font-body)", fontSize: 16, fontWeight: 700,
              padding: "16px 40px", borderRadius: 9999, textDecoration: "none",
              boxShadow: "0 8px 32px rgba(0,0,0,0.15)",
              transition: "all 0.35s cubic-bezier(0.34,1.56,0.64,1)",
            }}
            onMouseEnter={(e) => { e.target.style.transform = "translateY(-3px) scale(1.05)"; e.target.style.boxShadow = "0 16px 48px rgba(0,0,0,0.2)"; }}
            onMouseLeave={(e) => { e.target.style.transform = ""; e.target.style.boxShadow = "0 8px 32px rgba(0,0,0,0.15)"; }}
            >
              Esplora i Borghi
            </a>
            <a href="#esperienze" style={{
              background: "rgba(255,255,255,0.2)", backdropFilter: "blur(12px)",
              color: "#fff", fontFamily: "var(--font-body)", fontSize: 16, fontWeight: 600,
              padding: "16px 40px", borderRadius: 9999, textDecoration: "none",
              border: "2px solid rgba(255,255,255,0.4)",
              transition: "all 0.35s cubic-bezier(0.34,1.56,0.64,1)",
            }}
            onMouseEnter={(e) => { e.target.style.background = "rgba(255,255,255,0.35)"; e.target.style.transform = "translateY(-2px)"; }}
            onMouseLeave={(e) => { e.target.style.background = "rgba(255,255,255,0.2)"; e.target.style.transform = ""; }}
            >
              Vedi Esperienze
            </a>
          </div>
        </Reveal>
      </div>
    </section>
  );
}

// ─── FOOTER ──────────────────────────────────────────────────────

function Footer() {
  return (
    <footer id="contatti" style={{
      background: COLORS.notte, padding: "64px 24px 32px",
      position: "relative", overflow: "hidden",
    }}>
      <div className="noise" style={{ position: "absolute", inset: 0 }} />

      <div style={{ maxWidth: 1280, margin: "0 auto", position: "relative", zIndex: 1 }}>
        <div style={{ display: "grid", gridTemplateColumns: "2fr 1fr 1fr 1fr", gap: 48, marginBottom: 48 }}>
          {/* Brand col */}
          <div>
            <Logo variant="dark" size={36} />
            <p style={{
              fontFamily: "var(--font-body)", fontSize: 14, color: "rgba(250,250,248,0.5)",
              lineHeight: 1.7, marginTop: 16, maxWidth: 280,
            }}>
              La piattaforma digitale per scoprire, vivere e prenotare esperienze autentiche nei borghi dell'Alta Irpinia.
            </p>
            {/* Social row */}
            <div style={{ display: "flex", gap: 12, marginTop: 20 }}>
              {["IG", "FB", "LI", "YT"].map((s) => (
                <div key={s} style={{
                  width: 36, height: 36, borderRadius: "50%",
                  background: "rgba(255,255,255,0.06)", border: "1px solid rgba(255,255,255,0.08)",
                  display: "flex", alignItems: "center", justifyContent: "center",
                  color: "rgba(250,250,248,0.5)", fontFamily: "var(--font-body)",
                  fontSize: 11, fontWeight: 600, cursor: "pointer",
                  transition: "all 0.3s",
                }}
                onMouseEnter={(e) => { e.target.style.background = "rgba(0,208,132,0.15)"; e.target.style.color = COLORS.green; e.target.style.borderColor = "rgba(0,208,132,0.3)"; }}
                onMouseLeave={(e) => { e.target.style.background = "rgba(255,255,255,0.06)"; e.target.style.color = "rgba(250,250,248,0.5)"; e.target.style.borderColor = "rgba(255,255,255,0.08)"; }}
                >
                  {s}
                </div>
              ))}
            </div>
          </div>

          {/* Links columns */}
          {[
            { title: "Piattaforma", links: ["Borghi", "Esperienze", "Aziende", "Prodotti", "Artigianato"] },
            { title: "Azienda", links: ["Chi Siamo", "Team", "Progetti B2B", "Stampa", "Lavora con noi"] },
            { title: "Supporto", links: ["FAQ", "Contatti", "Privacy Policy", "Termini", "Cookie"] },
          ].map((col) => (
            <div key={col.title}>
              <h4 style={{
                fontFamily: "var(--font-body)", fontSize: 13, fontWeight: 600,
                color: "rgba(250,250,248,0.4)", letterSpacing: 1.5, textTransform: "uppercase",
                marginBottom: 20,
              }}>{col.title}</h4>
              {col.links.map((l) => (
                <a key={l} href="#" style={{
                  display: "block", fontFamily: "var(--font-body)", fontSize: 14,
                  color: "rgba(250,250,248,0.6)", textDecoration: "none",
                  padding: "6px 0", transition: "color 0.2s",
                }}
                onMouseEnter={(e) => e.target.style.color = COLORS.green}
                onMouseLeave={(e) => e.target.style.color = "rgba(250,250,248,0.6)"}
                >
                  {l}
                </a>
              ))}
            </div>
          ))}
        </div>

        {/* Bottom bar */}
        <div style={{
          borderTop: "1px solid rgba(255,255,255,0.06)", paddingTop: 24,
          display: "flex", justifyContent: "space-between", alignItems: "center",
        }}>
          <p style={{ fontFamily: "var(--font-body)", fontSize: 13, color: "rgba(250,250,248,0.3)" }}>
            © 2026 InnTour S.r.l. — Startup Innovativa · P.IVA 03079550640
          </p>
          {/* Tricolor accent */}
          <div style={{ display: "flex", gap: 4 }}>
            <div style={{ width: 20, height: 3, borderRadius: 2, background: COLORS.green }} />
            <div style={{ width: 20, height: 3, borderRadius: 2, background: COLORS.cyan }} />
            <div style={{ width: 20, height: 3, borderRadius: 2, background: COLORS.yellow }} />
          </div>
        </div>
      </div>
    </footer>
  );
}

// ─── MAIN APP ────────────────────────────────────────────────────

export default function MetaBorghiFrontend() {
  const scrollY = useScrollY();

  return (
    <>
      <style>{globalStyles}</style>
      <Navbar scrollY={scrollY} />
      <HeroSection scrollY={scrollY} />
      <StatsRibbon />
      <BorghiSection />
      <ParallaxDivider scrollY={scrollY} />
      <EsperienzeSection />
      <MapSection />
      <ChiSiamoSection />
      <CTASection />
      <Footer />
    </>
  );
}
