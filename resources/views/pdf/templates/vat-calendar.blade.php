<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: Helvetica, sans-serif; font-size: 9.5pt; color: #1a202c; line-height: 1.5; }
        .header { border-bottom: 3px solid #9b5de5; padding-bottom: 1em; margin-bottom: 1.5em; }
        h1 { font-size: 22pt; color: #9b5de5; margin: 0; }
        h2 { font-size: 13pt; color: #9b5de5; margin-top: 1.5em; margin-bottom: 0.5em; border-bottom: 1px solid #cbd5e0; padding-bottom: 0.3em; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.5em; }
        th { background: #9b5de5; color: white; padding: 0.5em; text-align: left; font-size: 9pt; }
        td { padding: 0.5em; border-bottom: 1px solid #e2e8f0; }
        tr.month-row { background: #faf5ff; font-weight: bold; }
        .deadline { color: #dc2626; font-weight: bold; }
        .info { background: #fef3c7; border-left: 3px solid #f59e0b; padding: 0.8em 1em; margin: 1em 0; font-size: 9pt; }
        .footer { margin-top: 2em; text-align: center; font-size: 8pt; color: #718096; padding-top: 1em; border-top: 1px solid #e2e8f0; }
        .powered-by { color: #9b5de5; text-decoration: none; font-weight: bold; }
        .legend { display: table; width: 100%; margin-top: 0.5em; font-size: 8.5pt; }
        .legend-item { display: table-cell; padding: 0.3em 0.6em; }
    </style>
</head>
<body>
@php
    $lang = $language ?? 'fr';
    $L = [
        'fr' => [
            'title' => 'Calendrier TVA Luxembourg 2026',
            'subtitle' => 'Échéances de déclarations mensuelles, trimestrielles et annuelles',
            'info' => 'Toutes les déclarations TVA luxembourgeoises se font via la plateforme eCDF de l\'AED. Échéance : le 15 du deuxième mois suivant la période.',
            'date' => 'Date',
            'deadline' => 'Échéance',
            'desc' => 'Description',
            'monthly' => 'Déclarations mensuelles',
            'quarterly' => 'Déclarations trimestrielles',
            'annual' => 'Déclarations annuelles',
            'esl' => 'Liste récapitulative intracommunautaire',
            'declaration_for' => 'Déclaration pour',
            'monthly_desc' => 'Déclaration TVA mensuelle - CA > 620 000 EUR',
            'quarterly_desc' => 'Déclaration TVA trimestrielle - CA 112 000 à 620 000 EUR',
            'esl_desc' => 'Liste récapitulative (opérations B2B intra-UE)',
            'annual_desc' => 'Déclaration annuelle - déclaration récapitulative',
            'q1' => 'T1 (jan-mar)', 'q2' => 'T2 (avr-jun)', 'q3' => 'T3 (jul-sep)', 'q4' => 'T4 (oct-dec)',
            'jan' => 'janvier', 'feb' => 'février', 'mar' => 'mars', 'apr' => 'avril', 'may' => 'mai', 'jun' => 'juin',
            'jul' => 'juillet', 'aug' => 'août', 'sep' => 'septembre', 'oct' => 'octobre', 'nov' => 'novembre', 'dec' => 'décembre',
            'annual_taxable' => 'Déclaration annuelle TVA 2026 - Assujettis TVA',
            'annual_exempt' => 'Déclaration annuelle 2026 - Franchise TVA',
            'cta' => '💡 Avec faktur.lu, recevez des rappels avant chaque échéance TVA + un export de données prêt à soumettre. Essai gratuit 14 jours : faktur.lu',
            'footer' => 'Modèle gratuit - 2026',
        ],
        'de' => [
            'title' => 'MwSt-Kalender Luxemburg 2026',
            'subtitle' => 'Abgabetermine für monatliche, vierteljährliche und jährliche Erklärungen',
            'info' => 'Alle luxemburgischen MwSt-Erklärungen werden über die eCDF-Plattform der AED eingereicht. Frist: 15. Tag des zweiten Monats nach dem Zeitraum.',
            'date' => 'Datum',
            'deadline' => 'Frist',
            'desc' => 'Beschreibung',
            'monthly' => 'Monatliche Erklärungen',
            'quarterly' => 'Vierteljährliche Erklärungen',
            'annual' => 'Jährliche Erklärungen',
            'esl' => 'Zusammenfassende Meldung',
            'declaration_for' => 'Erklärung für',
            'monthly_desc' => 'Monatliche MwSt-Erklärung - Umsatz > 620.000 EUR',
            'quarterly_desc' => 'Vierteljährliche MwSt-Erklärung - Umsatz 112.000 bis 620.000 EUR',
            'esl_desc' => 'Zusammenfassende Meldung (B2B-Operationen innerhalb der EU)',
            'annual_desc' => 'Jährliche Erklärung - Zusammenfassungsmeldung',
            'q1' => 'Q1 (Jan-Mär)', 'q2' => 'Q2 (Apr-Jun)', 'q3' => 'Q3 (Jul-Sep)', 'q4' => 'Q4 (Okt-Dez)',
            'jan' => 'Januar', 'feb' => 'Februar', 'mar' => 'März', 'apr' => 'April', 'may' => 'Mai', 'jun' => 'Juni',
            'jul' => 'Juli', 'aug' => 'August', 'sep' => 'September', 'oct' => 'Oktober', 'nov' => 'November', 'dec' => 'Dezember',
            'annual_taxable' => 'Jährliche MwSt-Erklärung 2026 - MwSt-pflichtig',
            'annual_exempt' => 'Jährliche Erklärung 2026 - MwSt-Befreiung',
            'cta' => '💡 Mit faktur.lu erhalten Sie Erinnerungen vor jeder MwSt-Frist + einen abgabebereiten Datenexport. 14 Tage gratis testen: faktur.lu',
            'footer' => 'Kostenlose Vorlage - 2026',
        ],
        'en' => [
            'title' => 'Luxembourg VAT Calendar 2026',
            'subtitle' => 'Filing deadlines for monthly, quarterly and annual returns',
            'info' => 'All Luxembourg VAT returns must be filed via the eCDF platform of the AED. Deadline: 15th day of the second month following the period.',
            'date' => 'Date',
            'deadline' => 'Deadline',
            'desc' => 'Description',
            'monthly' => 'Monthly returns',
            'quarterly' => 'Quarterly returns',
            'annual' => 'Annual returns',
            'esl' => 'EC Sales List',
            'declaration_for' => 'Return for',
            'monthly_desc' => 'Monthly VAT return - turnover > €620,000',
            'quarterly_desc' => 'Quarterly VAT return - turnover €112,000 to €620,000',
            'esl_desc' => 'EC sales list (intra-EU B2B operations)',
            'annual_desc' => 'Annual VAT return - recapitulative declaration',
            'q1' => 'Q1 (Jan-Mar)', 'q2' => 'Q2 (Apr-Jun)', 'q3' => 'Q3 (Jul-Sep)', 'q4' => 'Q4 (Oct-Dec)',
            'jan' => 'January', 'feb' => 'February', 'mar' => 'March', 'apr' => 'April', 'may' => 'May', 'jun' => 'June',
            'jul' => 'July', 'aug' => 'August', 'sep' => 'September', 'oct' => 'October', 'nov' => 'November', 'dec' => 'December',
            'annual_taxable' => 'Annual VAT return 2026 - VAT taxable',
            'annual_exempt' => 'Annual return 2026 - VAT exempt (franchise)',
            'cta' => '💡 With faktur.lu, get reminders before each VAT deadline + ready-to-submit data export. Free 14-day trial: faktur.lu',
            'footer' => 'Free template - 2026',
        ],
        'lb' => [
            'title' => 'TVA-Kalenner Lëtzebuerg 2026',
            'subtitle' => 'Echéancen vu mounatlechen, trimestriellen a jährlechen Deklaratiounen',
            'info' => 'All Lëtzebuerger TVA-Deklaratioune ginn iwwer d\'eCDF-Plattform vun der AED gemaach. Echéance: de 15. Dag vum zweete Mount no der Period.',
            'date' => 'Datum',
            'deadline' => 'Echéance',
            'desc' => 'Beschreiwung',
            'monthly' => 'Mounatlech Deklaratiounen',
            'quarterly' => 'Trimestriell Deklaratiounen',
            'annual' => 'Jährlech Deklaratiounen',
            'esl' => 'Rekapitulativ Lëscht',
            'declaration_for' => 'Deklaratioun fir',
            'monthly_desc' => 'Mounatlech TVA-Deklaratioun - Chiffre d\'affaires > 620.000 EUR',
            'quarterly_desc' => 'Trimestriell TVA-Deklaratioun - Chiffre d\'affaires 112.000 bis 620.000 EUR',
            'esl_desc' => 'Rekapitulativ Lëscht (B2B-Operatiounen bannent der EU)',
            'annual_desc' => 'Jährlech Deklaratioun - rekapitulativ Deklaratioun',
            'q1' => 'T1 (Jan-Mäe)', 'q2' => 'T2 (Abr-Jun)', 'q3' => 'T3 (Jul-Sep)', 'q4' => 'T4 (Okt-Dez)',
            'jan' => 'Januar', 'feb' => 'Februar', 'mar' => 'Mäerz', 'apr' => 'Abrëll', 'may' => 'Mee', 'jun' => 'Juni',
            'jul' => 'Juli', 'aug' => 'August', 'sep' => 'September', 'oct' => 'Oktober', 'nov' => 'November', 'dec' => 'Dezember',
            'annual_taxable' => 'Jährlech TVA-Deklaratioun 2026 - TVA-pflichtig',
            'annual_exempt' => 'Jährlech Deklaratioun 2026 - TVA-Franchise',
            'cta' => '💡 Mat faktur.lu kritt Dir Rappeller virun all TVA-Echéance + en Datenexport prett fir ze soumetéieren. Gratis Essai 14 Deeg: faktur.lu',
            'footer' => 'Gratis Modell - 2026',
        ],
        'pt' => [
            'title' => 'Calendário IVA Luxemburgo 2026',
            'subtitle' => 'Prazos das declarações mensais, trimestrais e anuais',
            'info' => 'Todas as declarações de IVA do Luxemburgo são submetidas através da plataforma eCDF da AED. Prazo: dia 15 do segundo mês após o período.',
            'date' => 'Data',
            'deadline' => 'Prazo',
            'desc' => 'Descrição',
            'monthly' => 'Declarações mensais',
            'quarterly' => 'Declarações trimestrais',
            'annual' => 'Declarações anuais',
            'esl' => 'Mapa recapitulativo intracomunitário',
            'declaration_for' => 'Declaração de',
            'monthly_desc' => 'Declaração mensal de IVA - volume de negócios > 620 000 EUR',
            'quarterly_desc' => 'Declaração trimestral de IVA - volume de negócios 112 000 a 620 000 EUR',
            'esl_desc' => 'Mapa recapitulativo (operações B2B intra-UE)',
            'annual_desc' => 'Declaração anual - declaração recapitulativa',
            'q1' => 'T1 (jan-mar)', 'q2' => 'T2 (abr-jun)', 'q3' => 'T3 (jul-set)', 'q4' => 'T4 (out-dez)',
            'jan' => 'janeiro', 'feb' => 'fevereiro', 'mar' => 'março', 'apr' => 'abril', 'may' => 'maio', 'jun' => 'junho',
            'jul' => 'julho', 'aug' => 'agosto', 'sep' => 'setembro', 'oct' => 'outubro', 'nov' => 'novembro', 'dec' => 'dezembro',
            'annual_taxable' => 'Declaração anual de IVA 2026 - Sujeitos a IVA',
            'annual_exempt' => 'Declaração anual 2026 - Isenção de IVA',
            'cta' => '💡 Com o faktur.lu, receba lembretes antes de cada prazo de IVA + uma exportação de dados pronta a submeter. Avaliação gratuita de 14 dias: faktur.lu',
            'footer' => 'Modelo gratuito - 2026',
        ],
    ];
    $T = $L[$lang] ?? $L['fr'];
@endphp

<div class="header">
    <h1>{{ $T['title'] }}</h1>
    <p>{{ $T['subtitle'] }}</p>
</div>

<div class="info">
    {{ $T['info'] }}
</div>

<h2>{{ $T['monthly'] }} - {{ $T['monthly_desc'] }}</h2>
<table>
    <tr><th style="width:25%">{{ $T['deadline'] }}</th><th>{{ $T['declaration_for'] }}</th></tr>
    <tr><td class="deadline">15/03/2026</td><td>{{ $T['jan'] }} 2026</td></tr>
    <tr><td class="deadline">15/04/2026</td><td>{{ $T['feb'] }} 2026</td></tr>
    <tr><td class="deadline">15/05/2026</td><td>{{ $T['mar'] }} 2026</td></tr>
    <tr><td class="deadline">15/06/2026</td><td>{{ $T['apr'] }} 2026</td></tr>
    <tr><td class="deadline">15/07/2026</td><td>{{ $T['may'] }} 2026</td></tr>
    <tr><td class="deadline">15/08/2026</td><td>{{ $T['jun'] }} 2026</td></tr>
    <tr><td class="deadline">15/09/2026</td><td>{{ $T['jul'] }} 2026</td></tr>
    <tr><td class="deadline">15/10/2026</td><td>{{ $T['aug'] }} 2026</td></tr>
    <tr><td class="deadline">15/11/2026</td><td>{{ $T['sep'] }} 2026</td></tr>
    <tr><td class="deadline">15/12/2026</td><td>{{ $T['oct'] }} 2026</td></tr>
    <tr><td class="deadline">15/01/2027</td><td>{{ $T['nov'] }} 2026</td></tr>
    <tr><td class="deadline">15/02/2027</td><td>{{ $T['dec'] }} 2026</td></tr>
</table>

<h2>{{ $T['quarterly'] }} - {{ $T['quarterly_desc'] }}</h2>
<table>
    <tr><th style="width:25%">{{ $T['deadline'] }}</th><th>{{ $T['declaration_for'] }}</th></tr>
    <tr><td class="deadline">15/05/2026</td><td>{{ $T['q1'] }} 2026</td></tr>
    <tr><td class="deadline">15/08/2026</td><td>{{ $T['q2'] }} 2026</td></tr>
    <tr><td class="deadline">15/11/2026</td><td>{{ $T['q3'] }} 2026</td></tr>
    <tr><td class="deadline">15/02/2027</td><td>{{ $T['q4'] }} 2026</td></tr>
</table>

<h2>{{ $T['esl'] }} - {{ $T['esl_desc'] }}</h2>
<table>
    <tr><th style="width:25%">{{ $T['deadline'] }}</th><th>{{ $T['declaration_for'] }}</th></tr>
    <tr><td class="deadline">25/02/2026</td><td>{{ $T['jan'] }} 2026</td></tr>
    <tr><td class="deadline">25/03/2026</td><td>{{ $T['feb'] }} 2026</td></tr>
    <tr><td class="deadline">25/04/2026</td><td>{{ $T['mar'] }} 2026</td></tr>
    <tr><td class="deadline">25/05/2026</td><td>{{ $T['apr'] }} 2026</td></tr>
    <tr><td class="deadline">25/06/2026</td><td>{{ $T['may'] }} 2026</td></tr>
    <tr><td class="deadline">25/07/2026</td><td>{{ $T['jun'] }} 2026</td></tr>
    <tr><td class="deadline">25/08/2026</td><td>{{ $T['jul'] }} 2026</td></tr>
    <tr><td class="deadline">25/09/2026</td><td>{{ $T['aug'] }} 2026</td></tr>
    <tr><td class="deadline">25/10/2026</td><td>{{ $T['sep'] }} 2026</td></tr>
    <tr><td class="deadline">25/11/2026</td><td>{{ $T['oct'] }} 2026</td></tr>
    <tr><td class="deadline">25/12/2026</td><td>{{ $T['nov'] }} 2026</td></tr>
    <tr><td class="deadline">25/01/2027</td><td>{{ $T['dec'] }} 2026</td></tr>
</table>

<h2>{{ $T['annual'] }} - {{ $T['annual_desc'] }}</h2>
<table>
    <tr><th style="width:25%">{{ $T['deadline'] }}</th><th>{{ $T['declaration_for'] }}</th></tr>
    <tr><td class="deadline">01/05/2027</td><td>{{ $T['annual_taxable'] }}</td></tr>
    <tr><td class="deadline">01/03/2027</td><td>{{ $T['annual_exempt'] }}</td></tr>
</table>

<div class="info">
    {{ $T['cta'] }}
</div>

<div class="footer">
    <a href="https://{{ config('marque.domaine') }}" class="powered-by">{{ config('marque.nom') }}</a> - {{ $T['footer'] }}
</div>
</body>
</html>
