<x-layouts.app title="Company Profile">

<div class="max-w-2xl">
    <div class="page-header">
        <div>
            <h1 class="page-title">Company Profile</h1>
            <p class="page-subtitle">Manage your organization's details</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('client.profile.update') }}" class="space-y-5">
                @csrf @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Company Name</label>
                        <input type="text" value="{{ $organization->name }}" class="form-input" disabled>
                        <p class="form-hint">Contact your administrator to change the company name.</p>
                    </div>
                    <div>
                        <label class="form-label">Contact Email</label>
                        <input type="email" value="{{ auth()->user()->email }}" class="form-input" disabled>
                    </div>
                    <div>
                        <label class="form-label">GSTIN</label>
                        <input name="gstin" type="text" value="{{ old('gstin', $organization->gstin) }}"
                               class="form-input @error('gstin') border-red-400 @enderror" placeholder="22AAAAA0000A1Z5">
                        @error('gstin') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Contact Phone</label>
                        <input name="contact_phone" type="tel" value="{{ old('contact_phone', $organization->contact_phone) }}"
                               class="form-input @error('contact_phone') border-red-400 @enderror" placeholder="+91 XXXXX XXXXX">
                        @error('contact_phone') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Company Address</label>
                        <textarea name="address" rows="3" class="form-textarea @error('address') border-red-400 @enderror"
                                  placeholder="Registered office address">{{ old('address', $organization->address) }}</textarea>
                        @error('address') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

</x-layouts.app>
