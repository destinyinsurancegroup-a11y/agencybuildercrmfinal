@extends('layouts.app')

@section('content')
<div style="padding: 40px;">
    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 20px;">
        Calendar
    </h1>

    <!-- Calendar Container -->
    <div id="calendar" style="background:white; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.08);"></div>
</div>

<!-- Event Modal -->
<div id="eventModal"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.6); justify-content:center; align-items:center;">
    <div style="background:white; padding:25px; border-radius:12px; width:350px;">
        <h3 style="font-size:20px; font-weight:600; margin-bottom:10px;">Add Event</h3>

        <label style="font-size:14px;">Event title:</label>
        <input id="eventTitle"
               type="text"
               style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; margin-bottom:15px;">

        <button id="saveEventBtn"
                style="background:#facc15; padding:10px 18px; border:none; border-radius:8px;
                       font-weight:600; cursor:pointer; width:100%; margin-bottom:8px;">
            Save Event
        </button>

        <button onclick="closeModal()"
                style="background:#bbb; padding:10px 18px; border:none; border-radius:8px;
                       font-weight:600; cursor:pointer; width:100%;">
            Cancel
        </button>
    </div>
</div>

<!-- FullCalendar Styles -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

<!-- FullCalendar Script -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
let selectedDate = null;
let calendar;

function openModal(date) {
    selectedDate = date;
    document.getElementById("eventModal").style.display = "flex";
    document.getElementById("eventTitle").value = "";
}

function closeModal() {
    document.getElementById("eventModal").style.display = "none";
}

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    calendar = new FullCalendar.Calendar(calendarEl, {
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
            openModal(info.dateStr);
        },

        eventClick(info) {
            alert("Event: " + info.event.title);
        },

        events: []
    });

    calendar.render();
});

// Save event handler
document.getElementById("saveEventBtn").addEventListener("click", function () {
    const title = document.getElementById("eventTitle").value.trim();

    if (title === "") {
        alert("Event title cannot be empty");
        return;
    }

    // Add event to calendar
    calendar.addEvent({
        title: title,
        start: selectedDate
    });

    closeModal();
});
</script>

@endsection
