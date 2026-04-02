<?php
require_once 'config.php';
iniciarSessao();
session_destroy();
header('Location: /admin/index.php');
exit;
