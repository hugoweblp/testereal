<?php
require_once 'config.php';
verificarLogin();

$db = getDB();

// Buscar vídeos agrupados por slot
$videos = $db->query("SELECT * FROM portfolio_videos ORDER BY slot, ordem ASC")->fetchAll();
$videosPorSlot = [];
foreach ($videos as $v) {
    $videosPorSlot[$v['slot']][] = $v;
}

// Buscar fotos da galeria
$fotos = $db->query("SELECT * FROM gallery_photos ORDER BY ordem ASC")->fetchAll();

// Buscar depoimentos
$depoimentos = $db->query("SELECT * FROM depoimentos ORDER BY id DESC")->fetchAll();

// Slots disponíveis
$slots = [
    'politica'    => ['label' => 'Publicidade Política',    'icon' => '🎬'],
    'imoveis'     => ['label' => 'Marketing Imobiliário',   'icon' => '🏠'],
    'eventos'     => ['label' => 'Cobertura de Eventos',    'icon' => '🎥'],
    'audiovisual' => ['label' => 'Produção Audiovisual',    'icon' => '🎞️'],
];

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Admin DQ</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--cyan:#00f5ff;--magenta:#ff006e;--gold:#ffd700;--dark:#02040a;--dark2:#060c16;--text:#e8eaf0;--muted:#556070;--border:rgba(0,245,255,.12)}
body{background:var(--dark);color:var(--text);font-family:'Barlow',sans-serif;min-height:100vh}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,245,255,.012) 1px,transparent 1px),linear-gradient(90deg,rgba(0,245,255,.012) 1px,transparent 1px);background-size:80px 80px;pointer-events:none;z-index:0}

/* HEADER */
header{position:sticky;top:0;z-index:100;background:rgba(2,4,10,.95);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between}
.h-logo{font-family:'Bebas Neue',sans-serif;font-size:1.8rem;color:transparent;background:linear-gradient(135deg,var(--cyan),var(--magenta));-webkit-background-clip:text;background-clip:text}
.h-info{font-family:'Space Mono',monospace;font-size:.6rem;color:var(--muted);letter-spacing:.2em}
.h-info span{color:var(--cyan)}
.btn-logout{font-family:'Space Mono',monospace;font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:.4rem .8rem;transition:all .2s}
.btn-logout:hover{color:var(--magenta);border-color:var(--magenta)}

/* LAYOUT */
main{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:2rem}
.section-title{font-family:'Bebas Neue',sans-serif;font-size:2rem;letter-spacing:.05em;color:var(--cyan);margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem}
.section-title::after{content:'';flex:1;height:1px;background:var(--border)}

/* MENSAGEM */
.msg{padding:.8rem 1.2rem;margin-bottom:1.5rem;font-family:'Space Mono',monospace;font-size:.7rem}
.msg.ok{background:rgba(0,245,255,.08);border:1px solid rgba(0,245,255,.25);color:var(--cyan)}
.msg.erro{background:rgba(255,0,110,.08);border:1px solid rgba(255,0,110,.25);color:#ff6b9d}

/* SLOTS */
.slots-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(480px,1fr));gap:1.5rem;margin-bottom:3rem}
.slot-card{background:rgba(6,12,22,.8);border:1px solid var(--border);padding:1.5rem}
.slot-card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border)}
.slot-card-title{font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:.05em}
.slot-count{font-family:'Space Mono',monospace;font-size:.6rem;color:var(--muted)}

