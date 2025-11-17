@extends('layouts.app')

@section('content')

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

<style>
/* REMOVE ALL HIGHLIGHTS */
.fc-daygrid-day.fc-day-today,
.fc-daygrid-day.fc-daygrid-day-selected,
.fc-highlight {
    background:none !important;
}

/* Event text only */
.fc-daygrid-event {
    background:none !important;
    border:none !important;
    padding:0 !important;
}

.fc-event-title,
.fc-event-time {
    font-size:12px;
    color:#111827 !important;
    font-weight:600 !important;
}

.event-location {
    display:block;
    font-size:11px;
    color:#374151;
}
</style>


<!-- Modal -->
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
                    <label>Location</label>
                    <input type="text" id="eventLocation" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-danger d-none" id="deleteEventBtn">Delete</button>
                <button class="btn" id="saveEventBtn" style="background:#facc15; font-weight:600;">
                    Save
                </button>
            </div>

        </div>
    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

    let modal = new bootstrap.Modal(document.getElementById("eventModal"));

    let calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {

        initialView: "dayGridMonth",
        selectable: false,
        editable: false,

        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay"
        },

        events: "/calendar/events",

        eventContent: function(arg) {
            let time = arg.timeText ? arg.timeText + " — " : "";
            let title = arg.event.title;
            let location = arg.event.extendedProps.location ?? "";

            return {
                html: `
                    <div>
                        ${time}${title}
                        ${location ? `<span class="event-location">${location}</span>` : ""}
                    </div>
                `
            };
        },

        dateClick: function(info) {
            document.getElementById("eventId").value = "";
            document.getElementById("eventTitle").value = "";
            document.getElementById("eventStart").value = info.dateStr + "T00:00";
            document.getElementById("eventLocation").value = "";
            document.getElementById("deleteEventBtn").classList.add("d-none");
            document.getElementById("modalTitle").innerText = "Create Event";
            modal.show();
        },

        eventClick: function(info) {
            let e = info.event;

            document.getElementById("eventId").value = e.id;
            document.getElementById("eventTitle").value = e.title;
            document.getElementById("eventStart").value = e.start.toISOString().slice(0,16);
            document.getElementById("eventLocation").value = e.extendedProps.location ?? "";
            document.getElementById("deleteEventBtn").classList.remove("d-none");
            document.getElementById("modalTitle").innerText = "Edit Event";
            modal.show();
        }
    });

    calendar.render();


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
        }).then(() => {
            modal.hide();
            calendar.refetchEvents();
        });
    };


    document.getElementById("deleteEventBtn").onclick = function () {
        let id = document.getElementById("eventId").value;
        if (!confirm("Delete this event?")) return;

        fetch(`/calendar/events/${id}`, {
            method: "DELETE",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            }
        }).then(() => {
            modal.hide();
            calendar.refetchEvents();
        });
    };

});
</script>

@endsection
