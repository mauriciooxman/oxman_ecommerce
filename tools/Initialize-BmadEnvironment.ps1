# Dot-source this file before running the exact command from a BMAD SKILL.md.
# Changes apply to the current PowerShell process only. No global installation
# or workflow source is modified, and the working directory is preserved.
$bmadProjectRoot = Split-Path -Parent $PSScriptRoot
$bmadLocalRuntime = Join-Path $bmadProjectRoot '.local\bmad'
$bmadTempDirectory = Join-Path $bmadLocalRuntime 'temp'
$bmadCacheDirectory = Join-Path $bmadLocalRuntime 'cache'

$bmadUvCommand = Get-Command uv.exe -CommandType Application -ErrorAction Stop | Select-Object -First 1
if ($bmadUvCommand.Source -match '[\\/]Microsoft[\\/]WinGet[\\/]Links[\\/]uv\.exe$') {
    # LOCALAPPDATA may be redirected into an execution sandbox. Derive the
    # real WinGet package directory from the command's observed installation.
    $bmadWingetRoot = Split-Path -Parent (Split-Path -Parent $bmadUvCommand.Source)
    $bmadWingetPackages = Join-Path $bmadWingetRoot 'Packages'
} else {
    $bmadWingetPackages = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages'
}
$bmadUvCandidates = @(
    if (Test-Path -LiteralPath $bmadWingetPackages) {
        Get-ChildItem -LiteralPath $bmadWingetPackages -Directory -Filter 'astral-sh.uv_*' |
            ForEach-Object { Join-Path $_.FullName 'uv.exe' } |
            Where-Object { Test-Path -LiteralPath $_ -PathType Leaf }
    }
)
if ($bmadUvCandidates.Count -gt 1) {
    throw 'Multiple uv installations found. Select the intended executable before continuing.'
}
if ($bmadUvCandidates.Count -eq 1) {
    $bmadUvExecutable = $bmadUvCandidates[0]
} else {
    $bmadUvExecutable = $bmadUvCommand.Source
}

New-Item -ItemType Directory -Force -Path $bmadTempDirectory, $bmadCacheDirectory | Out-Null
$bmadUvDirectory = Split-Path -Parent $bmadUvExecutable
$env:PATH = $bmadUvDirectory + [IO.Path]::PathSeparator + $env:PATH
$env:TEMP = $bmadTempDirectory
$env:TMP = $bmadTempDirectory
$env:TMPDIR = $bmadTempDirectory
$env:UV_CACHE_DIR = $bmadCacheDirectory
$env:UV_PYTHON_DOWNLOADS = 'never'

# Use an already installed interpreter; uv remains responsible for running it.
$bmadPythonCommand = Get-Command python.exe -CommandType Application -ErrorAction Stop | Select-Object -First 1
$env:UV_PYTHON = $bmadPythonCommand.Source
