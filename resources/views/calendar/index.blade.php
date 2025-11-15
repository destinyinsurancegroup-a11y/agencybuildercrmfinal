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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            selectable: true,
            height: "auto",

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },

            events: '/calendar/events', // ← LOAD FROM DB

            select: function(info) {
                const title = prompt('Event Title:');
                if (title) {
                    const color = '#facc15'; // Destiny Gold

                    fetch('/calendar/events', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            title: title,
                            start: info.startStr,
                            end: info.endStr,
                            color: color
                        })
                    })
                    .then(response => response.json())
                    .then(event => {
                        calendar.addEvent(event); // Add instantly
                    })
                    .catch(error => console.error('Error saving event:', error));
                }
            },

            eventColor: '#facc15',
            displayEventEnd: true,
        });

        calendar.render();
    });
</script>

{{-- Style the calendar to match your CRM --}}
<style>
    #calendar {
        background: white;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        max-width: 1200px;
    }
    .fc .fc-toolbar-title {
        font-weight: 700;
        font-size: 20px;
    }
    .fc-button {
        background: #111827 !important;
        border: none !important;
        color: #facc15 !important;
        border-radius: 6px !important;
        padding: 6px 12px !important;
    }
</style>

@endsection
