<?php
require_once __DIR__ . '/admin/config.php';

try {
    $db = getDB();
    $depoimentos = $db->query("SELECT * FROM depoimentos WHERE ativo = 1 ORDER BY ordem ASC, id DESC")->fetchAll();
} catch (Throwable $e) {
    $depoimentos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Daniel Queiroz — Videomaker e Publicitário em Santarém, PA. Produções cinematográficas para marcas, campanhas eleitorais e marketing imobiliário.">
<meta property="og:title" content="Daniel Queiroz — Videomaker">
<meta property="og:description" content="Vídeos cinematográficos que transformam sua marca em autoridade.">
<meta property="og:type" content="website">
<title>Daniel Queiroz — Videomaker</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:ital,wght@0,300;0,400;0,600;1,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════
   RESET + VARIÁVEIS
═══════════════════════════════════════════ */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --cyan:#00f5ff;
  --magenta:#ff006e;
  --purple:#7b2fff;
  --gold:#ffd700;
  --dark:#02040a;
  --dark2:#060c16;
  --text:#e8eaf0;
  --muted:#556070;
  --glass:rgba(255,255,255,0.05);
  --glass-border:rgba(255,255,255,0.10);
}
html{scroll-behavior:smooth;overflow-x:hidden}
body{
  background:var(--dark);
  color:var(--text);
  font-family:'Barlow',sans-serif;
  overflow-x:hidden;
  cursor:none;
}

/* ═══════════════════════════════════════════
   CURSOR CUSTOMIZADO
═══════════════════════════════════════════ */
.cursor{
  position:fixed;width:10px;height:10px;
  background:var(--cyan);border-radius:50%;
  pointer-events:none;z-index:9999;
  transform:translate(-50%,-50%);
  transition:transform .1s,background .2s;
  mix-blend-mode:difference;
}
.cursor-ring{
  position:fixed;width:36px;height:36px;
  border:1px solid rgba(0,245,255,0.5);
  border-radius:50%;pointer-events:none;
  z-index:9998;transform:translate(-50%,-50%);
  transition:transform .18s ease,width .2s,height .2s,border-color .2s;
}
body:has(a:hover) .cursor{background:var(--gold);transform:translate(-50%,-50%) scale(1.5)}
body:has(a:hover) .cursor-ring{width:50px;height:50px;border-color:var(--gold)}

/* ═══════════════════════════════════════════
   GRAIN OVERLAY
═══════════════════════════════════════════ */
.grain{
  position:fixed;inset:0;pointer-events:none;z-index:9990;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  opacity:.35;mix-blend-mode:overlay;
  animation:grain .3s steps(2) infinite;
}
@keyframes grain{0%,100%{transform:translate(0,0)}25%{transform:translate(-2px,1px)}50%{transform:translate(1px,-2px)}75%{transform:translate(-1px,2px)}}

/* ═══════════════════════════════════════════
   LOADER
═══════════════════════════════════════════ */
#loader{
  position:fixed;inset:0;z-index:9000;
  background:var(--dark);
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  transition:opacity .8s ease, visibility .8s ease;
}
#loader.hide{opacity:0;visibility:hidden}
.loader-bar-wrap{
  width:200px;height:1px;
  background:rgba(255,255,255,.1);
  margin-top:2rem;overflow:hidden;
}
.loader-bar{
  height:100%;width:0;
  background:linear-gradient(90deg,var(--cyan),var(--magenta));
  animation:loadBar 2.2s ease forwards;
}
@keyframes loadBar{to{width:100%}}
.loader-text{
  font-family:'Space Mono',monospace;
  font-size:.55rem;letter-spacing:.3em;
  color:var(--muted);margin-top:1rem;
  text-transform:uppercase;
}

/* ═══════════════════════════════════════════
   LETTERBOX
═══════════════════════════════════════════ */
.letterbox-top,.letterbox-bottom{
  position:fixed;left:0;right:0;
  height:60px;background:var(--dark);
  z-index:8000;transition:transform 1s ease;
}
.letterbox-top{top:0;transform:translateY(0)}
.letterbox-bottom{bottom:0;transform:translateY(0)}
.letterbox-top.open{transform:translateY(-100%)}
.letterbox-bottom.open{transform:translateY(100%)}

/* ═══════════════════════════════════════════
   NAV
═══════════════════════════════════════════ */
nav{
  position:fixed;top:0;left:0;right:0;
  z-index:7000;padding:1.2rem 2rem;
  display:flex;align-items:center;justify-content:space-between;
  transition:background .3s,backdrop-filter .3s;
}
nav.scrolled{
  background:rgba(2,4,10,.92);
  backdrop-filter:blur(20px);
  border-bottom:1px solid rgba(0,245,255,.08);
}
.nav-logo{
  font-family:'Bebas Neue',sans-serif;
  font-size:1.8rem;letter-spacing:.05em;
  background:linear-gradient(135deg,var(--cyan),var(--magenta));
  -webkit-background-clip:text;background-clip:text;color:transparent;
  text-decoration:none;
}
.nav-links{display:flex;gap:2rem;list-style:none}
.nav-links a{
  font-family:'Space Mono',monospace;
  font-size:.6rem;letter-spacing:.2em;
  text-transform:uppercase;color:var(--muted);
  text-decoration:none;transition:color .2s;
  position:relative;
}
.nav-links a::after{
  content:'';position:absolute;bottom:-4px;left:0;right:0;
  height:1px;background:var(--cyan);
  transform:scaleX(0);transition:transform .3s;
}
.nav-links a:hover{color:var(--cyan)}
.nav-links a:hover::after{transform:scaleX(1)}
.nav-toggle{
  display:none;flex-direction:column;gap:5px;
  cursor:pointer;background:none;border:none;
}
.nav-toggle span{
  width:24px;height:1px;
  background:var(--text);transition:.3s;
}

