@extends('settings.layout')

@php
    $settingsPage = 'drips';

    $campaignId = $campaign->id ?? null;
    $campaignType = $campaign->type ?? null;

    $typeLabel = $types[$campaignType] ?? $campaignType;

    $showDelayDays = ($campaignType === 'onboarding_timeline');
    $showMmdd = in_array($campaignType, ['date_holiday', 'date_birthday'], true);

    // Policy anniversary (initial draft date) does NOT use MM-DD or delay days
    $isPolicyAnniversary = ($campaignType === 'policy_anniversary');
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-body d-flex justify-content-between align-items-start">
        <div>
            <h2 class="mb-1" style="font-weight:900;">Campaign Steps</h2>
            <div class="text-muted">
                <div><strong>{{ $campaign->name }}</strong></div>
                <div>Type: <span class="badge bg-light text-dark">{{ $typeLabel }}</span></div>
                <div>Status: <span class="badge bg-dark">{{ ucfirst($campaign->status) }}</span></div>
                <div>Channel: <span class="badge bg-light text-dark">{{ strtoupper($campaign->channel_mode) }}</span></div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('settings.messaging.drips.index') }}" class="btn btn-outline-secondary">
                ← Back
            </a>
        </div>
    </div>
</div>

{{-- Flash messages --}}
@if (session('success'))
    <div class="alert alert-success mt-3">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger mt-3">
        <div class="fw-bold mb-1">Please fix the following:</div>
        <ul class="mb-0">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Help / rules --}}
<div class="panel-card mt-3">
    <div class="panel-card-body">
        <h5 class="mb-2" style="font-weight:800;">How scheduling works</h5>

        @if($showDelayDays)
            <div class="text-muted">
                This is a <strong>Timeline</strong> campaign. Each step runs <strong>X days after enrollment</strong>.
            </div>
        @elseif($showMmdd)
            <div class="text-muted">
                This is a <strong>Date-based</strong> campaign. Each step uses a <strong>MM-DD</strong> date (example: <code>12-25</code>).
            </div>
        @elseif($isPolicyAnniversary)
            <div class="text-muted">
                This is a <strong>Policy Anniversary</strong> campaign. The date comes from the client’s
                <strong>Initial Draft Date</strong> on the policy card. Steps run each year on that anniversary date.
            </div>
        @else
            <div class="text-muted">
                This campaign type uses custom scheduling rules.
            </div>
        @endif
    </div>
</div>

{{-- Add Step --}}
<div class="panel-card mt-3">
    <div class="panel-card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="mb-0" style="font-weight:900;">Add Step</h4>
        </div>

        <form method="POST" action="{{ route('settings.messaging.drips.steps.store', $campaignId) }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Template</label>
                    <select name="template_id" class="form-select" required>
                        <option value="">Select template...</option>
                        @foreach($templates as $t)
                            <option value="{{ $t->id }}">
                                [{{ strtoupper($t->channel) }}] {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text text-muted">
                        Uses existing message templates.
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">Order</label>
                    <input type="number" name="step_order" class="form-control" min="1" max="999" placeholder="Auto">
                    <div class="form-text text-muted">
                        Leave blank to append.
                    </div>
                </div>

                @if($showDelayDays)
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Delay (days)</label>
                        <input type="number" name="delay_days" class="form-control" min="0" max="3650" required>
                    </div>
                @endif

                @if($showMmdd)
                    <div class="col-md-2">
                        <label class="form-label fw-bold">MM-DD</label>
                        <input type="text" name="holiday_mmdd" class="form-control" placeholder="12-25" required>
                        <div class="form-text text-muted">Format: MM-DD</div>
                    </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label fw-bold">Send Time</label>
                    <input type="text" name="send_time_local" class="form-control" placeholder="09:00">
                    <div class="form-text text-muted">
                        Server timezone (MVP).
                    </div>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="is_active_new" name="is_active" checked>
                        <label class="form-check-label fw-bold" for="is_active_new">
                            Active
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark">
                    + Add Step
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Existing Steps --}}
<div class="panel-card mt-3">
    <div class="panel-card-body">
        <h4 class="mb-3" style="font-weight:900;">Steps</h4>

        @if(($steps ?? collect())->count() === 0)
            <div class="text-muted">No steps yet. Add your first step above.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:90px;">Order</th>
                            <th>Template</th>
                            @if($showDelayDays)
                                <th style="width:160px;">Delay (days)</th>
                            @endif
                            @if($showMmdd)
                                <th style="width:160px;">MM-DD</th>
                            @endif
                            <th style="width:170px;">Send Time</th>
                            <th style="width:120px;">Active</th>
                            <th style="width:220px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($steps as $s)
                            @php
                                $template = $templates->firstWhere('id', $s->template_id);
                            @endphp

                            <tr>
                                <td class="fw-bold">{{ $s->step_order }}</td>

                                <td>
                                    <div class="fw-bold">
                                        @if($template)
                                            [{{ strtoupper($template->channel) }}] {{ $template->name }}
                                        @else
                                            Template #{{ $s->template_id }}
                                        @endif
                                    </div>
                                    <div class="text-muted" style="font-size: 12px;">
                                        Step ID: {{ $s->id }}
                                    </div>
                                </td>

                                <td colspan="5">
                                    <form method="POST"
                                          action="{{ route('settings.messaging.drips.steps.update', [$campaignId, $s->id]) }}"
                                          class="border rounded p-3 bg-light">
                                        @csrf
                                        @method('PUT')

                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Template</label>
                                                <select name="template_id" class="form-select" required>
                                                    @foreach($templates as $t)
                                                        <option value="{{ $t->id }}" @selected((int)$t->id === (int)$s->template_id)>
                                                            [{{ strtoupper($t->channel) }}] {{ $t->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label fw-bold">Order</label>
                                                <input type="number" name="step_order" class="form-control" min="1" max="999"
                                                       value="{{ $s->step_order }}" required>
                                            </div>

                                            @if($showDelayDays)
                                                <div class="col-md-2">
                                                    <label class="form-label fw-bold">Delay (days)</label>
                                                    <input type="number" name="delay_days" class="form-control" min="0" max="3650"
                                                           value="{{ $s->delay_days ?? '' }}" required>
                                                </div>
                                            @endif

                                            @if($showMmdd)
                                                <div class="col-md-2">
                                                    <label class="form-label fw-bold">MM-DD</label>
                                                    <input type="text" name="holiday_mmdd" class="form-control"
                                                           value="{{ $s->holiday_mmdd ?? '' }}" placeholder="12-25" required>
                                                </div>
                                            @endif

                                            <div class="col-md-2">
                                                <label class="form-label fw-bold">Send Time</label>
                                                <input type="text" name="send_time_local" class="form-control"
                                                       value="{{ $s->send_time_local ?? '' }}" placeholder="09:00">
                                            </div>

                                            <div class="col-md-2 d-flex align-items-end">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1"
                                                           id="active_{{ $s->id }}" name="is_active" @checked($s->is_active)>
                                                    <label class="form-check-label fw-bold" for="active_{{ $s->id }}">
                                                        Active
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 d-flex justify-content-end gap-2">
                                            <button type="submit" class="btn btn-outline-dark">
                                                Save
                                            </button>
                                        </form>

                                            <form method="POST"
                                                  action="{{ route('settings.messaging.drips.steps.destroy', [$campaignId, $s->id]) }}"
                                                  onsubmit="return confirm('Delete this step? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
