@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <h2>Calendar</h2>
    <div id="calendar"></div>
</div>

<!-- CREATE / EDIT EVENT MODAL -->
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
document.addEventListener('DOMContentLoaded', function() {

    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        selectable: true,
        editable: false,

        events: "/calendar/events",

        /* -----------------------------
           CREATE EVENT (select)
        ------------------------------ */
        select: function(info) {
            document.getElementById("modalTitle").innerText = "Create Event";
            document.getElementById("deleteEventBtn").classList.add("d-none");
            document.getElementById("eventId").value = "";
            
            document.getElementById("eventTitle").value = "";
            document.getElementById("eventStart").value = info.startStr + "T00:00";
            document.getElementById("eventEnd").value = info.endStr + "T00:00";
            document.getElementById("eventColor").value = "#3a87ad";

            new bootstrap.Modal(document.getElementById("eventModal")).show();
        },

        /* -----------------------------
           EDIT EVENT (click)
        ------------------------------ */
        eventClick: function(info) {

            document.getElementById("modalTitle").innerText = "Edit Event";
            document.getElementById("deleteEventBtn").classList.remove("d-none");

            let e = info.event;

            document.getElementById("eventId").value = e.id;
            document.getElementById("eventTitle").value = e.title;
            document.getElementById("eventColor").value = e.backgroundColor;

            document.getElementById("eventStart").value =
                e.start.toISOString().slice(0,16);

            if (e.end) {
                document.getElementById("eventEnd").value =
                    e.end.toISOString().slice(0,16);
            } else {
                document.getElementById("eventEnd").value = "";
            }

            new bootstrap.Modal(document.getElementById("eventModal")).show();
        }
    });

    calendar.render();


    /* -----------------------------
       SAVE EVENT (Create or Update)
    ------------------------------ */
    document.getElementById("saveEventBtn").onclick = function() {

        let id = document.getElementById("eventId").value;
        let payload = {
            title: document.getElementById("eventTitle").value,
            start: document.getElementById("eventStart").value,
            end: document.getElementById("eventEnd").value,
            color: document.getElementById("eventColor").value,
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
        })
        .then(res => res.json())
        .then(() => {
            bootstrap.Modal.getInstance(document.getElementById("eventModal")).hide();
            calendar.refetchEvents();
        });
    };


    /* -----------------------------
       DELETE EVENT
    ------------------------------ */
    document.getElementById("deleteEventBtn").onclick = function() {
        let id = document.getElementById("eventId").value;

        if (!confirm("Delete this event?")) return;

        fetch(`/calendar/events/${id}`, {
            method: "DELETE",
            headers: {
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