/* ═══════════════════════════════════════════
   HERO
═══════════════════════════════════════════ */
#hero{
  position:relative;height:100vh;min-height:600px;
  display:flex;align-items:flex-end;
  overflow:hidden;
}
.hero-bg{
  position:absolute;inset:0;
  background:
    radial-gradient(circle at 72% 42%, rgba(0,245,255,.16) 0%, rgba(0,245,255,.07) 18%, transparent 52%),
    radial-gradient(circle at 83% 72%, rgba(0,245,255,.08) 0%, transparent 42%),
    linear-gradient(115deg, #02040a 0%, #040914 42%, #02040a 100%);
}
.hero-bg::before{
  content:'';
  position:absolute;
  inset:0;
  background:
    linear-gradient(to right, rgba(2,4,10,.96) 0%, rgba(2,4,10,.84) 28%, rgba(2,4,10,.56) 48%, rgba(2,4,10,.20) 68%, rgba(2,4,10,.06) 100%),
    radial-gradient(circle at 22% 40%, rgba(255,255,255,.025) 0%, transparent 28%),
    linear-gradient(90deg, transparent 0%, rgba(0,245,255,.03) 46%, transparent 100%);
}
.hero-bg::after{
  content:'';
  position:absolute;
  width:720px;
  height:720px;
  border-radius:50%;
  top:50%;
  right:-120px;
  transform:translate(0,-50%);
  background:rgba(0,245,255,.20);
  filter:blur(165px);
  animation:heroLight 18s ease-in-out infinite alternate;
  pointer-events:none;
  opacity:.95;
}
.hero-img{
  position:absolute;inset:0;
  width:100%;height:100%;
  object-fit:cover;
  object-position:center top;
  opacity:.5;
}
.hero-overlay{
  position:absolute;inset:0;
  background:linear-gradient(
    to right,
    rgba(2,4,10,.85) 0%,
    rgba(2,4,10,.5) 50%,
    rgba(2,4,10,.2) 100%
  );
}
.hero-overlay-bottom{
  position:absolute;bottom:0;left:0;right:0;height:40%;
  background:linear-gradient(to top,var(--dark),transparent);
}
.hero-content{
  position:relative;
  z-index:3;
  width:min(100%, 1180px);
  margin:0 auto;
  padding:0 2rem 5rem;
  display:flex;
  justify-content:flex-start;
}
.hero-panel{
  position:relative;
  max-width:620px;
  padding:2.2rem 1.75rem 1.6rem;
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  background:linear-gradient(135deg, rgba(255,255,255,.055), rgba(255,255,255,.02));
  border:1px solid rgba(255,255,255,.08);
  box-shadow:
    0 0 90px rgba(0,245,255,.10),
    inset 0 0 26px rgba(255,255,255,.04);
  overflow:hidden;
}
.hero-panel::before{
  content:'';
  position:absolute;
  inset:-1px;
  background:linear-gradient(120deg, transparent 20%, rgba(0,245,255,.22), rgba(255,0,110,.12), transparent 80%);
  filter:blur(28px);
  opacity:.32;
  z-index:-1;
}
.hero-panel::after{
  content:'';
  position:absolute;
  inset:0;
  background:linear-gradient(180deg, rgba(255,255,255,.02), transparent 35%, rgba(0,0,0,.06) 100%);
  pointer-events:none;
}
.hero-daniel{
  position:absolute;
  right:max(1.5vw, 18px);
  bottom:0;
  height:min(88vh, 900px);
  max-height:96%;
  z-index:2;
  pointer-events:none;
  filter:
    drop-shadow(0 0 35px rgba(0,245,255,.15))
    drop-shadow(0 18px 80px rgba(0,0,0,.45));
}
.hero-daniel img{
  display:block;
  height:100%;
  width:auto;
  object-fit:contain;
}
.hero-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:clamp(3rem,8vw,6.5rem);
  line-height:.95;letter-spacing:.02em;
  color:var(--text);
  max-width:800px;
  opacity:0;animation:fadeUp .8s ease .7s forwards;
}
.hero-title span{
  background:linear-gradient(120deg,#7afcff 0%,var(--cyan) 45%,#d7fdff 100%);
  -webkit-background-clip:text;background-clip:text;color:transparent;
  text-shadow:0 0 26px rgba(0,245,255,.45);
  filter:drop-shadow(0 0 10px rgba(0,245,255,.35));
}
.hero-sub{
  font-size:1rem;font-weight:300;color:rgba(232,234,240,.7);
  max-width:500px;margin-top:1.2rem;line-height:1.6;
  opacity:0;animation:fadeUp .8s ease .9s forwards;
}
/* Botão primário — energia premium */
.btn-primary{
  position:relative;
  display:inline-flex;align-items:center;gap:.55rem;
  padding:14px 26px;
  font-family:'Space Mono',monospace;
  font-weight:600;
  letter-spacing:.08em;
  text-transform:uppercase;
  font-size:.75rem;
  background:linear-gradient(90deg,#00f5ff,#00c8ff);
  color:#001015;
  border:none;
  overflow:hidden;
  text-decoration:none;
  box-shadow:0 0 25px rgba(0,245,255,.35),0 8px 30px rgba(0,0,0,.35);
  transition:all .25s ease;
}
.btn-primary::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(120deg,transparent,rgba(255,255,255,.4),transparent);
  transform:translateX(-100%);
  transition:.6s;
}
.btn-primary:hover::before{transform:translateX(100%)}
.btn-primary:hover{
  transform:translateY(-2px) scale(1.02);
  box-shadow:0 0 40px rgba(0,245,255,.6),0 12px 40px rgba(0,0,0,.45);
}
.btn-primary:active{transform:translateY(1px) scale(.98)}

/* Botão secundário — elegante premium */
.btn-secondary{
  display:inline-flex;align-items:center;gap:.5rem;
  padding:14px 26px;
  font-family:'Space Mono',monospace;
  font-weight:600;
  letter-spacing:.08em;
  text-transform:uppercase;
  font-size:.75rem;
  background:transparent;
  color:#e6faff;
  border:1px solid rgba(255,255,255,.2);
  backdrop-filter:blur(6px);
  text-decoration:none;
  transition:all .25s ease;
}
.btn-secondary:hover{
  border-color:#00f5ff;
  color:#00f5ff;
  box-shadow:0 0 15px rgba(0,245,255,.2);
  transform:translateY(-2px);
}

.hero-btns{
  display:flex;
  gap:18px;
  margin-top:2rem;
  flex-wrap:wrap;
  opacity:0;animation:fadeUp .8s ease 1.1s forwards;
}
.hero-btns a{display:inline-flex;align-items:center;transition:all .25s ease}

@keyframes heroLight{
  0%{transform:translate(-10px,-52%) scale(1.00)}
  50%{transform:translate(20px,-48%) scale(1.12)}
  100%{transform:translate(-35px,-54%) scale(1.24)}
}
@keyframes heroPulse{
  0%,100%{opacity:.45;transform:translate3d(0,0,0) scale(1)}
  50%{opacity:.72;transform:translate3d(-12px,8px,0) scale(1.06)}
}

/* ═══════════════════════════════════════════
   SEÇÃO PROBLEMA
═══════════════════════════════════════════ */
#problema{
  padding:6rem 2rem;
  background:var(--dark);
  position:relative;overflow:hidden;
}
#problema::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 50% 50%,rgba(255,0,110,.04) 0%,transparent 70%);
}
.section-tag{
  font-family:'Space Mono',monospace;
  font-size:.55rem;letter-spacing:.35em;
  text-transform:uppercase;color:var(--magenta);
  margin-bottom:1rem;
}
.section-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:clamp(2rem,5vw,3.5rem);
  letter-spacing:.03em;line-height:1;
  margin-bottom:3rem;
}
.problema-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:1.5rem;
  max-width:1100px;margin:0 auto;
}
/* Glass card */
.glass-card{
  background:var(--glass);
  border:1px solid var(--glass-border);
  backdrop-filter:blur(20px);
  padding:2rem;
  position:relative;overflow:hidden;
  transition:border-color .3s,transform .3s;
}
.glass-card::before{
  content:'';position:absolute;inset:-2px;
  background:linear-gradient(135deg,transparent 40%,rgba(255,255,255,.05) 50%,transparent 60%);
  transform:translateX(-100%);
  transition:transform .6s;
}
.glass-card:hover::before{transform:translateX(100%)}
.glass-card:hover{
  border-color:rgba(255,0,110,.3);
  transform:translateY(-4px);
}
.card-icon{
  width:44px;height:44px;
  border:1px solid rgba(255,0,110,.3);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:1.2rem;
}
.card-icon svg{width:20px;height:20px;stroke:var(--magenta);fill:none;stroke-width:1.5}
.card-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:1.3rem;letter-spacing:.05em;
  margin-bottom:.8rem;
}
.card-desc{
  font-size:.9rem;font-weight:300;
  color:rgba(232,234,240,.65);line-height:1.6;
}

