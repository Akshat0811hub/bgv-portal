@props(['check', 'case', 'schema' => []])
@php
    $fileFields = collect($schema)->flatMap(fn ($fields) => $fields)->keyBy('name');
    $groups = $check->documents->sortBy('id')->groupBy(fn ($document) => $document->field_key ?: '_file');
@endphp
<section class="check-evidence" aria-label="Supporting documents">
    <div class="check-evidence-heading">
        <div><h3>Supporting documents <span>{{ $check->documents->count() }}</span></h3><p>Files from candidate, client and agency are included with this check in the complete report.</p></div>
    </div>
    @forelse($groups as $fieldKey => $documents)
        @php
            $groupLabel = $fieldKey === 'verified' ? 'Agency evidence' : ($fileFields[$fieldKey]['label'] ?? ($fieldKey === '_file' ? 'Submitted documents' : ucwords(str_replace('_', ' ', trim($fieldKey, '_')))));
        @endphp
        <div class="check-evidence-group">
            <h4>{{ $groupLabel }} <span>{{ $documents->count() }} {{ $documents->count() === 1 ? 'file' : 'files' }}</span></h4>
            <div class="check-evidence-grid">
                @foreach($documents as $doc)
                    @php
                        $extension = strtoupper(pathinfo($doc->original_name, PATHINFO_EXTENSION) ?: 'FILE');
                        $isImage = in_array(strtolower($doc->mime_type), ['image/jpeg', 'image/png', 'image/webp']);
                        $canDelete = auth()->user()->isAdmin() || ($doc->uploaded_by_role === 'client' && !$case->isCompletedStatus());
                    @endphp
                    <article class="check-file-card">
                        <a class="check-file-preview {{ $isImage ? 'check-file-preview-image' : '' }}" href="{{ route('documents.view', $doc) }}" target="_blank" rel="noopener noreferrer" aria-label="Open {{ $doc->original_name }}">
                            @if($isImage)
                                <img src="{{ route('documents.view', $doc) }}" loading="lazy" alt="{{ $doc->original_name }}">
                            @else
                                <svg width="25" height="25" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M8 13h8M8 17h5"/></svg>
                                <span>{{ $extension }}</span>
                            @endif
                        </a>
                        <div class="check-file-content">
                            <a class="check-file-name" href="{{ route('documents.view', $doc) }}" target="_blank" rel="noopener noreferrer">{{ $doc->original_name }}</a>
                            <p>{{ $extension }} <span>·</span> {{ $doc->formatted_size }}</p>
                            <span @class(['check-file-uploader', 'check-file-uploader-agency' => $doc->uploaded_by_role === 'admin'])>{{ match($doc->uploaded_by_role) { 'admin' => 'Agency upload', 'client' => 'Client upload', default => 'Candidate upload' } }}</span>
                        </div>
                        <div class="check-file-actions">
                            <a href="{{ route('documents.view', $doc) }}" target="_blank" rel="noopener noreferrer" class="check-file-open">Open <span aria-hidden="true">↗</span></a>
                            @if($canDelete)
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}" onsubmit="return confirm('Remove this document?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="check-file-remove" aria-label="Remove {{ $doc->original_name }}" title="Remove document"><svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16M9 7V4h6v3"/></svg></button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @empty
        <p class="check-comparison-empty">No supporting files uploaded for this check yet.</p>
    @endforelse
</section>
