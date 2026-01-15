<?php
// ---- CONFIG ----
$siteRoot = __DIR__;
$artDir   = $siteRoot . "/Artworks";
$listFile = $siteRoot . "/artworks.txt";

// Create folder if missing
if (!is_dir($artDir)) {
  mkdir($artDir, 0755, true);
}

function clean($s) {
  $s = trim($s ?? "");
  $s = str_replace(["\r","\n"], " ", $s);
  return $s;
}

// Basic input
$title = clean($_POST["title"] ?? "");
$tagsArr = $_POST["tags"] ?? [];
if (!is_array($tagsArr)) $tagsArr = [];
$tagsArr = array_map("clean", $tagsArr);
$tagsArr = array_filter($tagsArr, fn($t) => $t !== "");
$tags = implode(", ", $tagsArr);
$desc  = clean($_POST["desc"] ?? "");

// Require file
if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo "Upload failed.";
  exit;
}

// Only allow images (simple check)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $_FILES["file"]["tmp_name"]);
finfo_close($finfo);

$allowed = ["image/jpeg","image/png","image/webp","image/gif"];
if (!in_array($mime, $allowed, true)) {
  http_response_code(400);
  echo "Only image uploads allowed.";
  exit;
}

// Safe filename
$orig = $_FILES["file"]["name"];
$orig = preg_replace("/[^A-Za-z0-9._ -]/", "", $orig);
$orig = trim(preg_replace("/\s+/", " ", $orig));
if ($orig === "") $orig = "upload";

// Avoid overwrite: if file exists, add -1 -2 ...
$target = $artDir . "/" . $orig;
$pathInfo = pathinfo($orig);
$base = $pathInfo["filename"] ?? "upload";
$ext  = isset($pathInfo["extension"]) ? "." . $pathInfo["extension"] : "";

$i = 1;
while (file_exists($target)) {
  $target = $artDir . "/" . $base . "-" . $i . $ext;
  $i++;
}

$finalName = basename($target);

// Move the file
if (!move_uploaded_file($_FILES["file"]["tmp_name"], $target)) {
  http_response_code(500);
  echo "Could not save file.";
  exit;
}

// Server timestamp (your server’s timezone)
$timestamp = date("Y-m-d H:i:s");

// Append line to artworks.txt
// Format: file | title | tags | description | timestamp
$line = $finalName . " | " . $title . " | " . $tags . " | " . $desc . " | " . $timestamp . PHP_EOL;
$bytes = file_put_contents($listFile, $line, FILE_APPEND);
if ($bytes === false) {
  http_response_code(500);
  echo "Failed to append to artworks.txt";
  exit;
}

// Redirect back to site (or gallery)
header("Location: /index.html");
exit;