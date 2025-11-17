@extends('layouts.app')

@section('content')

<!-- PAGE WRAPPER – match dashboard background -->
<div style="padding: 32px 48px; background-color:#f9fafb; min-height:100vh;">

    <!-- PAGE TITLE -->
    <h1 style="font-size: 28px; font-weight: 700; color:#111827;">
        Calendar
    </h1>
    <p style="color:#4b5563; margin-bottom: 20px;">
        Manage your events and reminders.
    </p>

    <!-- CALENDAR CARD – match dashboard card -->
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

<!-- ================================================
     FullCalendar + Bootstrap (CDN)
===================================================-->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<!-- ================================================
     FULLCALENDAR DASHBOARD STYLE OVERRIDES
===================================================-->
<style>
    /* Base font + text color inside calendar */
    #calendar,
    #calendar .fc {
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #111827;
    }

    /* Toolbar title + days */
    .fc .fc-toolbar-title,
    .fc .fc-col-header-cell-cushion,
    .fc .fc-daygrid-day-number {
        color: #111827;
    }

    /* Today highlight */
    .fc .fc-day-today {
        background-color: rgba(250, 204, 21, 0.14) !important;
    }

    /* FullCalendar gold buttons */
    .fc .fc-button-primary {
        background-color: #facc15 !important;
        border-color: #facc15 !important;
        color: #111827 !important;
        font-weight: 600;
        border-radius: 999px !important;
        padding: 0.35rem 0.9rem !important;
    }

    .fc .fc-button-primary:hover {
        background-color: #fbbf24 !important;
        border-color: #fbbf24 !important;
    }

    /* Event pill style */
    .fc .fc-daygrid-event {
        border-radius: 999px;
        background: #facc15 !important;
        border: none !important;
        padding: 2px 6px;
    }

    .fc .fc-daygrid-event .fc-event-title {
        color: #111827 !important;
        font-weight: 600;
    }

    /*********************************************
        REMOVE ALL EVENT AND DAY HIGHLIGHTS
    **********************************************/
    .fc-daygrid-day.fc-daygrid-day-selected {
        background: none !important;
    }

    .fc-highlight {
        background: none !important;
    }

    .fc-event-selected,
    .fc-event-mirror,
    .fc-event-selected .fc-event-main {
        background: #facc15 !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .fc-daygrid-event:focus,
    .fc-daygrid-event:active,
    .fc-daygrid-event:focus-visible {
        outline: none !important;
        border: none !important;
        box-shadow: none !important;
    }
</style>


<!-- ================================================
     EVENT MODAL
===================================================-->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="color:#111827;">

            <div class="modal-header" style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                <h5 class="modal-title" id="modalTitle" style="color:#111827;">
                    Create Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="eventId">

                <div class="mb-3">
                    <label style="color:#111827;">Title</label>
                    <input type="text" id="eventTitle" class="form-control">
                </div>

                <div class="mb-3">
                    <label style="color:#111827;">Start (Date &amp; Time)</label>
                    <input type="datetime-local" id="eventStart" class="form-control">
                </div>

                <div class="mb-3">
                    <label style="color:#111827;">End (Date &amp; Time)</label>
                    <input type="datetime-local" id="eventEnd" class="form-control">
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


<!-- ================================================
     FULLCALENDAR LOGIC
===================================================-->
<script>
document.addEventListener("DOMContentLoaded", function () {

    let modalEl = document.getElementById("eventModal");
    let modal = new bootstrap.Modal(modalEl);

    let calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {

        initialView: "dayGridMonth",

        /* DISABLE HIGHLIGHTED DATE SELECTION COMPLETELY */
        selectable: true,
        selectMinDistance: 99999,   // disables visual selection highlight
        selectOverlap: false,

        editable: false,
        height: "auto",

        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay"
        },

        eventTextColor: "#111827",

        events: "/calendar/events",


        /* ================================
           CREATE EVENT (manual click)
        =================================*/
        dateClick: function(info) {

            document.getElementById("modalTitle").innerText = "Create Event";
            document.getElementById("deleteEventBtn").classList.add("d-none");

            document.getElementById("eventId").value = "";
            document.getElementById("eventTitle").value = "";

            document.getElementById("eventStart").value = info.dateStr + "T00:00";
            document.getElementById("eventEnd").value = info.dateStr + "T00:00";

            modal.show();
        },


        /* ================================
           EDIT EVENT
        =================================*/
        eventClick: function(info) {
            let e = info.event;

            document.getElementById("modalTitle").innerText = "Edit Event";
            document.getElementById("deleteEventBtn").classList.remove("d-none");

            document.getElementById("eventId").value = e.id;
            document.getElementById("eventTitle").value = e.title;

            document.getElementById("eventStart").value = e.start.toISOString().slice(0, 16);
            document.getElementById("eventEnd").value = e.end ? e.end.toISOString().slice(0, 16) : "";

            modal.show();
        }
    });

    calendar.render();


    /* ================================
       SAVE (CREATE / UPDATE)
    =================================*/
    document.getElementById("saveEventBtn").onclick = function () {

        let id = document.getElementById("eventId").value;

        let payload = {
            title: document.getElementById("eventTitle").value,
            start: document.getElementById("eventStart").value,
            end: document.getElementById("eventEnd").value
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


    /* ================================
       DELETE
    =================================*/
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
