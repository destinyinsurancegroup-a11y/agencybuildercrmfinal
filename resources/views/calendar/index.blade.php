@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <div class="card shadow border-0" style="border-top: 4px solid #facc15;">
        <div class="card-body">
            <h4 class="mb-3" style="color:#facc15;">Calendar</h4>

            <div id="calendar"></div>
        </div>
    </div>

</div>

<!-- ===========================
     EVENT MODAL (CREATE/EDIT)
=========================== -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="eventForm">
        @csrf
        <div class="modal-content">

            <div class="modal-header" style="background:#000; color:#facc15;">
                <h5 class="modal-title" id="eventModalLabel">Add Event</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="event_id">

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" id="event_title" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="datetime-local" id="event_start" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">End Date</label>
                    <input type="datetime-local" id="event_end" class="form-control">
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>

                <button type="submit" class="btn" style="background:#facc15; color:#000;">
                    Save Event
                </button>

                <button type="button" id="deleteEventBtn" class="btn btn-danger d-none">
                    Delete
                </button>
            </div>

        </div>
    </form>
  </div>
</div>

@endsection


@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    let modal = new bootstrap.Modal(document.getElementById('eventModal'));
    let deleteBtn = document.getElementById('deleteEventBtn');

    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: true,
        editable: true,
        events: @json($events),

        select: function(info) {
            resetForm();
            document.getElementById('event_start').value = formatDate(info.start);
            document.getElementById('event_end').value = formatDate(info.end);
            document.getElementById('eventModalLabel').innerText = "Add Event";
            deleteBtn.classList.add('d-none');
            modal.show();
        },

        eventClick: function(info) {
            resetForm();
            const event = info.event;

            document.getElementById('event_id').value = event.id;
            document.getElementById('event_title').value = event.title;
            document.getElementById('event_start').value = formatDate(event.start);

            if (event.end) {
                document.getElementById('event_end').value = formatDate(event.end);
            }

            document.getElementById('eventModalLabel').innerText = "Edit Event";
            deleteBtn.classList.remove('d-none');
            modal.show();
        },

        eventDrop: function(info) {
            updateEvent(info.event);
        },

        eventResize: function(info) {
            updateEvent(info.event);
        }
    });

    calendar.render();

    // ======================================================
    // SAVE (CREATE / UPDATE)
    // ======================================================
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        e.preventDefault();

        let id = document.getElementById('event_id').value;
        let url = id ? `/calendar/events/${id}` : `/calendar/events`;
        let method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                title: document.getElementById('event_title').value,
                start: document.getElementById('event_start').value,
                end: document.getElementById('event_end').value
            })
        })
        .then(res => res.json())
        .then(data => {
            modal.hide();
            calendar.refetchEvents(); // refresh UI
        });
    });

    // ======================================================
    // DELETE EVENT
    // ======================================================
    deleteBtn.addEventListener('click', function() {
        let id = document.getElementById('event_id').value;

        fetch(`/calendar/events/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            }
        })
        .then(res => res.json())
        .then(data => {
            modal.hide();
            calendar.refetchEvents();
        });
    });

    // ======================================================
    // HELPER FUNCTIONS
    // ======================================================
    function formatDate(date) {
        let d = new Date(date);
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 16);
    }

    function resetForm() {
        document.getElementById('eventForm').reset();
        document.getElementById('event_id').value = "";
    }

    function updateEvent(event) {
        fetch(`/calendar/events/${event.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                title: event.title,
                start: formatDate(event.start),
                end: event.end ? formatDate(event.end) : null
            })
        });
    }

});
</script>

@endsection
