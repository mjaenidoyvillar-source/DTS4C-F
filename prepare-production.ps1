# Production Preparation Script for Windows
# This script helps prepare your Laravel application for production deployment

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Production Preparation Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if .env exists
if (-not (Test-Path ".env")) {
    Write-Host "ERROR: .env file not found!" -ForegroundColor Red
    Write-Host "Please create a .env file first." -ForegroundColor Red
    exit 1
}

Write-Host "Step 1: Backing up current .env file..." -ForegroundColor Yellow
Copy-Item ".env" ".env.localhost.backup" -Force
Write-Host "✓ Backup created: .env.localhost.backup" -ForegroundColor Green
Write-Host ""

Write-Host "Step 2: Optimizing for production..." -ForegroundColor Yellow
Write-Host "  - Installing production dependencies..." -ForegroundColor Gray
composer install --optimize-autoloader --no-dev
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Composer install failed!" -ForegroundColor Red
    exit 1
}
Write-Host "  ✓ Dependencies installed" -ForegroundColor Green
Write-Host ""

Write-Host "Step 3: Clearing caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
Write-Host "  ✓ Caches cleared" -ForegroundColor Green
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Preparation Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Update your .env file with production settings:" -ForegroundColor White
Write-Host "   - Set APP_ENV=production" -ForegroundColor Gray
Write-Host "   - Set APP_DEBUG=false" -ForegroundColor Gray
Write-Host "   - Set APP_URL=https://yourdomain.com" -ForegroundColor Gray
Write-Host "   - Update database credentials" -ForegroundColor Gray
Write-Host "   - Update mail settings" -ForegroundColor Gray
Write-Host "   - Set SESSION_DOMAIN=yourdomain.com" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Review PRODUCTION_PREPARATION.md for detailed instructions" -ForegroundColor White
Write-Host ""
Write-Host "3. Upload files via FileZilla (see FILEZILLA_DEPLOYMENT_GUIDE.md)" -ForegroundColor White
Write-Host ""
Write-Host "Note: Your local .env has been backed up to .env.localhost.backup" -ForegroundColor Cyan
Write-Host ""


