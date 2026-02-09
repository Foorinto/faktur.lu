<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * Display the legal mentions page.
     */
    public function mentions(): Response
    {
        return Inertia::render('Legal/Mentions');
    }

    /**
     * Display the privacy policy page.
     */
    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy');
    }

    /**
     * Display the terms and conditions page.
     */
    public function terms(): Response
    {
        return Inertia::render('Legal/Terms');
    }

    /**
     * Display the cookie policy page.
     */
    public function cookies(): Response
    {
        return Inertia::render('Legal/Cookies');
    }
}
