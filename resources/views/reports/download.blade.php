<x-layouts.app title="Download Complete Report">
    <style>#report-save[hidden], #report-retry[hidden] { display: none !important; }</style>
    <div class="max-w-2xl mx-auto card">
        <div class="card-body space-y-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-600">Complete case report</p>
            <h1 class="text-2xl font-bold text-[#0f2044]">Your report is ready</h1>
            <p class="text-sm text-slate-600">{{ $case->candidate_name }} &middot; {{ $case->case_number }}<br>All included checks and supporting documents will be saved together as one PDF.</p>
            <progress id="report-progress" value="0" max="{{ $download->parts }}" class="w-full accent-teal-600" aria-label="Report download progress"></progress>
            <p id="report-status" role="status" aria-live="polite" class="text-sm text-slate-600">Preparing your download…</p>
            <button id="report-retry" type="button" class="btn-teal" hidden>Retry download</button>
            <a id="report-save" class="btn-teal" hidden>Save complete PDF</a>
            <p><a href="{{ $backUrl }}" class="text-sm text-teal-700 underline">Back to case</a></p>
            <noscript><p>JavaScript is required to download this large report. Enable JavaScript and reload this page.</p></noscript>
        </div>
    </div>
    <script>
        (() => {
            const config = {{ Illuminate\Support\Js::from([
                'parts' => $download->parts,
                'bytes' => $download->byte_size,
                'sha256' => $download->sha256,
                'filename' => $download->filename,
                'partUrl' => route('reports.download.part', ['download' => $download->id, 'part' => 0]),
            ]) }};
            const status = document.getElementById('report-status');
            const progress = document.getElementById('report-progress');
            const retry = document.getElementById('report-retry');
            const save = document.getElementById('report-save');
            let objectUrl;

            async function download() {
                retry.hidden = true;
                save.hidden = true;
                progress.value = 0;
                const buffers = [];
                try {
                    for (let part = 0; part < config.parts; part++) {
                        const response = await fetch(config.partUrl.replace(/\/0$/, '/' + part), {
                            credentials: 'same-origin', cache: 'no-store', headers: {'Accept': 'application/octet-stream'}
                        });
                        if (!response.ok || response.headers.get('content-type')?.includes('text/html')) {
                            throw new Error(response.status === 410 ? 'This download expired. Return to the case and generate it again.' : 'The download was interrupted. Check your connection and retry.');
                        }
                        buffers.push(await response.arrayBuffer());
                        progress.value = part + 1;
                        status.textContent = 'Downloading report: ' + Math.round(((part + 1) / config.parts) * 100) + '%';
                    }
                    const blob = new Blob(buffers, {type: 'application/pdf'});
                    if (blob.size !== config.bytes) throw new Error('The download is incomplete. Please retry.');
                    if (window.crypto?.subtle) {
                        const digest = await crypto.subtle.digest('SHA-256', await blob.arrayBuffer());
                        const hash = Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('');
                        if (hash !== config.sha256) throw new Error('The download could not be verified. Please retry.');
                    }
                    if (objectUrl) URL.revokeObjectURL(objectUrl);
                    objectUrl = URL.createObjectURL(blob);
                    save.href = objectUrl;
                    save.download = config.filename;
                    save.hidden = false;
                    status.textContent = 'Complete. Your single PDF is ready to save.';
                    save.click();
                } catch (error) {
                    status.textContent = error.message || 'Unable to download the report. Please retry.';
                    retry.hidden = false;
                }
            }
            retry.addEventListener('click', download);
            window.addEventListener('pagehide', () => { if (objectUrl) URL.revokeObjectURL(objectUrl); });
            download();
        })();
    </script>
</x-layouts.app>
