/*
|--------------------------------------------------------------------------
| CALENDAR API ROUTES (FIXED)
|--------------------------------------------------------------------------
*/

/* --------------------------
   FETCH ALL EVENTS
-------------------------- */
Route::get('/calendar/events', function () {
    try {
        return Event::all();
    } catch (\Throwable $e) {
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
    }
});

/* --------------------------
   CREATE EVENT (FIXED)
-------------------------- */
Route::post('/calendar/events', function (Request $request) {
    try {
        $data = $request->validate([
            'title'    => 'required|string',
            'start'    => 'required|string',
            'location' => 'nullable|string',
        ]);

        $event = Event::create([
            'title'      => $data['title'],
            'start'      => $data['start'],
            'end'        => $data['start'], // you aren't using "end" yet
            'location'   => $data['location'] ?? null,
            'tenant_id'  => 1,
            'created_by' => 1,
        ]);

        return response()->json($event, 201);

    } catch (\Throwable $e) {
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
    }
});

/* --------------------------
   UPDATE EVENT (FIXED)
-------------------------- */
Route::put('/calendar/events/{id}', function (Request $request, $id) {
    try {
        $data = $request->validate([
            'title'    => 'required|string',
            'start'    => 'required|string',
            'location' => 'nullable|string',
        ]);

        $event = Event::findOrFail($id);

        $event->update([
            'title'    => $data['title'],
            'start'    => $data['start'],
            'end'      => $data['start'],
            'location' => $data['location'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'event'   => $event,
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
    }
});

/* --------------------------
   DELETE EVENT
-------------------------- */
Route::delete('/calendar/events/{id}', function ($id) {
    try {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json([
            'success' => true,
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
    }
});
