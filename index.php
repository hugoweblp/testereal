<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// ðŸ” SECURITY HEADERS - ProteÃ§Ã£o contra mÃºltiplos tipos de ataque
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
header('X-Frame-Options: DENY');  // Previne clickjacking
header('X-Content-Type-Options: nosniff');  // ForÃ§a respeitar tipo MIME
header('X-XSS-Protection: 1; mode=block');  // ProteÃ§Ã£o XSS em navegadores antigos
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');  // ForÃ§a HTTPS
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';");
header('Referrer-Policy: strict-origin-when-cross-origin');  // Controla dados de referrer

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// ðŸ” ERROR HANDLING SEGURO - Logging sem expor informaÃ§Ãµes
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
if (getenv('APP_ENV') !== 'development') {
    error_reporting(0);  // Em produÃ§Ã£o, nÃ£o exibir erros
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Configurar logging seguro em arquivo (fora de public_html)
ini_set('log_errors', 1);
if (!file_exists(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0755, true);
}
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

require_once __DIR__ . '/admin/config.php';

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// ðŸ” FUNÃ‡Ã•ES DE SEGURANÃ‡A MELHORADAS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

/**
 * Escapa HTML - ProteÃ§Ã£o contra XSS
 * @param string|null $value Valor a ser escapado
 * @return string Valor escapado ou string vazia
 */
function e(?string $value): string {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * ObtÃ©m primeira letra do nome com validaÃ§Ã£o robusta
 * @param string $name Nome a processar
 * @return string Primeira letra maiÃºscula ou '?'
 */
function initial(string $name): string {
    // ValidaÃ§Ã£o de tamanho para evitar abuse
    if (strlen($name) > 255 || strlen($name) === 0) {
        return '?';
    }

    $name = trim($name);
    if ($name === '') {
        return '?';
    }

    // Usar funÃ§Ãµes multi-byte para UTF-8 correto, com fallback seguro
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
    }

    return strtoupper(substr($name, 0, 1));
}

/**
 * ObtÃ©m depoimentos ativos com Prepared Statements (ProteÃ§Ã£o SQL Injection)
 * @return array Array de depoimentos ou vazio se erro
 */
function getDepoimentos(): array {
    try {
        $db = getDB();
        
        // âœ… PREPARED STATEMENT - ProteÃ§Ã£o contra SQL Injection
        // ðŸ” CORRIGIDO: Usando os campos corretos da tabela depoimentos
        $stmt = $db->prepare(
            "SELECT id, nome, empresa, comentario, foto, ativo
             FROM depoimentos 
             WHERE ativo = 1
             ORDER BY id DESC 
             LIMIT 100"
        );
        
        // Executar com tratamento
        if (!$stmt->execute()) {
            throw new Exception('Erro ao executar query: ' . json_encode($stmt->errorInfo()));
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
    } catch (PDOException $e) {
        // Log seguro - nÃ£o expÃµe ao usuÃ¡rio
        error_log('[DATABASE_ERROR] ' . date('Y-m-d H:i:s') . ' - Depoimentos: ' . $e->getMessage());
        return [];
        
    } catch (Throwable $e) {
        // Catch genÃ©rico
        error_log('[UNEXPECTED_ERROR] ' . date('Y-m-d H:i:s') . ' - Depoimentos: ' . $e->getMessage());
        return [];
    }
}

// Obter depoimentos com seguranÃ§a
$depoimentos = getDepoimentos();

/**
 * ObtÃ©m vÃ­deos do portfÃ³lio agrupados por slot
 * @return array Array de vÃ­deos agrupados por slot
 */
function getVideos(): array {
    try {
        $db = getDB();
        
        // âœ… PREPARED STATEMENT - ProteÃ§Ã£o contra SQL Injection
        // ðŸ” Busca vÃ­deos do banco de dados agrupados por slot
        $stmt = $db->prepare(
            "SELECT id, slot, titulo, youtube_url, ordem, ativo
             FROM portfolio_videos 
             WHERE ativo = 1
             ORDER BY slot ASC, ordem ASC"
        );
        
        // Executar com tratamento
        if (!$stmt->execute()) {
            throw new Exception('Erro ao executar query: ' . json_encode($stmt->errorInfo()));
        }
        
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Agrupar por slot
        $videosPorSlot = [];
        foreach ($videos as $video) {
            $slot = $video['slot'];
            if (!isset($videosPorSlot[$slot])) {
                $videosPorSlot[$slot] = [];
            }
            $videosPorSlot[$slot][] = $video;
        }
        
        return $videosPorSlot;
        
    } catch (PDOException $e) {
        // Log seguro
        error_log('[DATABASE_ERROR] ' . date('Y-m-d H:i:s') . ' - VÃ­deos: ' . $e->getMessage());
        return [];
        
    } catch (Throwable $e) {
        // Catch genÃ©rico
        error_log('[UNEXPECTED_ERROR] ' . date('Y-m-d H:i:s') . ' - VÃ­deos: ' . $e->getMessage());
        return [];
    }
}

/**
 * ðŸŽ¥ Detecta orientaÃ§Ã£o do vÃ­deo (vertical/horizontal)
 * Identifica se Ã© Shorts (9:16) ou vÃ­deo normal (16:9)
 */
function detectarOrientacaoVideo(string $youtubeUrl): string {
    // Extrair ID do YouTube
    preg_match('/(?:v=|youtu\.be\/|embed\/)([^&\?]+)/', $youtubeUrl, $matches);
    $youtubeId = $matches[1] ?? '';
    
    if (empty($youtubeId)) {
        return 'horizontal'; // default
    }
    
    // Se URL contÃ©m "shorts", Ã© vertical
    if (strpos($youtubeUrl, 'shorts') !== false) {
        return 'vertical';
    }
    
    // Tentar detectar via oEmbed do YouTube
    $oembedUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$youtubeId}&format=json";
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'user_agent' => 'Mozilla/5.0'
            ]
        ]);
        
        $response = @file_get_contents($oembedUrl, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            
            if (isset($data['width']) && isset($data['height'])) {
                $width = (int)$data['width'];
                $height = (int)$data['height'];
                
                // Se altura > largura, Ã© vertical (shorts)
                return ($height > $width) ? 'vertical' : 'horizontal';
            }
        }
    } catch (Exception $e) {
        error_log('Erro ao detectar orientaÃ§Ã£o: ' . $e->getMessage());
    }
    
    return 'horizontal'; // default
}

/**
 * ðŸŽ¥ Enriquece vÃ­deos com informaÃ§Ãµes de orientaÃ§Ã£o
 */
function getVideosComOrientacao(): array {
    $videosPorSlot = getVideos();
    
    // Adicionar informaÃ§Ã£o de orientaÃ§Ã£o a cada vÃ­deo
    foreach ($videosPorSlot as &$videos) {
        foreach ($videos as &$video) {
            $video['orientacao'] = detectarOrientacaoVideo($video['youtube_url']);
            
            // Extrair ID do YouTube
            preg_match('/(?:v=|youtu\.be\/|embed\/)([^&\?]+)/', $video['youtube_url'], $m);
            $video['youtube_id'] = $m[1] ?? '';
        }
    }
    
    return $videosPorSlot;
}

// ðŸŽ¥ Buscar vÃ­deos com orientaÃ§Ã£o detectada
$videosPorSlot = getVideosComOrientacao();

// ðŸŽ¥ Definir slots com labels e Ã­cones (EstÃ©tica Premium sem Emojis)
$slots = [
    'politica' => ['label' => 'Publicidade PolÃ­tica', 'letter' => 'P'],
    'imoveis' => ['label' => 'Marketing ImobiliÃ¡rio', 'letter' => 'I'],
    'eventos' => ['label' => 'Cobertura de Eventos', 'letter' => 'E'],
    'audiovisual' => ['label' => 'ProduÃ§Ã£o Audiovisual', 'letter' => 'A'],
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Daniel Queiroz â€” Videomaker e PublicitÃ¡rio em SantarÃ©m, PA. ProduÃ§Ãµes cinematogrÃ¡ficas para marcas, campanhas eleitorais e marketing imobiliÃ¡rio.">
<meta property="og:title" content="Daniel Queiroz â€” Videomaker">
<meta property="og:description" content="VÃ­deos cinematogrÃ¡ficos que transformam sua marca em autoridade.">
<meta property="og:type" content="website">
<meta name="theme-color" content="#02040a">
<!-- ðŸ” Security Meta Tags -->
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="referrer" content="strict-origin-when-cross-origin">
<title>Daniel Queiroz â€” Videomaker</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Bebas+Neue&family=Barlow:ital,wght@0,300;0,400;0,600;1,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

<style>
/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   RESET + VARIÃVEIS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --cyan:#00f5ff;
  --magenta:#ff006e;
  --gold:#ffd700;
  --dark:#02040a;
  --dark-secondary:#0a0f1f;
  --text:#f7fbff;
  --text-secondary:#c9d1df;
  --muted:#7a8695;
  --glass:rgba(255,255,255,0.06);
  --glass-border:rgba(255,255,255,0.12);
  
  /* Glass Hierarchy */
  --glass-1: rgba(255,255,255,0.08);
  --glass-2: rgba(255,255,255,0.05);
  --glass-border-1: rgba(0,245,255,0.15);
  --glass-border-2: rgba(255,255,255,0.08);
  
  /* Glow Effects */
  --glow-subtle: 0 0 20px rgba(0,245,255,0.1);
  --glow-medium: 0 0 30px rgba(0,245,255,0.2);
}
html{scroll-behavior:smooth;overflow-x:hidden}
body{
  background:var(--dark);
  color:var(--text);
  font-family:'Barlow',sans-serif;
  overflow-x:hidden;
  position:relative;
}

body.menu-open{overflow:hidden}

