@extends('layouts.app')

@section('content')

<!-- PAGE WRAPPER -->
<div style="padding: 32px 48px; background-color:#f9fafb; min-height:100vh;">

    <h1 style="font-size: 28px; font-weight: 700; color:#111827;">Calendar</h1>
    <p style="color:#4b5563; margin-bottom: 20px;">Manage your events and reminders.</p>

    <div style="
        background:#ffffff;
        padding: 28px;
        border-radius: 22px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 18px 40px rgba(0,0,0,0.12);
        border-top: 4px solid #facc15;
        min-height: 650px;
        color:#111827;
    ">
        <div id="calendar" style="min-height: 600px;"></div>
    </div>

</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<!-- ================================
     FULLCALENDAR HARD OVERRIDES
================================ -->
<style>
/* Base fonts */
#calendar, #calendar .fc {
    font-family: system-ui, sans-serif;
    color: #111827;
}

/* Header titles + day numbers */
.fc .fc-toolbar-title,
.fc .fc-col-header-cell-cushion,
.fc .fc-daygrid-day-number {
    color: #111827 !important;
}

/* 🔥 REMOVE TODAY HIGHLIGHT */
.fc-daygrid-day.fc-day-today {
    background:none !important;
}

/* 🔥 REMOVE SELECTED-DAY HIGHLIGHT */
.fc-daygrid-day.fc-daygrid-day-selected {
    background:none !important;
}

/* 🔥 REMOVE DRAG/SELECTION HIGHLIGHT */
.fc-highlight {
    background:none !important;
}

/* 🔥 REMOVE BACKGROUND EVENT LAYER */
.fc-daygrid-bg-harness .fc-event {
    background:none !important;
    border:none !important;
}

/* 🔥 REMOVE EVENT FOCUS HIGHLIGHT */
.fc-event:focus,
.fc-event:active,
.fc-event:focus-visible {
    outline:none !important;
    box-shadow:none !important;
    border:none !important;
}

/* Event pill styling */
.fc .fc-daygrid-event {
    background:#facc15 !important;
    border:none !important;
    border-radius:999px !important;
    padding:2px 8px !important;
}
.fc .fc-event-title {
    color:#111827 !important;
    font-weight:600 !important;
}

/* Gold buttons */
.fc .fc-button-primary {
    background:#facc15 !important;
    border:#facc15 !important;
    color:#111 !important;
    border-radius:999px !important;
    font-weight:600;
}
.fc .fc-button-primary:hover {
    background:#fbbf24 !important;
}
</style>



<!-- ==========================
     EVENT MODAL
=========================== -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="color:#111827;">

            <div class="modal-header" style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                <h5 class="modal-title" id="modalTitle">Create Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="eventId">

                <div class="mb-3">
                    <label>Title</label>
                    <input type="text" id="eventTitle" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Date & Time</label>
                    <input type="datetime-local" id="eventStart" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Location (optional)</label>
                    <input type="text" id="eventLocation" class="form-control">
                </div>

            </div>

            <div class="modal-footer" style="background:#f9fafb; border-top:1px solid #e5e7eb;">
                <button class="btn btn-danger d-none" id="deleteEventBtn">Delete</button>
                <button class="btn" id="saveEventBtn"
                        style="background:#facc15; color:#000; font-weight:600;">
                    Save
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ==========================
     FULLCALENDAR LOGIC
=========================== -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    let modal = new bootstrap.Modal(document.getElementById("eventModal"));

    let calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {

        initialView: "dayGridMonth",

        /* 🔥 THE TRUE FIX — NO SELECTION ENGINE AT ALL */
        selectable: false,

        eventDisplay: "block",
        editable: false,
        height: "auto",

        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay"
        },

        events: "/calendar/events",


        /* ------------------------
           CREATE EVENT
        ------------------------- */
        dateClick: function(info) {

            document.getElementById("modalTitle").innerText = "Create Event";
            document.getElementById("deleteEventBtn").classList.add("d-none");

            document.getElementById("eventId").value = "";
            document.getElementById("eventTitle").value = "";
            document.getElementById("eventStart").value = info.dateStr + "T00:00";
            document.getElementById("eventLocation").value = "";

            modal.show();
        },


        /* ------------------------
           EDIT EVENT
        ------------------------- */
        eventClick: function(info) {
            let e = info.event;

            document.getElementById("modalTitle").innerText = "Edit Event";
            document.getElementById("deleteEventBtn").classList.remove("d-none");

            document.getElementById("eventId").value = e.id;
            document.getElementById("eventTitle").value = e.title;
            document.getElementById("eventStart").value = e.start.toISOString().slice(0,16);
            document.getElementById("eventLocation").value = e.extendedProps.location ?? "";

            modal.show();
        }
    });

    calendar.render();


    /* ------------------------
       SAVE EVENT
    ------------------------- */
    document.getElementById("saveEventBtn").onclick = function () {

        let id = document.getElementById("eventId").value;

        let payload = {
            title: document.getElementById("eventTitle").value,
            start: document.getElementById("eventStart").value,
            location: document.getElementById("eventLocation").value
        };

        let url = id ? `/calendar/events/${id}` : "/calendar/events";
        let method = id ? "PUT" : "POST";

        fetch(url, {
            method: method,
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify(payload)
        })
        .then(() => {
            modal.hide();
            calendar.refetchEvents();
        });
    };


    /* ------------------------
       DELETE EVENT
    ------------------------- */
    document.getElementById("deleteEventBtn").onclick = function () {

        let id = document.getElementById("eventId").value;
        if (!confirm("Delete this event?")) return;

        fetch(`/calendar/events/${id}`, {
            method: "DELETE",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            }
        })
        .then(() => {
            modal.hide();
            calendar.refetchEvents();
        });
    };

});
</script>

@endsection
