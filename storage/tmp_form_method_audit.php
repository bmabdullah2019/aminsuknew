<?php
$routes = json_decode(shell_exec('php artisan route:list --json'), true);
if (!is_array($routes)) { fwrite(STDERR, "route list parse failed\n"); exit(1);} 
$methodsByName = [];
foreach ($routes as $r) {
    $name = $r['name'] ?? null;
    if (!$name) continue;
    $methodText = $r['method'] ?? '';
    $methods = array_filter(array_map('trim', explode('|', $methodText)));
    $methodsByName[$name] = $methods;
}
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('resources/views'));
$issues = [];
foreach ($rii as $f) {
    if ($f->isDir()) continue;
    $path = $f->getPathname();
    if (!str_ends_with($path, '.blade.php')) continue;
    $content = file_get_contents($path);
    if ($content === false) continue;

    if (!preg_match_all('/<form\\b[\\s\\S]*?<\\/form>/i', $content, $forms)) continue;
    foreach ($forms[0] as $form) {
        if (!preg_match('/action\\s*=\\s*"\\{\\{\\s*route\\(["\']([^"\']+)["\']/i', $form, $m)) continue;
        $route = $m[1];
        if (!isset($methodsByName[$route])) continue;

        $expected = 'GET';
        if (preg_match('/method\\s*=\\s*"(GET|POST)"/i', $form, $mm2)) {
            $expected = strtoupper($mm2[1]);
        }
        if (preg_match('/@method\\s*\\(\\s*["\'](DELETE|PUT|PATCH)["\']\\s*\\)/i', $form, $mm)) {
            $expected = strtoupper($mm[1]);
        }

        $allowed = $methodsByName[$route];
        if (!in_array($expected, $allowed, true)) {
            $issues[] = [$path, $route, $expected, implode('|', $allowed)];
        }
    }
}
if (!$issues) {
    echo "NO_MISMATCH\n";
    exit(0);
}
foreach ($issues as [$p,$r,$e,$a]) {
    echo str_replace('\\\\','/',$p)," => route(",$r,") form=",$e," route=",$a,"\n";
}
