# Docker Build Troubleshooting

## Fix for Git Authentication Issues

If you're encountering Git authentication errors during the Docker build, follow these steps:

1. **Update composer.json**
   - We've already modified the `repositories` section to use HTTPS instead of SSH URLs
   - This should prevent Git authentication issues during the Docker build

2. **Update composer.lock file locally**
   - Run the `update_composer_lock.bat` script
   - This will update your composer.lock file to match the updated composer.json

3. **Perform a clean Docker rebuild**
   - Run the `clean_restart_docker.bat` script
   - This will completely clean Docker caches and rebuild from scratch

4. **Additional Git configuration in Dockerfile**
   - Added Git configuration to prefer HTTPS over SSH URLs
   - This ensures packages are fetched via HTTPS during Docker builds

## Changes Made

1. Modified `composer.json`:
   - Changed `dompdf/php-svg-lib` repository to use package type with HTTPS URL

2. Modified `Dockerfile`:
   - Added Git configuration to use HTTPS instead of SSH
   - Removed the `composer update --no-install` step
   - Added `--no-scripts` flag to composer install to avoid post-install failures

3. Updated build scripts:
   - Enhanced Docker cache cleaning in `clean_restart_docker.bat`

## If Problems Persist

If you're still having issues:

1. Try removing `composer.lock` locally and letting Docker generate it
   ```
   del composer.lock
   ```

2. Or try a more minimal installation:
   ```
   # In Dockerfile
   RUN composer install --no-dev --no-scripts --prefer-dist --ignore-platform-reqs
   ```

3. Consider modifying composer.json to use Packagist packages only, removing any VCS repositories
