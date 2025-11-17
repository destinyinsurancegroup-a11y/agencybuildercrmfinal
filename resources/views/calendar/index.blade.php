@extends('layouts.app')

@section('content')

<style>
    /* === CRM CARD STYLE === */
    .crm-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.10);
        border: 1px solid #e5e7eb;
    }

    /* === FULLCALENDAR HEADER === */
    .fc-toolbar-title {
        font-size: 26px !important;
        font-weight: 700;
        color: #111827;
    }

    /* === CRM GOLD BUTTON === */
    .crm-btn-gold {
        background: #facc15 !important;
        color: #111827 !important;
        font-weight: 600;
        border-radius: 10px !important;
        border: none !important;
        padding: 8px 14px !important;
    }

    /* === MODAL === */
    .crm-modal {
        border-radius: 18px !important;
        border: 2px solid #facc15;
        padding: 10px;
        background: #ffffff;
    }

    .crm-modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
    }

    .form-control {
        border-radius: 10px !important;
    }
</style>

<h1 style="font-size:28px; font-weight:700; color:#111827;">Calendar</h1>
<p style="color:#4b5563; margin-bottom:20px;">Manage your events and reminders.</p>

<div class="crm-card mb-4">
    <div id="calendar"></div>
</div>

<!-- MODAL ONLY — no bottom form -->
<div class="modal fade" id="eventModal">
    <div class="modal-dialog">
        <div class="modal-content crm-modal">

            <div class="modal-header border-0">
                <h5 class="crm-modal-title" id="modalTitle">Event</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="eventId">

                <label>Title</label>
                <input id="eventTitle" class="form-control mb-3" type="text">

                <label>Start</label>
                <input id="eventStart" class="form-control mb-3" type="datetime-local">

                <label>End</label>
                <input id="eventEnd" class="form-control mb-3" type="datetime-local">

                <label>Color</label>
                <input id="eventColor" class="form-control form-control-color" type="color" value="#facc15">
            </div>

            <div class="modal-footer border-0">
                <button id="deleteEventBtn" class="btn btn-danger d-none" style="border-radius:10px;">Delete</button>
                <button id="saveEventBtn" class="crm-btn-gold">Save</button>
            </div>

        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const csrf = "{{ csrf_token() }}";

    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        selectable: true,
        editable: true,
        height: "auto",

        events: "/calendar/events",

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },

        /* ==========================
            CREATE EVENT (click + drag)
        =========================== */
        select(info) {
            document.getElementById("modalTitle").innerText = "Create Event";
            document.getElementById("deleteEventBtn").classList.add("d-none");

            document.getElementById("eventId").value = "";
            document.getElementById("eventTitle").value = "";
            document.getElementById("eventStart").value = info.startStr + "T08:00";
            document.getElementById("eventEnd").value = "";
            document.getElementById("eventColor").value = "#facc15";

            new bootstrap.Modal(document.getElementById('eventModal')).show();
        },

        /* ==========================
            EDIT EVENT (click existing)
        =========================== */
        eventClick(info) {
            const e = info.event;

            document.getElementById("modalTitle").innerText = "Edit Event";
            document.getElementById("deleteEventBtn").classList.remove("d-none");

            document.getElementById("eventId").value = e.id;
            document.getElementById("eventTitle").value = e.title;
            document.getElementById("eventColor").value = e.backgroundColor;

            document.getElementById("eventStart").value = e.start.toISOString().slice(0,16);
            document.getElementById("eventEnd").value = e.end ? e.end.toISOString().slice(0,16) : "";

            new bootstrap.Modal(document.getElementById('eventModal')).show();
        }
    });

    calendar.render();


    /* ==========================
        SAVE (CREATE / UPDATE)
    =========================== */
    document.getElementById("saveEventBtn").onclick = function () {

        const id = document.getElementById("eventId").value;

        const payload = {
            title: document.getElementById("eventTitle").value,
            start: document.getElementById("eventStart").value,
            end: document.getElementById("eventEnd").value,
            color: document.getElementById("eventColor").value,
        };

        fetch(id ? `/calendar/events/${id}` : "/calendar/events", {
            method: id ? "PUT" : "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
                "Accept": "application/json"
            },
            body: JSON.stringify(payload)
        })
        .then(() => {
            bootstrap.Modal.getInstance(document.getElementById("eventModal")).hide();
            calendar.refetchEvents();
        });
    };


    /* ==========================
        DELETE
    =========================== */
    document.getElementById("deleteEventBtn").onclick = function () {
        const id = document.getElementById("eventId").value;

        fetch(`/calendar/events/${id}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": csrf,
                "Accept": "application/json"
            }
        })
        .then(() => {
            bootstrap.Modal.getInstance(document.getElementById("eventModal")).hide();
            calendar.refetchEvents();
        });
    };

});
</script>

@endsection
