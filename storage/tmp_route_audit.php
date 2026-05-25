<?php
$routesJson = shell_exec('php artisan route:list --json');
$routes = json_decode((string) $routesJson, true);
if (!is_array($routes)) {
    fwrite(STDERR, "Failed to load routes\n");
    exit(1);
}

$routeNames = [];
foreach ($routes as $r) {
    if (!empty($r['name'])) {
        $routeNames[$r['name']] = true;
    }
}

function collectMissing(string $baseDir, array $routeNames, array $extensions): array
{
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
    $missing = [];

    foreach ($it as $file) {
        if ($file->isDir()) {
            continue;
        }

        $path = $file->getPathname();
        $ok = false;
        foreach ($extensions as $ext) {
            if (str_ends_with($path, $ext)) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            continue;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            continue;
        }

        if (preg_match_all("/route\\([\"']([A-Za-z0-9_.-]+)[\"']/", $content, $m)) {
            foreach (($m[1] ?? []) as $name) {
                if (!isset($routeNames[$name])) {
                    $normalized = str_replace('\\\\', '/', $path);
                    $missing[$name][] = $normalized;
                }
            }
        }
    }

    ksort($missing);
    return $missing;
}

$viewsMissing = collectMissing('resources/views', $routeNames, ['.blade.php']);
$ctrlMissing = collectMissing('app/Http/Controllers', $routeNames, ['.php']);

function printMissing(string $title, array $missing): void
{
    echo "===", $title, "===\n";
    foreach ($missing as $name => $files) {
        echo $name, "\n";
        $files = array_values(array_unique($files));
        foreach (array_slice($files, 0, 8) as $filePath) {
            echo '  - ', $filePath, "\n";
        }
        if (count($files) > 8) {
            echo "  - ...\n";
        }
    }
}

printMissing('VIEWS', $viewsMissing);
printMissing('CONTROLLERS', $ctrlMissing);
