# Laravel Cache Clear and Recache Script (PowerShell)
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Laravel Cache Clear and Recache Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Function to execute command and check for errors
function Invoke-ArtisanCommand {
    param(
        [string]$Command,
        [string]$Description
    )
    
    Write-Host "[$Description]..." -ForegroundColor Yellow
    $result = php artisan $Command 2>&1
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Failed to $Description" -ForegroundColor Red
        Write-Host $result -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    } else {
        Write-Host "✓ $Description completed" -ForegroundColor Green
    }
}

# Clear all caches
Invoke-ArtisanCommand "cache:clear" "Clear application cache"
Invoke-ArtisanCommand "config:clear" "Clear configuration cache"
Invoke-ArtisanCommand "route:clear" "Clear route cache"
Invoke-ArtisanCommand "view:clear" "Clear view cache"
Invoke-ArtisanCommand "clear-compiled" "Clear compiled class files"

# Optimize autoloader
Write-Host "[Optimize autoloader]..." -ForegroundColor Yellow
$result = composer dump-autoload --optimize 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to optimize autoloader" -ForegroundColor Red
    Write-Host $result -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
} else {
    Write-Host "✓ Autoloader optimized" -ForegroundColor Green
}

# Recache for production
Invoke-ArtisanCommand "config:cache" "Cache configuration"
Invoke-ArtisanCommand "route:cache" "Cache routes"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Cache clear and recache completed!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "All Laravel caches have been cleared and recached." -ForegroundColor White
Write-Host "The application should now be running with fresh caches." -ForegroundColor White
Write-Host ""
Read-Host "Press Enter to continue" 