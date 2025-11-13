@extends('layouts.app')

@section('content')
<div class="layout">
  <aside class="sidebar">
    <img src="/assets/images/logo.png" class="sidebar-logo">
    <nav class="sidebar-nav">
      <a href="/dashboard">🏠 Dashboard</a>
      <a href="/all-contacts">👥 All Contacts</a>
      <a href="{{ route('book.business') }}" class="active">📘 Book of Business</a>
      <a href="/leads">💬 Leads</a>
      <a href="/service">🧰 Service</a>
      <a href="/calendar-activity">📅 Calendar / Activity</a>
      <a href="/activity">📊 Activity</a>
      <a href="/billing">💳 Billing</a>
      <a href="/settings">⚙️ Settings</a>
      <a href="/logout">🚪 Logout</a>
    </nav>
  </aside>

  <section class="middle">
    <div class="middle-actions">
      <button class="btn">+ Add New Client</button>
    </div>

    <div class="pager">
      <span class="meta">
        Showing {{ $offset+1 }}–{{ min($offset+$perPage, $total) }} of {{ $total }}
      </span>
    </div>

    <div class="contact-list">
      @foreach ($clients as $c)
        <div class="contact-item {{ $client && $client->id == $c->id ? 'active' : '' }}">
          <a class="name" href="{{ route('book.business', ['id' => $c->id]) }}">{{ $c->name }}</a>
        </div>
      @endforeach
    </div>
  </section>

  <section class="right">
    @if ($client)
      <h1>{{ $client->name }}</h1>
      <div class="phone">{{ $client->phone }}</div>

      <div class="section">
        <h3>Contact Information</h3>
        <div class="field-grid">
          <div class="field"><label>Email</label><span>{{ $client->email }}</span></div>
          <div class="field"><label>Policy Type</label><span>{{ $client->policy_type }}</span></div>
          <div class="field"><label>Premium</label><span>${{ number_format($client->premium_amount, 2) }} / Mo</span></div>
        </div>
      </div>

      <div class="section">
        <h3>Notes</h3>
        @foreach ($notes as $note)
          <div class="note">
            <small>{{ $note->created_at }}</small>
            <div>{{ $note->note }}</div>
          </div>
        @endforeach
      </div>
    @else
      <p>No client selected.</p>
    @endif
  </section>
</div>
@endsection
