<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";

ensure_session();
$_SESSION = [];
session_destroy();
redirect("/login.php");

