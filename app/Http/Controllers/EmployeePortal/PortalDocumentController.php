<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeePortal\Concerns\ResolvesEmployee;
use Inertia\Inertia;
use Inertia\Response;

class PortalDocumentController extends Controller
{
    use ResolvesEmployee;

    public function index(): Response
    {
        $employee = $this->employee();

        $documents = $employee->documents()
            ->withoutGlobalScope('user')
            ->latest()
            ->get();

        return Inertia::render('EmployeePortal/Documents/Index', [
            'documents' => $documents,
        ]);
    }
}
