Dear {{ $case->candidate_name }},

{{ $organizationName }} has initiated a background verification case for you through Orbit Verify.
Case reference: {{ $case->case_number }}

Verification checks:
@foreach($checkNames as $name)
- {{ $name }}
@endforeach

@if($requestDocuments)
Please submit the requested documents using your personal upload link:
{{ $uploadUrl }}
@if($expiresAt)
Valid until: {{ $expiresAt->format('d M Y, h:i A T') }}
@endif
Keep this link private.
@else
Your organization is handling document submission. No upload is requested in this email.
@endif

If you were not expecting this verification, contact your hiring organization before sharing documents.

Orbit Verify