/* ═══════════════════════════════════════════
   SEÇÃO QUEM SOU
═══════════════════════════════════════════ */
#quem-sou{
  padding:6rem 2rem;
  background:var(--dark2);
  position:relative;overflow:hidden;
}
#quem-sou::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 20% 50%,rgba(0,245,255,.04) 0%,transparent 60%);
}
.quem-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:5rem;align-items:center;
  max-width:1100px;margin:0 auto;
}
.quem-photo-wrap{
  position:relative;
}
.quem-photo{
  width:100%;aspect-ratio:3/4;
  object-fit:cover;object-position:top;
  filter:grayscale(20%) contrast(1.1);
  position:relative;z-index:1;
}
/* Placeholder foto */
.quem-photo-placeholder{
  width:100%;aspect-ratio:3/4;
  background:linear-gradient(135deg,#0a0010,#1a0520,#0a0520);
  position:relative;z-index:1;
  display:flex;align-items:center;justify-content:center;
}
.quem-photo-placeholder::after{
  content:'DANIEL\AQUEIROZ';
  font-family:'Bebas Neue',sans-serif;
  font-size:3rem;color:rgba(255,0,110,.2);
  text-align:center;white-space:pre;line-height:1.2;
}
.photo-border{
  position:absolute;
  bottom:-12px;right:-12px;
  width:calc(100% - 20px);
  height:calc(100% - 20px);
  border:1px solid rgba(0,245,255,.2);
  z-index:0;
}
.photo-accent{
  position:absolute;
  top:-1px;left:-1px;
  width:60px;height:2px;
  background:linear-gradient(90deg,var(--cyan),transparent);
}
.photo-accent-v{
  position:absolute;
  top:-1px;left:-1px;
  width:2px;height:60px;
  background:linear-gradient(180deg,var(--cyan),transparent);
}
/* Texto lado direito */
.quem-content .section-tag{color:var(--cyan)}
.quem-quote{
  font-family:'Bebas Neue',sans-serif;
  font-size:clamp(1.8rem,3vw,2.5rem);
  line-height:1.1;letter-spacing:.03em;
  margin-bottom:.5rem;
}
.quem-quote-sub{
  font-family:'Bebas Neue',sans-serif;
  font-size:clamp(1.8rem,3vw,2.5rem);
  line-height:1.1;letter-spacing:.03em;
  color:var(--cyan);margin-bottom:2rem;
}
.quem-text{
  font-size:.95rem;font-weight:300;
  color:rgba(232,234,240,.7);line-height:1.8;
  margin-bottom:2.5rem;
}
.quem-stats{
  display:grid;grid-template-columns:1fr 1fr;
  gap:1rem;margin-bottom:2.5rem;
}
.stat-item{
  border-left:2px solid var(--cyan);
  padding:.6rem 1rem;
}
.stat-num{
  font-family:'Bebas Neue',sans-serif;
  font-size:1.6rem;color:var(--cyan);
  line-height:1;
}
.stat-label{
  font-family:'Space Mono',monospace;
  font-size:.5rem;letter-spacing:.15em;
  text-transform:uppercase;color:var(--muted);
  margin-top:.2rem;
}

/* ═══════════════════════════════════════════
   SEÇÃO SERVIÇOS
═══════════════════════════════════════════ */
#servicos{
  padding:6rem 2rem;
  background:var(--dark);
  position:relative;overflow:hidden;
}
#servicos::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 80% 50%,rgba(123,47,255,.05) 0%,transparent 60%);
}
.servicos-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
  gap:1.5rem;
  max-width:1100px;margin:3rem auto 0;
}
.servico-card{
  position:relative;overflow:hidden;
  background:var(--glass);
  border:1px solid var(--glass-border);
  backdrop-filter:blur(20px);
  padding:2.5rem 2rem;
  transition:border-color .3s,transform .3s;
  cursor:default;
}
.servico-card::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(123,47,255,.08),transparent);
  opacity:0;transition:opacity .3s;
}
.servico-card:hover{
  border-color:rgba(123,47,255,.4);
  transform:translateY(-6px);
}
.servico-card:hover::after{opacity:1}
/* Card imobiliário em destaque */
.servico-card.destaque{
  border-color:rgba(0,245,255,.2);
}
.servico-card.destaque::after{
  background:linear-gradient(135deg,rgba(0,245,255,.06),transparent);
}
.servico-card.destaque:hover{border-color:var(--cyan)}
.servico-num{
  font-family:'Bebas Neue',sans-serif;
  font-size:4rem;color:rgba(123,47,255,.15);
  line-height:1;margin-bottom:1rem;
  position:absolute;top:1.5rem;right:1.5rem;
}
.servico-card.destaque .servico-num{color:rgba(0,245,255,.1)}
.servico-icon{
  width:48px;height:48px;
  border:1px solid rgba(123,47,255,.3);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:1.5rem;position:relative;z-index:1;
}
.servico-card.destaque .servico-icon{border-color:rgba(0,245,255,.3)}
.servico-icon svg{width:22px;height:22px;stroke:var(--purple);fill:none;stroke-width:1.5}
.servico-card.destaque .servico-icon svg{stroke:var(--cyan)}
.servico-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:1.6rem;letter-spacing:.04em;
  margin-bottom:.8rem;position:relative;z-index:1;
}
.servico-desc{
  font-size:.9rem;font-weight:300;
  color:rgba(232,234,240,.65);line-height:1.6;
  margin-bottom:1.2rem;position:relative;z-index:1;
}
.servico-tag{
  font-family:'Space Mono',monospace;
  font-size:.55rem;letter-spacing:.2em;
  text-transform:uppercase;
  color:var(--purple);position:relative;z-index:1;
}
.servico-card.destaque .servico-tag{color:var(--cyan)}

/* ═══════════════════════════════════════════
   PORTFÓLIO
═══════════════════════════════════════════ */
#portfolio{
  padding:6rem 2rem;
  background:var(--dark2);
  position:relative;overflow:hidden;
}
#portfolio::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 50% 0%,rgba(0,245,255,.04) 0%,transparent 60%);
}
.portfolio-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:1.5rem;
  max-width:1100px;margin:3rem auto 0;
}
.portfolio-slot{
  position:relative;aspect-ratio:16/9;
  background:rgba(6,12,22,.8);
  border:1px solid var(--glass-border);
  overflow:hidden;cursor:pointer;
  transition:border-color .3s;
}
.portfolio-slot:hover{border-color:var(--cyan)}
.portfolio-slot-inner{
  position:absolute;inset:0;
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  gap:1rem;
}
.portfolio-play{
  width:56px;height:56px;
  border:1px solid rgba(0,245,255,.3);
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  transition:background .3s,border-color .3s;
}
.portfolio-slot:hover .portfolio-play{
  background:rgba(0,245,255,.1);
  border-color:var(--cyan);
}
.portfolio-play svg{width:18px;height:18px;fill:var(--cyan);margin-left:3px}
.portfolio-cat{
  font-family:'Space Mono',monospace;
  font-size:.55rem;letter-spacing:.25em;
  text-transform:uppercase;color:var(--muted);
}
.portfolio-slot:hover .portfolio-cat{color:var(--cyan)}
.portfolio-coming{
  font-family:'Space Mono',monospace;
  font-size:.5rem;letter-spacing:.2em;
  color:rgba(85,96,112,.5);text-transform:uppercase;
}
.portfolio-note{
  text-align:center;margin-top:2rem;
  font-family:'Space Mono',monospace;
  font-size:.6rem;letter-spacing:.15em;
  color:var(--muted);
}
.portfolio-note span{color:var(--cyan)}