/* FORM ADICIONAR VÍDEO */
.add-video-form{display:flex;gap:.8rem;margin-bottom:1.2rem;flex-wrap:wrap}
.add-video-form input{flex:1;min-width:200px;background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--text);font-family:'Barlow',sans-serif;font-size:.9rem;padding:.6rem .9rem;outline:none;transition:border-color .2s}
.add-video-form input:focus{border-color:var(--cyan)}
.btn-add{background:var(--cyan);color:#000;font-family:'Space Mono',monospace;font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;padding:.6rem 1.2rem;border:none;cursor:pointer;font-weight:700;white-space:nowrap;transition:opacity .2s}
.btn-add:hover{opacity:.8}

/* LISTA DE VÍDEOS */
.video-list{display:flex;flex-direction:column;gap:.6rem}
.video-item{display:flex;align-items:center;gap:.8rem;background:rgba(255,255,255,.03);border:1px solid var(--border);padding:.7rem 1rem}
.video-thumb{width:60px;height:34px;background:#000;flex-shrink:0;overflow:hidden}
.video-thumb img{width:100%;height:100%;object-fit:cover}
.video-info{flex:1;min-width:0}
.video-titulo{font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.video-url{font-family:'Space Mono',monospace;font-size:.55rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.btn-del{background:none;border:1px solid rgba(255,0,110,.3);color:var(--magenta);font-size:.7rem;padding:.3rem .6rem;cursor:pointer;transition:all .2s;flex-shrink:0}
.btn-del:hover{background:rgba(255,0,110,.1)}
.empty{font-family:'Space Mono',monospace;font-size:.65rem;color:var(--muted);padding:.8rem;text-align:center;border:1px dashed var(--border)}

/* GALERIA */
.gallery-section{margin-bottom:3rem}
.upload-area{border:2px dashed var(--border);padding:2rem;text-align:center;margin-bottom:1.5rem;transition:border-color .2s;cursor:pointer}
.upload-area:hover{border-color:var(--cyan)}
.upload-area input{display:none}
.upload-label{font-family:'Space Mono',monospace;font-size:.7rem;color:var(--muted);letter-spacing:.15em;cursor:pointer}
.upload-label span{color:var(--cyan)}
.btn-upload{background:var(--cyan);color:#000;font-family:'Space Mono',monospace;font-size:.65rem;letter-spacing:.15em;text-transform:uppercase;padding:.7rem 1.5rem;border:none;cursor:pointer;font-weight:700;margin-top:1rem;transition:opacity .2s}
.btn-upload:hover{opacity:.8}
.photos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.8rem}
.photo-item{position:relative;aspect-ratio:4/3;overflow:hidden;border:1px solid var(--border)}
.photo-item img{width:100%;height:100%;object-fit:cover}
.photo-del{position:absolute;top:.3rem;right:.3rem;background:rgba(255,0,110,.8);color:#fff;border:none;width:24px;height:24px;cursor:pointer;font-size:.8rem;display:flex;align-items:center;justify-content:center}

/* SENHA */
.senha-section{background:rgba(6,12,22,.8);border:1px solid var(--border);padding:1.5rem;margin-bottom:2rem}
.senha-form{display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end}
.senha-form .form-group{flex:1;min-width:180px}
.senha-form label{font-family:'Space Mono',monospace;font-size:.6rem;color:var(--muted);letter-spacing:.15em;display:block;margin-bottom:.4rem;text-transform:uppercase}
.senha-form input{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--text);font-family:'Barlow',sans-serif;font-size:.9rem;padding:.6rem .9rem;outline:none}
.senha-form input:focus{border-color:var(--cyan)}
</style>
</head>
<body>

<header>
  <div class="h-logo">DQ Admin</div>
  <div class="h-info">Logado como <span><?= htmlspecialchars($_SESSION['admin_usuario']) ?></span></div>
  <a href="logout.php" class="btn-logout">Sair</a>
</header>

<main>
  <?php if ($msg === 'ok'): ?>
    <div class="msg ok">✓ Operação realizada com sucesso.</div>
  <?php elseif ($msg === 'erro'): ?>
    <div class="msg erro">✗ Ocorreu um erro. Tente novamente.</div>
  <?php elseif ($msg === 'senha_ok'): ?>
    <div class="msg ok">✓ Senha atualizada com sucesso.</div>
  <?php endif; ?>

  <!-- ═══ PORTFÓLIO ═══ -->
  <div class="section-title">Portfólio — Vídeos</div>
  <div class="slots-grid">
    <?php foreach ($slots as $key => $slot): ?>
    <div class="slot-card">
      <div class="slot-card-header">
        <div class="slot-card-title"><?= $slot['icon'] ?> <?= $slot['label'] ?></div>
        <div class="slot-count"><?= count($videosPorSlot[$key] ?? []) ?> vídeo(s)</div>
      </div>

      <!-- Formulário adicionar vídeo -->
      <form method="POST" action="acoes.php">
        <input type="hidden" name="acao" value="add_video">
        <input type="hidden" name="slot" value="<?= $key ?>">
        <div class="add-video-form">
          <input type="text" name="titulo" placeholder="Título do vídeo" required>
          <input type="text" name="youtube_url" placeholder="Link do YouTube" required>
          <button type="submit" class="btn-add">+ Adicionar</button>
        </div>
      </form>

      <!-- Lista de vídeos -->
      <div class="video-list">
        <?php if (empty($videosPorSlot[$key])): ?>
          <div class="empty">Nenhum vídeo adicionado ainda</div>
        <?php else: ?>
          <?php foreach ($videosPorSlot[$key] as $v): ?>
            <?php
              // Extrair ID do YouTube para thumbnail
              preg_match('/(?:v=|youtu\.be\/)([^&\?]+)/', $v['youtube_url'], $m);
              $ytId = $m[1] ?? '';
            ?>
            <div class="video-item">
              <div class="video-thumb">
                <?php if ($ytId): ?>
                  <img src="https://img.youtube.com/vi/<?= $ytId ?>/mqdefault.jpg" alt="">
                <?php endif; ?>
              </div>
              <div class="video-info">
                <div class="video-titulo"><?= htmlspecialchars($v['titulo']) ?></div>
                <div class="video-url"><?= htmlspecialchars($v['youtube_url']) ?></div>
              </div>
              <form method="POST" action="acoes.php" onsubmit="return confirm('Remover este vídeo?')">
                <input type="hidden" name="acao" value="del_video">
                <input type="hidden" name="id" value="<?= $v['id'] ?>">
                <button type="submit" class="btn-del">✕</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ═══ GALERIA ═══ -->
  <div class="section-title">Galeria — Fotos</div>
  <div class="gallery-section">
    <form method="POST" action="acoes.php" enctype="multipart/form-data">
      <input type="hidden" name="acao" value="upload_foto">
      <div class="upload-area" onclick="document.getElementById('fotoInput').click()">
        <input type="file" id="fotoInput" name="fotos[]" multiple accept=".jpg,.jpeg,.png,.webp" onchange="this.form.submit()">
        <label class="upload-label">Clique aqui ou arraste as fotos<br><span>JPG, PNG ou WEBP</span></label>
      </div>
    </form>

    <div class="photos-grid">
      <?php foreach ($fotos as $f): ?>
        <div class="photo-item">
          <img src="/assets/uploads/gallery/<?= htmlspecialchars($f['filename']) ?>" alt="">
          <form method="POST" action="acoes.php" onsubmit="return confirm('Remover esta foto?')">
            <input type="hidden" name="acao" value="del_foto">
            <input type="hidden" name="id" value="<?= $f['id'] ?>">
            <button type="submit" class="photo-del">✕</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ═══ ALTERAR SENHA ═══ -->
  <div class="section-title">Segurança</div>
  <div class="senha-section">
    <form method="POST" action="acoes.php" class="senha-form">
      <input type="hidden" name="acao" value="alterar_senha">
      <div class="form-group">
        <label>Nova senha</label>
        <input type="password" name="nova_senha" required minlength="8">
      </div>
      <div class="form-group">
        <label>Confirmar senha</label>
        <input type="password" name="confirmar_senha" required minlength="8">
      </div>
      <button type="submit" class="btn-add">Atualizar Senha</button>
    </form>
  </div>
<!-- ═══ DEPOIMENTOS ═══ -->
<div class="section-title">Depoimentos — Clientes</div>

<div class="gallery-section">

  <!-- Formulário adicionar depoimento -->
  <form method="POST" action="acoes.php" enctype="multipart/form-data">
    <input type="hidden" name="acao" value="add_depoimento">

    <div class="senha-form">

      <div class="form-group">
        <label>Nome do cliente</label>
        <input type="text" name="nome" required>
      </div>

      <div class="form-group">
        <label>Empresa / Cargo</label>
        <input type="text" name="empresa">
      </div>

      <div class="form-group">
        <label>Comentário</label>
        <input type="text" name="comentario" required>
      </div>

      <div class="form-group">
        <label>Foto do cliente</label>
        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
      </div>

      <button type="submit" class="btn-add">Adicionar Depoimento</button>

    </div>
  </form>

  <div class="video-list" style="margin-top:1.2rem;">

  <?php if (empty($depoimentos)): ?>
    <div class="empty">Nenhum depoimento cadastrado ainda</div>
  <?php else: ?>
    <?php foreach ($depoimentos as $d): ?>
      <div class="video-item">
        <div class="video-thumb">
          <?php if (!empty($d['foto'])): ?>
            <img src="/assets/uploads/depoimentos/<?= htmlspecialchars($d['foto']) ?>" alt="">
          <?php endif; ?>
        </div>

        <div class="video-info">
          <div class="video-titulo"><?= htmlspecialchars($d['nome']) ?><?php if (!empty($d['empresa'])): ?> — <?= htmlspecialchars($d['empresa']) ?><?php endif; ?></div>
          <div class="video-url"><?= htmlspecialchars($d['comentario']) ?></div>
        </div>

        <form method="POST" action="acoes.php" onsubmit="return confirm('Remover este depoimento?')">
          <input type="hidden" name="acao" value="del_depoimento">
          <input type="hidden" name="id" value="<?= $d['id'] ?>">
          <button type="submit" class="btn-del">✕</button>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  </div>
</div>  
</main>
</body>
</html>
