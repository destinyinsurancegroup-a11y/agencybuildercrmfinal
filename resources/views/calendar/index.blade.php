@extends('layouts.app')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    :root {
        --gold: #c9a227;
        --gold-soft: #f5e6b3;
        --bg-page: #f5f5f5;
        --text-main: #111827;
        --text-subtle: #4b5563;
    }

    body, #calendar, .fc, .modal-content {
        font-family: 'Inter', sans-serif !important;
    }

    .page-wrapper {
        padding: 32px 48px;
        background: var(--bg-page);
        min-height: 100vh;
    }

    /* Page Title */
    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
    }

    .page-subtitle {
        color: var(--text-subtle);
        margin-bottom: 24px;
        font-size: 18px;
    }

    /* Card container matching dashboard style */
    .calendar-card {
        background: #ffffff;
        border-radius: 22px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
        border-top: 4px solid var(--gold);
        padding: 28px;
        min-height: 650px;
    }

    /* FullCalendar toolbar matching dashboard */
    .fc-toolbar-title {
        color: var(--text-main) !important;
        font-size: 20px !important;
        font-weight: 700 !important;
    }

    .fc-button-primary {
        background: var(--gold) !important;
        border: none !important;
        color: #111 !important;
        font-weight: 600 !important;
        box-shadow: none !important;
    }

    .fc-button-primary:hover {
        background: #b8931e !important;
    }

    /* Remove FullCalendar blue focus outline */
    .fc-button:focus,
    .fc:focus,
    .fc-daygrid-day:focus {
        outline: none !important;
        box-shadow: none !important;
    }

    /* Remove event background pill */
    .fc-daygrid-event {
        background: none !important;
        border: none !important;
        padding: 0 !important;
    }

    .fc-event-title,
    .fc-event-time {
        color: var(--text-main) !important;
        font-size: 12px;
        font-weight: 600;
    }

    .event-location {
        display: block;
        font-size: 11px;
        color: #374151;
        margin-left: 2px;
    }

    .fc-daygrid-event-dot {
        display: none !important;
    }

    /* Modal styling to match dashboard */
    .modal-content {
        border-radius: 16px !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 18px 30px rgba(0,0,0,0.3);
    }

    .modal-header,
    .modal-footer {
        background: #f9fafb !important;
        border-color: #e5e7eb !important;
    }

    .modal-title {
        font-weight: 700;
        color: var(--text-main);
    }

    .btn-gold {
        background: var(--gold);
        color: #000;
        font-weight: 600;
        border: none;
    }

    .btn-gold:hover {
        background: #b8931e;
    }

    .btn-danger {
        font-weight: 600;
    }
</style>

<div class="page-wrapper">

    <div class="page-title">Calendar</div>
    <div class="page-subtitle">Manage your events and reminders.</div>

    <div class="calendar-card">
        <div id="calendar" style="min-height: 600px;"></div>
    </div>

</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- ==========================
     EVENT MODAL
========================== -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Create Event</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="eventId">

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" id="eventTitle" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Date & Time</label>
                    <input type="datetime-local" id="eventStart" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Location (optional)</label>
                    <input type="text" id="eventLocation" class="form-control">
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-danger d-none" id="deleteEventBtn">Delete</button>
                <button class="btn btn-gold" id="saveEventBtn">Save</button>
            </div>

        </div>
    </div>
</div>

<!-- ==========================
     FULLCALENDAR LOGIC (unchanged)
========================== -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    let modal = new bootstrap.Modal(document.getElementById("eventModal"));

    let calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {
        initialView: "dayGridMonth",
        selectable: false,
        editable: false,
        height: "auto",

        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay"
        },

        events: "/calendar/events",

        eventContent: function(arg) {
            let time = arg.timeText ? arg.timeText + " — " : "";
            let title = arg.event.title;
            let location = arg.event.extendedProps.location || "";

            return {
                html: `
                    <div>
                        ${time}${title}
                        ${location ? `<span class='event-location'>${location}</span>` : ""}
                    </div>
                `
            };
        },

        dateClick: function(info) {
            document.querySelector(".modal-title").innerText = "Create Event";
            document.getElementById("deleteEventBtn").classList.add("d-none");

            document.getElementById("eventId").value = "";
            document.getElementById("eventTitle").value = "";
            document.getElementById("eventStart").value = info.dateStr + "T00:00";
            document.getElementById("eventLocation").value = "";

            modal.show();
        },

        eventClick: function(info) {
            let e = info.event;

            document.querySelector(".modal-title").innerText = "Edit Event";
            document.getElementById("deleteEventBtn").classList.remove("d-none");

            document.getElementById("eventId").value = e.id;
            document.getElementById("eventTitle").value = e.title;
            document.getElementById("eventStart").value = e.start.toISOString().slice(0,16);
            document.getElementById("eventLocation").value = e.extendedProps.location ?? "";

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
        })
        .then(() => {
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
        })
        .then(() => {
            modal.hide();
            calendar.refetchEvents();
        });
    };

});
</script>

@endsection
