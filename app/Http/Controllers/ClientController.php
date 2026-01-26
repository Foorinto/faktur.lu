<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\StoreClientRequest;
use App\Http\Requests\Api\V1\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Display a listing of clients.
     */
    public function index(Request $request): Response
    {
        $clients = Client::query()
            ->search($request->input('search'))
            ->ofType($request->input('type'))
            ->fromCountry($request->input('country'))
            ->withCount(['invoices', 'timeEntries'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $request->input('search'),
                'type' => $request->input('type'),
                'country' => $request->input('country'),
            ],
            'clientTypes' => [
                ['value' => 'b2b', 'label' => 'Entreprise (B2B)'],
                ['value' => 'b2c', 'label' => 'Particulier (B2C)'],
            ],
            'currencies' => [
                ['value' => 'EUR', 'label' => 'Euro (EUR)', 'symbol' => '€'],
                ['value' => 'USD', 'label' => 'Dollar US (USD)', 'symbol' => '$'],
                ['value' => 'GBP', 'label' => 'Livre Sterling (GBP)', 'symbol' => '£'],
                ['value' => 'CHF', 'label' => 'Franc Suisse (CHF)', 'symbol' => 'CHF'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new client.
     */
    public function create(): Response
    {
        return Inertia::render('Clients/Create', [
            'clientTypes' => [
                ['value' => 'b2b', 'label' => 'Entreprise (B2B)', 'description' => 'Client professionnel avec numéro de TVA'],
                ['value' => 'b2c', 'label' => 'Particulier (B2C)', 'description' => 'Client particulier sans TVA'],
            ],
            'currencies' => [
                ['value' => 'EUR', 'label' => 'Euro (EUR)', 'symbol' => '€'],
                ['value' => 'USD', 'label' => 'Dollar US (USD)', 'symbol' => '$'],
                ['value' => 'GBP', 'label' => 'Livre Sterling (GBP)', 'symbol' => '£'],
                ['value' => 'CHF', 'label' => 'Franc Suisse (CHF)', 'symbol' => 'CHF'],
            ],
            'countries' => $this->getCountries(),
        ]);
    }

    /**
     * Store a newly created client.
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client créé avec succès.');
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client): Response
    {
        $client->loadCount(['invoices', 'timeEntries']);

        return Inertia::render('Clients/Show', [
            'client' => $client,
        ]);
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client): Response
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client,
            'clientTypes' => [
                ['value' => 'b2b', 'label' => 'Entreprise (B2B)', 'description' => 'Client professionnel avec numéro de TVA'],
                ['value' => 'b2c', 'label' => 'Particulier (B2C)', 'description' => 'Client particulier sans TVA'],
            ],
            'currencies' => [
                ['value' => 'EUR', 'label' => 'Euro (EUR)', 'symbol' => '€'],
                ['value' => 'USD', 'label' => 'Dollar US (USD)', 'symbol' => '$'],
                ['value' => 'GBP', 'label' => 'Livre Sterling (GBP)', 'symbol' => '£'],
                ['value' => 'CHF', 'label' => 'Franc Suisse (CHF)', 'symbol' => 'CHF'],
            ],
            'countries' => $this->getCountries(),
        ]);
    }

    /**
     * Update the specified client.
     */
    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client mis à jour avec succès.');
    }

    /**
     * Remove the specified client.
     */
    public function destroy(Client $client): RedirectResponse
    {
        if (!$client->canBeDeleted()) {
            return back()->with('error', 'Impossible de supprimer ce client car il a des factures associées.');
        }

        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client supprimé avec succès.');
    }

    /**
     * Get the list of countries.
     */
    private function getCountries(): array
    {
        return [
            ['code' => 'LU', 'name' => 'Luxembourg', 'eu' => true],
            ['code' => 'BE', 'name' => 'Belgique', 'eu' => true],
            ['code' => 'FR', 'name' => 'France', 'eu' => true],
            ['code' => 'DE', 'name' => 'Allemagne', 'eu' => true],
            ['code' => 'NL', 'name' => 'Pays-Bas', 'eu' => true],
            ['code' => 'AT', 'name' => 'Autriche', 'eu' => true],
            ['code' => 'IT', 'name' => 'Italie', 'eu' => true],
            ['code' => 'ES', 'name' => 'Espagne', 'eu' => true],
            ['code' => 'PT', 'name' => 'Portugal', 'eu' => true],
            ['code' => 'IE', 'name' => 'Irlande', 'eu' => true],
            ['code' => 'GB', 'name' => 'Royaume-Uni', 'eu' => false],
            ['code' => 'CH', 'name' => 'Suisse', 'eu' => false],
            ['code' => 'US', 'name' => 'États-Unis', 'eu' => false],
        ];
    }
}
