@extends('settings.layout')

@php
    $settingsPage = 'templates';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-header">
        <div>
            <h2 class="mb-0">Edit Message Template</h2>
            <p class="mb-0" style="font-size:14px; font-weight:600;">
                Update your SMS or Email template.
            </p>
        </div>

        <a href="{{ route('settings.messaging.templates.index') }}" class="btn btn-abc-outline">
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

        <form method="POST" action="{{ route('settings.messaging.templates.update', $template->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Channel</label>
                <select name="channel" class="form-control">
                    <option value="sms" {{ old('channel', $template->channel) === 'sms' ? 'selected' : '' }}>SMS</option>
                    <option value="email" {{ old('channel', $template->channel) === 'email' ? 'selected' : '' }}>Email</option>
                </select>
                @error('channel') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Template Name</label>
                <input type="text" name="name" class="form-control"
                       value="{{ old('name', $template->name) }}"
                       placeholder="e.g. Welcome Email - Day 0">
                @error('name') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Subject (Email only)</label>
                <input type="text" name="subject" class="form-control"
                       value="{{ old('subject', $template->subject) }}"
                       placeholder="e.g. Welcome, {{'{{first_name}}'}}">
                @error('subject') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                <div class="text-muted mt-1" style="font-size:13px;">
                    If Channel is SMS, subject is ignored.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Message Body</label>
                <textarea name="body" class="form-control" rows="8"
                          placeholder="Write your message here...">{{ old('body', $template->body) }}</textarea>
                @error('body') <div class="text-danger mt-1">{{ $message }}</div> @enderror

                <div class="text-muted mt-2" style="font-size:13px;">
                    Variables you can use (examples): <code>{{'{{first_name}}'}}</code>, <code>{{'{{last_name}}'}}</code>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Status</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $template->is_active ? '1' : '') ? 'checked' : '' }}>
                    <label class="form-check-label" style="font-weight:700;">
                        Active (available to use)
                    </label>
                </div>
                @error('is_active') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-abc-gold">Save Changes</button>
                <a href="{{ route('settings.messaging.templates.index') }}" class="btn btn-abc-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
