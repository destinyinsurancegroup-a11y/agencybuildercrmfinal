@extends('layouts.app')

@section('content')

<div style="padding: 25px 40px;">
    <h1 style="font-size: 28px; font-weight: 700; color:#111827;">Calendar</h1>
    <p style="color:#4b5563; margin-bottom: 20px;">Manage your events and reminders.</p>

    <!-- CALENDAR CARD -->
    <div style="
        background: #ffffff;
        padding: 25px;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        min-height: 650px;
        color:#111827;
    ">
        <div id="calendar" style="min-height: 600px;"></div>
    </div>
</div>

<!-- FullCalendar -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<!-- Bootstrap Modal -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<!-- EVENT MODAL -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="color:#111827;">

            <div class="modal-header">
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
                    <label>Start</label>
                    <input type="datetime-local" id="eventStart" class="form-control">
                </div>

                <div class="mb-3">
                    <label>End</label>
                    <input type="datetime-local" id="eventEnd" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Color</label>
                    <input type="color" id="eventColor" class="form-control form-control-color">
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-danger d-none" id="deleteEventBtn">Delete</button>
                <button class="btn btn-primary" id="saveEventBtn">Save</button>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    let eventModalEl = document.getElementById("eventModal");
    let eventModal = new bootstrap.Modal(eventModalEl);

    let calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {

        initialView: "dayGridMonth",
        selectable: true,
        editable: false,
        height: "auto",

        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay"
        },

        eventTextColor: "#111827",

        events: "/calendar/events",

        /* CREATE */
        select: function(info) {
            document.getElementById("modalTitle").innerText = "Create Event";
            document.getElementById("deleteEventBtn").classList.add("d-none");

            document.getElementById("eventId").value = "";
            document.getElementById("eventTitle").value = "";
            document.getElementById("eventStart").value = info.startStr + "T00:00";
            document.getElementById("eventEnd").value = info.endStr + "T00:00";
            document.getElementById("eventColor").value = "#facc15";

            eventModal.show();
        },

        /* EDIT */
        eventClick: function(info) {
            let e = info.event;

            document.getElementById("modalTitle").innerText = "Edit Event";
            document.getElementById("deleteEventBtn").classList.remove("d-none");

            document.getElementById("eventId").value = e.id;
            document.getElementById("eventTitle").value = e.title;
            document.getElementById("eventColor").value = e.backgroundColor || "#facc15";
            document.getElementById("eventStart").value = e.start.toISOString().slice(0, 16);
            document.getElementById("eventEnd").value = e.end ? e.end.toISOString().slice(0, 16) : "";

            eventModal.show();
        }
    });

    calendar.render();


    /* SAVE EVENT (CREATE / UPDATE) */
    document.getElementById("saveEventBtn").onclick = function () {

        let id = document.getElementById("eventId").value;

        let payload = {
            title: document.getElementById("eventTitle").value,
            start: document.getElementById("eventStart").value,
            end: document.getElementById("eventEnd").value,
            color: document.getElementById("eventColor").value
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
            eventModal.hide();
            calendar.refetchEvents();
        });
    };


    /* DELETE EVENT */
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
            eventModal.hide();
            calendar.refetchEvents();
        });
    };

});
</script>

@endsection
