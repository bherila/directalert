<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminExportController extends Controller
{
    /**
     * Show the admin export page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.export');
    }
}
