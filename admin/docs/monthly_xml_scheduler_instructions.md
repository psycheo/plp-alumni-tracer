# Monthly XML Export Scheduler

This project includes `admin/scheduled_monthly_xml_export.php` for automatic report generation every 30 days.

## Command

```powershell
php "C:\xampp\htdocs\plp-alumni-tracer\admin\scheduled_monthly_xml_export.php"
```

## Output folder

Generated files are saved to:

`C:\xampp\htdocs\plp-alumni-tracer\exports\xml\monthly`

## Windows Task Scheduler setup

1. Open **Task Scheduler**.
2. Click **Create Basic Task**.
3. Name: `PLP Monthly XML Export`.
4. Trigger: **Monthly** (or Daily with repeat every 30 days, depending on your policy).
5. Action: **Start a Program**.
6. Program/script: path to `php.exe` (example: `C:\xampp\php\php.exe`).
7. Add arguments:
   `C:\xampp\htdocs\plp-alumni-tracer\admin\scheduled_monthly_xml_export.php`
8. Finish and test with **Run**.

## Notes

- Script runs in CLI mode only.
- If schema validation fails, the console output will show `XSD validation: FAIL`.
