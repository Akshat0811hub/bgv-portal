# Replacing an existing Hostinger site

1. Save the complete old application, its private uploads, `.env`, web root and a
   current consistent SQL export to a local backup. Verify archive CRCs and SQL
   checksums. Keep the old server folders and database intact for rollback.
2. Stage the new application in a separate folder outside `public_html`, for
   example `domains/orbitverify.in/orbitverify-bgv`. Use a separate MySQL database.
   Both the web and CLI PHP versions must be at least 8.4.
3. Ship production Composer dependencies and compiled `public/build`. Regenerate
   package discovery against production dependencies. Do not ship `.env`, local
   database files, cached configuration, local compiled views, `.codex`, or the
   Vercel-specific entry point and configuration.
4. Configure the private target `.env` using `.env.hostinger.example`. Generate a
   fresh application encryption key; the Vercel key was published in tracked
   configuration and must not be reused. Remove Vercel `DATABASE_URL` and `/tmp`
   storage overrides. Keep automatic database setup disabled.
5. Run `php artisan migrate --force`. Do not run `db:seed`: that creates default
   accounts instead of preserving the existing portal users.
6. Export the source as `orbit-bgv-transfer-v1` JSON. Its `tables` object contains
   arrays of raw database rows for `client_organizations`, `users`, `checks`,
   `cases`, `case_checks`, `upload_tokens`, `settings`, and `documents`. Include
   password hashes, custom schemas and complete Base64 `file_content`. Eloquent
   serialization hides passwords and file contents, so use raw database rows.
7. Validate then import the private export:

   ```bash
   php artisan portal:import-backup /PRIVATE/PATH/source-bgv-backup.json --validate-only
   php artisan portal:import-backup /PRIVATE/PATH/source-bgv-backup.json
   ```

   The importer refuses a nonempty destination, unsafe paths, incomplete document
   content and conflicting existing files. It preserves primary keys/password
   hashes, restores private originals and verifies their hashes before committing
   the database transaction. Sessions and password-reset tokens are not imported.
   Migration history comes from the new target's Laravel migrations.
8. Compare source/target counts, IDs, roles, custom field data and all originals.
   Coordinate the final source export with cutover so late uploads are preserved.
9. Preserve the old web root, then copy only the new `public/` contents (including
   `.htaccess` and built assets) into `public_html`. Change all three paths in its
   `index.php`: maintenance file, autoloader and bootstrap must point to the new
   application directory. Do not expose app sources, `.env`, SQL/JSON exports,
   private documents or deployment scripts through the web root.
10. Generate config/route/view caches on the target and test HTTPS, admin/client
    logins, candidate links, multiple uploads, customized checks, combined PDF
    reports and authorized fallback links. A Word/ODF converter is required for
    inline rendering of those formats. Verify SMTP using an approved recipient.
11. After target validation, redirect the retired Vercel portal to the new domain
    so existing candidate URLs continue to work and two separate databases do not
    keep accepting writes. Remove temporary deployment cron jobs and helpers.
12. Back up MySQL and `storage/app/private` together. A document may be disk-only
    when a database packet limit prevents its Base64 copy. Schedule Laravel's
    `schedule:run` every minute for temporary report cleanup.

Rollback restores the old web-root entry files/assets pointing to the preserved
old application and database. Preserve any writes on the new portal before
rolling back. Do not mix databases or overwrite existing source records.
