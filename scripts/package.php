<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pluginSource = file_get_contents($root . '/mosaicora.php');
if ($pluginSource === false || preg_match('/^ \* Version:\s+([0-9]+\.[0-9]+\.[0-9]+)$/m', $pluginSource, $matches) !== 1) {
    throw new RuntimeException('Could not resolve the plugin version from mosaicora.php.');
}

$version = $matches[1];
$buildDirectory = $root . '/build';
$archivePath = $buildDirectory . "/mosaicora-{$version}.zip";
$pluginPrefix = 'mosaicora/';
$releaseTimestamp = 315532800; // 1980-01-01, the earliest ZIP timestamp.

if (!class_exists(ZipArchive::class)) {
    throw new RuntimeException('The PHP zip extension is required to package the plugin.');
}

$requiredFiles = [
    'mosaicora.php',
    'uninstall.php',
    'readme.txt',
    'README.md',
    'CHANGELOG.md',
    'LICENSE',
    'third-party-notices.txt',
    'composer.json',
    'vendor/autoload.php',
];
foreach ($requiredFiles as $file) {
    if (!is_file($root . '/' . $file)) {
        throw new RuntimeException("Missing required release file: {$file}");
    }
}

if (!is_file($root . '/vendor/mosaicora/plugin-core-php/src/OgImageUrl.php')) {
    throw new RuntimeException('Install Composer runtime dependencies before packaging.');
}

if (!is_dir($buildDirectory) && !mkdir($buildDirectory, 0775, true) && !is_dir($buildDirectory)) {
    throw new RuntimeException('Could not create the build directory.');
}

$zip = new ZipArchive();
if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Could not create the release ZIP.');
}

$paths = [
    'mosaicora.php',
    'uninstall.php',
    'readme.txt',
    'README.md',
    'CHANGELOG.md',
    'LICENSE',
    'third-party-notices.txt',
    'composer.json',
    'assets',
    'languages',
    'src',
    'vendor/autoload.php',
    'vendor/composer',
    'vendor/mosaicora/plugin-core-php/LICENSE',
    'vendor/mosaicora/plugin-core-php/README.md',
    'vendor/mosaicora/plugin-core-php/src',
];

$archiveFiles = [];
foreach ($paths as $relativePath) {
    $absolutePath = $root . '/' . $relativePath;
    if (is_file($absolutePath)) {
        $archiveFiles[$relativePath] = $absolutePath;
        continue;
    }
    if (!is_dir($absolutePath)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absolutePath, RecursiveDirectoryIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $filePath = $file->getPathname();
        $archiveRelativePath = substr($filePath, strlen($root) + 1);
        $archiveFiles[$archiveRelativePath] = $filePath;
    }
}

ksort($archiveFiles, SORT_STRING);
foreach ($archiveFiles as $relativePath => $absolutePath) {
    $archiveName = $pluginPrefix . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    if (!$zip->addFile($absolutePath, $archiveName)) {
        throw new RuntimeException("Could not add {$relativePath} to the release ZIP.");
    }
    if (!$zip->setMtimeName($archiveName, $releaseTimestamp)) {
        throw new RuntimeException("Could not normalize the timestamp for {$relativePath}.");
    }
}

$zip->close();
fwrite(STDOUT, "Created {$archivePath}\n");
