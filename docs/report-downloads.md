# Large combined PDF downloads

The same report download button returns a single PDF containing all supported
documents. On Vercel, PDFs larger than 3 MB use an authenticated download page:
the server stores the finished report temporarily in the database, the browser
fetches 1 MB parts, verifies their combined size and SHA-256 checksum, and saves
one PDF. No evidence is removed to fit a response limit. Other hosting platforms
and smaller PDFs use the direct PDF response.

Before deploying, run:

```sh
php artisan migrate --force
```

This creates `report_downloads` and `report_download_parts`. The database must be
durable and shared between requests (for example MySQL or PostgreSQL). An
ephemeral SQLite database on a serverless instance cannot hold downloads across
requests reliably.

Temporary downloads expire after 30 minutes. Each part requires a logged-in
session, the original requesting user, and current access to the candidate's
case. Downloads belonging to another user are inaccessible, including to other
admins. Responses are private and not cached. Expired downloads are deleted when
another large download is prepared or when an expired download is accessed.

For regular cleanup, run Laravel's scheduler, which invokes the prune command
hourly, or call this command from your hosting scheduler:

```sh
php artisan reports:prune-downloads
```

An expired download cannot be resumed; return to the case and download the
report again. A connection interruption can be retried from the download page
while its 30-minute window is still active.

File uploads accept PDF, JPG/JPEG, PNG, WebP, DOC, DOCX, ODT, RTF, TXT, CSV, XLS,
XLSX, ODS, PPT, PPTX, ODP, ODG, ODF and flat OpenDocument files, up to 10 MB per file. Office rendering additionally
requires the converter described in `report-conversion.md`. PHP/web-server
request limits and Vercel's upload request limit still apply to a multi-file
submission; selecting multiple 10 MB files does not increase the host's request
limit.
