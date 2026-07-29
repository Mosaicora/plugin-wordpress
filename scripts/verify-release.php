<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pluginSource = file_get_contents($root . '/mosaicora.php');
$readmeSource = file_get_contents($root . '/readme.txt');
$packageSource = file_get_contents($root . '/package.json');
$composerSource = file_get_contents($root . '/composer.json');
$lockSource = file_get_contents($root . '/composer.lock');
$changelogSource = file_get_contents($root . '/CHANGELOG.md');

foreach (
    [
        'mosaicora.php' => $pluginSource,
        'readme.txt' => $readmeSource,
        'package.json' => $packageSource,
        'composer.json' => $composerSource,
        'composer.lock' => $lockSource,
        'CHANGELOG.md' => $changelogSource,
    ] as $file => $source
) {
    if ($source === false) {
        throw new RuntimeException("Could not read {$file}.");
    }
}

if (preg_match('/^ \* Version:\s+([0-9]+\.[0-9]+\.[0-9]+)$/m', $pluginSource, $matches) !== 1) {
    throw new RuntimeException('Could not resolve the plugin header version.');
}
$version = $matches[1];

if (!str_contains($pluginSource, "define('MOSAICORA_WORDPRESS_VERSION', '{$version}');")) {
    throw new RuntimeException('The runtime version constant does not match the plugin header.');
}
if (!str_contains($readmeSource, "Stable tag: {$version}")) {
    throw new RuntimeException('The WordPress.org stable tag does not match the plugin header.');
}
if (!str_contains($changelogSource, "## [{$version}]")) {
    throw new RuntimeException('CHANGELOG.md does not contain the release version.');
}

$package = json_decode($packageSource, true, flags: JSON_THROW_ON_ERROR);
if (($package['version'] ?? null) !== $version) {
    throw new RuntimeException('package.json does not match the plugin header version.');
}

$composer = json_decode($composerSource, true, flags: JSON_THROW_ON_ERROR);
if (($composer['require']['mosaicora/plugin-core-php'] ?? null) !== '^1.1') {
    throw new RuntimeException('composer.json must require the stable Mosaicora core ^1.1 release.');
}
if (isset($composer['repositories']) || isset($composer['minimum-stability'])) {
    throw new RuntimeException('composer.json must not use local repositories or development stability.');
}

$lock = json_decode($lockSource, true, flags: JSON_THROW_ON_ERROR);
$corePackages = array_values(array_filter(
    $lock['packages'] ?? [],
    static fn (array $package): bool => ($package['name'] ?? null) === 'mosaicora/plugin-core-php',
));
if (count($corePackages) !== 1 || !preg_match('/^v?1\.[1-9][0-9]*\./', $corePackages[0]['version'] ?? '')) {
    throw new RuntimeException('composer.lock must contain a stable Mosaicora core 1.1+ release.');
}
if (($corePackages[0]['dist']['type'] ?? null) === 'path' || str_starts_with($corePackages[0]['version'], 'dev-')) {
    throw new RuntimeException('composer.lock must not contain a path or development Mosaicora core dependency.');
}

$tag = getenv('GITHUB_REF_NAME');
$refType = getenv('GITHUB_REF_TYPE');
if ($refType === 'tag' && $tag !== false && $tag !== '' && $tag !== "v{$version}") {
    throw new RuntimeException("Git tag {$tag} does not match plugin version v{$version}.");
}

$archivePath = $root . "/build/mosaicora-{$version}.zip";
if (is_file($archivePath)) {
    $zip = new ZipArchive();
    if ($zip->open($archivePath) !== true) {
        throw new RuntimeException('Could not open the release ZIP.');
    }

    $required = [
        'mosaicora/mosaicora.php',
        'mosaicora/readme.txt',
        'mosaicora/CHANGELOG.md',
        'mosaicora/LICENSE',
        'mosaicora/third-party-notices.txt',
        'mosaicora/vendor/autoload.php',
        'mosaicora/vendor/mosaicora/plugin-core-php/LICENSE',
        'mosaicora/vendor/mosaicora/plugin-core-php/src/OgImageUrl.php',
        'mosaicora/vendor/mosaicora/plugin-core-php/src/MosaicoraOgSemanticRoles.php',
    ];
    foreach ($required as $path) {
        if ($zip->locateName($path) === false) {
            throw new RuntimeException("Release ZIP is missing {$path}.");
        }
    }

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = $zip->getNameIndex($index);
        if (
            $name === false
            || !str_starts_with($name, 'mosaicora/')
            || preg_match('#(^|/)(?:tests?|node_modules|\.git|\.github|build)(?:/|$)#', $name) === 1
        ) {
            throw new RuntimeException("Release ZIP contains an invalid path: {$name}");
        }
    }
    $zip->close();
}

fwrite(STDOUT, "Release contract verified for Mosaicora {$version}.\n");