:focus-visible{
  outline:2px solid var(--cyan);
  outline-offset:3px;
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   CURSOR CUSTOMIZADO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   GRAIN OVERLAY
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.grain{
  position:fixed;inset:0;pointer-events:none;z-index:9990;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  opacity:.18;mix-blend-mode:overlay;
  animation:grain .4s steps(2) infinite;
  will-change: transform;
}
@media (prefers-reduced-motion: reduce) {
  .grain { animation: none; }
}
@keyframes grain{0%,100%{transform:translate(0,0)}25%{transform:translate(-1px,1px)}50%{transform:translate(1px,-1px)}75%{transform:translate(-1px,1px)}}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   LOADER
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
#loader{
  position:fixed;inset:0;z-index:9000;
  background:var(--dark);
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  transition:opacity .8s ease, visibility .8s ease;
}
#loader.hide{opacity:0;visibility:hidden}
.loader-logo{
  font-family:'Bebas Neue',sans-serif;
  font-size:clamp(5rem,15vw,10rem);
  background:linear-gradient(135deg,var(--cyan),var(--magenta));
  -webkit-background-clip:text;background-clip:text;
  color:transparent;
  animation:logoPulse 1.5s ease-in-out infinite alternate;
  letter-spacing:.05em;
}
@keyframes logoPulse{
  from{filter:drop-shadow(0 0 20px rgba(0,245,255,.4))}
  to{filter:drop-shadow(0 0 40px rgba(255,0,110,.4))}
}
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   LETTERBOX
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.letterbox-top,.letterbox-bottom{
  position:fixed;left:0;right:0;
  height:60px;background:var(--dark);
  z-index:8000;transition:transform 1s ease;
}
.letterbox-top{top:0;transform:translateY(0)}
.letterbox-bottom{bottom:0;transform:translateY(0)}
.letterbox-top.open{transform:translateY(-100%)}
.letterbox-bottom.open{transform:translateY(100%)}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   NAV
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   HERO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
#hero{
  position:relative;
  min-height:100vh;
  display:flex;
  align-items:center;
  overflow:hidden;
  isolation:isolate;
  padding:6.5rem 0 2rem;
}
.hero-bg{
  position:absolute;
  inset:0;
  background:
    radial-gradient(circle at 78% 32%, rgba(0,234,255,.16) 0%, rgba(0,234,255,.06) 22%, transparent 52%),
    radial-gradient(circle at 18% 18%, rgba(255,255,255,.04) 0%, transparent 24%),
    linear-gradient(135deg, rgba(2,4,10,.58) 0%, rgba(2,4,10,.32) 45%, rgba(2,4,10,.72) 100%),
    url('/assets/img/danielherocanva.webp') center center / cover no-repeat,
    linear-gradient(115deg, #02040a 0%, #040914 42%, #02040a 100%);
  transform:scale(1.03);
}
.hero-bg::before{
  content:'';
  position:absolute;
  inset:0;
  background:
    linear-gradient(180deg, rgba(2,4,10,.44) 0%, rgba(2,4,10,.72) 100%),
    linear-gradient(90deg, rgba(2,4,10,.88) 0%, rgba(2,4,10,.66) 32%, rgba(2,4,10,.26) 66%, rgba(2,4,10,.18) 100%);
}
.hero-bg::after{
  content:'';
  position:absolute;
  inset:auto auto 6% 8%;
  width:min(42vw, 520px);
  height:min(42vw, 520px);
  border-radius:50%;
  background:radial-gradient(circle, rgba(0,234,255,.16) 0%, rgba(0,234,255,.06) 34%, transparent 68%);
  filter:blur(48px);
  opacity:.95;
  animation:heroLight 12s ease-in-out infinite alternate;
}
.hero-noise{
  position:absolute;
  inset:0;
  pointer-events:none;
  opacity:.16;
  background:linear-gradient(180deg, rgba(255,255,255,.02) 0%, transparent 16%, transparent 84%, rgba(255,255,255,.02) 100%);
}
.hero-vignette{
  position:absolute;
  inset:0;
  pointer-events:none;
  background:radial-gradient(circle at center, transparent 44%, rgba(2,4,10,.12) 70%, rgba(2,4,10,.42) 100%);
}
.hero-content{
  position:relative;
  z-index:2;
  width:min(100%, 1400px);
  margin:0 auto;
  padding:0 1rem 2rem;
  display:flex;
  justify-content:flex-start;
}
.hero-panel{
  width:min(100%, 720px);
  padding:1.2rem 0 0;
}
.hero-kicker{
  display:inline-flex;
  align-items:center;
  gap:.6rem;
  margin-bottom:1.2rem;
  font-family:'Inter',sans-serif;
  font-size:.78rem;
  font-weight:600;
  letter-spacing:.2em;
  text-transform:uppercase;
  color:var(--text-secondary);
}
.hero-kicker::before{
  content:'';
  width:42px;
  height:1px;
  background:linear-gradient(90deg, var(--cyan), transparent);
}
.hero-title{
  margin:0;
  max-width:12ch;
  font-family:'Inter',sans-serif;
  font-size:clamp(2.9rem, 8.5vw, 6.8rem);
  font-weight:800;
  line-height:.9;
  letter-spacing:-.04em;
  color:var(--text);
  text-wrap:balance;
}
.hero-title .text-cyan{
  color:var(--cyan);
  text-shadow:0 0 30px rgba(0,245,255,.2);
}
.hero-dynamic-wrap{
  display:inline-flex;
  align-self:flex-start;
  margin-top:1.5rem;
  width:auto;
  max-width:min(100%, 580px);
  padding:1rem 0;
}
.hero-dynamic{
  margin:0;
  max-width:36ch;
  min-height:3.6em;
  font-family:'Inter',sans-serif;
  font-size:clamp(1.15rem, 2.8vw, 1.4rem);
  font-weight:600;
  line-height:1.6;
  color:var(--text);
}
.hero-dynamic .accent{
  color:var(--cyan);
  font-weight:700;
}
.hero-cursor{
  display:inline-block;
  width:.6ch;
  color:var(--cyan);
  animation:blinkCursor .9s steps(1) infinite;
}
.hero-actions{
  display:flex;
  gap:.9rem;
  margin-top:1.4rem;
  flex-wrap:wrap;
}
.btn-primary,
.btn-secondary{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:.55rem;
  min-height:54px;
  padding:0 1.35rem;
  border-radius:16px;
  font-family:'Inter',sans-serif;
  font-size:.93rem;
  font-weight:700;
  letter-spacing:.01em;
  text-transform:none;
  text-decoration:none;
  transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-primary{
  color:#041018;
  border:1px solid rgba(0,245,255,.26);
  background:linear-gradient(135deg, var(--cyan) 0%, #4ef5ff 100%);
  box-shadow:0 12px 38px rgba(0,245,255,.2);
}
.btn-primary:hover{
  transform:translateY(-3px) scale(1.02);
  box-shadow:0 18px 48px rgba(0,245,255,.35);
  background:linear-gradient(135deg, #4ef5ff 0%, var(--cyan) 100%);
}
.btn-primary:active{
  transform:translateY(-1px) scale(0.98);
}
.btn-secondary{
  color:var(--cyan);
  border:1px solid rgba(0,245,255,.3);
  background:rgba(255,255,255,.02);
  backdrop-filter: blur(10px);
}
.btn-secondary:hover{
  transform:translateY(-3px);
  background:rgba(0,245,255,.1);
  box-shadow:0 10px 30px rgba(0,245,255,.15);
  border-color:var(--cyan);
}
.btn-secondary:active{
  transform:translateY(-1px) scale(0.98);
}
.scroll-indicator{
  left:auto;
  right:1.35rem;
  bottom:1.35rem;
  transform:none;
  align-items:flex-end;
}
.scroll-indicator span{
  font-family:'Inter',sans-serif;
  font-size:.68rem;
  letter-spacing:.14em;
}
@keyframes blinkCursor{
  0%,49%{opacity:1}
  50%,100%{opacity:0}
}
@keyframes heroLight{
  0%{transform:translate3d(0,0,0) scale(1)}
  50%{transform:translate3d(18px,-10px,0) scale(1.08)}
  100%{transform:translate3d(-10px,16px,0) scale(1.03)}
}
@keyframes heroPulse{
  0%,100%{opacity:.45;transform:translate3d(0,0,0) scale(1)}
  50%{opacity:.72;transform:translate3d(-12px,8px,0) scale(1.06)}
}
@keyframes quemLightDrift{
  0%{transform:translate3d(-1.5%,0,0) scale(1);opacity:.78}
  50%{transform:translate3d(1.5%,-1%,0) scale(1.04);opacity:.96}
  100%{transform:translate3d(3%,1%,0) scale(1.08);opacity:.84}
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SEÃ‡ÃƒO PROBLEMA
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
#problema{
  padding:7rem 2rem;
  background:
    radial-gradient(circle at 12% 18%, rgba(0,245,255,.08) 0%, transparent 28%),
    radial-gradient(circle at 84% 26%, rgba(255,0,110,.07) 0%, transparent 30%),
    linear-gradient(180deg, #02040a 0%, #040813 100%);
  position:relative;
  overflow:hidden;
}
#problema::before{
  content:'';
  position:absolute;
  inset:0;
  background:
    linear-gradient(180deg, rgba(255,255,255,.02), transparent 18%, transparent 82%, rgba(255,255,255,.02)),
    radial-gradient(ellipse at center, rgba(255,255,255,.018) 0%, transparent 68%);
  pointer-events:none;
}
.section-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:clamp(2.3rem, 6vw, 5rem);
  letter-spacing:.025em;
  line-height:.92;
  margin-bottom:3.2rem;
}
.problema-head{
  max-width:900px;
  margin:0 0 3.2rem;
}
.problema-title{
  max-width:780px;
  text-wrap:balance;
}
.problema-title .title-accent{
  background:linear-gradient(90deg, var(--cyan) 0%, #a2f8ff 45%, #ffffff 100%);
  -webkit-background-clip:text;
  background-clip:text;
  color:transparent;
  text-shadow:0 0 30px rgba(0,245,255,.2);
}
.problema-grid{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:1.35rem;
  max-width:1100px;
  margin:0 auto;
}
.glass-card{
  min-height:205px;
  border-radius:24px;
  background:linear-gradient(180deg, rgba(255,255,255,.055), rgba(255,255,255,.026));
  border:1px solid rgba(255,255,255,.09);
  backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  padding:1.65rem 1.5rem 1.45rem;
  position:relative;
  overflow:hidden;
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.04),
    0 18px 50px rgba(0,0,0,.28);
  transition:
    transform .32s ease,
    border-color .32s ease,
    box-shadow .32s ease,
    background .32s ease;
}
.glass-card::before{
  content:'';
  position:absolute;
  inset:0;
  background:
    linear-gradient(135deg, rgba(0,245,255,.09), transparent 28%, transparent 68%, rgba(255,0,110,.08)),
    linear-gradient(180deg, rgba(255,255,255,.03), transparent 48%);
  opacity:.78;
  pointer-events:none;
}
.glass-card::after{
  content:'';
  position:absolute;
  inset:1px;
  border-radius:23px;
  border:1px solid rgba(255,255,255,.03);
  pointer-events:none;
}
.glass-card:hover{
  transform:translateY(-8px);
  border-color:rgba(0,245,255,.18);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.06),
    0 22px 60px rgba(0,0,0,.34),
    0 0 0 1px rgba(0,245,255,.04);
}
.card-icon{
  width:52px;
  height:52px;
  border-radius:16px;
  border:1px solid rgba(0,245,255,.24);
  background:linear-gradient(180deg, rgba(0,245,255,.08), rgba(0,245,255,.02));
  display:flex;
  align-items:center;
  justify-content:center;
  margin-bottom:1.25rem;
}
.card-icon svg{
  width:22px;
  height:22px;
  stroke:var(--cyan);
  fill:none;
  stroke-width:1.8;
}
.card-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:1.38rem;
  letter-spacing:.04em;
  margin-bottom:.85rem;
  line-height:1;
}
.card-desc{
  max-width:31ch;
  font-size:.96rem;
  font-weight:300;
  color:rgba(232,234,240,.72);
  line-height:1.68;
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SEÃ‡ÃƒO QUEM SOU



â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
#quem-sou{
  position:relative;
  overflow:visible;
  min-height:100vh  ;
  padding:6rem 1.25rem;
  display:flex;
  align-items:center;
  background:
    radial-gradient(circle at 20% 50%, rgba(0,234,255,.14) 0%, rgba(0,234,255,.06) 24%, transparent 48%),
    radial-gradient(circle at 76% 46%, rgba(0,234,255,.06) 0%, transparent 30%),
    linear-gradient(90deg, rgba(2,4,10,.98) 0%, rgba(2,4,10,.78) 28%, rgba(2,4,10,.48) 52%, rgba(2,4,10,.88) 100%),
    linear-gradient(115deg, #02040a 0%, #040914 42%, #02040a 100%);
}
#quem-sou::before{
  content:'';
  position:absolute;
  inset:0;
  background:
    radial-gradient(circle at 24% 52%, rgba(0,234,255,.12) 0%, transparent 34%),
    linear-gradient(180deg, rgba(255,255,255,.015) 0%, transparent 24%, transparent 76%, rgba(255,255,255,.012) 100%);
  pointer-events:none;
}
#quem-sou::after{display:none;}
.quem-wrap{
  position:relative;
  z-index:1;
  width:min(100%, 1280px);
  margin:0 auto;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:clamp(1.25rem, 4vw, 4.5rem);
  align-items:stretch;
  min-height:inherit;

}
.quem-visual{
  position:relative;
  min-height:100%;
  display:flex;
  align-items:flex-end;
  justify-content:flex-start;

}
.quem-visual::before{
  content:'';
  position:absolute;
  width:min(54vw, 560px);
  height:min(54vw, 560px);
  left:2%;
  top:10%;
  border-radius:50%;
  background:radial-gradient(circle, rgba(0,234,255,.22) 0%, rgba(0,234,255,.09) 34%, transparent 72%);
  filter:blur(46px);
  opacity:.92;
  pointer-events:none;
  animation:quemOrb 10s ease-in-out infinite alternate;
}
.quem-visual::after{
  content:'';
  position:absolute;
  inset:auto auto 8% 0;
  width:96%;
  height:24%;
  background:radial-gradient(ellipse at center, rgba(0,0,0,.38) 0%, transparent 72%);
  filter:blur(24px);
  pointer-events:none;
}
.quem-picture{
  position:relative;
  z-index:1;
  width:900px;
  max-width:none;
  margin-left:-12rem;
  margin-bottom:-2.8rem;
  filter:
    drop-shadow(0 24px 80px rgba(0,0,0,.48))
    drop-shadow(0 0 32px rgba(0,234,255,.12));
}
.quem-picture img{
  display:block;
  width:100%;
  height:auto;
  object-fit:contain;
  transform:translateX(-140px);
  transform-origin:left bottom;

}
/* Fade base da foto no mobile */
.quem-picture::after{
  content:'';
  position:absolute;
  left:0;
  right:0;
  bottom:0;
  height:45%;
  pointer-events:none;
  z-index:2;
  display:none;
  background:linear-gradient(
    to bottom,
    rgba(2,4,10,0) 0%,
    rgba(2,4,10,.35) 35%,
    rgba(2,4,10,.72) 62%,
    rgba(2,4,10,.94) 82%,
    rgba(2,4,10,1) 100%
  );
}
.quem-content{
  width:min(100%, 620px);
  display:flex;
  flex-direction:column;
  align-items:flex-start;
  text-align:left;
  gap:1rem;
  margin-top:140px;
}
.quem-kicker{
  font-family:'Space Mono',monospace;
  font-size:.58rem;
  letter-spacing:.24em;
  text-transform:uppercase;
  color:rgba(232,234,240,.82);
}
.quem-title{
  display:flex;
  flex-direction:column;
  gap:.15rem;
  margin:0;
  line-height:.92;
}
.quem-title-line{
  font-family:'Bebas Neue',sans-serif;
  letter-spacing:.03em;
  color:#f2f5fb;
}
.quem-title-line--small{
  font-size:clamp(2rem, 4vw, 3rem);
}
.quem-title-line--mid{
  font-size:clamp(3rem, 5.2vw, 4.8rem);
}
.quem-title-line--highlight{
  font-size:clamp(4rem, 6.8vw, 7rem);
  background:linear-gradient(90deg, #defcff 0%, #80f9ff 28%, #00f5ff 70%, #9ef7ff 100%);
  -webkit-background-clip:text;
  background-clip:text;
  color:transparent;
  text-shadow:0 0 22px rgba(0,245,255,.16);
}
.quem-subtext{
  max-width:38ch;
  font-size:1rem;
  line-height:1.7;
  color:rgba(232,234,240,.78);
}
.quem-provas{
  width:100%;
  display:grid;
  grid-template-columns:repeat(2, minmax(0, 1fr));
  gap:.95rem;
  margin-top:.5rem;
}
.quem-prova{
  padding:1rem 1.05rem;
}
.quem-prova-valor{
  display:block;
  font-family:'Bebas Neue',sans-serif;
  font-size:1.9rem;
  line-height:.95;
  letter-spacing:.04em;
  color:var(--cyan);
}
.quem-prova-label{
  display:block;
  margin-top:.2rem;
  font-family:'Space Mono',monospace;
  font-size:.55rem;
  letter-spacing:.16em;
  text-transform:uppercase;
  color:rgba(232,234,240,.68);
}

.quem-provas-mobile{
  display:none;
}
.mobile-curve-card{
  position:absolute;
  width:165px;
  padding:.75rem .8rem;
  border-radius:16px;
  background:linear-gradient(180deg, rgba(8,12,22,.94), rgba(8,12,22,.78));
  border:1px solid rgba(255,255,255,.09);
  box-shadow:
    0 16px 38px rgba(0,0,0,.36),
    inset 0 1px 0 rgba(255,255,255,.04);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  opacity:0;
  transform:translateX(-46px);
  transition:
    opacity .55s cubic-bezier(.22,1,.36,1),
    transform .55s cubic-bezier(.22,1,.36,1);
  z-index:3;
}
.mobile-curve-card::before{
  content:'';
  position:absolute;
  inset:-1px;
  border-radius:inherit;
  background:linear-gradient(120deg, transparent 10%, rgba(0,234,255,.14) 46%, transparent 86%);
  filter:blur(14px);
  opacity:.28;
  z-index:-1;
  pointer-events:none;
}
.mobile-curve-value{
  display:block;
  font-family:'Bebas Neue',sans-serif;
  font-size:1.9rem;
  line-height:.92;
  letter-spacing:.03em;
  color:var(--cyan);
  text-align:center;
}
.mobile-curve-label{
  display:block;
  margin-top:.28rem;
  font-family:'Space Mono',monospace;
  font-size:.44rem;
  line-height:1.35;
  letter-spacing:.12em;
  text-transform:uppercase;
  color:rgba(232,234,240,.48);
  text-align:center;
}
@media(min-width:981px){
.quem-visual.visible .mobile-curve-card{
  opacity:1;
  transform:translateX(0);
}
.quem-visual.visible .mobile-curve-card--1{transition-delay:.10s}
.quem-visual.visible .mobile-curve-card--2{transition-delay:.22s}
.quem-visual.visible .mobile-curve-card--3{transition-delay:.34s}
.quem-visual.visible .mobile-curve-card--4{transition-delay:.46s}
}
@keyframes quemOrb{
  0%{transform:translate3d(0,0,0) scale(1);opacity:.82}
  50%{transform:translate3d(2%, -2%, 0) scale(1.06);opacity:1}
  100%{transform:translate3d(-1%, 2%, 0) scale(1.02);opacity:.88}
}
@media(max-width:980px){
  #quem-sou{
    min-height:auto;
    padding:0rem 1rem 0rem;
    background:
      radial-gradient(circle at 24% 56%, rgba(0,234,255,.15) 0%, rgba(0,234,255,.05) 22%, transparent 48%),
      linear-gradient(180deg, rgba(2,4,10,.98) 0%, rgba(2,4,10,.86) 52%, rgba(2,4,10,.98) 100%);
  }
  .quem-wrap{
    grid-template-columns:1fr;
    gap:0;
  }
  .quem-content{
    width:100%;
    order:1;
    align-items:center;
    text-align:center;
    gap:.35rem;
    margin:0;
    margin-top:0px;
    position:relative;
    z-index:3;
  }
  .quem-kicker{
    width:100%;
    text-align:center;
    margin-bottom:.15rem;
  }
  .quem-title{
    gap:0;
    line-height:.9;
  }
  .quem-subtext{
    max-width:27ch;
    text-align:center;
    font-size:.92rem;
    line-height:1.42;
    margin-top:.1rem;
  }
  .quem-visual{
    min-height:400px;
    justify-content:flex-start;
    align-items:flex-end;
    order:2;
    margin-top:-1.4rem;
  }
  .quem-picture::after{
    display:block;
  }
  .quem-visual::before{
    left:3%;
    top:14%;
    transform:none;
    width:min(60vw, 280px);
    height:min(60vw, 280px);
  }
  .quem-visual::after{
    left:0;
    transform:none;
    width:78%;
    bottom:2%;
  }
  .quem-picture{
    width:420px;
    max-width:none;
    margin-left:-0.7rem;
    margin-bottom:-1.2rem;
  }
  .quem-provas{
    display:none;
  }
  .quem-provas-mobile{
    display:block;
    position:absolute;
    inset:0;
    pointer-events:none;
    z-index:4;
  }
  .mobile-curve-card{
    width:138px;
    padding:.66rem .68rem;
  }
  .mobile-curve-card--1{
  top:50%;
  right:2%;
}

.mobile-curve-card--2{
  top:60%;
  right:2%;
}

.mobile-curve-card--3{
  top:70%;
  right:2%;
}

.mobile-curve-card--4{
  top:80%;
  right:2%;

  }
}
@media(max-width:450px){
  #quem-sou{
    padding:4rem 1rem 2.2rem;
  }
  .quem-visual{
    min-height:430px;
    margin-top:-1.25rem;
  }
  .quem-picture{
    width:520px;
    max-width:none;
    margin-left:-8rem;
    margin-bottom:-1.2rem;
  }
  .quem-visual::before{
    left:4%;
    top:14%;
    width:min(56vw, 215px);
    height:min(56vw, 215px);
  }
  .quem-title-line--small{
    font-size:1.42rem;
  }
  .quem-title-line--mid{
    font-size:2.2rem;
  }
  .quem-title-line--highlight{
    font-size:2.78rem;
  }
  .quem-subtext{
    font-size:.84rem;
    line-height:1.36;
    max-width:26ch;
  }
  .mobile-curve-card{
    min-width:150px;
    max-width:180px;
    padding:.56rem .58rem;
    border-radius:16px;
  }
  .mobile-curve-value{
    font-size:1.34rem;
  }
  .mobile-curve-label{
    font-size:.36rem;
    letter-spacing:.09em;
  }
  .mobile-curve-card--1{
    top:50%;
    right:2%;

    min-width:150px;
    max-width:165px;
    padding:.75rem .8rem;

  }
  .mobile-curve-card--2{
    top:60%;
    right:2%;
    
    min-width:150px;
    max-width:165px;
    padding:.75rem .8rem;
  }
  .mobile-curve-card--3{
    top:70%;
    right:2%;
    min-width:150px;
    max-width:165px;
    padding:.70rem .8rem;
  }
  .mobile-curve-card--4{
    top:80%;
    right:2%;
    
    min-width:150px;
    max-width:165px;
    padding:.70rem .8rem;
  }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SEÃ‡ÃƒO SERVIÃ‡OS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
#servicos{
  padding:7rem 2rem;
  background:var(--dark);
  position:relative;overflow:hidden;
}
#servicos::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 80% 50%,rgba(0,245,255,.04) 0%,transparent 70%);
}
.servicos-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
  gap:1.5rem;
  max-width:1100px;margin:3.5rem auto 0;
}
.servico-card{
  position:relative;overflow:hidden;
  background:var(--glass-1);
  border:1px solid var(--glass-border-2);
  backdrop-filter:blur(20px);
  padding:2.8rem 2.2rem;
  transition:all .35s cubic-bezier(0.4, 0, 0.2, 1);
  cursor:default;
}
.servico-card:hover{
  border-color:var(--cyan);
  transform:translateY(-8px);
  box-shadow:var(--glow-medium);
}
.servico-card.destaque{
  border-color:rgba(255,0,110,.25);
  background:linear-gradient(180deg, rgba(255,0,110,0.04), transparent);
}
.servico-card.destaque:hover{
  border-color:var(--magenta);
  box-shadow:0 0 30px rgba(255,0,110,0.2);
}
.servico-num{
  font-family:'Bebas Neue',sans-serif;
  font-size:4.5rem;color:rgba(255,255,255,.04);
  line-height:1;margin-bottom:1rem;
  position:absolute;top:1rem;right:1.5rem;
}
.servico-icon{
  width:54px;height:54px;
  border:1px solid rgba(0,245,255,.3);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:1.8rem;position:relative;z-index:1;
  transition:transform .3s;
}
.servico-card:hover .servico-icon{transform:scale(1.05)}
.servico-icon svg{width:24px;height:24px;stroke:var(--cyan);fill:none;stroke-width:1.5}
.servico-card.destaque .servico-icon{border-color:rgba(255,0,110,.3)}
.servico-card.destaque .servico-icon svg{stroke:var(--magenta)}
.servico-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:1.75rem;letter-spacing:.03em;
  margin-bottom:1rem;position:relative;z-index:1;
  color:var(--text);
}
.servico-desc{
  font-size:.95rem;font-weight:400;
  color:var(--text-secondary);line-height:1.7;
  margin-bottom:1.5rem;position:relative;z-index:1;
}
.servico-tag{
  font-family:'Space Mono',monospace;
  font-size:.58rem;letter-spacing:.18em;
  text-transform:uppercase;
  color:var(--cyan);position:relative;z-index:1;
  font-weight:700;
}
.servico-card.destaque .servico-tag{color:var(--magenta)}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   PORTFÃ“LIO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
  max-width:1100px;margin:3.5rem auto 0;
}
@media (max-width: 768px) {
  .portfolio-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }
}
.portfolio-slot{
  position:relative;aspect-ratio:16/9;
  background:rgba(8,12,22,.8);
  border:1px solid var(--glass-border-2);
  border-radius:24px;
  overflow:hidden;cursor:pointer;
  transition:all .4s cubic-bezier(0.4, 0, 0.2, 1);
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  padding:2rem;
}
@media (max-width: 600px) {
  .portfolio-slot {
    aspect-ratio: 4/3;
  }
}
.portfolio-slot:hover{
  border-color:var(--cyan);
  box-shadow:var(--glow-medium);
  transform:translateY(-5px);
}
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
  font-size:.58rem;letter-spacing:.28em;
  text-transform:uppercase;color:var(--muted);
}
.portfolio-slot:hover .portfolio-cat{color:var(--cyan)}
.portfolio-coming{
  font-family:'Space Mono',monospace;
  font-size:.52rem;letter-spacing:.22em;
  color:var(--muted);text-transform:uppercase;
}
.portfolio-note{
  text-align:center;margin-top:2rem;
  font-family:'Space Mono',monospace;
  font-size:.6rem;letter-spacing:.15em;
  color:var(--muted);
}
.portfolio-note span{color:var(--cyan)}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   VIRA BRASIL
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   PROCESSO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
    transparent,var(--cyan),var(--magenta),var(--cyan),transparent
  );
  opacity: 0.3;
}
.fade-text{
  transition: opacity 0.4s ease, transform 0.4s ease;
  display: inline-block;
}
.fade-text.hidden{
  opacity: 0;
  transform: translateY(10px);
}
.fade-text.is-accent{
  color: var(--cyan);
  text-shadow: 0 0 20px rgba(0,245,255,0.3);
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
  font-size:1.15rem;letter-spacing:.08em;
  margin-bottom:.5rem;
  color:var(--text);
}
.step-desc{
  font-size:.78rem;font-weight:400;
  color:var(--text-secondary);line-height:1.6;
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   DEPOIMENTOS â€” Holographic
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
  padding:2.5rem 2rem;
  border-radius:24px;
  background:linear-gradient(120deg,rgba(255,255,255,.07),rgba(255,255,255,.02));
  backdrop-filter:blur(16px);
  box-shadow:0 0 50px rgba(0,245,255,.05),inset 0 0 30px rgba(255,255,255,.05);
  transform-style:preserve-3d;
  animation:holoFloat 6s ease-in-out infinite;
  position:relative;border:1px solid rgba(255,255,255,.1);
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


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   FAQ
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   CTA FINAL
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
    radial-gradient(ellipse at 70% 50%,rgba(0,245,255,.04) 0%,transparent 50%);
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
  max-width:720px;margin:0 auto;
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
  font-size:clamp(2rem, 4vw, 3rem);
  line-height:1.1;letter-spacing:-0.01em;
  color:rgba(255,255,255,0.9);
  margin-bottom:1.5rem;
}
.contato-title span{
  background:linear-gradient(90deg, #22d3ee, #38bdf8);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
.contato-sub{
  font-size:15px;font-weight:400;
  color:rgba(255,255,255,0.65);margin-bottom:2.5rem;line-height:1.6;
  max-width:42ch;margin-left:auto;margin-right:auto;
}
.contato-escassez{
  font-family:'Space Mono',monospace;
  font-size:.62rem;letter-spacing:.25em;
  text-transform:uppercase;
  color:var(--cyan);margin-bottom:2.5rem;
  font-weight:700;
  animation:subtle-pulse 2s ease-in-out infinite;
}
@keyframes subtle-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.85; transform: scale(0.98); }
}
.contato-btns{
  display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;
  margin-bottom:3rem;
}
.contato-links{
  display:flex;gap:2.5rem;justify-content:center;flex-wrap:wrap;
}
.contato-link{
  display:flex;align-items:center;gap:.6rem;
  font-family:'Space Mono',monospace;
  font-size:.6rem;letter-spacing:.18em;
  text-transform:uppercase;color:var(--muted);
  text-decoration:none;transition:all .3s ease;
}
.contato-link:hover{
  color:var(--cyan);
  transform:translateX(5px);
}
.contato-link svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.5}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   FOOTER
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   WHATSAPP FLUTUANTE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   BACKGROUND GRADIENTE RESPONSIVO POR SEÃ‡ÃƒO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SCROLL REVEAL
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.reveal{opacity:0;transform:translateY(40px);transition:opacity .7s ease,transform .7s ease}
.reveal.visible{opacity:1;transform:translateY(0)}
.reveal-left{opacity:0;transform:translateX(-40px);transition:opacity .7s ease,transform .7s ease}
.reveal-left.visible{opacity:1;transform:translateX(0)}
.reveal-right{opacity:0;transform:translateX(52px);transition:opacity .8s cubic-bezier(.22,1,.36,1),transform .8s cubic-bezier(.22,1,.36,1)}
.reveal-right.visible{opacity:1;transform:translateX(0)}
.reveal-delay-1{transition-delay:.1s}
.reveal-delay-2{transition-delay:.2s}
.reveal-delay-3{transition-delay:.3s}
.reveal-delay-4{transition-delay:.4s}


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   ULTRA PREMIUM DESIGN SYSTEM
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
body::before{
  content:'';
  position:fixed;
  inset:0;
  background:
    radial-gradient(circle at 18% 26%, rgba(0,234,255,.08) 0%, transparent 34%),
    radial-gradient(circle at 82% 72%, rgba(255,0,110,.06) 0%, transparent 34%),
    radial-gradient(circle at 50% 50%, rgba(255,255,255,.02) 0%, transparent 58%);
  pointer-events:none;
  z-index:-2;
}
body::after{
  z-index:-1;
}

