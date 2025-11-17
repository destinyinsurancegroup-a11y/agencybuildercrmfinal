@extends('layouts.app')

@section('content')

<div class="p-6">

    <h1 style="font-size:28px; font-weight:700; color:#111827;">Calendar</h1>
    <p style="color:#6b7280; margin-bottom:20px;">Manage your events and reminders.</p>

    <!-- CARD WRAPPER -->
    <div style="
        background:white;
        padding:25px;
        border-radius:16px;
        border:1px solid #e5e7eb;
        box-shadow:0 4px 12px rgba(0,0,0,0.07);
    ">
        <div id="calendar"></div>
    </div>

</div>

<!-- FULLCALENDAR CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">

<!-- FULLCALENDAR JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>

<!-- BOOTSTRAP 5 (needed for modal) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<!-- EVENT MODAL -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

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
document.addEventListener('DOMContentLoaded', function () {

    let calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {

        initialView: 'dayGridMonth',
        selectable: true,
        editable: false,
        height: "auto",

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },

        events: "/calendar/events",


        /* -----------------------------
           CREATE
        ------------------------------ */
        select: function(info) {

            document.getElementById("modalTitle").innerText = "Create Event";

            document.getElementById("eventId").value = "";
            document.getElementById("deleteEventBtn").classList.add("d-none");

            document.getElementById("eventTitle").value = "";
            document.getElementById("eventColor").value = "#facc15";
            document.getElementById("eventStart").value = info.startStr + "T00:00";
            document.getElementById("eventEnd").value = info.endStr + "T00:00";

            new bootstrap.Modal(document.getElementById("eventModal")).show();
        },


        /* -----------------------------
           EDIT
        ------------------------------ */
        eventClick: function(info) {

            let e = info.event;

            document.getElementById("modalTitle").innerText = "Edit Event";

            document.getElementById("eventId").value = e.id;
            document.getElementById("eventTitle").value = e.title;
            document.getElementById("eventColor").value = e.backgroundColor;

            document.getElementById("eventStart").value = e.start.toISOString().slice(0,16);
            document.getElementById("eventEnd").value =
                e.end ? e.end.toISOString().slice(0,16) : "";

            document.getElementById("deleteEventBtn").classList.remove("d-none");

            new bootstrap.Modal(document.getElementById("eventModal")).show();
        }

    });

    calendar.render();


    /* -----------------------------
       SAVE (Create or Update)
    ------------------------------ */
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
                "Accept": "application/json"
            },
            body: JSON.stringify(payload)
        }).then(() => {
            bootstrap.Modal.getInstance(document.getElementById("eventModal")).hide();
            calendar.refetchEvents();
        });
    };


    /* -----------------------------
       DELETE EVENT
    ------------------------------ */
    document.getElementById("deleteEventBtn").onclick = function () {

        let id = document.getElementById("eventId").value;
        if (!confirm("Delete this event?")) return;

        fetch(`/calendar/events/${id}`, {
            method: "DELETE",
            headers: { "Accept": "application/json" }
        }).then(() => {
            bootstrap.Modal.getInstance(document.getElementById("eventModal")).hide();
            calendar.refetchEvents();
        });
    };

});
</script>

@endsection
