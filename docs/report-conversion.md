# Complete case reports and document conversion

Each case report includes the findings and the supporting documents immediately after their check. Uploaded PDFs, images, and text are rendered inside the downloaded report. Office files require a real office converter so that tables, images, headers, and all document pages are retained; extracting text from DOCX is not a complete conversion.

## Option 1: LibreOffice on the application server

Install LibreOffice, qpdf, and the fonts used by your clients. On Ubuntu/Debian:

```sh
sudo apt-get update
sudo apt-get install libreoffice-writer libreoffice-calc libreoffice-impress libreoffice-draw libreoffice-math qpdf fonts-dejavu fonts-liberation
```

Set application environment variables:

```dotenv
REPORT_DOCUMENT_CONVERTER=libreoffice
REPORT_LIBREOFFICE_BINARY=/usr/bin/soffice
REPORT_QPDF_BINARY=/usr/bin/qpdf
REPORT_CONVERSION_TIMEOUT=90
```

The application launches each conversion in a separate private temporary directory and LibreOffice profile, then removes both. The PHP user must be able to execute the binaries, create temporary directories, and use `proc_open`. LibreOffice exports all pages to PDF/A-1; it preserves the document's own layout rather than rebuilding it from text. qpdf handles PDFs with compressed object streams that the basic FPDI parser cannot import. Normalization does not make an unreadable or encrypted file readable.

On Windows, set `REPORT_LIBREOFFICE_BINARY` to `C:/Program Files/LibreOffice/program/soffice.exe` and `REPORT_QPDF_BINARY` to the installed qpdf executable. Common LibreOffice installation paths are detected automatically.

## Option 2: A private Gotenberg service

Use this for Vercel or shared hosting without LibreOffice/process execution. Run a trusted, isolated Gotenberg **8.x** service on a server controlled by your organization. The current adapter targets the Gotenberg 8 routes; do not replace it with Gotenberg 9 without updating the PDF normalization route.

Example for a local development server only:

```sh
docker run --rm -p 127.0.0.1:3000:3000 gotenberg/gotenberg:8
```

```dotenv
REPORT_DOCUMENT_CONVERTER=gotenberg
REPORT_GOTENBERG_URL=https://your-private-converter.example
REPORT_GOTENBERG_USERNAME=portal
REPORT_GOTENBERG_PASSWORD=your-secret
# Alternatively use your reverse proxy's bearer authentication:
# REPORT_GOTENBERG_TOKEN=your-secret
REPORT_CONVERSION_TIMEOUT=90
```

For the local Docker example use `REPORT_GOTENBERG_URL=http://127.0.0.1:3000` and omit authentication. In production use authenticated HTTPS or a private network. Basic or bearer authentication must be enforced by the converter/reverse proxy. Do not expose an unauthenticated conversion endpoint to the internet. Block converter outbound network access and configure its LibreOffice external-link restrictions. Install the same fonts on the conversion service as are used in your source documents.

The application sends document bytes only when a trusted converter URL is explicitly configured. Files are sent over multipart POST with a generic filename; personal names are not used as converter filenames. It does not contact a public conversion service automatically. Redirects are disabled so evidence and authentication cannot be forwarded to another host.

Office documents use `/forms/libreoffice/convert` with `pdfa=PDF/A-1b`. PDFs requiring normalization use `/forms/pdfengines/convert` with `pdfa=PDF/A-1b`; an installed qpdf binary takes precedence for PDF normalization. Every response must be a PDF. Password-protected, damaged, unsupported, or unavailable files fail the complete report rather than silently disappearing or becoming external download links.

## Formats and limits

- Office conversion: DOC, DOCX, ODT, RTF, XLS, XLSX, ODS, PPT, PPTX, ODP, ODG, ODF, FODT, FODS, FODP, subject to installed LibreOffice filters and fonts.
- PDFs retain their orientation and aspect ratio, fitted inside a labelled A4 evidence frame without cropping. The combined file reproduces document pages; it does not preserve the cryptographic validity of digital signatures in source PDFs. Keep originals in document storage.
- `REPORT_MAX_CONVERTED_BYTES` defaults to 100 MiB per converted document. Tune PHP memory and execution time for the largest permitted case. A case with many files can require multiple conversions, so server request duration must cover the complete case.
- The adapter times out after `REPORT_CONVERSION_TIMEOUT` (5–300 seconds per conversion). The service timeout must be at least as long.
- On Vercel, reports over 3 MB use the authenticated chunk download described in [report-downloads.md](report-downloads.md). Apply the new database migration and use a durable shared database. The browser saves one complete PDF; no document is removed to fit the response ceiling.

After configuring the server, run `php artisan config:clear`. Test a case with PDF, Word/ODT containing tables and embedded images, and image evidence. Open the combined report and verify every page appears under the correct check, including documents from candidate, client, and admin uploads.

References: [Gotenberg 8 office conversion](https://gotenberg.dev/docs/8.x/convert-with-libreoffice/convert-to-pdf), [Gotenberg 8 PDF normalization](https://gotenberg.dev/docs/8.x/manipulate-pdfs/pdfa-pdfua), [LibreOffice PDF CLI options](https://help.libreoffice.org/latest/en-US/text/shared/guide/pdf_params.html), [qpdf CLI](https://qpdf.readthedocs.io/en/stable/cli.html).
