<?php

declare(strict_types=1);

function crm_load_env_file(string $filePath): void
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
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
}

$appEnv = getenv('APP_ENV') ?: getenv('ENVIRONMENT') ?: ($_SERVER['APP_ENV'] ?? 'development');
$appEnv = strtolower(trim((string) $appEnv));
$envCandidates = [];

if (getenv('CRM_ENV_FILE')) {
    $envCandidates[] = getenv('CRM_ENV_FILE');
}

$environmentFile = dirname(__DIR__) . '/.env';
if ($appEnv !== '') {
    $envCandidates[] = dirname(__DIR__) . '/.env.' . $appEnv;
    $envCandidates[] = dirname(__DIR__) . '/.env.' . ($appEnv === 'production' ? 'prod' : 'dev');
    $envCandidates[] = dirname(__DIR__) . '/.env.' . ($appEnv === 'production' ? 'production' : 'development');
}
$envCandidates[] = $environmentFile;

$seenFiles = [];
foreach ($envCandidates as $candidate) {
    if ($candidate === '' || in_array($candidate, $seenFiles, true)) {
        continue;
    }
    $seenFiles[] = $candidate;
    crm_load_env_file((string) $candidate);
}

if (!getenv('APP_ENV') && $appEnv !== '') {
    putenv('APP_ENV=' . $appEnv);
    $_ENV['APP_ENV'] = $appEnv;
    $_SERVER['APP_ENV'] = $appEnv;
}
