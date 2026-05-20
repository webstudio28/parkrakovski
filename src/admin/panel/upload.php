<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/media.php";

header("Content-Type: application/json; charset=utf-8");

require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["ok" => false, "error" => "Методът не е позволен."], JSON_UNESCAPED_UNICODE);
  exit;
}

verify_csrf($_POST["csrf"] ?? null);

if (empty($_FILES["file"])) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Липсва файл."], JSON_UNESCAPED_UNICODE);
  exit;
}

$prefix = panel_post_string("prefix", "upload");
$result = panel_upload_image($_FILES["file"], $prefix);

http_response_code($result["ok"] ? 200 : 400);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
