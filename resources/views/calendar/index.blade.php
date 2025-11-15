@extends('layouts.app')

@section('content')
<div style="padding: 40px;">
    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 20px;">
        Calendar
    </h1>

    <!-- Calendar Container -->
    <div id="calendar" style="background:white; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.08);"></div>
</div>

<!-- FullCalendar Styles -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

<!-- FullCalendar Script -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },

        selectable: true,
        editable: true,

        dateClick(info) {
            alert("Clicked date: " + info.dateStr);
        },

        eventClick(info) {
            alert("Event: " + info.event.title);
        },

        events: [
            {
                title: 'Demo Event',
                start: new Date().toISOString().slice(0, 10)
            }
        ]
    });

    calendar.render();
});
</script>
@endsection