.light-orb{
  position:fixed;
  width:520px;
  height:520px;
  border-radius:50%;
  pointer-events:none;
  filter:blur(145px);
  opacity:.55;
  z-index:-1;
}
.light-orb--hero{
  top:10%;
  right:-130px;
  background:rgba(0,234,255,.16);
}
.light-orb--secondary{
  left:-160px;
  bottom:12%;
  background:rgba(255,0,110,.08);
}

.premium-card,
.glass-card,
.servico-card,
.portfolio-slot,
.holo-card,
.quem-prova,
.proof-item,
.hero-dynamic-wrap{
  position:relative;
  border-radius:24px;
  background:linear-gradient(180deg, rgba(255,255,255,.055), rgba(255,255,255,.022));
  border:1px solid rgba(255,255,255,.08);
  box-shadow:
    0 22px 64px rgba(0,0,0,.42),
    inset 0 1px 0 rgba(255,255,255,.06);
  backdrop-filter:blur(24px);
  -webkit-backdrop-filter:blur(24px);
  transition:
    transform .35s cubic-bezier(.22,1,.36,1),
    box-shadow .35s cubic-bezier(.22,1,.36,1),
    border-color .35s cubic-bezier(.22,1,.36,1),
    background .35s cubic-bezier(.22,1,.36,1);
}

.premium-card::before,
.glass-card::before,
.servico-card::before,
.portfolio-slot::before,
.quem-prova::before,
.proof-item::before,
.hero-dynamic-wrap::before{
  content:'';
  position:absolute;
  inset:-1px;
  border-radius:inherit;
  background:linear-gradient(
    120deg,
    transparent 12%,
    rgba(0,234,255,.16) 38%,
    rgba(255,0,110,.07) 62%,
    transparent 88%
  );
  filter:blur(24px);
  opacity:.36;
  z-index:-1;
  pointer-events:none;
}

.premium-card:hover,
.glass-card:hover,
.servico-card:hover,
.portfolio-slot:hover,
.holo-card:hover,
.quem-prova:hover,
.proof-item:hover,
.hero-dynamic-wrap:hover{
  transform:translateY(-8px) scale(1.01);
  border-color:rgba(0,234,255,.18);
  box-shadow:
    0 30px 84px rgba(0,0,0,.58),
    0 0 40px rgba(0,234,255,.12),
    inset 0 1px 0 rgba(255,255,255,.08);
}

section{
  position:relative;
}
/* overlay removed */

.container,
.processo-wrap,
.hero-content,
.quem-wrap{
  position:relative;
  z-index:1;
}

.btn-primary,
.btn-secondary,
.btn-premium{
  min-height:56px;
  border-radius:16px;
  font-family:'Inter',sans-serif;
  font-weight:700;
  letter-spacing:.01em;
  transition:
    transform .3s cubic-bezier(.22,1,.36,1),
    box-shadow .3s cubic-bezier(.22,1,.36,1),
    border-color .3s cubic-bezier(.22,1,.36,1),
    background .3s cubic-bezier(.22,1,.36,1),
    color .3s cubic-bezier(.22,1,.36,1);
}

