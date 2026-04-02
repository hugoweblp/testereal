<?php
require_once 'config.php';
iniciarSessao();

// Se já estiver logado, vai direto pro dashboard
if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    header('Location: /admin/dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = trim($_POST['senha'] ?? '');

    if ($usuario && $senha) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, password FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['password'])) {
            $_SESSION['admin_logado']  = true;
            $_SESSION['admin_id']      = $user['id'];
            $_SESSION['admin_usuario'] = $usuario;
            $_SESSION['ultimo_acesso'] = time();
            header('Location: /admin/dashboard.php');
            exit;
        } else {
            $erro = 'Usuário ou senha incorretos.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Daniel Queiroz</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--cyan:#00f5ff;--magenta:#ff006e;--dark:#02040a;--text:#e8eaf0;--muted:#556070}
body{background:var(--dark);color:var(--text);font-family:'Barlow',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,245,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(0,245,255,.015) 1px,transparent 1px);background-size:80px 80px;pointer-events:none}
.login-box{width:100%;max-width:400px;padding:3rem 2.5rem;border:1px solid rgba(0,245,255,.12);background:rgba(6,12,22,.9);backdrop-filter:blur(20px);position:relative}
.login-box::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--cyan),var(--magenta))}
.login-logo{font-family:'Bebas Neue',sans-serif;font-size:3.5rem;color:transparent;background:linear-gradient(135deg,var(--cyan),var(--magenta));-webkit-background-clip:text;background-clip:text;text-align:center;margin-bottom:.3rem}
.login-sub{font-family:'Space Mono',monospace;font-size:.6rem;letter-spacing:.3em;color:var(--muted);text-align:center;text-transform:uppercase;margin-bottom:2.5rem}
.form-group{margin-bottom:1.2rem}
.form-group label{font-family:'Space Mono',monospace;font-size:.6rem;letter-spacing:.2em;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:.5rem}
.form-group input{width:100%;background:rgba(255,255,255,.04);border:1px solid rgba(0,245,255,.15);color:var(--text);font-family:'Barlow',sans-serif;font-size:1rem;padding:.8rem 1rem;outline:none;transition:border-color .2s}
.form-group input:focus{border-color:var(--cyan);box-shadow:0 0 15px rgba(0,245,255,.1)}
.btn-login{width:100%;background:linear-gradient(135deg,var(--cyan),var(--magenta));color:#000;font-family:'Space Mono',monospace;font-size:.75rem;letter-spacing:.2em;text-transform:uppercase;padding:1rem;border:none;cursor:pointer;margin-top:1rem;font-weight:700;transition:opacity .2s}
.btn-login:hover{opacity:.85}
.erro{background:rgba(255,0,110,.1);border:1px solid rgba(255,0,110,.3);color:#ff6b9d;font-size:.8rem;padding:.8rem 1rem;margin-bottom:1.2rem;font-family:'Space Mono',monospace}
.expirou{background:rgba(0,245,255,.08);border:1px solid rgba(0,245,255,.2);color:var(--cyan);font-size:.75rem;padding:.8rem 1rem;margin-bottom:1.2rem;font-family:'Space Mono',monospace}
</style>
</head>
<body>
<div class="login-box">
  <div class="login-logo">DQ</div>
  <div class="login-sub">Painel Administrativo</div>

  <?php if (isset($_GET['expirou'])): ?>
    <div class="expirou">Sessão expirada. Faça login novamente.</div>
  <?php endif; ?>

  <?php if ($erro): ?>
    <div class="erro"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Usuário</label>
      <input type="text" name="usuario" autocomplete="username" required>
    </div>
    <div class="form-group">
      <label>Senha</label>
      <input type="password" name="senha" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn-login">Entrar</button>
  </form>
</div>
</body>
</html>