/* ═══════════════════════════════════════════
   VIRA BRASIL
═══════════════════════════════════════════ */
#vira-brasil{
  padding:6rem 2rem;
  background:var(--dark);
  position:relative;overflow:hidden;
}
#vira-brasil::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 50% 50%,rgba(255,215,0,.03) 0%,transparent 70%);
}
.vira-grid{
  display:grid;grid-template-columns:1fr 1fr;
  gap:4rem;align-items:center;
  max-width:1100px;margin:0 auto;
}
.vira-photos{
  display:grid;grid-template-columns:1fr 1fr;
  gap:1rem;
}
.vira-photo{
  aspect-ratio:3/4;object-fit:cover;
  filter:contrast(1.05);
  border:1px solid rgba(255,215,0,.1);
}
.vira-photo-placeholder{
  aspect-ratio:3/4;
  background:linear-gradient(135deg,#1a1000,#0a0800);
  border:1px solid rgba(255,215,0,.1);
  display:flex;align-items:center;justify-content:center;
}
.vira-content .section-tag{color:var(--gold)}
.vira-content .section-title span{color:var(--gold)}
.badge-credencial{
  display:inline-flex;align-items:center;gap:.6rem;
  background:rgba(255,215,0,.08);
  border:1px solid rgba(255,215,0,.2);
  padding:.6rem 1.2rem;margin-bottom:1.5rem;
}
.badge-credencial span{
  font-family:'Space Mono',monospace;
  font-size:.55rem;letter-spacing:.2em;
  text-transform:uppercase;color:var(--gold);
}
.vira-text{
  font-size:.95rem;font-weight:300;
  color:rgba(232,234,240,.7);line-height:1.8;
}
.vira-text strong{color:var(--gold);font-weight:600}

/* ═══════════════════════════════════════════
   PROCESSO
═══════════════════════════════════════════ */
#processo{
  padding:6rem 2rem;
  background:var(--dark2);
  position:relative;
}
.processo-wrap{max-width:1100px;margin:0 auto}
.processo-steps{
  display:grid;
  grid-template-columns:repeat(5,1fr);
  gap:0;margin-top:4rem;position:relative;
}
.processo-steps::before{
  content:'';position:absolute;
  top:28px;left:10%;right:10%;height:1px;
  background:linear-gradient(90deg,
    transparent,var(--cyan),var(--magenta),var(--purple),transparent
  );
}
.step{
  display:flex;flex-direction:column;
  align-items:center;text-align:center;
  padding:0 .5rem;position:relative;
}
.step-dot{
  width:56px;height:56px;
  border:1px solid rgba(0,245,255,.3);
  background:var(--dark2);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:1.5rem;position:relative;z-index:1;
  transition:border-color .3s,background .3s;
}
.step:hover .step-dot{
  border-color:var(--cyan);
  background:rgba(0,245,255,.05);
}
.step-dot svg{width:20px;height:20px;stroke:var(--cyan);fill:none;stroke-width:1.5}
.step-num{
  position:absolute;top:-8px;right:-8px;
  width:20px;height:20px;
  background:var(--magenta);
  font-family:'Space Mono',monospace;
  font-size:.5rem;font-weight:700;
  color:#000;display:flex;align-items:center;justify-content:center;
}
.step-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:1rem;letter-spacing:.08em;
  margin-bottom:.4rem;
}
.step-desc{
  font-size:.75rem;font-weight:300;
  color:var(--muted);line-height:1.5;
}

/* ═══════════════════════════════════════════
   DEPOIMENTOS — Holographic
═══════════════════════════════════════════ */
#depoimentos{
  padding:6rem 2rem;
  background:var(--dark);
  position:relative;overflow:hidden;
}
#depoimentos::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 50% 50%,rgba(255,215,0,.03) 0%,transparent 70%);
}
.depo-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
  gap:2rem;
  max-width:1100px;margin:3rem auto 0;
}
/* Holographic card */
.holo-card{
  padding:2rem;
  border-radius:0;
  background:linear-gradient(120deg,rgba(255,255,255,.07),rgba(255,255,255,.02));
  backdrop-filter:blur(16px);
  box-shadow:0 0 40px rgba(255,215,0,.08),inset 0 0 30px rgba(255,255,255,.05);
  transform-style:preserve-3d;
  animation:holoFloat 6s ease-in-out infinite;
  position:relative;border:1px solid rgba(255,215,0,.15);
}
.holo-card:nth-child(2){animation-delay:-2s}
.holo-card:nth-child(3){animation-delay:-4s}
.holo-card::before{
  content:'';position:absolute;inset:-1px;
  background:linear-gradient(120deg,transparent 20%,var(--gold),var(--magenta),var(--cyan),transparent 80%);
  filter:blur(20px);opacity:.15;z-index:-1;
  animation:holoShift 4s linear infinite;
  background-size:400% 100%;
}
@keyframes holoShift{to{background-position:400% 0}}
@keyframes holoFloat{
  0%,100%{transform:rotateX(4deg) rotateY(-4deg) translateY(0)}
  50%{transform:rotateX(6deg) rotateY(4deg) translateY(-12px)}
}
.depo-top{display:flex;gap:.8rem;align-items:center;margin-bottom:1rem}
.depo-avatar{
  width:36px;height:36px;border-radius:50%;
  background:linear-gradient(135deg,var(--gold),var(--magenta));
  display:flex;align-items:center;justify-content:center;
  font-family:'Bebas Neue',sans-serif;font-size:1rem;color:#000;
  flex-shrink:0;
}
.depo-avatar img{
  width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;
}
.depo-name{
  font-family:'Space Mono',monospace;
  font-size:.7rem;font-weight:700;
  letter-spacing:.1em;
}
.depo-role{
  font-size:.75rem;font-weight:300;
  color:var(--muted);margin-top:.1rem;
}
.depo-stars{
  color:var(--gold);font-size:.8rem;
  letter-spacing:2px;margin-bottom:.8rem;
}
.depo-text{
  font-size:.9rem;font-weight:300;
  color:rgba(232,234,240,.8);line-height:1.7;
  font-style:italic;
}
.depo-waiting{
  text-align:center;padding:3rem;
  font-family:'Space Mono',monospace;
  font-size:.6rem;letter-spacing:.2em;
  color:var(--muted);text-transform:uppercase;
  border:1px dashed rgba(255,215,0,.15);
}

/* ═══════════════════════════════════════════
   FAQ
═══════════════════════════════════════════ */
#faq{
  padding:6rem 2rem;
  background:var(--dark2);
}
.faq-wrap{max-width:700px;margin:3rem auto 0}
.faq-item{
  border-bottom:1px solid var(--glass-border);
  overflow:hidden;
}
.faq-q{
  width:100%;background:none;border:none;
  padding:1.4rem 0;
  display:flex;align-items:center;justify-content:space-between;
  cursor:pointer;text-align:left;
  font-family:'Barlow',sans-serif;font-size:1rem;font-weight:600;
  color:var(--text);transition:color .2s;
  gap:1rem;
}
.faq-q:hover{color:var(--cyan)}
.faq-icon{
  width:20px;height:20px;flex-shrink:0;
  border:1px solid var(--glass-border);
  display:flex;align-items:center;justify-content:center;
  font-size:.8rem;color:var(--muted);
  transition:transform .3s,border-color .2s,color .2s;
}
.faq-item.open .faq-icon{
  transform:rotate(45deg);
  border-color:var(--cyan);color:var(--cyan);
}
.faq-a{
  max-height:0;overflow:hidden;
  transition:max-height .4s ease,padding .3s;
  font-size:.9rem;font-weight:300;
  color:rgba(232,234,240,.7);line-height:1.8;
}
.faq-item.open .faq-a{
  max-height:300px;padding-bottom:1.4rem;
}

