/* Remove ANY highlight, border, shadow, or outline on selected events */
.fc .fc-event.fc-event-selected,
.fc .fc-daygrid-event.fc-event-selected,
.fc-event:focus,
.fc-event:active,
.fc-event:focus-visible {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}

/* FullCalendar wraps selected event with fc-event-mirror → remove it */
.fc .fc-event-mirror {
    background: inherit !important;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}

/* Remove selection highlight cell shading */
.fc .fc-highlight {
    background: none !important;
}

/* Remove the drag/resize shadow highlight */
.fc .fc-event.fc-event-dragging,
.fc .fc-event.fc-event-resizing {
    outline: none !important;
    box-shadow: none !important;
    border: none !important;
}

/* Remove Chrome default outline on event <a> elements */
.fc-daygrid-event-harness a:focus,
.fc-daygrid-event-harness a:active,
.fc-daygrid-event-harness a:focus-visible {
    outline: none !important;
    box-shadow: none !important;
    border: none !important;
}
