<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("conexion.php");
require_once("dataset_service.php");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

ateneaDatasetEnsureSchema($conn);

$datasetId = (int) ($_GET["dataset"] ?? 0);
$dataset = $datasetId > 0 ? ateneaDatasetCurrentUpload($conn, $datasetId) : null;

if (!$dataset || empty($dataset["archivo_guardado"])) {
    http_response_code(404);
    echo "Dataset no encontrado.";
    exit();
}

$projectRoot = dirname(__DIR__);
$datasetRoot = realpath($projectRoot . "/DATASET");
$storedPath = (string) $dataset["archivo_guardado"];
$candidate = is_file($storedPath) ? $storedPath : $projectRoot . "/" . ltrim($storedPath, "/");
$realFile = realpath($candidate);
$insideDataset = $datasetRoot
    && $realFile
    && ($realFile === $datasetRoot || strpos($realFile, $datasetRoot . DIRECTORY_SEPARATOR) === 0);

if (!$insideDataset || !is_file($realFile)) {
    http_response_code(404);
    echo "Archivo no disponible.";
    exit();
}

$downloadName = basename((string) ($dataset["nombre_original"] ?: "dataset.csv"));

header("Content-Type: text/csv; charset=utf-8");
header("Content-Length: " . filesize($realFile));
header("Content-Disposition: attachment; filename=\"" . str_replace('"', '', $downloadName) . "\"");
readfile($realFile);
exit();