.btn-primary,
.btn-premium{
  background:linear-gradient(135deg,#00eaff 0%,#4ef5ff 100%);
  border:1px solid rgba(0,234,255,.26);
  box-shadow:
    0 0 25px rgba(0,234,255,.25),
    0 16px 40px rgba(0,0,0,.32);
}

.btn-primary:hover,
.btn-premium:hover{
  transform:translateY(-2px) scale(1.02);
  box-shadow:
    0 0 42px rgba(0,234,255,.40),
    0 16px 40px rgba(0,0,0,.45);
}

.btn-secondary{
  background:rgba(255,255,255,.025);
  border:1px solid rgba(0,234,255,.34);
  color:#dbf9ff;
  box-shadow:
    inset 0 0 0 1px rgba(255,255,255,.03),
    0 10px 28px rgba(0,0,0,.22);
}

.btn-secondary:hover{
  transform:translateY(-2px);
  background:rgba(0,234,255,.10);
  box-shadow:
    0 0 30px rgba(0,234,255,.16),
    0 14px 34px rgba(0,0,0,.34);
}

.card-icon,
.servico-icon,
.portfolio-play,
.step-dot,
.faq-icon{
  border-radius:16px;
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.05),
    0 10px 24px rgba(0,0,0,.24);
}

.section-title,
.hero-title,
.quem-title-line,
.contato-title{
  text-shadow:0 4px 24px rgba(0,0,0,.18);
}

.problema-grid,
.servicos-grid,
.portfolio-grid,
.depo-grid{
  gap:1.6rem;
}

#problema,
#servicos,
#portfolio,
#vira-brasil,
#processo,
#depoimentos,
#faq,
#contato{
  overflow:hidden;
}

@media(max-width:768px){
  .light-orb{
    display:none;
  }
  .premium-card,
  .glass-card,
  .servico-card,
  .portfolio-slot,
  .holo-card,
  .quem-prova,
  .proof-item,
  .hero-dynamic-wrap{
    border-radius:20px;
  }
}


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION OVERLAY CINEMATOGRÃFICO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.section-overlap{
  position:relative;
  margin-top:-88px;
  z-index:4;
  border-radius:34px 34px 0 0;
  box-shadow:
    0 -24px 60px rgba(0,0,0,.42),
    0 -1px 0 rgba(255,255,255,.04),
    0 0 0 1px rgba(255,255,255,.03);
}
.section-overlap::before{
  content:'';
  position:absolute;
  inset:0;
  border-radius:inherit;
  pointer-events:none;
  background:
    linear-gradient(180deg, rgba(255,255,255,.035) 0%, transparent 18%),
    radial-gradient(circle at 50% 0%, rgba(0,234,255,.08) 0%, transparent 42%);
  opacity:.9;
}
#problema.section-overlap,
#servicos.section-overlap,
#contato.section-overlap{
  overflow:visible;
}
#problema.section-overlap{ z-index:5; }
#servicos.section-overlap{ z-index:4; }
#contato.section-overlap{ z-index:4; }

@media(max-width:1024px){
  .section-overlap{
    margin-top:-64px;
    border-radius:28px 28px 0 0;
  }
}
@media(max-width:768px){
  .section-overlap{
    margin-top:-36px;
    border-radius:24px 24px 0 0;
    box-shadow:
      0 -14px 34px rgba(0,0,0,.34),
      0 -1px 0 rgba(255,255,255,.03);
  }
}


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   VIEWPORT ACTIVATION TEST
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
@media(max-width:980px){
  .grain{
    display:none;
  }

  #quem-sou .quem-visual::before{
    animation-play-state:paused;
    opacity:.72;
  }

  #quem-sou .quem-content > *,
  #quem-sou .quem-picture,
  #quem-sou .mobile-curve-card{
    will-change:transform, opacity;
  }

  #quem-sou .quem-kicker,
  #quem-sou .quem-title,
  #quem-sou .quem-subtext,
  #quem-sou .quem-picture{
    opacity:0;
    transition:
      opacity .55s cubic-bezier(.22,1,.36,1),
      transform .55s cubic-bezier(.22,1,.36,1);
  }

  #quem-sou .quem-kicker{ transform:translateY(18px); }
  #quem-sou .quem-title{ transform:translateY(24px); }
  #quem-sou .quem-subtext{ transform:translateY(30px); }
  #quem-sou .quem-picture{ transform:translate3d(-18px, 24px, 0); }

  #quem-sou .mobile-curve-card{
    opacity:0;
    transform:translate3d(-24px, 0, 0);
    transition:
      opacity .48s cubic-bezier(.22,1,.36,1),
      transform .48s cubic-bezier(.22,1,.36,1);
  }

  #quem-sou.is-active .quem-visual::before{
    animation-play-state:running;
    opacity:.92;
  }

  #quem-sou.is-active .quem-kicker,
  #quem-sou.is-active .quem-title,
  #quem-sou.is-active .quem-subtext,
  #quem-sou.is-active .quem-picture{
    opacity:1;
    transform:none;
  }

  #quem-sou.is-active .quem-kicker{ transition-delay:.04s; }
  #quem-sou.is-active .quem-title{ transition-delay:.14s; }
  #quem-sou.is-active .quem-subtext{ transition-delay:.24s; }
  #quem-sou.is-active .quem-picture{ transition-delay:.34s; }

  #quem-sou.is-active .mobile-curve-card{
    opacity:1;
    transform:none;
  }

  #quem-sou.is-active .mobile-curve-card--1{ transition-delay:.42s; }
  #quem-sou.is-active .mobile-curve-card--2{ transition-delay:.52s; }
  #quem-sou.is-active .mobile-curve-card--3{ transition-delay:.62s; }
  #quem-sou.is-active .mobile-curve-card--4{ transition-delay:.72s; }
}


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION ACTIVATION â€” PÃGINA INTEIRA
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
#hero .hero-kicker,
#hero .hero-title,
#hero .hero-dynamic-wrap,
#hero .hero-actions,
#hero .hero-proof,
#problema .problema-head,
#problema .glass-card,
#servicos .section-tag,
#servicos .section-title,
#servicos .servico-card,
#portfolio .section-tag,
#portfolio .section-title,
#portfolio .portfolio-slot,
#portfolio .portfolio-note,
#vira-brasil .vira-photos,
#vira-brasil .vira-content,
#processo .section-tag,
#processo .section-title,
#processo .step,
#depoimentos .section-tag,
#depoimentos .section-title,
#depoimentos .holo-card,
#depoimentos .depo-waiting,
#faq .section-tag,
#faq .section-title,
#faq .faq-item,
#contato .contato-pre,
#contato .contato-title,
#contato .contato-sub,
#contato .contato-escassez,
#contato .contato-btns,
#contato .contato-links{
  opacity:0;
  will-change:transform, opacity;
}

#hero .hero-kicker,
#hero .hero-title,
#hero .hero-dynamic-wrap,
#hero .hero-actions,
#hero .hero-proof,
#problema .problema-head,
#servicos .section-tag,
#servicos .section-title,
#portfolio .section-tag,
#portfolio .section-title,
#portfolio .portfolio-note,
#processo .section-tag,
#processo .section-title,
#depoimentos .section-tag,
#depoimentos .section-title,
#faq .section-tag,
#faq .section-title,
#contato .contato-pre,
#contato .contato-title,
#contato .contato-sub,
#contato .contato-escassez,
#contato .contato-btns,
#contato .contato-links{
  transform:translateY(26px);
  transition:
    opacity .55s cubic-bezier(.22,1,.36,1),
    transform .55s cubic-bezier(.22,1,.36,1);
}

#problema .glass-card,
#servicos .servico-card,
#portfolio .portfolio-slot,
#depoimentos .holo-card,
#depoimentos .depo-waiting,
#faq .faq-item{
  transform:translateY(28px);
  transition:
    opacity .52s cubic-bezier(.22,1,.36,1),
    transform .52s cubic-bezier(.22,1,.36,1);
}

#vira-brasil .vira-photos{
  transform:translateX(-26px);
  transition:
    opacity .58s cubic-bezier(.22,1,.36,1),
    transform .58s cubic-bezier(.22,1,.36,1);
}

#vira-brasil .vira-content{
  transform:translateX(26px);
  transition:
    opacity .58s cubic-bezier(.22,1,.36,1),
    transform .58s cubic-bezier(.22,1,.36,1);
}

#processo .step{
  transform:translateY(24px);
  transition:
    opacity .5s cubic-bezier(.22,1,.36,1),
    transform .5s cubic-bezier(.22,1,.36,1);
}

#hero.is-active .hero-kicker,
#hero.is-active .hero-title,
#hero.is-active .hero-dynamic-wrap,
#hero.is-active .hero-actions,
#hero.is-active .hero-proof,
#problema.is-active .problema-head,
#problema.is-active .glass-card,
#servicos.is-active .section-tag,
#servicos.is-active .section-title,
#servicos.is-active .servico-card,
#portfolio.is-active .section-tag,
#portfolio.is-active .section-title,
#portfolio.is-active .portfolio-slot,
#portfolio.is-active .portfolio-note,
#vira-brasil.is-active .vira-photos,
#vira-brasil.is-active .vira-content,
#processo.is-active .section-tag,
#processo.is-active .section-title,
#processo.is-active .step,
#depoimentos.is-active .section-tag,
#depoimentos.is-active .section-title,
#depoimentos.is-active .holo-card,
#depoimentos.is-active .depo-waiting,
#faq.is-active .section-tag,
#faq.is-active .section-title,
#faq.is-active .faq-item,
#contato.is-active .contato-pre,
#contato.is-active .contato-title,
#contato.is-active .contato-sub,
#contato.is-active .contato-escassez,
#contato.is-active .contato-btns,
#contato.is-active .contato-links{
  opacity:1;
  transform:none;
}

#hero.is-active .hero-kicker{transition-delay:.04s}
#hero.is-active .hero-title{transition-delay:.12s}
#hero.is-active .hero-dynamic-wrap{transition-delay:.22s}
#hero.is-active .hero-actions{transition-delay:.32s}
#hero.is-active .hero-proof{transition-delay:.42s}

#problema.is-active .problema-head{transition-delay:.05s}
#problema.is-active .glass-card:nth-child(1){transition-delay:.14s}
#problema.is-active .glass-card:nth-child(2){transition-delay:.24s}
#problema.is-active .glass-card:nth-child(3){transition-delay:.34s}

#servicos.is-active .section-tag{transition-delay:.04s}
#servicos.is-active .section-title{transition-delay:.12s}
#servicos.is-active .servico-card:nth-child(1){transition-delay:.22s}
#servicos.is-active .servico-card:nth-child(2){transition-delay:.32s}
#servicos.is-active .servico-card:nth-child(3){transition-delay:.42s}

#portfolio.is-active .section-tag{transition-delay:.04s}
#portfolio.is-active .section-title{transition-delay:.12s}
#portfolio.is-active .portfolio-slot:nth-child(1){transition-delay:.22s}
#portfolio.is-active .portfolio-slot:nth-child(2){transition-delay:.30s}
#portfolio.is-active .portfolio-slot:nth-child(3){transition-delay:.38s}
#portfolio.is-active .portfolio-slot:nth-child(4){transition-delay:.46s}
#portfolio.is-active .portfolio-note{transition-delay:.54s}

#vira-brasil.is-active .vira-photos{transition-delay:.10s}
#vira-brasil.is-active .vira-content{transition-delay:.22s}

#processo.is-active .section-tag{transition-delay:.04s}
#processo.is-active .section-title{transition-delay:.12s}
#processo.is-active .step:nth-child(1){transition-delay:.22s}
#processo.is-active .step:nth-child(2){transition-delay:.28s}
#processo.is-active .step:nth-child(3){transition-delay:.34s}
#processo.is-active .step:nth-child(4){transition-delay:.40s}
#processo.is-active .step:nth-child(5){transition-delay:.46s}

#depoimentos.is-active .section-tag{transition-delay:.04s}
#depoimentos.is-active .section-title{transition-delay:.12s}
#depoimentos.is-active .holo-card:nth-child(1){transition-delay:.22s}
#depoimentos.is-active .holo-card:nth-child(2){transition-delay:.32s}
#depoimentos.is-active .holo-card:nth-child(3){transition-delay:.42s}
#depoimentos.is-active .depo-waiting{transition-delay:.22s}

#faq.is-active .section-tag{transition-delay:.04s}
#faq.is-active .section-title{transition-delay:.12s}
#faq.is-active .faq-item:nth-child(1){transition-delay:.22s}
#faq.is-active .faq-item:nth-child(2){transition-delay:.28s}
#faq.is-active .faq-item:nth-child(3){transition-delay:.34s}
#faq.is-active .faq-item:nth-child(4){transition-delay:.40s}
#faq.is-active .faq-item:nth-child(5){transition-delay:.46s}

#contato.is-active .contato-pre{transition-delay:.04s}
#contato.is-active .contato-title{transition-delay:.12s}
#contato.is-active .contato-sub{transition-delay:.20s}
#contato.is-active .contato-escassez{transition-delay:.28s}
#contato.is-active .contato-btns{transition-delay:.36s}
#contato.is-active .contato-links{transition-delay:.44s}

@media(max-width:980px){
  #hero .hero-kicker,
  #hero .hero-title,
  #hero .hero-dynamic-wrap,
  #hero .hero-actions,
  #problema .problema-head,
  #problema .glass-card,
  #servicos .section-tag,
  #servicos .section-title,
  #servicos .servico-card,
  #portfolio .section-tag,
  #portfolio .section-title,
  #portfolio .portfolio-slot,
  #portfolio .portfolio-note,
  #vira-brasil .vira-photos,
  #vira-brasil .vira-content,
  #processo .section-tag,
  #processo .section-title,
  #processo .step,
  #depoimentos .section-tag,
  #depoimentos .section-title,
  #depoimentos .holo-card,
  #depoimentos .depo-waiting,
  #faq .section-tag,
  #faq .section-title,
  #faq .faq-item,
  #contato .contato-pre,
  #contato .contato-title,
  #contato .contato-sub,
  #contato .contato-escassez,
  #contato .contato-btns,
  #contato .contato-links{
    transition-duration:.42s;
  }
}


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   DEVICE PROFILES / LITE MODE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
html.touch-device .cursor,
html.touch-device .cursor-ring{
  display:none !important;
}

html.mobile-device .grain{
  opacity:.14;
}

html.lite-mode .grain,
html.lite-mode .light-orb,
html.lite-mode .hero-noise{
  display:none !important;
}

html.lite-mode .glass-card,
html.lite-mode .servico-card,
html.lite-mode .portfolio-slot,
html.lite-mode .holo-card,
html.lite-mode .quem-prova,
html.lite-mode .proof-item,
html.lite-mode .hero-dynamic-wrap,
html.lite-mode .mobile-curve-card{
  backdrop-filter:none !important;
  -webkit-backdrop-filter:none !important;
  box-shadow:
    0 10px 28px rgba(0,0,0,.24),
    inset 0 1px 0 rgba(255,255,255,.04) !important;
}

