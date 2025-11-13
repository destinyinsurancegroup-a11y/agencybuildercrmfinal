<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookOfBusinessController extends Controller
{
    public function index(Request $request)
    {
        // Pagination setup
        $perPage = 25;
        $page = max(1, (int)$request->query('page', 1));
        $offset = ($page - 1) * $perPage;

        // Load all clients
        $total = DB::table('book_of_business')->count();
        $clients = DB::table('book_of_business')
            ->orderBy('name', 'asc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        // Selected client (defaults to first)
        $selectedId = (int)$request->query('id', $clients->first()->id ?? 0);
        $client = DB::table('book_of_business')->where('id', $selectedId)->first();

        // Related notes
        $notes = DB::table('notes')
            ->where('client_type', 'book_of_business')
            ->where('client_id', $selectedId)
            ->orderByDesc('created_at')
            ->get();

        return view('book_of_business', [
            'clients' => $clients,
            'client' => $client,
            'notes' => $notes,
            'page' => $page,
            'perPage' => $perPage,
            'offset' => $offset,
            'total' => $total
        ]);
    }
}
