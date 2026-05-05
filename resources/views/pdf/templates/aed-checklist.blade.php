<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: Helvetica, sans-serif; font-size: 10pt; color: #1a202c; line-height: 1.5; }
        .header { border-bottom: 3px solid #9b5de5; padding-bottom: 1em; margin-bottom: 1.5em; }
        h1 { font-size: 22pt; color: #9b5de5; margin: 0; }
        h2 { font-size: 13pt; color: #9b5de5; margin-top: 1.5em; margin-bottom: 0.5em; border-bottom: 1px solid #cbd5e0; padding-bottom: 0.3em; }
        .checkbox { display: inline-block; width: 14px; height: 14px; border: 2px solid #9b5de5; border-radius: 3px; vertical-align: middle; margin-right: 0.5em; }
        ul { list-style: none; padding-left: 0; }
        li { margin-bottom: 0.5em; padding-left: 0.5em; }
        .important { background: #fef3c7; border-left: 3px solid #f59e0b; padding: 0.8em 1em; margin: 1em 0; font-size: 9pt; }
        .footer { margin-top: 2em; text-align: center; font-size: 8pt; color: #718096; padding-top: 1em; border-top: 1px solid #e2e8f0; }
        .powered-by { color: #9b5de5; text-decoration: none; font-weight: bold; }
        .deadline { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
@php
    $isEN = ($language ?? 'fr') === 'en';
@endphp

<div class="header">
    <h1>{{ $isEN ? 'AED Tax Audit Preparation Checklist' : 'Checklist préparation contrôle fiscal AED' }}</h1>
    <p>{{ $isEN ? 'Luxembourg — 2026' : 'Luxembourg — 2026' }}</p>
</div>

<div class="important">
    @if ($isEN)
        ⚠ <strong>Deadline:</strong> the AED typically gives <strong>1-2 weeks</strong> to provide the requested documents. Prepare in advance to avoid panic.
    @else
        ⚠ <strong>Délai :</strong> l'AED accorde généralement <strong>1 à 2 semaines</strong> pour fournir les documents demandés. Préparez-vous en amont.
    @endif
</div>

<h2>{{ $isEN ? '1. FAIA file' : '1. Fichier FAIA' }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $isEN ? 'Generate FAIA file in version 2.01 (mandatory since 2017)' : 'Générer le fichier FAIA en version 2.01 (obligatoire depuis 2017)' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Validate against the official AED XSD schema' : 'Valider contre le schéma XSD officiel AED' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Cover the period requested by the auditor' : 'Couvrir la période demandée par l\'auditeur' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Include all sales journals and accounting lines' : 'Inclure tous les journaux de ventes et lignes comptables' }}</li>
</ul>

<h2>{{ $isEN ? '2. Invoices and credit notes' : '2. Factures et notes de crédit' }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $isEN ? 'Continuous sequential numbering (Article 61 LIVA)' : 'Numérotation séquentielle continue (Article 61 LIVA)' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'PDF/A archives with timestamps for the last 10 years' : 'Archives PDF/A avec horodatage pour les 10 dernières années' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Mandatory mentions present (VAT number, address, sequential, etc.)' : 'Mentions légales obligatoires présentes (TVA, adresse, séquence, etc.)' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? '"Article 21 LIVA" mention on B2B intra-EU invoices' : 'Mention "Article 21 LIVA" sur factures B2B intra-UE' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'VIES validation log of client VAT numbers' : 'Journal de validation VIES des numéros de TVA clients' }}</li>
</ul>

<h2>{{ $isEN ? '3. VAT returns' : '3. Déclarations TVA' }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $isEN ? 'Monthly or quarterly returns submitted on time' : 'Déclarations mensuelles ou trimestrielles soumises à temps' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Match with FAIA totals (HT, VAT, TTC)' : 'Cohérence avec les totaux FAIA (HT, TVA, TTC)' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'EC sales list for intra-EU B2B operations' : 'Liste récapitulative intracommunautaire pour les B2B intra-UE' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'OSS declarations if cross-border B2C above threshold' : 'Déclarations OSS si B2C cross-border au-dessus du seuil' }}</li>
</ul>

<h2>{{ $isEN ? '4. Documentation and supporting documents' : '4. Documentation et justificatifs' }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $isEN ? 'Bank statements (10 years)' : 'Relevés bancaires (10 ans)' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Purchase invoices and expenses with VAT receipts' : 'Factures d\'achat et dépenses avec justificatifs TVA' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Modification log on accounting entries' : 'Journal des modifications sur les écritures comptables' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Backup proofs (daily backups for 30+ days)' : 'Preuves de sauvegardes (backups quotidiens 30+ jours)' }}</li>
</ul>

<h2>{{ $isEN ? '5. Day of the audit' : '5. Jour du contrôle' }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $isEN ? 'Acknowledge receipt of the notification within 24h' : 'Accuser réception de la notification dans les 24h' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Request an extension if needed (often granted)' : 'Demander une extension si besoin (souvent accordée)' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Provide all documents in the format requested (XML, PDF/A, CSV)' : 'Fournir tous les documents au format demandé (XML, PDF/A, CSV)' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Take notes of all communications (date, time, contact)' : 'Prendre des notes de toutes les communications (date, heure, contact)' }}</li>
    <li><span class="checkbox"></span> {{ $isEN ? 'Contact a fiduciary if the audit is complex' : 'Contacter une fiduciaire si le contrôle est complexe' }}</li>
</ul>

<div class="important">
    {{ $isEN
        ? '💡 With faktur.lu, generate your FAIA file in 1 click and prepare an audit in minutes. Free 14-day trial: faktur.lu'
        : '💡 Avec faktur.lu, générez votre FAIA en 1 clic et préparez un contrôle en quelques minutes. Essai gratuit 14 jours : faktur.lu' }}
</div>

<div class="footer">
    <a href="https://faktur.lu" class="powered-by">faktur.lu</a> — {{ $isEN ? 'Free template' : 'Modèle gratuit' }}
</div>
</body>
</html>