html.lite-mode .glass-card::before,
html.lite-mode .servico-card::before,
html.lite-mode .portfolio-slot::before,
html.lite-mode .holo-card::before,
html.lite-mode .quem-prova::before,
html.lite-mode .proof-item::before,
html.lite-mode .hero-dynamic-wrap::before,
html.lite-mode .mobile-curve-card::before{
  display:none !important;
}

html.lite-mode #hero .hero-bg::after,
html.lite-mode #quem-sou .quem-visual::before,
html.lite-mode .holo-card,
html.lite-mode .loader-logo,
html.lite-mode .grain{
  animation:none !important;
}

html.lite-mode .premium-card:hover,
html.lite-mode .glass-card:hover,
html.lite-mode .servico-card:hover,
html.lite-mode .portfolio-slot:hover,
html.lite-mode .holo-card:hover,
html.lite-mode .quem-prova:hover,
html.lite-mode .proof-item:hover,
html.lite-mode .hero-dynamic-wrap:hover,
html.lite-mode .btn-primary:hover,
html.lite-mode .btn-secondary:hover{
  transform:none !important;
  box-shadow:
    0 10px 28px rgba(0,0,0,.24),
    inset 0 1px 0 rgba(255,255,255,.04) !important;
}

html.lite-mode .section-overlap{
  box-shadow:
    0 -8px 24px rgba(0,0,0,.22),
    0 -1px 0 rgba(255,255,255,.03) !important;
}

html.lite-mode .hero-bg::after{
  opacity:.45 !important;
  filter:blur(26px) !important;
}

html.lite-mode .quem-visual::before{
  filter:blur(26px) !important;
  opacity:.62 !important;
}

html.lite-mode .mobile-curve-card,
html.lite-mode .glass-card,
html.lite-mode .servico-card,
html.lite-mode .portfolio-slot,
html.lite-mode .holo-card,
html.lite-mode .faq-item{
  transition-duration:.28s !important;
}

@media(max-width:980px){
  html.mobile-device .glass-card,
  html.mobile-device .servico-card,
  html.mobile-device .portfolio-slot,
  html.mobile-device .holo-card,
  html.mobile-device .hero-dynamic-wrap,
  html.mobile-device .proof-item,
  html.mobile-device .quem-prova,
  html.mobile-device .mobile-curve-card{
    box-shadow:
      0 12px 30px rgba(0,0,0,.26),
      inset 0 1px 0 rgba(255,255,255,.04);
  }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   UTILITÃRIOS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.container{max-width:1100px;margin:0 auto}
@keyframes fadeUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.text-cyan{color:var(--cyan)}
.text-magenta{color:var(--magenta)}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   RESPONSIVIDADE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
@media(max-width:1024px){
  .quem-grid{gap:3rem}
  .processo-steps{grid-template-columns:1fr 1fr;gap:2rem}
  .processo-steps::before{display:none}
  .vira-grid{gap:2rem}
}
@media(min-width:769px){
  #hero{
    align-items:stretch;
    min-height:100vh;
  }
  .hero-bg{
    background:
      linear-gradient(to right, rgba(2,4,10,.88) 0%, rgba(2,4,10,.72) 28%, rgba(2,4,10,.38) 52%, rgba(2,4,10,.14) 72%, rgba(2,4,10,.08) 100%),
      url('/assets/img/danielherocanva.webp') right top / auto 108% no-repeat,
      radial-gradient(circle at 72% 42%, rgba(0,245,255,.16) 0%, rgba(0,245,255,.07) 18%, transparent 52%),
      radial-gradient(circle at 83% 72%, rgba(0,245,255,.08) 0%, transparent 42%),
      linear-gradient(115deg, #02040a 0%, #040914 42%, #02040a 100%);
  }
  .hero-bg::before{
    background:
      linear-gradient(to right, rgba(2,4,10,.92) 0%, rgba(2,4,10,.78) 24%, rgba(2,4,10,.44) 46%, rgba(2,4,10,.16) 70%, rgba(2,4,10,.05) 100%),
      radial-gradient(circle at 22% 40%, rgba(255,255,255,.025) 0%, transparent 28%),
      linear-gradient(90deg, transparent 0%, rgba(0,245,255,.03) 46%, transparent 100%);
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
    width:100%;
    max-width:none;
    margin:0;
    padding:0 clamp(1.8rem, 6vw, 7rem) 2.5rem;
    justify-content:flex-start;
  }
  .hero-panel{
    width:min(100%, 760px);
  }
  .hero-title{
    font-size:clamp(4rem, 6vw, 6.4rem);
    max-width:10ch;
  }
  .hero-dynamic-wrap{
    margin-top:1.45rem;
  }
  .hero-proof{
    gap:1rem;
  }
  .proof-item{
    min-width:170px;
  }
  .hero-daniel{
    display:none;
    right:clamp(-0.5rem, 2.5vw, 2.5rem);
    height:min(95vh, 980px);
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
@media(min-width:1200px){
  .hero-content{
    padding-left:clamp(4rem, 9vw, 11rem);
    padding-right:clamp(3rem, 6vw, 6rem);
  }
  .hero-panel{
    max-width:560px;
  }
  .hero-title{
    font-size:clamp(4.8rem, 5.5vw, 6.8rem);
  }
  .hero-daniel{
    right:clamp(0rem, 2vw, 2rem);
    height:min(96vh, 1040px);
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
    padding:0 1rem 2rem;
  }
  .hero-panel{
    max-width:100%;
    margin-top:0;
    padding:0;
  }
  .hero-dynamic{
    max-width:100%;
    min-height:3.8em;
  }
  .hero-actions{
    flex-direction:column;
  }
  .hero-actions .btn-primary,
  .hero-actions .btn-secondary{
    width:100%;
  }
  .scroll-indicator{
    display:none;
  }
  .hero-daniel{
    display:none;
  }
  .problema-grid{grid-template-columns:1fr;gap:1rem}
  .problema-head{margin-bottom:2.1rem}
  .problema-title{max-width:100%}
  .glass-card{min-height:auto;padding:1.35rem 1.15rem 1.2rem;border-radius:22px}
  .card-desc{max-width:100%;font-size:.92rem;line-height:1.62}
  .quem-grid{grid-template-columns:1fr}
  .quem-photo-wrap{max-width:400px;margin:0 auto}
  .vira-grid{grid-template-columns:1fr}
  .portfolio-grid{grid-template-columns:1fr}
  .processo-steps{grid-template-columns:1fr}
  footer{flex-direction:column;align-items:flex-start}
}
@media(max-width:480px){
  #hero{min-height:100svh}
  .hero-title{font-size:2.7rem}
  .hero-dynamic{
    max-width:100%;
    min-height:4em;
    font-size:1.08rem;
    line-height:1.58;
  }
  .hero-dynamic-wrap{
    width:100%;
    max-width:100%;
    padding:.85rem .9rem;
    border-radius:18px;
  }
  .hero-daniel{
    display:none;
  }
  .scroll-indicator{bottom:1.1rem}
  .section-title{font-size:2rem}
  .problema-title{font-size:2.5rem;line-height:.96}
  .card-title{font-size:1.26rem}
  .contato-btns{flex-direction:column;align-items:center}
  .vira-photos{grid-template-columns:1fr}
  .quem-stats{grid-template-columns:1fr 1fr}
}



@media(max-width:980px){
  #quem-sou .quem-content{
    transform:translateY(200px) !important;
    margin-top:0 !important;
  }
}
@media(max-width:480px){
  #quem-sou .quem-content{
    transform:translateY(200px) !important;
    margin-top:0 !important;
  }
}


@media(max-width:980px){
  #hero .hero-bg::after,
  #quem-sou .quem-visual::before,
  #depoimentos .holo-card{
    animation-play-state:paused;
  }

  #hero.is-active .hero-bg::after,
  #quem-sou.is-active .quem-visual::before,
  #depoimentos.is-active .holo-card{
    animation-play-state:running;
  }
}


/* === FADE TEXT EFFECT (Como trabalho) === */
.fade-text{
  transition: opacity .5s ease;
  opacity:1;
}
.fade-text.hidden{
  opacity:0;
}


/* === FIX REAL DO TÃTULO DINÃ‚MICO EM PROCESSO === */
.processo-title-dynamic{
  min-height: 2.2em;
}

.fade-text{
  display:inline-block;
  min-width: 10ch;
  font-weight:800;
  letter-spacing:-0.02em;
  line-height:.95;
  background:linear-gradient(90deg,#f2f5fb 0%, #eaf0f6 45%, #ffffff 100%);
  -webkit-background-clip:text;
  background-clip:text;
  color:transparent;
  text-shadow:0 0 20px rgba(255,255,255,.08);
  transition:opacity .45s ease, transform .45s ease, filter .45s ease;
  opacity:1;
  transform:translateY(0);
  filter:blur(0);
}
.fade-text.is-accent{
  background:linear-gradient(90deg,#00eaff 0%, #79f7ff 100%);
  -webkit-background-clip:text;
  background-clip:text;
  color:transparent;
  text-shadow:0 0 24px rgba(0,234,255,.16);
}
.fade-text.hidden{
  opacity:0;
  transform:translateY(10px);
  filter:blur(6px);
}

@media (max-width: 768px){
  .processo-title-dynamic{
    min-height: 2.6em;
  }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   ðŸŽ¥ CARROSSEL DE VÃDEOS COM SWIPER - ESTILOS CUSTOMIZADOS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

/* Grid de cards */
.portfolio-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin: 2rem 0;
}

/* Card de portfÃ³lio */
.portfolio-slot {
  background: rgba(6, 12, 22, 0.8);
  border: 1px solid rgba(0, 245, 255, 0.12);
  padding: 2rem 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 8px;
}

.portfolio-slot:hover {
  border-color: var(--cyan);
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 245, 255, 0.1);
}

.portfolio-slot-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.portfolio-slot-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 1.3rem;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
  color: var(--text);
}

.portfolio-slot-count {
  font-family: 'Space Mono', monospace;
  font-size: 0.75rem;
  color: var(--cyan);
  letter-spacing: 0.2em;
  text-transform: uppercase;
  font-weight: 700;
}

.portfolio-slot-empty {
  color: var(--muted);
  font-size: 0.9rem;
}

/* Modal/Pop-up */
.modal-portfolio {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(2, 4, 10, 0.95);
  backdrop-filter: blur(10px);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  overflow: auto;
}

.modal-portfolio.active {
  display: flex;
}

.modal-content {
  background: rgba(6, 12, 22, 0.9);
  border: 1px solid rgba(0, 245, 255, 0.12);
  border-radius: 12px;
  width: 100%;
  max-width: 750px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid rgba(0, 245, 255, 0.12);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 1.5rem;
  letter-spacing: 0.05em;
  color: var(--cyan);
}

.modal-close {
  background: none;
  border: none;
  color: var(--text);
  font-size: 2rem;
  cursor: pointer;
  transition: color 0.2s;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  color: var(--magenta);
}

.modal-body {
  flex: 1;
  overflow: auto;
  padding: 2rem;
}

/* Swiper Container */
.swiper-container {
  width: 100%;
  height: 100%;
}

.swiper-slide {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

/* Container de vÃ­deo com aspect ratio */
.video-container {
  position: relative;
  width: 100%;
  overflow: hidden;
  border-radius: 8px;
  border: 1px solid rgba(0, 245, 255, 0.12);
}

.video-container.vertical {
  max-width: 400px;
  aspect-ratio: 9 / 16;
}

.video-container.horizontal {
  max-width: 100%;
  aspect-ratio: 16 / 9;
}

.video-container iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: none;
  border-radius: 8px;
}

/* EFEITOS SWIPER - Blur e Scale */
.swiper-slide-prev {
  filter: blur(10px);
  transform: scale(0.5);
  transition: 0.5s;
  opacity: 0.5;
}

.swiper-slide-active {
  filter: blur(3px);
  transform: scale(0.7);
  transition: 0.5s;
  opacity: 1;
}

.swiper-slide-next {
  filter: blur(10px);
  transform: scale(0.5);
  transition: 0.5s;
  opacity: 0.5;
}

.swiper-slide-next ~ .swiper-slide {
  filter: blur(3px);
  transform: scale(0.7);
  transition: 0.5s;
  opacity: 0.5;
}

/* InformaÃ§Ãµes do vÃ­deo */
.video-info {
  text-align: center;
  margin-top: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 8px;
  border: 1px solid rgba(0, 245, 255, 0.12);
}

.video-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 1.1rem;
  color: var(--cyan);
  margin-bottom: 0.5rem;
}

.video-count {
  font-family: 'Space Mono', monospace;
  font-size: 0.75rem;
  color: var(--muted);
  letter-spacing: 0.2em;
}

/* PaginaÃ§Ã£o */
.swiper-pagination {
  bottom: 0 !important;
  padding: 1rem 0;
}

.swiper-pagination-bullet {
  background: var(--cyan);
  opacity: 0.5;
}

.swiper-pagination-bullet-active {
  background: var(--magenta);
  opacity: 1;
}

/* BotÃµes de navegaÃ§Ã£o */
.swiper-button-next,
.swiper-button-prev {
  color: var(--cyan);
  width: 40px;
  height: 40px;
  background: rgba(0, 245, 255, 0.1);
  border-radius: 50%;
  transition: all 0.3s;
  margin-top: 0;
}

.swiper-button-next:hover,
.swiper-button-prev:hover {
  background: rgba(0, 245, 255, 0.3);
  color: var(--magenta);
}

.swiper-button-next::after,
.swiper-button-prev::after {
  font-size: 18px;
}

/* Responsividade */
@media (max-width: 768px) {
  .portfolio-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .modal-content {
    max-width: 95vw;
    max-height: 95vh;
  }
  
  .modal-body {
    padding: 1rem;
  }
  
  .swiper-slide {
    padding: 1rem 0.5rem;
  }
  
  .video-container.vertical {
    max-width: 300px;
  }
  
  .swiper-button-next,
  .swiper-button-prev {
    width: 35px;
    height: 35px;
  }
}

@media (max-width: 480px) {
  .portfolio-slot {
    padding: 1.5rem 1rem;
  }
  
  .portfolio-slot-icon {
    font-size: 2rem;
  }
  
  .modal-header {
    padding: 1rem;
  }
  
  .modal-body {
    padding: 1rem;
  }
  
  .video-container.vertical {
    max-width: 280px;
  }
}


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   PORTFÃ“LIO 3D INTEGRADO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.portfolio-grid-3d{
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:1.5rem;
}

