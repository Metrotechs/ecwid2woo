param(
    [string]$OutputDirectory = (Join-Path $PSScriptRoot '..\dist')
)

$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$mainPlugin = Join-Path $repoRoot 'ecwid-to-woocommerce-sync.php'
$header = Get-Content -LiteralPath $mainPlugin -Raw
$versionMatch = [regex]::Match($header, '(?m)^Version:\s*([0-9]+\.[0-9]+\.[0-9]+)\s*$')
if (-not $versionMatch.Success) {
    throw 'Could not determine plugin version from the main plugin header.'
}

$version = $versionMatch.Groups[1].Value
$slug = 'metrotechs-e2w-sync'
$outputRoot = [System.IO.Path]::GetFullPath($OutputDirectory)
$stagingRoot = Join-Path $outputRoot '.staging'
$packageRoot = Join-Path $stagingRoot $slug
$zipPath = Join-Path $outputRoot "$slug-$version.zip"
$checksumPath = "$zipPath.sha256"

New-Item -ItemType Directory -Force -Path $outputRoot | Out-Null
if (Test-Path -LiteralPath $stagingRoot) {
    $resolvedStaging = (Resolve-Path -LiteralPath $stagingRoot).Path
    if (-not $resolvedStaging.StartsWith($outputRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Refusing to remove staging directory outside output root: $resolvedStaging"
    }
    Remove-Item -LiteralPath $resolvedStaging -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $packageRoot | Out-Null

$releaseFiles = @(
    'ecwid-to-woocommerce-sync.php',
    'category-sync-page.php',
    'product-sync-page.php',
    'full-sync-page.php',
    'uninstall.php',
    'readme.txt',
    'README.md',
    'changelog.txt',
    'LICENSE'
)
foreach ($relativePath in $releaseFiles) {
    Copy-Item -LiteralPath (Join-Path $repoRoot $relativePath) -Destination (Join-Path $packageRoot $relativePath)
}

foreach ($assetDirectory in @('assets\css', 'assets\js')) {
    $destination = Join-Path $packageRoot $assetDirectory
    New-Item -ItemType Directory -Force -Path $destination | Out-Null
    Get-ChildItem -LiteralPath (Join-Path $repoRoot $assetDirectory) -File | Copy-Item -Destination $destination
}

foreach ($artifact in @($zipPath, $checksumPath)) {
    if (Test-Path -LiteralPath $artifact) {
        Remove-Item -LiteralPath $artifact -Force
    }
}

Compress-Archive -LiteralPath $packageRoot -DestinationPath $zipPath -CompressionLevel Optimal
$forbiddenEntries = @('.git', 'tests', 'scripts', 'SECURITY-PERFORMANCE-IMPLEMENTATION-PLAN.md')
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $entryNames = @($archive.Entries | ForEach-Object { $_.FullName })
    foreach ($forbidden in $forbiddenEntries) {
        if ($entryNames | Where-Object { $_ -match "(^|/)$([regex]::Escape($forbidden))(/|$)" }) {
            throw "Forbidden development content found in release archive: $forbidden"
        }
    }
} finally {
    $archive.Dispose()
}

$hash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLowerInvariant()
Set-Content -LiteralPath $checksumPath -Value "$hash  $([System.IO.Path]::GetFileName($zipPath))" -Encoding ascii
Remove-Item -LiteralPath $stagingRoot -Recurse -Force

Write-Output "Release: $zipPath"
Write-Output "SHA256: $hash"
