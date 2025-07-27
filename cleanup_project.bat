@echo off
echo Cleaning up MIW project directory...
echo.
echo Removing test files...
del /F /Q comprehensive_test_*.php
del /F /Q comprehensive_testing_suite.php
del /F /Q comprehensive_project_test.php
del /F /Q test_*.php
del /F /Q *_test.php
del /F /Q *_tester.php
del /F /Q workflow_test*.php
del /F /Q workflow_validator.php
del /F /Q debug_*.php
del /F /Q run_production_test.php
del /F /Q health_check.php
del /F /Q db_check.php
del /F /Q dev_inspector.php
del /F /Q error_viewer.php
del /F /Q database_diagnostic.php
del /F /Q *_diagnostic.php
del /F /Q issues_analysis.php
del /F /Q production_flow_tester.php
del /F /Q registration_flow_tester.php
del /F /Q form_submission_tester.php
rmdir /S /Q test_logs

echo.
echo Removing deployment configuration files...
del /F /Q docker*.yml
del /F /Q Dockerfile*
del /F /Q docker-compose*.yml
del /F /Q deploy*.bat
del /F /Q deploy*.sh
del /F /Q *_docker*.bat
del /F /Q *.railway.*
del /F /Q railway*.*
del /F /Q render*.*
del /F /Q *.render.*
del /F /Q migrate_to_*.*
del /F /Q fix_*.bat
del /F /Q clean_restart*.bat
del /F /Q complete_*.bat
del /F /Q setup_*.bat
del /F /Q start_*.bat
del /F /Q push_to_*.bat
del /F /Q quick_fix_*.bat
del /F /Q update_*.bat
del /F /Q import_*.bat
del /F /Q configure_*.bat
del /F /Q restart_*.bat
del /F /Q *.postgres*.*

echo.
echo Removing documentation files...
del /F /Q *.md
del /F /Q init_database_*.sql
del /F /Q file_handler_fixed.php
del /F /Q upload_handler_fixed.php
del /F /Q apache2.conf
del /F /Q COPY_PASTE_DATABASE.sql
del /F /Q add_*_fields.sql
del /F /Q create_*_table.sql
del /F /Q check_and_add*.sql

echo.
echo Clean up complete!
echo.
pause