.portfolio-slot{
  min-height:240px;
  aspect-ratio:auto;
  border-radius:24px;
  background:linear-gradient(180deg, rgba(255,255,255,.055), rgba(255,255,255,.022));
  border:1px solid rgba(255,255,255,.08);
  box-shadow:
    0 22px 64px rgba(0,0,0,.42),
    inset 0 1px 0 rgba(255,255,255,.06);
  backdrop-filter:blur(24px);
  -webkit-backdrop-filter:blur(24px);
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  text-align:center;
  padding:2rem 1.4rem;
  transition:transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s cubic-bezier(.22,1,.36,1), border-color .35s cubic-bezier(.22,1,.36,1);
}

.portfolio-slot::before{
  content:'';
  position:absolute;
  inset:-1px;
  border-radius:inherit;
  background:linear-gradient(120deg, transparent 12%, rgba(0,234,255,.16) 38%, rgba(255,0,110,.07) 62%, transparent 88%);
  filter:blur(24px);
  opacity:.34;
  z-index:-1;
  pointer-events:none;
}

.portfolio-slot:hover{
  transform:translateY(-8px) scale(1.01);
  border-color:rgba(0,234,255,.18);
  box-shadow:
    0 30px 84px rgba(0,0,0,.58),
    0 0 40px rgba(0,234,255,.12),
    inset 0 1px 0 rgba(255,255,255,.08);
}

.portfolio-slot-icon{
  font-size:2.6rem;
  margin-bottom:.9rem;
}

.portfolio-slot-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:1.5rem;
  letter-spacing:.04em;
  color:#f2f5fb;
  margin-bottom:.5rem;
}

.portfolio-slot-count{
  font-family:'Space Mono',monospace;
  font-size:.68rem;
  letter-spacing:.18em;
  text-transform:uppercase;
  color:var(--cyan);
}

.portfolio-slot-empty{
  font-size:.8rem;
  color:rgba(232,234,240,.54);
}

.portfolio-modal-3d{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(2,4,10,.96);
  backdrop-filter:blur(12px);
  z-index:1200;
  align-items:center;
  justify-content:center;
  padding:2rem;
}

.portfolio-modal-3d.active{
  display:flex;
}

.portfolio-modal-dialog{
  width:min(100%, 1100px);
  max-height:92vh;
  border-radius:28px;
  border:1px solid rgba(255,255,255,.08);
  background:linear-gradient(180deg, rgba(6,12,22,.92), rgba(3,7,14,.92));
  box-shadow:
    0 30px 90px rgba(0,0,0,.55),
    inset 0 1px 0 rgba(255,255,255,.06);
  overflow:hidden;
}

.portfolio-modal-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:1rem;
  padding:1.25rem 1.4rem;
  border-bottom:1px solid rgba(255,255,255,.07);
}

.portfolio-modal-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:1.8rem;
  letter-spacing:.05em;
  color:var(--cyan);
}

.portfolio-modal-close{
  width:42px;
  height:42px;
  border-radius:50%;
  border:1px solid rgba(0,234,255,.18);
  background:rgba(255,255,255,.03);
  color:var(--text);
  font-size:1.4rem;
  cursor:pointer;
  transition:all .25s ease;
}

.portfolio-modal-close:hover{
  color:var(--cyan);
  background:rgba(0,234,255,.08);
}

.portfolio-modal-body{
  padding:1.4rem 1.4rem 1.8rem;
}

.portfolio-empty-state{
  text-align:center;
  padding:3rem 1rem;
  color:var(--muted);
}
.portfolio-empty-icon{
  font-size:2.6rem;
  margin-bottom:.8rem;
}
.portfolio-empty-state span{
  display:block;
  margin-top:.45rem;
  font-size:.9rem;
}

.portfolio-carousel-shell{
  position:relative;
  width:100%;
  min-height:560px;
  display:flex;
  align-items:center;
  justify-content:center;
  perspective:1400px;
}

.portfolio-carousel-3d{
  position:relative;
  width:min(100%, 840px);
  height:520px;
  transform-style:preserve-3d;
}

.portfolio-3d-card{
  position:absolute;
  inset:0;
  display:flex;
  align-items:center;
  justify-content:center;
  transform-style:preserve-3d;
  transition:
    transform .45s cubic-bezier(.22,1,.36,1),
    opacity .45s cubic-bezier(.22,1,.36,1),
    filter .45s cubic-bezier(.22,1,.36,1);
}

.portfolio-3d-card-inner{
  width:100%;
  height:100%;
  padding:1.25rem;
  border-radius:24px;
  background:linear-gradient(135deg, rgba(0,245,255,.08), rgba(255,0,110,.07));
  border:1px solid rgba(255,255,255,.08);
  box-shadow:
    0 22px 60px rgba(0,0,0,.38),
    inset 0 1px 0 rgba(255,255,255,.05);
  display:flex;
  flex-direction:column;
  overflow:hidden;
}

.portfolio-3d-card-title{
  font-family:'Bebas Neue',sans-serif;
  font-size:1.45rem;
  letter-spacing:.05em;
  color:var(--cyan);
  text-align:center;
  margin-bottom:1rem;
}

.portfolio-3d-video{
  position:relative;
  width:100%;
  flex:1;
  min-height:0;
  background:#000;
  border-radius:16px;
  overflow:hidden;
  border:1px solid rgba(0,234,255,.16);
  box-shadow:0 0 30px rgba(0,245,255,.18);
}

.portfolio-3d-video.horizontal{
  aspect-ratio:16 / 9;
}

.portfolio-3d-video.vertical{
  max-width:310px;
  width:100%;
  aspect-ratio:9 / 16;
  margin:0 auto;
}

.portfolio-3d-video iframe{
  position:absolute;
  inset:0;
  width:100%;
  height:100%;
  border:0;
}

.portfolio-3d-meta{
  margin-top:.9rem;
  text-align:center;
}

.portfolio-3d-meta span{
  font-family:'Space Mono',monospace;
  font-size:.68rem;
  letter-spacing:.16em;
  text-transform:uppercase;
  color:rgba(232,234,240,.62);
}

.portfolio-carousel-nav{
  position:absolute;
  top:50%;
  transform:translateY(-50%);
  width:52px;
  height:52px;
  border-radius:16px;
  border:1px solid rgba(0,234,255,.18);
  background:rgba(255,255,255,.04);
  color:var(--cyan);
  font-size:2rem;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:all .25s ease;
  z-index:5;
}

.portfolio-carousel-nav:hover{
  background:rgba(0,234,255,.10);
  box-shadow:0 0 24px rgba(0,234,255,.16);
}

.portfolio-carousel-nav.left{ left:0; }
.portfolio-carousel-nav.right{ right:0; }

@media (max-width: 980px){
  .portfolio-grid-3d{
    grid-template-columns:1fr;
  }
  .portfolio-carousel-shell{
    min-height:500px;
  }
  .portfolio-carousel-3d{
    width:min(100%, 680px);
    height:460px;
  }
}

@media (max-width: 768px){
  #portfolio{
    padding:5rem 1rem;
  }
  .portfolio-modal-3d{
    padding:1rem;
  }
  .portfolio-modal-dialog{
    border-radius:24px;
  }
  .portfolio-modal-header{
    padding:1rem 1rem .9rem;
  }
  .portfolio-modal-title{
    font-size:1.45rem;
  }
  .portfolio-modal-body{
    padding:1rem 1rem 1.25rem;
  }
  .portfolio-carousel-shell{
    min-height:420px;
  }
  .portfolio-carousel-3d{
    width:100%;
    height:390px;
  }
  .portfolio-3d-card-inner{
    padding:.9rem;
    border-radius:20px;
  }
  .portfolio-3d-card-title{
    font-size:1.15rem;
    margin-bottom:.75rem;
  }
  .portfolio-3d-video.vertical{
    max-width:220px;
  }
  .portfolio-carousel-nav{
    width:42px;
    height:42px;
    font-size:1.5rem;
    border-radius:14px;
  }
}

@media (max-width: 560px){
  .portfolio-slot{
    min-height:200px;
    padding:1.45rem 1rem;
    border-radius:20px;
  }
  .portfolio-slot-icon{
    font-size:2.2rem;
  }
  .portfolio-slot-title{
    font-size:1.28rem;
  }
  .portfolio-carousel-shell{
    min-height:380px;
  }
  .portfolio-carousel-3d{
    height:350px;
  }
  .portfolio-3d-video.vertical{
    max-width:190px;
  }
}

</style>
</head>
<body data-section="hero">
<script>
(function () {
  const ua = navigator.userAgent || '';
  const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  const isTouch = window.matchMedia('(pointer: coarse)').matches || 'ontouchstart' in window;
  const isMobile = window.innerWidth <= 980 || /Android|iPhone|iPad|iPod|Mobile/i.test(ua);
  const lowMemory = typeof navigator.deviceMemory === 'number' && navigator.deviceMemory <= 4;
  const lowCpu = typeof navigator.hardwareConcurrency === 'number' && navigator.hardwareConcurrency <= 4;
  const isSlowNetwork = connection && (connection.effectiveType === '2g' || connection.effectiveType === '3g' || connection.saveData);
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isLiteMode = isMobile && (lowMemory || lowCpu || reduceMotion || isSlowNetwork);

  const root = document.documentElement;
  if (isTouch) root.classList.add('touch-device');
  if (isMobile) root.classList.add('mobile-device');
  if (isLiteMode) root.classList.add('lite-mode');
  if (lowCpu || lowMemory) root.classList.add('low-end');
  if (isSlowNetwork) root.classList.add('slow-network');
})();
</script>

<!-- CURSOR -->
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<div class="light-orb light-orb--hero" aria-hidden="true"></div>
<div class="light-orb light-orb--secondary" aria-hidden="true"></div>

<!-- GRAIN -->
<div class="grain"></div>

<!-- LOADER -->
<div id="loader">
  <div class="loader-logo">DQ</div>
  <div class="loader-bar-wrap"><div class="loader-bar"></div></div>
  <div class="loader-text">Carregando experiÃªncia</div>
</div>

<!-- LETTERBOX -->
<div class="letterbox-top" id="ltTop"></div>
<div class="letterbox-bottom" id="ltBot"></div>

<!-- WHATSAPP FLUTUANTE -->
<a href="https://wa.me/559391929586" target="_blank" rel="noopener noreferrer" class="wa-btn" aria-label="WhatsApp">
  <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
</a>

<!-- NAV -->
<nav id="mainNav">
  <a href="#hero" class="nav-logo">DQ</a>
  <ul class="nav-links" id="navLinks">
    <li><a href="#quem-sou">Sobre</a></li>
    <li><a href="#servicos">ServiÃ§os</a></li>
    <li><a href="#portfolio">PortfÃ³lio</a></li>
    <li><a href="#vira-brasil">Vira Brasil</a></li>
    <li><a href="#contato">Contato</a></li>
  </ul>
  <button class="nav-toggle" id="navToggle" aria-label="Abrir menu" aria-controls="navLinks" aria-expanded="false" type="button">
    <span></span><span></span><span></span>
  </button>
</nav>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  HERO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="hero">
  <div class="hero-bg"></div>
  <div class="hero-noise"></div>
  <div class="hero-vignette"></div>

  <div class="hero-content">
    <div class="hero-panel">
      <div class="hero-kicker">VÃ­deo estratÃ©gico para marcas que querem crescer</div>

      <h1 class="hero-title">
  VocÃª posta<br>
  se dedica<br>
  <span class="text-cyan">mas os resultados nÃ£o vÃªm</span>
</h1>

      <div class="hero-dynamic-wrap">
        <p class="hero-dynamic" aria-live="polite">
          <span id="typingText"></span><span class="hero-cursor">|</span>
        </p>
      </div>

      <div class="hero-actions">
        <a href="https://wa.me/559391929586" class="btn-primary">Solicitar orÃ§amento</a>
        <a href="#portfolio" class="btn-secondary">Ver portfÃ³lio</a>
      </div>
    </div>
  </div>

  <div class="scroll-indicator">
    <span>Role para explorar</span>
    <div class="scroll-line"></div>
  </div>
</section>
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  PROBLEMA
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="problema" class="section-overlap">
  <div class="container">
    <div class="problema-head reveal-right">
      <h2 class="section-title problema-title">SUA EMPRESA AINDA<br><span class="title-accent">PARECE AMADORA</span> ONLINE?</h2>
    </div>

    <div class="problema-grid">
      <div class="glass-card reveal reveal-delay-1">
        <div class="card-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        </div>
        <div class="card-title">Sem planejamento</div>
        <p class="card-desc">VÃ­deos que existem, mas nÃ£o comunicam, nÃ£o engajam e nÃ£o posicionam sua marca onde ela deveria estar.</p>
      </div>

      <div class="glass-card reveal reveal-delay-2">
        <div class="card-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div class="card-title">Imagem sem autoridade</div>
        <p class="card-desc">Sem produÃ§Ã£o profissional, seu negÃ³cio perde credibilidade antes mesmo de falar â€” o visual fala primeiro.</p>
      </div>

      <div class="glass-card reveal reveal-delay-3">
        <div class="card-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div class="card-title">Concorrente Ã  sua frente</div>
        <p class="card-desc">Enquanto vocÃª hesita, quem jÃ¡ investe em vÃ­deo profissional estÃ¡ fechando os clientes que deveriam ser seus.</p>
      </div>
    </div>
  </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  QUEM SOU
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="quem-sou">
  <div class="quem-wrap">
    <div class="quem-visual reveal-left">
      <picture class="quem-picture">
        <source media="(max-width: 980px)" srcset="/assets/img/soueumobile.webp">
        <img src="/assets/img/soueupc.webp" alt="Daniel Queiroz" loading="lazy" decoding="async">
      </picture>

      <div class="quem-provas-mobile" aria-hidden="true">
        <div class="mobile-curve-card mobile-curve-card--1">
          <span class="mobile-curve-value">4 ANOS</span>
          <span class="mobile-curve-label">DIR. COMUNICAÃ‡ÃƒO UAADESAN</span>
        </div>
        <div class="mobile-curve-card mobile-curve-card--2">
          <span class="mobile-curve-value">7 ANOS</span>
          <span class="mobile-curve-label">NO AUDIOVISUAL</span>
        </div>
        <div class="mobile-curve-card mobile-curve-card--3">
          <span class="mobile-curve-value">3 ANOS</span>
          <span class="mobile-curve-label">VOX+</span>
        </div>
        <div class="mobile-curve-card mobile-curve-card--4">
          <span class="mobile-curve-value">+10</span>
          <span class="mobile-curve-label">CAMPANHAS ELEITORAIS</span>
        </div>
      </div>
    </div>

    <div class="quem-content reveal-right">
      <h2 class="quem-title">
        <span class="quem-title-line quem-title-line--small">NÃƒO ENTREGO APENAS VÃDEO</span>
        <span class="quem-title-line quem-title-line--mid">EU ENTREGO</span>
        <span class="quem-title-line quem-title-line--highlight">POSICIONAMENTO</span>
      </h2>
      <p class="quem-subtext">
        Transformo ideias em conteÃºdos que geram atenÃ§Ã£o, autoridade e resultado.
      </p>
      <div class="quem-provas">
        <div class="quem-prova">
          <span class="quem-prova-valor">+10</span>
          <span class="quem-prova-label">campanhas eleitorais</span>
        </div>
        <div class="quem-prova">
          <span class="quem-prova-valor">7 anos</span>
          <span class="quem-prova-label">no audiovisual</span>
        </div>
        <div class="quem-prova">
          <span class="quem-prova-valor">3 anos</span>
          <span class="quem-prova-label">Vox+</span>
        </div>
        <div class="quem-prova">
          <span class="quem-prova-valor">4 anos</span>
          <span class="quem-prova-label">dir. comunicaÃ§Ã£o UAADESAN</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  SERVIÃ‡OS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="servicos" class="section-overlap">
  <div class="container">
    <div class="reveal">
      <h2 class="section-title">Como posso<br><span class="text-cyan">transformar</span> sua marca</h2>
    </div>
    <div class="servicos-grid">
      <div class="servico-card reveal reveal-delay-1">
        <div class="servico-num">01</div>
        <div class="servico-icon">
          <svg viewBox="0 0 24 24"><path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg>
        </div>
        <div class="servico-title">Publicidade PolÃ­tica</div>
        <p class="servico-desc">VÃ­deos estratÃ©gicos para campanhas eleitorais. ConstruÃ§Ã£o de imagem pÃºblica com produÃ§Ã£o rÃ¡pida, roteiro claro e comunicaÃ§Ã£o direta que convence.</p>
        <div class="servico-tag">+10 campanhas produzidas</div>
      </div>

      <div class="servico-card destaque reveal reveal-delay-2">
        <div class="servico-num">02</div>
        <div class="servico-icon">
          <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div class="servico-title">Marketing ImobiliÃ¡rio</div>
        <p class="servico-desc">Tours cinematogrÃ¡ficos com drone. Mostre o imÃ³vel ou projeto antes mesmo de ser construÃ­do â€” tecnologia que encanta clientes e acelera vendas.</p>
        <div class="servico-tag">Tours cinematogrÃ¡ficos</div>
      </div>

      <div class="servico-card reveal reveal-delay-3">
        <div class="servico-num">03</div>
        <div class="servico-icon">
          <svg viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
        </div>
        <div class="servico-title">Eventos & MÃ­dia</div>
        <p class="servico-desc">Cobertura profissional de eventos corporativos, religiosos e culturais. Credenciado como Videomaker MÃ­dia Oficial no Vira Brasil 2025/2026.</p>
        <div class="servico-tag">Credencial Oficial de MÃ­dia</div>
      </div>
    </div>
  </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  PORTFÃ“LIO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->

