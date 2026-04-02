<?php
require_once 'config.php';
verificarLogin();

$db   = getDB();
$acao = $_POST['acao'] ?? '';

switch ($acao) {

    // ── ADICIONAR VÍDEO
    case 'add_video':
        $slot   = trim($_POST['slot'] ?? '');
        $titulo = trim($_POST['titulo'] ?? '');
        $url    = trim($_POST['youtube_url'] ?? '');
        $slotsValidos = ['politica','imoveis','eventos','audiovisual'];

        if ($slot && $titulo && $url && in_array($slot, $slotsValidos, true)) {
            $stmt = $db->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM portfolio_videos WHERE slot=?");
            $stmt->execute([$slot]);
            $ordem = $stmt->fetchColumn();

            $stmt = $db->prepare("INSERT INTO portfolio_videos (slot, titulo, youtube_url, ordem) VALUES (?,?,?,?)");
            $stmt->execute([$slot, $titulo, $url, $ordem]);
            header('Location: /admin/dashboard.php?msg=ok');
        } else {
            header('Location: /admin/dashboard.php?msg=erro');
        }
        exit;

    // ── DELETAR VÍDEO
    case 'del_video':
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM portfolio_videos WHERE id=?");
            $stmt->execute([$id]);
            header('Location: /admin/dashboard.php?msg=ok');
        } else {
            header('Location: /admin/dashboard.php?msg=erro');
        }
        exit;

    // ── UPLOAD DE FOTO
    case 'upload_foto':
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/gallery/';
        $formatosPermitidos = ['image/jpeg','image/png','image/webp'];
        $tamanhoMax = 5 * 1024 * 1024; // 5MB
        $sucesso = true;

        if (!empty($_FILES['fotos']['name'][0])) {
            foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp) {
                $tipo    = mime_content_type($tmp);
                $tamanho = $_FILES['fotos']['size'][$i];
                $ext     = pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION);
                $extValidas = ['jpg','jpeg','png','webp'];

                if (!in_array($tipo, $formatosPermitidos, true) || !in_array(strtolower($ext), $extValidas, true)) {
                    $sucesso = false;
                    continue;
                }

                if ($tamanho > $tamanhoMax) {
                    $sucesso = false;
                    continue;
                }

                $novoNome = uniqid('foto_') . '.' . strtolower($ext);

                if (move_uploaded_file($tmp, $uploadDir . $novoNome)) {
                    $stmt = $db->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM gallery_photos");
                    $stmt->execute();
                    $ordem = $stmt->fetchColumn();

                    $stmt = $db->prepare("INSERT INTO gallery_photos (filename, ordem) VALUES (?,?)");
                    $stmt->execute([$novoNome, $ordem]);
                }
            }
        }

        header('Location: /admin/dashboard.php?msg=' . ($sucesso ? 'ok' : 'erro'));
        exit;

    // ── DELETAR FOTO
    case 'del_foto':
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("SELECT filename FROM gallery_photos WHERE id=?");
            $stmt->execute([$id]);
            $foto = $stmt->fetch();

            if ($foto) {
                $arquivo = $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/gallery/' . $foto['filename'];
                if (file_exists($arquivo)) {
                    unlink($arquivo);
                }

                $stmt = $db->prepare("DELETE FROM gallery_photos WHERE id=?");
                $stmt->execute([$id]);
            }

            header('Location: /admin/dashboard.php?msg=ok');
        } else {
            header('Location: /admin/dashboard.php?msg=erro');
        }
        exit;

    // ── ALTERAR SENHA
    case 'alterar_senha':
        $nova     = trim($_POST['nova_senha'] ?? '');
        $confirma = trim($_POST['confirmar_senha'] ?? '');

        if ($nova && $nova === $confirma && strlen($nova) >= 8) {
            $hash = password_hash($nova, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE admin_users SET password=? WHERE id=?");
            $stmt->execute([$hash, $_SESSION['admin_id']]);
            header('Location: /admin/dashboard.php?msg=senha_ok');
        } else {
            header('Location: /admin/dashboard.php?msg=erro');
        }
        exit;

    // ── ADD DEPOIMENTO
    case 'add_depoimento':
        $nome       = trim($_POST['nome'] ?? '');
        $empresa    = trim($_POST['empresa'] ?? '');
        $comentario = trim($_POST['comentario'] ?? '');

        if ($nome === '' || $comentario === '') {
            header('Location: /admin/dashboard.php?msg=erro');
            exit;
        }

        $foto_nome = null;

        if (!empty($_FILES['foto']['name'])) {
            $permitidos = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $permitidos, true)) {
                $foto_nome = uniqid('dep_') . '.' . $ext;
                $destino = $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/depoimentos/' . $foto_nome;
                move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
            }
        }

        $stmt = $db->prepare("INSERT INTO depoimentos (nome, empresa, comentario, foto) VALUES (?,?,?,?)");
        $stmt->execute([$nome, $empresa, $comentario, $foto_nome]);

        header('Location: /admin/dashboard.php?msg=ok');
        exit;

    // ── DEL DEPOIMENTO
    case 'del_depoimento':
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/dashboard.php?msg=erro');
            exit;
        }

        $stmt = $db->prepare("SELECT foto FROM depoimentos WHERE id=?");
        $stmt->execute([$id]);
        $dep = $stmt->fetch();

        if ($dep && !empty($dep['foto'])) {
            $arquivo = $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/depoimentos/' . $dep['foto'];
            if (file_exists($arquivo)) {
                unlink($arquivo);
            }
        }

        $stmt = $db->prepare("DELETE FROM depoimentos WHERE id=?");
        $stmt->execute([$id]);

        header('Location: /admin/dashboard.php?msg=ok');
        exit;

    default:
        header('Location: /admin/dashboard.php');
        exit;
}
