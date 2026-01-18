@extends('settings.layout')

@php
    $settingsPage = 'templates';

    // Default channel:
    // - If you clicked "+ New Email Template" we pass ?channel=email
    // - Otherwise default to sms
    $defaultChannel = request('channel', 'sms');
    $currentChannel = old('channel', $defaultChannel);

    // Back should go to the correct library
    $backUrl = $currentChannel === 'email'
        ? route('settings.messaging.templates.email')
        : route('settings.messaging.templates.sms');
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-header">
        <div>
            <h2 class="mb-0">Create Message Template</h2>
            <p class="mb-0" style="font-size:14px; font-weight:600;">
                Build an SMS or Email template you can reuse.
            </p>
        </div>

        <a href="{{ $backUrl }}" class="btn btn-abc-outline">
            ← Back
        </a>
    </div>

    <div class="panel-card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div style="font-weight:800;">Please fix the errors below.</div>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.messaging.templates.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Channel</label>
                <select id="channelSelect" name="channel" class="form-control">
                    <option value="sms" {{ $currentChannel === 'sms' ? 'selected' : '' }}>SMS</option>
                    <option value="email" {{ $currentChannel === 'email' ? 'selected' : '' }}>Email</option>
                </select>
                @error('channel') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Template Name</label>
                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="{{ old('name') }}"
                    placeholder="e.g. Welcome Email - Day 0"
                >
                @error('name') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- ✅ SUBJECT: ONLY FOR EMAIL --}}
            <div id="subjectWrap" class="mb-3" style="{{ $currentChannel === 'email' ? '' : 'display:none;' }}">
                <label class="form-label">Subject</label>
                <input
                    type="text"
                    name="subject"
                    class="form-control"
                    value="{{ old('subject') }}"
                    placeholder="e.g. Welcome, &#123;&#123;first_name&#125;&#125;"
                >
                @error('subject') <div class="text-danger mt-1">{{ $message }}</div> @enderror

                <div class="text-muted mt-1" style="font-size:13px;">
                    Subject is required for Email templates.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Message Body</label>
                <textarea
                    name="body"
                    class="form-control"
                    rows="8"
                    placeholder="Write your message here..."
                >{{ old('body') }}</textarea>
                @error('body') <div class="text-danger mt-1">{{ $message }}</div> @enderror

                <div class="text-muted mt-2" style="font-size:13px;">
                    Variables you can use (examples):
                    <code>&#123;&#123;first_name&#125;&#125;</code>,
                    <code>&#123;&#123;last_name&#125;&#125;</code>,
                    <code>&#123;&#123;agent_name&#125;&#125;</code>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Status</label>
                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', '1') ? 'checked' : '' }}
                    >
                    <label class="form-check-label" style="font-weight:700;">
                        Active (available to use)
                    </label>
                </div>
                @error('is_active') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-abc-gold">Save Template</button>
                <a href="{{ $backUrl }}" class="btn btn-abc-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const channel = document.getElementById('channelSelect');
        const subjectWrap = document.getElementById('subjectWrap');

        if (!channel || !subjectWrap) return;

        function toggleSubject() {
            const isEmail = channel.value === 'email';
            subjectWrap.style.display = isEmail ? '' : 'none';

            // Optional: if switching to SMS, clear subject field
            if (!isEmail) {
                const subjectInput = subjectWrap.querySelector('input[name="subject"]');
                if (subjectInput) subjectInput.value = '';
            }
        }

        channel.addEventListener('change', toggleSubject);
        toggleSubject();
    })();
</script>
@endsection
