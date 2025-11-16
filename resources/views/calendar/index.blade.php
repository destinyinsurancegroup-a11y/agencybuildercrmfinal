{{-- resources/views/calendar/index.blade.php --}}
@extends('layouts.app')

@section('content')

<div style="padding: 25px 40px;">
    <h1 style="font-size: 28px; font-weight: 700; color:#111827;">Calendar</h1>
    <p style="color:#4b5563; margin-bottom: 20px;">Manage your events and reminders.</p>

    {{-- FullCalendar Container --}}
    <div id="calendar"></div>
</div>

{{-- FullCalendar CSS --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

{{-- FullCalendar Script --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<style>
    /* CRM Modal Styling */
    #eventModalBackdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    #eventModal {
        background: #ffffff;
        padding: 25px;
        width: 420px;
        border-radius: 16px;
        border: 2px solid #facc15;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }

    .modal-input {
        width: 100%;
        margin-bottom: 12px;
        padding: 10px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        font-size: 14px;
    }

    .modal-btn {
        padding: 10px 18px;
        border-radius: 999px;
        font-weight: 600;
        border: none;
        cursor: pointer;
    }

    .btn-save {
        background: #facc15;
        color: #111827;
    }

    .btn-cancel {
        background: #9ca3af;
        color: #111827;
    }
</style>

{{-- CRM Modal HTML --}}
<div id="eventModalBackdrop">
    <div id="eventModal">
        <h2 style="font-size: 20px; font-weight: 700;">Create Event</h2>

        <input id="eventTitle" class="modal-input" type="text" placeholder="Event Title">

        <label>Date</label>
        <input id="eventDate" class="modal-input" type="date">

        <label>Time</label>
        <input id="eventTime" class="modal-input" type="time">

        {{-- Future fields, disabled for now (DB does not support them yet) --}}
        {{-- <label>Location</label>
        <input id="eventLocation" class="modal-input" type="text" placeholder="Optional">

        <label>Reminder (minutes before)</label>
        <input id="eventReminder" class="modal-input" type="number" placeholder="Optional"> --}}

        <div style="text-align: right; margin-top: 10px;">
            <button class="modal-btn btn-cancel" onclick="closeEventModal()">Cancel</button>
            <button class="modal-btn btn-save" onclick="saveEvent()">Save Event</button>
        </div>
    </div>
</div>

<script>
    let calendar;
    let selectedDate;

    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            selectable: true,
            height: "auto",

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },

            events: '/calendar/events',

            select: function(info) {
                selectedDate = info.startStr;
                openEventModal();
            },

            eventColor: '#facc15',
            displayEventEnd: true,
        });

        calendar.render();
    });

    function openEventModal() {
        document.getElementById('eventModalBackdrop').style.display = "flex";
        document.getElementById('eventDate').value = selectedDate;
    }

    function closeEventModal() {
        document.getElementById('eventModalBackdrop').style.display = "none";
    }

    function saveEvent() {
        const title = document.getElementById('eventTitle').value;
        const date = document.getElementById('eventDate').value;
        const time = document.getElementById('eventTime').value;

        if (!title) {
            alert("Please enter an event title.");
            return;
        }

        const startDateTime = time ? `${date} ${time}` : date;

        fetch('/calendar/events', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                title: title,
                start: startDateTime,
                end: startDateTime,
                color: '#facc15'
            })
        })
        .then(res => res.json())
        .then(event => {
            calendar.addEvent({
                id: event.id,
                title: event.title,
                start: event.start,
                end: event.end,
                color: event.color
            });
            closeEventModal();
        })
        .catch(err => console.error("Save error:", err));
    }
</script>

@endsection