/* ═══════════════════════════════════════════
   CTA FINAL
═══════════════════════════════════════════ */
#contato{
  position:relative;padding:8rem 2rem;
  overflow:hidden;
  background:var(--dark);
}
.contato-bg{
  position:absolute;inset:0;
  background:linear-gradient(135deg,#05000f,#02040a,#000508);
}
.contato-bg::before{
  content:'';position:absolute;inset:0;
  background:
    radial-gradient(ellipse at 30% 50%,rgba(0,245,255,.05) 0%,transparent 50%),
    radial-gradient(ellipse at 70% 50%,rgba(255,0,110,.04) 0%,transparent 50%);
}
/* Scanlines */
.contato-bg::after{
  content:'';position:absolute;inset:0;
  background:repeating-linear-gradient(
    0deg,transparent,transparent 3px,
    rgba(0,0,0,.05) 3px,rgba(0,0,0,.05) 4px
  );pointer-events:none;
}
.contato-content{
  position:relative;z-index:1;
  max-width:700px;margin:0 auto;
  text-align:center;
}
.contato-pre{
  font-family:'Space Mono',monospace;
  font-size:.55rem;letter-spacing:.35em;
  text-transform:uppercase;color:var(--cyan);
  margin-bottom:1.5rem;
}
.contato-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:clamp(2.5rem,6vw,4.5rem);
  line-height:1;letter-spacing:.03em;
  margin-bottom:1rem;
}
.contato-title span{
  background:linear-gradient(135deg,var(--cyan),var(--magenta));
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
.contato-sub{
  font-size:.9rem;font-weight:300;
  color:rgba(232,234,240,.6);margin-bottom:2.5rem;line-height:1.6;
}
.contato-escassez{
  font-family:'Space Mono',monospace;
  font-size:.6rem;letter-spacing:.2em;
  text-transform:uppercase;
  color:var(--magenta);margin-bottom:2rem;
}
.contato-btns{
  display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;
  margin-bottom:3rem;
}
.contato-links{
  display:flex;gap:2.5rem;justify-content:center;flex-wrap:wrap;
}
.contato-link{
  display:flex;align-items:center;gap:.5rem;
  font-family:'Space Mono',monospace;
  font-size:.6rem;letter-spacing:.15em;
  text-transform:uppercase;color:var(--muted);
  text-decoration:none;transition:color .2s;
}
.contato-link:hover{color:var(--cyan)}
.contato-link svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:1.5}

