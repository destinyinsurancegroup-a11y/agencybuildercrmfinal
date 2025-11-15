<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;

class ContactIndexController extends Controller
{
    public function __invoke()
    {
        return view('contacts.index');
    }
}
