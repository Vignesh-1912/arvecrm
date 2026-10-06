<?php

declare(strict_types=1);

$environmentFile = getenv('CRM_ENV_FILE') ?: dirname(__DIR__) . '/.env';

if (!is_file($environmentFile)) {
    return;
}

if (!is_readable($environmentFile)) {
    error_log('Environment file is not readable: ' . $environmentFile);
    return;
}

$lines = file($environmentFile, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    error_log('Unable to read environment file: ' . $environmentFile);
    return;
}

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }

    if (strpos($line, 'export ') === 0) {
        $line = substr($line, 7);
    }

    $separator = strpos($line, '=');
    if ($separator === false) {
        continue;
    }

    $name = trim(substr($line, 0, $separator));
    $value = trim(substr($line, $separator + 1));

    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
        continue;
    }

    if (strlen($value) >= 2) {
        $firstCharacter = $value[0];
        $lastCharacter = $value[strlen($value) - 1];
        if (($firstCharacter === '"' && $lastCharacter === '"')
            || ($firstCharacter === "'" && $lastCharacter === "'")) {
            $value = substr($value, 1, -1);
        }
    }

    if (getenv($name) !== false) {
        continue;
    }

    putenv($name . '=' . $value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}
