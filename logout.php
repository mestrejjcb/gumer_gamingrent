<?php
require_once __DIR__ . '/app/bootstrap.php';
logout();
flash_set('info', 'Sesión cerrada.');
redirect('login.php');