/* ═══════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════ */
footer{
  padding:2rem;background:var(--dark);
  border-top:1px solid var(--glass-border);
  display:flex;align-items:center;justify-content:space-between;
  flex-wrap:wrap;gap:1rem;
}
.footer-logo{
  font-family:'Bebas Neue',sans-serif;font-size:1.4rem;
  background:linear-gradient(135deg,var(--cyan),var(--magenta));
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
.footer-copy{
  font-family:'Space Mono',monospace;
  font-size:.5rem;letter-spacing:.2em;
  text-transform:uppercase;color:var(--muted);
}

/* ═══════════════════════════════════════════
   WHATSAPP FLUTUANTE
═══════════════════════════════════════════ */
.wa-btn{
  position:fixed;bottom:2rem;right:2rem;z-index:6000;
  width:52px;height:52px;
  background:#25d366;
  border-radius:50%;border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 4px 20px rgba(37,211,102,.4);
  animation:waPulse 3s ease-in-out infinite;
  text-decoration:none;transition:transform .2s;
}
.wa-btn:hover{transform:scale(1.1)}
.wa-btn svg{width:26px;height:26px;fill:#fff}
@keyframes waPulse{
  0%,100%{box-shadow:0 4px 20px rgba(37,211,102,.4)}
  50%{box-shadow:0 4px 35px rgba(37,211,102,.7),0 0 0 8px rgba(37,211,102,.1)}
}

/* ═══════════════════════════════════════════
   BACKGROUND GRADIENTE RESPONSIVO POR SEÇÃO
═══════════════════════════════════════════ */
body::after{
  content:'';position:fixed;inset:0;pointer-events:none;
  z-index:-1;transition:background 1.2s ease;
}
body[data-section="hero"]::after{background:radial-gradient(ellipse at 70% 30%,rgba(0,245,255,.04) 0%,transparent 60%)}
body[data-section="problema"]::after{background:radial-gradient(ellipse at 50% 50%,rgba(255,0,110,.04) 0%,transparent 60%)}
body[data-section="quem-sou"]::after{background:radial-gradient(ellipse at 30% 50%,rgba(0,245,255,.04) 0%,transparent 60%)}
body[data-section="servicos"]::after{background:radial-gradient(ellipse at 70% 50%,rgba(123,47,255,.04) 0%,transparent 60%)}
body[data-section="portfolio"]::after{background:radial-gradient(ellipse at 50% 30%,rgba(0,245,255,.03) 0%,transparent 60%)}
body[data-section="vira-brasil"]::after{background:radial-gradient(ellipse at 50% 50%,rgba(255,215,0,.03) 0%,transparent 60%)}
body[data-section="depoimentos"]::after{background:radial-gradient(ellipse at 50% 50%,rgba(255,215,0,.03) 0%,transparent 60%)}
body[data-section="contato"]::after{background:radial-gradient(ellipse at 50% 50%,rgba(0,245,255,.04) 0%,transparent 60%)}

/* ═══════════════════════════════════════════
   SCROLL REVEAL
═══════════════════════════════════════════ */
.reveal{opacity:0;transform:translateY(40px);transition:opacity .7s ease,transform .7s ease}
.reveal.visible{opacity:1;transform:translateY(0)}
.reveal-left{opacity:0;transform:translateX(-40px);transition:opacity .7s ease,transform .7s ease}
.reveal-left.visible{opacity:1;transform:translateX(0)}
.reveal-right{opacity:0;transform:translateX(40px);transition:opacity .7s ease,transform .7s ease}
.reveal-right.visible{opacity:1;transform:translateX(0)}
.reveal-delay-1{transition-delay:.1s}
.reveal-delay-2{transition-delay:.2s}
.reveal-delay-3{transition-delay:.3s}
.reveal-delay-4{transition-delay:.4s}

/* ═══════════════════════════════════════════
   UTILITÁRIOS
═══════════════════════════════════════════ */
.container{max-width:1100px;margin:0 auto}
@keyframes fadeUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.text-cyan{color:var(--cyan)}
.text-magenta{color:var(--magenta)}

/* ═══════════════════════════════════════════
   RESPONSIVIDADE
═══════════════════════════════════════════ */
@media(max-width:1024px){
  .quem-grid{gap:3rem}
  .processo-steps{grid-template-columns:1fr 1fr;gap:2rem}
  .processo-steps::before{display:none}
  .vira-grid{gap:2rem}
}
@media(min-width:769px){
  #hero{
    align-items:stretch;
  }
  #hero::before{
    content:'';
    position:absolute;
    inset:0;
    background:
      radial-gradient(circle at 79% 52%, rgba(0,245,255,.16) 0%, rgba(0,245,255,.05) 28%, transparent 58%),
      radial-gradient(circle at 64% 82%, rgba(123,47,255,.12) 0%, transparent 45%),
      linear-gradient(to top, rgba(2,4,10,.86) 0%, transparent 28%);
    pointer-events:none;
    z-index:1;
    animation:heroPulse 16s ease-in-out infinite;
  }
  .hero-content{
    width:min(100%, 1280px);
    padding:0 3.5rem 5.5rem;
    align-items:flex-end;
  }
  .hero-panel{
    max-width:560px;
    padding:0;
    border:none;
    background:transparent;
    backdrop-filter:none;
    -webkit-backdrop-filter:none;
    box-shadow:none;
  }
  .hero-panel::before,
  .hero-panel::after{display:none}
  .hero-title{font-size:clamp(4rem,7vw,6.3rem)}
  .hero-sub{
    max-width:470px;
    color:rgba(232,234,240,.78);
  }
  .hero-daniel{
    right:clamp(1.4rem,5vw,5rem);
    height:min(92vh, 950px);
    filter:
      drop-shadow(0 0 42px rgba(0,245,255,.18))
      drop-shadow(0 32px 110px rgba(0,0,0,.52));
  }
  .hero-daniel::before{
    content:'';
    position:absolute;
    right:4%;
    bottom:8%;
    width:62%;
    height:38%;
    background:radial-gradient(circle at center, rgba(0,245,255,.34), rgba(0,245,255,0));
    filter:blur(46px);
    z-index:-1;
    opacity:.8;
    pointer-events:none;
  }
}
@media(max-width:768px){
  body{cursor:auto}
  .cursor,.cursor-ring{display:none}
  nav{padding:1rem 1.2rem}
  .nav-links{display:none}
  .nav-links.open{
    display:flex;flex-direction:column;gap:1.5rem;
    position:fixed;inset:0;background:rgba(2,4,10,.98);
    padding:5rem 2rem;z-index:6999;
  }
  .nav-links a{font-size:.8rem}
  .nav-toggle{display:flex}
  .hero-bg::after{
    width:420px;
    height:420px;
    right:-110px;
    top:30%;
    filter:blur(110px);
    opacity:.7;
  }
  .hero-content{
    padding:0 1rem 3rem;
  }
  .hero-panel{
    max-width:100%;
    margin-top:5.5rem;
    padding:1.55rem 1.15rem 1.2rem;
    background:linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.018));
  }
  .hero-daniel{
    right:-120px;
    height:68vh;
    opacity:.42;
    filter:
      drop-shadow(0 0 24px rgba(0,245,255,.12))
      drop-shadow(0 14px 50px rgba(0,0,0,.42));
  }
  .quem-grid{grid-template-columns:1fr}
  .quem-photo-wrap{max-width:400px;margin:0 auto}
  .vira-grid{grid-template-columns:1fr}
  .portfolio-grid{grid-template-columns:1fr}
  .processo-steps{grid-template-columns:1fr}
  footer{flex-direction:column;align-items:flex-start}
}
@media(max-width:480px){
  #hero{min-height:720px}
  .hero-title{font-size:2.5rem}
  .hero-panel{
    margin-top:5.8rem;
    padding:1.35rem 1rem 1.1rem;
  }
  .hero-sub{font-size:.95rem; max-width:100%;}
  .hero-btns{flex-direction:column}
  .hero-daniel{
    height:58vh;
    right:-140px;
    bottom:-10px;
    opacity:.28;
  }
  .section-title{font-size:2rem}
  .contato-btns{flex-direction:column;align-items:center}
  .vira-photos{grid-template-columns:1fr}
  .quem-stats{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body data-section="hero">

<!-- CURSOR -->
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- GRAIN -->
<div class="grain"></div>

<!-- LOADER -->
<div id="loader">
  <div class="loader-bar-wrap"><div class="loader-bar"></div></div>
  <div class="loader-text">Carregando experiência</div>
</div>

<!-- LETTERBOX -->
<div class="letterbox-top" id="ltTop"></div>
<div class="letterbox-bottom" id="ltBot"></div>

<!-- WHATSAPP FLUTUANTE -->
<a href="https://wa.me/5593919295862" target="_blank" class="wa-btn" aria-label="WhatsApp">
  <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
</a>

<!-- NAV -->
<nav id="mainNav">
  <a href="#hero" class="nav-logo">DQ</a>
  <ul class="nav-links" id="navLinks">
    <li><a href="#quem-sou" onclick="closeNav()">Sobre</a></li>
    <li><a href="#servicos" onclick="closeNav()">Serviços</a></li>
    <li><a href="#portfolio" onclick="closeNav()">Portfólio</a></li>
    <li><a href="#vira-brasil" onclick="closeNav()">Vira Brasil</a></li>
    <li><a href="#contato" onclick="closeNav()">Contato</a></li>
  </ul>
  <button class="nav-toggle" id="navToggle" aria-label="Menu" onclick="toggleNav()">
    <span></span><span></span><span></span>
  </button>
</nav>

<!-- ═══════════════════════════════════════
  HERO
═══════════════════════════════════════ -->
<section id="hero">
  <div class="hero-bg"></div>
  <!-- Substituir src pela foto tks_porsol_041 processada -->
  <!-- <picture>
    <source srcset="assets/img/hero.avif" type="image/avif">
    <source srcset="assets/img/hero.webp" type="image/webp">
    <img class="hero-img" src="assets/img/hero.jpg" alt="Daniel Queiroz filmando ao pôr do sol">
  </picture> -->
  <div class="hero-overlay"></div>
  <div class="hero-overlay-bottom"></div>

  <div class="hero-content">
    <div class="hero-panel">
      <h1 class="hero-title">
        Vídeos que <span>transformam</span> sua marca
      </h1>
      <p class="hero-sub">
        Não entrego apenas um vídeo bonito.<br>
        Entrego estratégia, autoridade e resultado.
      </p>
      <div class="hero-btns">
        <a href="https://wa.me/5593919295862" class="btn-primary">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
          Solicitar Orçamento
        </a>
        <a href="#portfolio" class="btn-secondary">Ver Portfólio</a>
      </div>
    </div>
  </div>

  <div class="hero-daniel" aria-hidden="true">
    <img src="danielhero.webp" alt="">
  </div>
</section>

<!-- ═══════════════════════════════════════
  PROBLEMA
═══════════════════════════════════════ -->
<section id="problema">
  <div class="container">
    <div class="reveal">
      <div class="section-tag">O problema</div>
      <h2 class="section-title">Sua empresa ainda<br>parece amadora online?</h2>
    </div>
    <div class="problema-grid">
      <div class="glass-card reveal reveal-delay-1">
        <div class="card-icon">
          <svg viewBox="0 0 24 24"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        </div>
        <div class="card-title">Sem planejamento</div>
        <p class="card-desc">Vídeos que existem mas não comunicam, não engajam e não posicionam sua marca onde deveria estar.</p>
      </div>
      <div class="glass-card reveal reveal-delay-2">
        <div class="card-icon">
          <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div class="card-title">Imagem sem autoridade</div>
        <p class="card-desc">Sem produção profissional, seu negócio perde credibilidade antes mesmo de falar — o visual fala primeiro.</p>
      </div>
      <div class="glass-card reveal reveal-delay-3">
        <div class="card-icon">
          <svg viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div class="card-title">Concorrente à sua frente</div>
        <p class="card-desc">Enquanto você hesita, quem já investe em vídeo profissional está fechando os clientes que deveriam ser seus.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
  QUEM SOU
═══════════════════════════════════════ -->
<section id="quem-sou">
  <div class="container">
    <div class="quem-grid">
      <div class="quem-photo-wrap reveal-left">
        <!-- Substituir pela foto tk_escuverme_000 -->
        <!-- <picture>
          <source srcset="assets/img/about.avif" type="image/avif">
          <source srcset="assets/img/about.webp" type="image/webp">
          <img class="quem-photo" src="assets/img/about.jpg" alt="Daniel Queiroz editando">
        </picture> -->
        <div class="quem-photo-placeholder"></div>
        <div class="photo-border"></div>
        <div class="photo-accent"></div>
        <div class="photo-accent-v"></div>
      </div>
      <div class="quem-content reveal-right">
        <div class="section-tag">Quem sou</div>
        <h2 class="quem-quote">Não entrego apenas um vídeo bonito.</h2>
        <div class="quem-quote-sub">Entrego estratégia.</div>
        <p class="quem-text">
          Sou publicitário e videomaker, especializado em transformar ideias em conteúdos que geram conexão, autoridade e resultado. Ao longo da minha trajetória no audiovisual percebi que o vídeo não é apenas estética — é uma poderosa ferramenta de posicionamento de marcas.
        </p>
        <p class="quem-text">
          Um vídeo marcante precisa unir <strong style="color:var(--cyan)">história, estratégia e emoção</strong>. Quando isso acontece, ele não apenas prende a atenção — ele gera identificação e faz a pessoa lembrar da sua marca.
        </p>
        <div class="quem-stats">
          <div class="stat-item">
            <div class="stat-num">+10</div>
            <div class="stat-label">Campanhas eleitorais</div>
          </div>
          <div class="stat-item">
            <div class="stat-num">3 anos</div>
            <div class="stat-label">@voxmaistv</div>
          </div>
          <div class="stat-item">
            <div class="stat-num">4 anos</div>
            <div class="stat-label">Dir. Comunicação</div>
          </div>
          <div class="stat-item">
            <div class="stat-num">2025</div>
            <div class="stat-label">Vira Brasil Mídia</div>
          </div>
        </div>
        <a href="https://wa.me/5593919295862" class="btn-primary">Falar com Daniel</a>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
  SERVIÇOS
═══════════════════════════════════════ -->
<section id="servicos">
  <div class="container">
    <div class="reveal">
      <div class="section-tag">O que faço</div>
      <h2 class="section-title">Como posso<br><span class="text-cyan">transformar</span> sua marca</h2>
    </div>
    <div class="servicos-grid">
      <div class="servico-card reveal reveal-delay-1">
        <div class="servico-num">01</div>
        <div class="servico-icon">
          <svg viewBox="0 0 24 24"><path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg>
        </div>
        <div class="servico-title">Publicidade Política</div>
        <p class="servico-desc">Vídeos estratégicos para campanhas eleitorais. Construção de imagem pública com produção rápida, roteiro claro e comunicação direta que convence.</p>
        <div class="servico-tag">+10 campanhas produzidas</div>
      </div>

      <div class="servico-card destaque reveal reveal-delay-2">
        <div class="servico-num">02</div>
        <div class="servico-icon">
          <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div class="servico-title">Marketing Imobiliário</div>
        <p class="servico-desc">Tours cinematográficos com drone. Mostre o imóvel ou projeto antes mesmo de ser construído — tecnologia que encanta clientes e acelera vendas.</p>
        <div class="servico-tag">Drone · Tecnologia exclusiva</div>
      </div>

      <div class="servico-card reveal reveal-delay-3">
        <div class="servico-num">03</div>
        <div class="servico-icon">
          <svg viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
        </div>
        <div class="servico-title">Eventos & Mídia</div>
        <p class="servico-desc">Cobertura profissional de eventos corporativos, religiosos e culturais. Credenciado como Videomaker Mídia Oficial no Vira Brasil 2025/2026.</p>
        <div class="servico-tag">Credencial Oficial de Mídia</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
  PORTFÓLIO
═══════════════════════════════════════ -->
<section id="portfolio">
  <div class="container">
    <div class="reveal">
      <div class="section-tag">Portfólio</div>
      <h2 class="section-title">O trabalho<br><span class="text-cyan">fala por si</span></h2>
    </div>
    <div class="portfolio-grid">
      <div class="portfolio-slot reveal reveal-delay-1">
        <div class="portfolio-slot-inner">
          <div class="portfolio-play">
            <svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          </div>
          <div class="portfolio-cat">Publicidade Política</div>
          <div class="portfolio-coming">Vídeo em breve</div>
        </div>
      </div>
      <div class="portfolio-slot reveal reveal-delay-2">
        <div class="portfolio-slot-inner">
          <div class="portfolio-play">
            <svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          </div>
          <div class="portfolio-cat">Marketing Imobiliário</div>
          <div class="portfolio-coming">Vídeo em breve</div>
        </div>
      </div>
      <div class="portfolio-slot reveal reveal-delay-3">
        <div class="portfolio-slot-inner">
          <div class="portfolio-play">
            <svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          </div>
          <div class="portfolio-cat">Cobertura de Evento</div>
          <div class="portfolio-coming">Vídeo em breve</div>
        </div>
      </div>
      <div class="portfolio-slot reveal reveal-delay-4">
        <div class="portfolio-slot-inner">
          <div class="portfolio-play">
            <svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          </div>
          <div class="portfolio-cat">Produção Audiovisual</div>
          <div class="portfolio-coming">Vídeo em breve</div>
        </div>
      </div>
    </div>
    <p class="portfolio-note reveal">Links de vídeos serão adicionados em breve — <span>fique de olho!</span></p>
  </div>
</section>

<!-- ═══════════════════════════════════════
  VIRA BRASIL
═══════════════════════════════════════ -->
<section id="vira-brasil">
  <div class="container">
    <div class="vira-grid">
      <div class="vira-photos reveal-left">
        <!-- Substituir pelas fotos IMG_01 e IMG_02 -->
        <div class="vira-photo-placeholder"></div>
        <div class="vira-photo-placeholder"></div>
      </div>
      <div class="vira-content reveal-right">
        <div class="section-tag">Credencial Oficial</div>
        <h2 class="section-title">Vira Brasil <span>2025</span></h2>
        <div class="badge-credencial">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <span>Videomaker · Credencial Oficial Mídia</span>
        </div>
        <p class="vira-text">
          Credenciado como <strong>Videomaker e Mídia Oficial</strong> no maior réveillon cristão do Brasil — Vira Brasil 2025/2026, realizado na <strong>Neo Química Arena</strong>, em São Paulo.
        </p>
        <p class="vira-text" style="margin-top:1rem">
          Uma prova de que a qualidade do trabalho fala mais alto que qualquer outra coisa.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
  PROCESSO
═══════════════════════════════════════ -->
<section id="processo">
  <div class="processo-wrap">
    <div class="reveal" style="text-align:center;margin-bottom:1rem">
      <div class="section-tag" style="justify-content:center;display:flex">Como trabalho</div>
      <h2 class="section-title" style="text-align:center">Simples. Estratégico.<br><span class="text-cyan">Sem surpresas.</span></h2>
    </div>
    <div class="processo-steps">
      <div class="step reveal reveal-delay-1">
        <div class="step-dot">
          <svg viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
          <div class="step-num">1</div>
        </div>
        <div class="step-title">Briefing</div>
        <div class="step-desc">Entendimento dos seus objetivos e necessidades</div>
      </div>
      <div class="step reveal reveal-delay-2">
        <div class="step-dot">
          <svg viewBox="0 0 24 24"><path d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
          <div class="step-num">2</div>
        </div>
        <div class="step-title">Estratégia</div>
        <div class="step-desc">Plano de conteúdo personalizado para sua marca</div>
      </div>
      <div class="step reveal reveal-delay-3">
        <div class="step-dot">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
          <div class="step-num">3</div>
        </div>
        <div class="step-title">Produção</div>
        <div class="step-desc">Captação e edição cinematográfica profissional</div>
      </div>
      <div class="step reveal reveal-delay-4">
        <div class="step-dot">
          <svg viewBox="0 0 24 24"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
          <div class="step-num">4</div>
        </div>
        <div class="step-title">Publicação</div>
        <div class="step-desc">Gestão de postagens e interações nas redes</div>
      </div>
      <div class="step reveal reveal-delay-4">
        <div class="step-dot">
          <svg viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          <div class="step-num">5</div>
        </div>
        <div class="step-title">Análise</div>
        <div class="step-desc">Monitoramento de resultados e ajustes estratégicos</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
  DEPOIMENTOS
═══════════════════════════════════════ -->
<section id="depoimentos">
  <div class="container">
    <div class="reveal" style="text-align:center">
      <div class="section-tag" style="justify-content:center;display:flex">Prova social</div>
      <h2 class="section-title" style="text-align:center">O que dizem sobre<br><span class="text-gold" style="color:var(--gold)">o trabalho</span></h2>
    </div>
    <div class="depo-grid">
      <?php if (!empty($depoimentos)): ?>
        <?php foreach ($depoimentos as $index => $d): ?>
          <div class="holo-card reveal <?= $index > 0 ? 'reveal-delay-' . min($index, 4) : '' ?>">
            <div class="depo-top">
              <div class="depo-avatar">
                <?php if (!empty($d['foto'])): ?>
                  <img src="/assets/uploads/depoimentos/<?= htmlspecialchars($d['foto']) ?>" alt="<?= htmlspecialchars($d['nome']) ?>">
                <?php else: ?>
                  <?= htmlspecialchars(mb_strtoupper(mb_substr(trim($d['nome']), 0, 1))) ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="depo-name"><?= htmlspecialchars($d['nome']) ?></div>
                <div class="depo-role"><?= htmlspecialchars($d['empresa'] ?? '') ?></div>
              </div>
            </div>
            <div class="depo-stars">★★★★★</div>
            <div class="depo-text">“<?= nl2br(htmlspecialchars($d['comentario'])) ?>”</div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="depo-waiting reveal">
          ⏳ Depoimentos reais sendo coletados
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
  FAQ
═══════════════════════════════════════ -->
<section id="faq">
  <div class="container">
    <div class="reveal" style="text-align:center">
      <div class="section-tag" style="justify-content:center;display:flex">Tire suas dúvidas</div>
      <h2 class="section-title" style="text-align:center">Perguntas <span class="text-cyan">frequentes</span></h2>
    </div>
    <div class="faq-wrap">
      <div class="faq-item reveal">
        <button class="faq-q" onclick="toggleFaq(this)">
          Quanto custa uma produção?
          <div class="faq-icon">+</div>
        </button>
        <div class="faq-a">Cada projeto é único e o investimento varia conforme o escopo, complexidade e tipo de produção. Entre em contato para receber um orçamento personalizado e detalhado para o seu projeto.</div>
      </div>
      <div class="faq-item reveal reveal-delay-1">
        <button class="faq-q" onclick="toggleFaq(this)">
          Quanto tempo leva para entregar?
          <div class="faq-icon">+</div>
        </button>
        <div class="faq-a">O prazo de entrega depende do tipo e complexidade do projeto. Esse detalhe é definido no briefing inicial, onde alinhamos todas as expectativas antes de começar.</div>
      </div>
      <div class="faq-item reveal reveal-delay-2">
        <button class="faq-q" onclick="toggleFaq(this)">
          Você atende fora de Santarém?
          <div class="faq-icon">+</div>
        </button>
        <div class="faq-a">Sim! Já atuei em São Paulo e em outros estados para eventos e campanhas. A distância não é um obstáculo para projetos que merecem produção de qualidade.</div>
      </div>
      <div class="faq-item reveal reveal-delay-3">
        <button class="faq-q" onclick="toggleFaq(this)">
          Não tenho experiência diante das câmeras.
          <div class="faq-icon">+</div>
        </button>
        <div class="faq-a">Sem problema! Dou direção durante toda a gravação — roteiro, postura e forma de falar. A ideia é deixar você confortável para transmitir a mensagem de forma natural e autêntica.</div>
      </div>
      <div class="faq-item reveal reveal-delay-4">
        <button class="faq-q" onclick="toggleFaq(this)">
          Você usa inteligência artificial nas produções?
          <div class="faq-icon">+</div>
        </button>
        <div class="faq-a">Sim! Uso IA na edição e em projetos imobiliários com drone + reconstrução 3D — uma tecnologia que permite mostrar um empreendimento antes mesmo de ser construído.</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
  CTA FINAL
═══════════════════════════════════════ -->
<section id="contato">
  <div class="contato-bg"></div>
  <div class="contato-content reveal">
    <div class="contato-pre">Pronto para o próximo nível?</div>
    <h2 class="contato-title">
      Transforme sua marca com<br>
      <span>vídeos que geram resultado</span>
    </h2>
    <p class="contato-sub">
      História, estratégia e emoção em cada produção.<br>
      Do briefing à entrega — sem surpresas.
    </p>
    <div class="contato-escassez">Agenda limitada · Poucos projetos disponíveis por mês</div>
    <div class="contato-btns">
      <a href="https://wa.me/5593919295862" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
        Falar no WhatsApp
      </a>
      <a href="mailto:danielqueiroz890@icloud.com" class="btn-secondary">Enviar E-mail</a>
    </div>
    <div class="contato-links">
      <a href="mailto:danielqueiroz890@icloud.com" class="contato-link">
        <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        danielqueiroz890@icloud.com
      </a>
      <a href="https://instagram.com/danielqueirozd.q" target="_blank" class="contato-link">
        <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        @danielqueirozd.q
      </a>
      <a href="https://wa.me/5593919295862" target="_blank" class="contato-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.68 12 19.79 19.79 0 01.61 3.41 2 2 0 012.6 1.24h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.83a16 16 0 006.29 6.29l.95-.96a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
        (93) 9192-9586
      </a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-logo">DQ</div>
  <div class="footer-copy">© 2026 Daniel Queiroz — Todos os direitos reservados</div>
</footer>

<script>
/* ═══════════════════════════════════════════
   CURSOR
═══════════════════════════════════════════ */
const cursor = document.getElementById('cursor');
const ring = document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cursor.style.left=mx+'px';cursor.style.top=my+'px'});
function animRing(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(animRing)}
animRing();

