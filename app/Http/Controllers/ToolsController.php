<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ToolsController extends Controller
{
    /**
     * Page hub listant tous les outils gratuits.
     */
    public function index(): Response
    {
        return Inertia::render('Tools/Index');
    }

    /**
     * Calculateur TVA Luxembourg.
     */
    public function vatCalculator(): Response
    {
        return Inertia::render('Tools/VatCalculator');
    }

    /**
     * Simulateur franchise TVA Luxembourg.
     */
    public function vatExemption(): Response
    {
        return Inertia::render('Tools/VatExemption');
    }

    /**
     * Validateur IBAN luxembourgeois.
     */
    public function ibanValidator(): Response
    {
        return Inertia::render('Tools/IbanValidator');
    }
}