<section id="portfolio">
  <div class="container">
    <div class="reveal">
      <h2 class="section-title">O trabalho<br><span class="text-cyan">fala por si</span></h2>
    </div>

    <div class="portfolio-grid portfolio-grid-3d">
      <?php foreach ($slots as $slotKey => $slotInfo):
        $videosDoSlot = $videosPorSlot[$slotKey] ?? [];
        $temVideos = !empty($videosDoSlot);
      ?>
        <div class="portfolio-slot reveal" onclick="abrirCarrossel('<?= $slotKey ?>')">
          <div class="portfolio-slot-icon" style="font-family:'Bebas Neue',sans-serif; font-size:3rem; color:var(--cyan); border:1px solid rgba(0,245,255,0.2); width:64px; height:64px; display:flex; align-items:center; justify-content:center; border-radius:12px; margin-bottom:1rem;"><?= $slotInfo['letter'] ?></div>
          <div class="portfolio-slot-title" style="font-family:'Bebas Neue',sans-serif; font-size:1.6rem; letter-spacing:0.04em; color:var(--text);"><?= $slotInfo['label'] ?></div>

          <?php if ($temVideos): ?>
            <div class="portfolio-slot-count" style="font-family:'Space Mono',monospace; font-size:0.6rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.1em; margin-top:0.5rem;">
              <?= count($videosDoSlot) ?> vÃ­deo(s)
            </div>
          <?php else: ?>
            <div class="portfolio-slot-empty" style="font-family:'Space Mono',monospace; font-size:0.55rem; color:var(--muted); text-transform:uppercase; opacity:0.6;">
              VÃ­deos em breve
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php foreach ($slots as $slotKey => $slotInfo):
  $videosDoSlot = $videosPorSlot[$slotKey] ?? [];
  $temVideos = !empty($videosDoSlot);
?>
  <div class="portfolio-modal-3d" id="modal-<?= $slotKey ?>">
    <div class="portfolio-modal-dialog" onclick="event.stopPropagation()">
      <div class="portfolio-modal-header">
        <div class="portfolio-modal-title"><?= $slotInfo['label'] ?></div>
        <button class="portfolio-modal-close" onclick="fecharCarrossel('<?= $slotKey ?>')" aria-label="Fechar">âœ•</button>
      </div>

      <div class="portfolio-modal-body">
        <?php if ($temVideos): ?>
          <div class="portfolio-carousel-shell">
            <div class="portfolio-carousel-3d" id="carousel-<?= $slotKey ?>">
              <?php foreach ($videosDoSlot as $index => $video): ?>
                <div class="portfolio-3d-card" data-card-index="<?= $index ?>">
                  <div class="portfolio-3d-card-inner">
                    <div class="portfolio-3d-card-title"><?= htmlspecialchars($video['titulo']) ?></div>
                    <div class="portfolio-3d-video <?= $video['orientacao'] ?>">
                      <iframe
                        id="iframe-<?= $slotKey ?>-<?= $index ?>"
                        src="https://www.youtube.com/embed/<?= $video['youtube_id'] ?>?enablejsapi=1&rel=0"
                        title="<?= htmlspecialchars($video['titulo']) ?>"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                      </iframe>
                    </div>
                    <div class="portfolio-3d-meta">
                      <span>VÃ­deo <?= ($index + 1) ?> de <?= count($videosDoSlot) ?></span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php if (count($videosDoSlot) > 1): ?>
              <button class="portfolio-carousel-nav left" onclick="carrosselAnterior('<?= $slotKey ?>')" aria-label="VÃ­deo anterior">â®</button>
              <button class="portfolio-carousel-nav right" onclick="carrosselProximo('<?= $slotKey ?>')" aria-label="PrÃ³ximo vÃ­deo">â¯</button>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="portfolio-empty-state">
            <div class="portfolio-empty-icon">ðŸ“¹</div>
            <p>Nenhum vÃ­deo adicionado ainda para esta categoria.</p>
            <span>Volte em breve.</span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  PROCESSO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->

<section id="processo">
  <div class="processo-wrap">
    <div class="reveal" style="text-align:center;margin-bottom:1rem">
      <h2 class="section-title processo-title-dynamic" style="text-align:center"><span id="fadeText" class="fade-text"></span></h2>
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
        <div class="step-title">EstratÃ©gia</div>
        <div class="step-desc">Plano de conteÃºdo personalizado para sua marca</div>
      </div>
      <div class="step reveal reveal-delay-3">
        <div class="step-dot">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
          <div class="step-num">3</div>
        </div>
        <div class="step-title">ProduÃ§Ã£o</div>
        <div class="step-desc">CaptaÃ§Ã£o e ediÃ§Ã£o cinematogrÃ¡fica profissional</div>
      </div>
      <div class="step reveal reveal-delay-4">
        <div class="step-dot">
          <svg viewBox="0 0 24 24"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
          <div class="step-num">4</div>
        </div>
        <div class="step-title">PublicaÃ§Ã£o</div>
        <div class="step-desc">GestÃ£o de postagens e interaÃ§Ãµes nas redes</div>
      </div>
      <div class="step reveal reveal-delay-4">
        <div class="step-dot">
          <svg viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          <div class="step-num">5</div>
        </div>
        <div class="step-title">AnÃ¡lise</div>
        <div class="step-desc">Monitoramento de resultados e ajustes estratÃ©gicos</div>
      </div>
    </div>
  </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  DEPOIMENTOS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="depoimentos">
  <div class="container">
    <div class="reveal" style="text-align:center">
      <h2 class="section-title" style="text-align:center">O QUE OS CLIENTES ACHARAM</h2>
    </div>
    <div class="depo-grid">
      <?php if (!empty($depoimentos)): ?>
        <?php foreach ($depoimentos as $index => $d): ?>
          <div class="holo-card reveal <?= $index > 0 ? 'reveal-delay-' . min($index, 4) : '' ?>">
            <div class="depo-top">
              <div class="depo-avatar">
                <?php if (!empty($d['foto'])): ?>
                  <img src="assets/uploads/depoimentos/<?= rawurlencode($d['foto']) ?>" alt="<?= e($d['nome']) ?>" loading="lazy" decoding="async">
                <?php else: ?>
                  <?= e(initial((string) ($d['nome'] ?? ''))) ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="depo-name"><?= e($d['nome']) ?></div>
                <div class="depo-role"><?= e($d['empresa'] ?? '') ?></div>
              </div>
            </div>
            <div class="depo-stars">â˜…â˜…â˜…â˜…â˜…</div>
            <div class="depo-text">â€œ<?= nl2br(e($d['comentario'])) ?>â€</div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="depo-waiting reveal">
          â³ Depoimentos reais sendo coletados
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  FAQ
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="faq">
  <div class="container">
    <div class="reveal" style="text-align:center">
      <h2 class="section-title" style="text-align:center">Perguntas <span class="text-cyan">frequentes</span></h2>
    </div>
    <div class="faq-wrap">
      <div class="faq-item reveal">
        <button class="faq-q" type="button" aria-expanded="false" aria-controls="faq-a-1" id="faq-q-1">
          Quanto custa uma produÃ§Ã£o?
          <div class="faq-icon" aria-hidden="true">+</div>
        </button>
        <div class="faq-a" id="faq-a-1" role="region" aria-labelledby="faq-q-1" hidden>Cada projeto Ã© Ãºnico e o investimento varia conforme o escopo, complexidade e tipo de produÃ§Ã£o. Entre em contato para receber um orÃ§amento personalizado e detalhado para o seu projeto.</div>
      </div>
      <div class="faq-item reveal reveal-delay-1">
        <button class="faq-q" type="button" aria-expanded="false" aria-controls="faq-a-2" id="faq-q-2">
          Quanto tempo leva para entregar?
          <div class="faq-icon" aria-hidden="true">+</div>
        </button>
        <div class="faq-a" id="faq-a-2" role="region" aria-labelledby="faq-q-2" hidden>O prazo de entrega depende do tipo e complexidade do projeto. Esse detalhe Ã© definido no briefing inicial, onde alinhamos todas as expectativas antes de comeÃ§ar.</div>
      </div>
      <div class="faq-item reveal reveal-delay-2">
        <button class="faq-q" type="button" aria-expanded="false" aria-controls="faq-a-3" id="faq-q-3">
          VocÃª atende fora de SantarÃ©m?
          <div class="faq-icon" aria-hidden="true">+</div>
        </button>
        <div class="faq-a" id="faq-a-3" role="region" aria-labelledby="faq-q-3" hidden>Sim! JÃ¡ atuei em SÃ£o Paulo e em outros estados para eventos e campanhas. A distÃ¢ncia nÃ£o Ã© um obstÃ¡culo para projetos que merecem produÃ§Ã£o de qualidade.</div>
      </div>
      <div class="faq-item reveal reveal-delay-3">
        <button class="faq-q" type="button" aria-expanded="false" aria-controls="faq-a-6" id="faq-q-6">
          NÃ£o tenho experiÃªncia diante das cÃ¢meras.
          <div class="faq-icon" aria-hidden="true">+</div>
        </button>
        <div class="faq-a" id="faq-a-6" role="region" aria-labelledby="faq-q-6" hidden>Sem problema! Dou direÃ§Ã£o durante toda a gravaÃ§Ã£o â€” roteiro, postura e forma de falar. A ideia Ã© deixar vocÃª confortÃ¡vel para transmitir a mensagem de forma natural e autÃªntica.</div>
      </div>
      <div class="faq-item reveal reveal-delay-4">
        <button class="faq-q" type="button" aria-expanded="false" aria-controls="faq-a-7" id="faq-q-7">
          VocÃª usa inteligÃªncia artificial nas produÃ§Ãµes?
          <div class="faq-icon" aria-hidden="true">+</div>
        </button>
        <div class="faq-a" id="faq-a-7" role="region" aria-labelledby="faq-q-7" hidden>Sim! Uso IA na ediÃ§Ã£o e em projetos imobiliÃ¡rios com drone + reconstruÃ§Ã£o 3D â€” uma tecnologia que permite mostrar um empreendimento antes mesmo de ser construÃ­do.</div>
      </div>
    </div>
  </div>
</section>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  CTA FINAL
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="contato" class="section-overlap">
  <div class="contato-bg"></div>
  <div class="contato-content reveal">
    <div class="contato-pre">Pronto para o prÃ³ximo nÃ­vel?</div>
    <h2 class="contato-title">
      Transforme sua marca com<br>
      <span>vÃ­deos que geram resultado</span>
    </h2>
    <p class="contato-sub">
      HistÃ³ria, estratÃ©gia e emoÃ§Ã£o em cada produÃ§Ã£o.<br>
      Do briefing Ã  entrega â€” sem surpresas.
    </p>
    <div class="contato-escassez">Agenda limitada Â· Poucos projetos disponÃ­veis por mÃªs</div>
    <div class="contato-btns">
      <a href="https://wa.me/559391929586" class="btn-primary">
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
      <a href="https://instagram.com/danielqueirozd.q" target="_blank" rel="noopener noreferrer" class="contato-link">
        <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        @danielqueirozd.q
      </a>
      <a href="https://wa.me/559391929586" target="_blank" rel="noopener noreferrer" class="contato-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.68 12 19.79 19.79 0 01.61 3.41 2 2 0 012.6 1.24h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.83a16 16 0 006.29 6.29l.95-.96a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
        (93) 9192-9586
      </a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-logo">DQ</div>
  <div class="footer-copy">Â© 2026 Daniel Queiroz â€” Todos os direitos reservados</div>
</footer>

<script>
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isLiteMode = document.documentElement.classList.contains('lite-mode');
const isMobileDevice = document.documentElement.classList.contains('mobile-device');


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   CURSOR
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const cursor = document.getElementById('cursor');
const ring = document.getElementById('cursorRing');

