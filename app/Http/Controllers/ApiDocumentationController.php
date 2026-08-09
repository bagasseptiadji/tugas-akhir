<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ApiDocumentationController extends Controller
{
    public function __invoke(): View
    {
        return view('api-docs.index');
    }
}