/* ═══════════════════════════════════════════
   LOADER
═══════════════════════════════════════════ */
window.addEventListener('load',()=>{
  setTimeout(()=>{
    document.getElementById('loader').classList.add('hide');
    setTimeout(()=>{
      document.getElementById('ltTop').classList.add('open');
      document.getElementById('ltBot').classList.add('open');
    },400);
  },2400);
});

/* ═══════════════════════════════════════════
   NAV
═══════════════════════════════════════════ */
const nav = document.getElementById('mainNav');
window.addEventListener('scroll',()=>{
  nav.classList.toggle('scrolled',window.scrollY>60);
});
function toggleNav(){
  document.getElementById('navLinks').classList.toggle('open');
}
function closeNav(){
  document.getElementById('navLinks').classList.remove('open');
}

/* ═══════════════════════════════════════════
   SCROLL REVEAL
═══════════════════════════════════════════ */
const revealEls = document.querySelectorAll('.reveal,.reveal-left,.reveal-right');
const revealObs = new IntersectionObserver((entries)=>{
  entries.forEach(e=>{
    if(e.isIntersecting) e.target.classList.add('visible');
  });
},{threshold:.12});
revealEls.forEach(el=>revealObs.observe(el));

/* ═══════════════════════════════════════════
   BACKGROUND RESPONSIVO POR SEÇÃO
═══════════════════════════════════════════ */
const sections = document.querySelectorAll('section[id]');
const bgObs = new IntersectionObserver((entries)=>{
  entries.forEach(e=>{
    if(e.isIntersecting && e.intersectionRatio>.3){
      document.body.setAttribute('data-section',e.target.id);
    }
  });
},{threshold:.3});
sections.forEach(s=>bgObs.observe(s));

/* ═══════════════════════════════════════════
   FAQ ACCORDION
═══════════════════════════════════════════ */
function toggleFaq(btn){
  const item = btn.parentElement;
  const isOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item').forEach(i=>i.classList.remove('open'));
  if(!isOpen) item.classList.add('open');
}
</script>
</body>
</html>