if (!prefersReducedMotion && !isLiteMode && cursor && ring && window.innerWidth > 768) {
  let mx = 0, my = 0, rx = 0, ry = 0;

  document.addEventListener('mousemove', (e) => {
    mx = e.clientX;
    my = e.clientY;
    cursor.style.left = mx + 'px';
    cursor.style.top = my + 'px';
  });

  function animRing() {
    rx += (mx - rx) * 0.12;
    ry += (my - ry) * 0.12;
    ring.style.left = rx + 'px';
    ring.style.top = ry + 'px';
    requestAnimationFrame(animRing);
  }

  animRing();
} else {
  document.body.style.cursor = 'auto';
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   LOADER
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
window.addEventListener('load', () => {
  const loader = document.getElementById('loader');
  const ltTop = document.getElementById('ltTop');
  const ltBot = document.getElementById('ltBot');

  if (loader) {
    loader.classList.add('hide');
  }

  setTimeout(() => {
    if (ltTop) ltTop.classList.add('open');
    if (ltBot) ltBot.classList.add('open');
  }, 120);
});

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   NAV
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const nav = document.getElementById('mainNav');
const navLinks = document.getElementById('navLinks');
const navToggle = document.getElementById('navToggle');

window.addEventListener('scroll', () => {
  if (nav) {
    nav.classList.toggle('scrolled', window.scrollY > 60);
  }
});

function openNav() {
  if (!navLinks || !navToggle) return;
  navLinks.classList.add('open');
  navToggle.setAttribute('aria-expanded', 'true');
  document.body.classList.add('menu-open');
}

function closeNav() {
  if (!navLinks || !navToggle) return;
  navLinks.classList.remove('open');
  navToggle.setAttribute('aria-expanded', 'false');
  document.body.classList.remove('menu-open');
}

function toggleNav() {
  if (!navLinks) return;
  if (navLinks.classList.contains('open')) {
    closeNav();
  } else {
    openNav();
  }
}

if (navToggle) {
  navToggle.addEventListener('click', toggleNav);
}

document.querySelectorAll('#navLinks a').forEach((link) => {
  link.addEventListener('click', closeNav);
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeNav();
  }
});

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SCROLL REVEAL
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const revealEls = document.querySelectorAll('.reveal,.reveal-left,.reveal-right');

if ('IntersectionObserver' in window && !prefersReducedMotion) {
  const revealObs = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) entry.target.classList.add('visible');
    });
  }, { threshold: 0.12 });

  revealEls.forEach((el) => revealObs.observe(el));
} else {
  revealEls.forEach((el) => el.classList.add('visible'));
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   BACKGROUND POR SEÃ‡ÃƒO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const sections = document.querySelectorAll('section[id]');

if ('IntersectionObserver' in window) {
  const bgObs = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting && entry.intersectionRatio > 0.3) {
        document.body.setAttribute('data-section', entry.target.id);
      }
    });
  }, { threshold: 0.3 });

  sections.forEach((s) => bgObs.observe(s));
}


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SECTION VIEWPORT ACTIVATION
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const viewportSections = document.querySelectorAll('section[id]');

if ('IntersectionObserver' in window) {
  const sectionFxObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      const isActive = entry.isIntersecting && entry.intersectionRatio > 0.24;
      entry.target.classList.toggle('is-active', isActive);
    });
  }, {
    threshold: [0, 0.24, 0.5]
  });

  viewportSections.forEach((section) => sectionFxObserver.observe(section));
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   FAQ
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
document.querySelectorAll('.faq-q').forEach((btn) => {
  btn.addEventListener('click', () => {
    const item = btn.closest('.faq-item');
    const panelId = btn.getAttribute('aria-controls');
    const panel = panelId ? document.getElementById(panelId) : null;
    const willOpen = btn.getAttribute('aria-expanded') !== 'true';

    document.querySelectorAll('.faq-item').forEach((faqItem) => {
      faqItem.classList.remove('open');
    });

    document.querySelectorAll('.faq-q').forEach((faqBtn) => {
      faqBtn.setAttribute('aria-expanded', 'false');
    });

    document.querySelectorAll('.faq-a').forEach((faqPanel) => {
      faqPanel.hidden = true;
    });

    if (willOpen && item && panel) {
      item.classList.add('open');
      btn.setAttribute('aria-expanded', 'true');
      panel.hidden = false;
    }
  });
});


/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   HERO TYPING
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const heroTypingEl = document.getElementById('heroTyping');
const heroTypingMessages = [
  'VocÃª posta',
  'Investe',
  'Se dedica',
  'Mas os resultados nÃ£o vÃªm?',
  'NÃ£o Ã© falta de esforÃ§o\nÃ‰ falta de direÃ§Ã£o',
  '<span class="accent">NÃ£o entrego sÃ³ vÃ­deos</span>\n<span class="accent">Entrego posicionamento</span>',
  'Pra que cada conteÃºdo seu\ntenha propÃ³sito, impacto e resultado',
  'Porque aparecer nÃ£o basta\nVocÃª precisa ser lembrado'
];

function playHeroTyping(element, messages, typingSpeed = 34, deletingSpeed = 18, holdDelay = 1200, nextDelay = 240) {
  if (!element || !messages.length) return;

  let messageIndex = 0;
  let charIndex = 0;
  let isDeleting = false;

  function stripHtml(value) {
    const temp = document.createElement('div');
    temp.innerHTML = value;
    return temp.textContent || temp.innerText || '';
  }

  function renderPartial(markup, charsToShow) {
    let visible = 0;
    let result = '';
    let i = 0;
    const stack = [];

    while (i < markup.length && visible < charsToShow) {
      if (markup[i] === '<') {
        const end = markup.indexOf('>', i);
        if (end === -1) break;
        const tag = markup.slice(i, end + 1);
        result += tag;

        const open = tag.match(/^<([a-zA-Z0-9]+)(\s|>)/);
        const close = tag.match(/^<\/([a-zA-Z0-9]+)>/);

        if (open && !tag.startsWith('</') && !tag.endsWith('/>')) {
          stack.push(open[1]);
        } else if (close) {
          const idx = stack.lastIndexOf(close[1]);
          if (idx !== -1) stack.splice(idx, 1);
        }

        i = end + 1;
      } else {
        result += markup[i];
        visible += 1;
        i += 1;
      }
    }

    for (let j = stack.length - 1; j >= 0; j -= 1) {
      result += `</${stack[j]}>`;
    }

    // âœ… SEGURO: Usar textContent + appendChild para elementos HTML
    element.innerHTML = '';  // Limpar conteÃºdo
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = result;  // Usa DOMParser na verdade
    while (tempDiv.firstChild) {
      element.appendChild(tempDiv.firstChild);
    }
    const cursor = document.createElement('span');
    cursor.className = 'hero-cursor';
    cursor.textContent = '|';
    element.appendChild(cursor);
  }

  function tick() {
    const markup = messages[messageIndex];
    const plain = stripHtml(markup);

    if (!isDeleting) {
      charIndex += 1;
      renderPartial(markup, charIndex);

      if (charIndex < plain.length) {
        setTimeout(tick, typingSpeed);
      } else {
        isDeleting = true;
        setTimeout(tick, holdDelay);
      }
    } else {
      charIndex -= 1;

      if (charIndex > 0) {
        renderPartial(markup, charIndex);
        setTimeout(tick, deletingSpeed);
      } else {
        // âœ… SEGURO: Usar textContent
        element.innerHTML = '';
        const cursorSpan = document.createElement('span');
        cursorSpan.className = 'hero-cursor';
        cursorSpan.textContent = '|';
        element.appendChild(cursorSpan);
        isDeleting = false;
        messageIndex = (messageIndex + 1) % messages.length;
        setTimeout(tick, nextDelay);
      }
    }
  }

  tick();
}

if (heroTypingEl) {
  if (prefersReducedMotion) {
    // âœ… SEGURO: Usar textContent
    heroTypingEl.textContent = heroTypingMessages[0];
  } else {
    playHeroTyping(heroTypingEl, heroTypingMessages, 34, 18, 1200, 240);
  }
}


</script>

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// ðŸ” RATE LIMITING (DESCOMENTE PARA ATIVAR)
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
/*
class RateLimiter {
    constructor(maxCalls = 100, timeWindow = 60000) {
        this.maxCalls = maxCalls;
        this.timeWindow = timeWindow;
        this.calls = {};
    }

    isAllowed(key = 'default') {
        const now = Date.now();
        
        if (!this.calls[key]) {
            this.calls[key] = [];
        }

        // Remover chamadas antigas
        this.calls[key] = this.calls[key].filter(time => now - time < this.timeWindow);

        if (this.calls[key].length >= this.maxCalls) {
            console.warn('Rate limit exceeded for key:', key);
            return false;
        }

        this.calls[key].push(now);
        return true;
    }
}

// Uso:
// const limiter = new RateLimiter(100, 60000);  // 100 chamadas por minuto
// if (!limiter.isAllowed('user-action')) {
//     alert('Muitas requisiÃ§Ãµes. Tente novamente em alguns segundos.');
//     return;
// }
*/

<script>
const texts = [
  "NÃ£o Ã© falta de esforÃ§o",
  "Ã‰ falta de direÃ§Ã£o",
  "VocÃª estÃ¡ fazendo",
  "Mas nÃ£o estÃ¡ construindo",
  "ConteÃºdo sem estratÃ©gia nÃ£o gera resultado",
  "VocÃª aparece",
  "Mas nÃ£o Ã© lembrado",
  "NÃ£o Ã© sobre postar mais",
  "Ã‰ sobre comunicar melhor",
];

let index = 0;
let charIndex = 0;
let currentText = "";
let isDeleting = false;

const el = document.getElementById("typingText");

function typeEffect(){
  if(!el) return;

  const fullText = texts[index];

  if(isDeleting){
    currentText = fullText.substring(0, charIndex - 1);
    charIndex--;
  }else{
    currentText = fullText.substring(0, charIndex + 1);
    charIndex++;
  }

  el.textContent = currentText;

  let speed = isDeleting ? 26 : 44;

  if(!isDeleting && charIndex >= fullText.length){
    speed = 1450;
    isDeleting = true;
  } else if(isDeleting && charIndex <= 0){
    isDeleting = false;
    index = (index + 1) % texts.length;
    speed = 280;
  }

  setTimeout(typeEffect, speed);
}

document.addEventListener("DOMContentLoaded", typeEffect);
</script>





<script>
document.addEventListener("DOMContentLoaded", function () {
  const el = document.getElementById("fadeText");
  if (!el) return;

  const words = [
    { text: "Simples", accent: false },
    { text: "EstratÃ©gico", accent: false },
    { text: "Sem surpresas", accent: true }
  ];

  let index = 0;

  function renderWord(item) {
    el.textContent = item.text;
    el.classList.toggle("is-accent", item.accent);
  }

  renderWord(words[0]);
  index = 1;

  setInterval(() => {
    el.classList.add("hidden");

    setTimeout(() => {
      renderWord(words[index]);
      el.classList.remove("hidden");
      index = (index + 1) % words.length;
    }, 420);
  }, 2200);
});
</script>


<script>
/* === PORTFÃ“LIO 3D === */
function pausarVideosPorSlot(slotKey) {
  document.querySelectorAll(`#carousel-${slotKey} iframe`).forEach((iframe) => {
    try {
      iframe.contentWindow.postMessage(JSON.stringify({
        event: 'command',
        func: 'pauseVideo',
        args: ''
      }), '*');
    } catch (e) {}
  });
}

function pausarTodosOsVideos() {
  document.querySelectorAll('[id^="carousel-"] iframe').forEach((iframe) => {
    try {
      iframe.contentWindow.postMessage(JSON.stringify({
        event: 'command',
        func: 'pauseVideo',
        args: ''
      }), '*');
    } catch (e) {}
  });
}

const carrosselState = {};

function abrirCarrossel(slotKey) {
  pausarTodosOsVideos();
  const modal = document.getElementById('modal-' + slotKey);
  if (!modal) return;
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
  resetCarrossel(slotKey);
}

function fecharCarrossel(slotKey) {
  pausarTodosOsVideos();
  const modal = document.getElementById('modal-' + slotKey);
  if (!modal) return;
  modal.classList.remove('active');
  document.body.style.overflow = '';
}

function resetCarrossel(slotKey) {
  carrosselState[slotKey] = 0;
  atualizarCarrossel(slotKey);
}

function carrosselProximo(slotKey) {
  const carousel = document.getElementById('carousel-' + slotKey);
  if (!carousel) return;
  const cards = carousel.querySelectorAll('.portfolio-3d-card');
  if (!cards.length) return;
  pausarVideosPorSlot(slotKey);
  carrosselState[slotKey] = ((carrosselState[slotKey] ?? 0) + 1) % cards.length;
  atualizarCarrossel(slotKey);
}

function carrosselAnterior(slotKey) {
  const carousel = document.getElementById('carousel-' + slotKey);
  if (!carousel) return;
  const cards = carousel.querySelectorAll('.portfolio-3d-card');
  if (!cards.length) return;
  pausarVideosPorSlot(slotKey);
  carrosselState[slotKey] = ((carrosselState[slotKey] ?? 0) - 1 + cards.length) % cards.length;
  atualizarCarrossel(slotKey);
}

function atualizarCarrossel(slotKey) {
  const carousel = document.getElementById('carousel-' + slotKey);
  if (!carousel) return;

  const cards = Array.from(carousel.querySelectorAll('.portfolio-3d-card'));
  const active = carrosselState[slotKey] ?? 0;
  const total = cards.length;
  if (!total) return;

  cards.forEach((card, index) => {
    let rawDiff = index - active;
    if (rawDiff > total / 2) rawDiff -= total;
    if (rawDiff < -total / 2) rawDiff += total;

    const abs = Math.abs(rawDiff);
    const isActive = rawDiff === 0;
    const translateX = rawDiff * 15;
    const translateZ = isActive ? 0 : -180 - (abs - 1) * 120;
    const rotateY = rawDiff * -26;
    const scale = isActive ? 1 : Math.max(0.72, 1 - abs * 0.12);
    const opacity = abs > 2 ? 0 : (isActive ? 1 : 0.46);
    const blur = isActive ? 0 : Math.min(10, abs * 3.5);

    card.style.transform = `translateX(${translateX}%) translateZ(${translateZ}px) rotateY(${rotateY}deg) scale(${scale})`;
    card.style.opacity = opacity;
    card.style.filter = `blur(${blur}px)`;
    card.style.pointerEvents = isActive ? 'auto' : 'none';
    card.style.zIndex = String(100 - abs);
  });
}

document.querySelectorAll('.portfolio-modal-3d').forEach((modal) => {
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      fecharCarrossel(modal.id.replace('modal-', ''));
    }
  });
});

document.addEventListener('keydown', (e) => {
  const activeModal = document.querySelector('.portfolio-modal-3d.active');
  if (!activeModal) return;

  const slotKey = activeModal.id.replace('modal-', '');

  if (e.key === 'Escape') {
    fecharCarrossel(slotKey);
  } else if (e.key === 'ArrowRight') {
    carrosselProximo(slotKey);
  } else if (e.key === 'ArrowLeft') {
    carrosselAnterior(slotKey);
  }
});

window.addEventListener('blur', pausarTodosOsVideos);
</script>

</body>
</html>

