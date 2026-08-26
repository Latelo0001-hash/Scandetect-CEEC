<?php
require_once __DIR__ . '/config.php';

function require_auth(): void
{
    if (empty($_SESSION['user'])) {
        redirect('login.php');
    }
}
