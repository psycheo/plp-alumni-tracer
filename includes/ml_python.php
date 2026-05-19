<?php
/**
 * Locate Python and ML scripts (career predict + employment forecast).
 */
declare(strict_types=1);

function ml_project_root(): string
{
    return dirname(__DIR__);
}

function ml_predict_script_path(): string
{
    return ml_project_root() . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'predict.py';
}

function ml_forecast_script_path(): string
{
    return ml_project_root() . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'forecast_employment.py';
}

function ml_python_executable(): ?string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $root = ml_project_root();
    
    // Removed the extra 'ml' directory from these paths
    $venvCandidates = [
        $root . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe',
        $root . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python',
    ];
    
    foreach ($venvCandidates as $path) {
        if (is_file($path)) {
            $cached = $path;
            return $cached;
        }
    }
    
    foreach (['python', 'python3'] as $bin) {
        $out = shell_exec(escapeshellcmd($bin) . ' --version 2>&1');
        if ($out !== null && $out !== false && preg_match('/Python\s+\d/i', (string) $out)) {
            $cached = $bin;
            return $cached;
        }
    }
    
    $cached = null;
    return $cached;
}