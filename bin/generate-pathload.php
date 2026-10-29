<?php

/**
 * Generate pathload.main.php using composer metadata and scoper rules.
 *
 * Usage: php generate-pathload.php <LIB_DIR> <MAJOR_NAME>
 * Example: php generate-pathload.php . phpspreadsheet@5
 */

function normalizePath(string $path): string {
    return str_replace(DIRECTORY_SEPARATOR, '/', $path);
}

function toArchivePath(string $baseDir, string $expression): string {
    $baseDir = normalizePath(realpath($baseDir));
    $target = $baseDir . '/vendor/composer/' . $expression;
    $realTarget = realpath($target);
    if ($realTarget && str_starts_with(normalizePath($realTarget), $baseDir . '/')) {
        return substr(normalizePath($realTarget), strlen($baseDir . '/'));
    }
    // Fallback: clean up relative path components without requiring existing realpath
    $parts = explode('/', normalizePath("vendor/composer/" . $expression));
    $clean = [];
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..') {
            array_pop($clean);
        } else {
            $clean[] = $part;
        }
    }
    return implode('/', $clean);
}

function buildPathloadConfig(string $libDir, array $installed, array $scoper): array {
    $prefix = isset($scoper['prefix']) && trim($scoper['prefix']) !== '' ? trim($scoper['prefix']) : null;
    $privateNamespace = $prefix ? $prefix . '\\' : '';
    $excludedNs = $scoper['exclude-namespaces'] ?? [];

    $isExcluded = function(string $ns) use ($excludedNs): bool {
        $trimmedNs = trim($ns, '\\');
        foreach ($excludedNs as $exc) {
            $trimmedExc = trim($exc, '\\');
            if ($trimmedNs === $trimmedExc || str_starts_with($trimmedNs, $trimmedExc . '\\')) {
                return true;
            }
        }
        return false;
    };

    $config = [];
    $packages = $installed['packages'] ?? $installed;
    if (!is_array($packages)) {
        $packages = [];
    }

    foreach ($packages as $package) {
        if (empty($package['install-path'])) {
            continue;
        }
        $installPath = toArchivePath($libDir, $package['install-path']);

        foreach (['include', 'psr-4', 'psr-0'] as $section) {
            foreach ($package['autoload'][$section] ?? [] as $nsPrefix => $nsPaths) {
                if ($section === 'include') {
                    foreach ((array) $nsPaths as $nsPath) {
                        $config['autoload']['include'][] = $installPath . '/' . $nsPath;
                    }
                } else {
                    $prefixToUse = $isExcluded($nsPrefix) ? $nsPrefix : $privateNamespace . $nsPrefix;
                    foreach ((array) $nsPaths as $nsPath) {
                        $config['autoload'][$section][$prefixToUse] ??= [];
                        $config['autoload'][$section][$prefixToUse][] = $installPath . '/' . $nsPath;
                    }
                }
            }
        }
    }

    return $config;
}

if ($argc < 3) {
    fprintf(STDERR, "Usage: php %s <LIB_DIR> <MAJOR_NAME>\n", $argv[0]);
    exit(1);
}

$libDir = realpath($argv[1]);
if (!$libDir || !is_dir($libDir)) {
    fprintf(STDERR, "Error: Invalid directory '%s'\n", $argv[1]);
    exit(1);
}

$majorName = $argv[2];

$scoperFile = $libDir . '/scoper.inc.php';
$scoper = file_exists($scoperFile) ? (require $scoperFile) : [];

$installedFile = $libDir . '/vendor/composer/installed.json';
if (!file_exists($installedFile)) {
    fprintf(STDERR, "Error: Missing '%s'\n", $installedFile);
    exit(1);
}

$installed = json_decode(file_get_contents($installedFile), true);
if (!$installed) {
    fprintf(STDERR, "Error: Failed to parse '%s'\n", $installedFile);
    exit(1);
}

$config = buildPathloadConfig($libDir, $installed, $scoper);
$namespace = $scoper['prefix'] ?? '';

echo "<?php\n";
if (!empty($namespace)) {
    printf("namespace %s;\n\n", $namespace);
}
printf("\\pathload()->activatePackage(%s, __DIR__, %s);\n\n",
    var_export($majorName, true),
    var_export($config, true)
);

$classmapFile = $libDir . '/vendor/composer/autoload_classmap.php';
if (file_exists($classmapFile) && !empty(require $classmapFile)) {
    printf("function classMapLoader(\$class) {\n");
    printf("  static \$map;\n");
    printf("  \$map ??= require __DIR__ . %s;\n", var_export("/vendor/composer/autoload_classmap.php", true));
    printf("  if (isset(\$map[\$class])) { require_once \$map[\$class]; }\n");
    printf("}\n\n");
    printf("spl_autoload_register(%s);\n\n", var_export(($namespace ? '\\' . $namespace : '') . '\\classMapLoader', true));
}
