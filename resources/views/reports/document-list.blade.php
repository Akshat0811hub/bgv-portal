@foreach($documents as $document)
    @php $result = $documentResults[$document->id]; @endphp
    <div class="file-row">
        <strong>{{ $loop->iteration }}. {{ $document->original_name }}</strong><br>
        <span class="muted">Uploaded by {{ ucfirst($document->uploaded_by_role ?: 'Unknown') }} / {{ $document->formatted_size }}</span><br>
        @if($result['status'] === 'embedded')
            <span class="evidence-included">Included on the following pages.</span>
        @elseif($result['status'] === 'linked')
            <span class="evidence-linked">Preview unavailable in this report. Open the original file using the link below.</span>
        @else
            <span class="evidence-linked">Original file unavailable. Ask your administrator to restore or re-upload it.</span>
        @endif
        @if($result['status'] !== 'missing')
            <br><a class="original-link" href="{{ route('documents.view', $document) }}">Open original file: {{ $document->original_name }}</a>
        @endif
    </div>
@endforeach
