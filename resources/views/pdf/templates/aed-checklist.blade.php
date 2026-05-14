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
    $lang = $language ?? 'fr';
    $T = [
        'fr' => [
            'title' => 'Checklist préparation contrôle fiscal AED',
            'subtitle' => 'Luxembourg — 2026',
            'deadline_box' => '⚠ <strong>Délai :</strong> l\'AED accorde généralement <strong>1 à 2 semaines</strong> pour fournir les documents demandés. Préparez-vous en amont.',
            's1' => '1. Fichier FAIA',
            's1_1' => 'Générer le fichier FAIA en version 2.01 (obligatoire depuis 2017)',
            's1_2' => 'Valider contre le schéma XSD officiel AED',
            's1_3' => 'Couvrir la période demandée par l\'auditeur',
            's1_4' => 'Inclure tous les journaux de ventes et lignes comptables',
            's2' => '2. Factures et notes de crédit',
            's2_1' => 'Numérotation séquentielle continue (Article 61 LIVA)',
            's2_2' => 'Archives PDF/A avec horodatage pour les 10 dernières années',
            's2_3' => 'Mentions légales obligatoires présentes (TVA, adresse, séquence, etc.)',
            's2_4' => 'Mention "Article 21 LIVA" sur factures B2B intra-UE',
            's2_5' => 'Journal de validation VIES des numéros de TVA clients',
            's3' => '3. Déclarations TVA',
            's3_1' => 'Déclarations mensuelles ou trimestrielles soumises à temps',
            's3_2' => 'Cohérence avec les totaux FAIA (HT, TVA, TTC)',
            's3_3' => 'Liste récapitulative intracommunautaire pour les B2B intra-UE',
            's3_4' => 'Déclarations OSS si B2C cross-border au-dessus du seuil',
            's4' => '4. Documentation et justificatifs',
            's4_1' => 'Relevés bancaires (10 ans)',
            's4_2' => 'Factures d\'achat et dépenses avec justificatifs TVA',
            's4_3' => 'Journal des modifications sur les écritures comptables',
            's4_4' => 'Preuves de sauvegardes (backups quotidiens 30+ jours)',
            's5' => '5. Jour du contrôle',
            's5_1' => 'Accuser réception de la notification dans les 24h',
            's5_2' => 'Demander une extension si besoin (souvent accordée)',
            's5_3' => 'Fournir tous les documents au format demandé (XML, PDF/A, CSV)',
            's5_4' => 'Prendre des notes de toutes les communications (date, heure, contact)',
            's5_5' => 'Contacter une fiduciaire si le contrôle est complexe',
            'cta' => '💡 Avec faktur.lu, générez votre FAIA en 1 clic et préparez un contrôle en quelques minutes. Essai gratuit 14 jours : faktur.lu',
            'footer' => 'Modèle gratuit',
        ],
        'de' => [
            'title' => 'Checkliste AED-Steuerprüfung Vorbereitung',
            'subtitle' => 'Luxemburg — 2026',
            'deadline_box' => '⚠ <strong>Frist:</strong> Die AED gewährt in der Regel <strong>1 bis 2 Wochen</strong> zur Bereitstellung der angeforderten Unterlagen. Bereiten Sie sich rechtzeitig vor.',
            's1' => '1. FAIA-Datei',
            's1_1' => 'FAIA-Datei in Version 2.01 erstellen (seit 2017 verpflichtend)',
            's1_2' => 'Gegen das offizielle AED-XSD-Schema validieren',
            's1_3' => 'Den vom Prüfer angeforderten Zeitraum abdecken',
            's1_4' => 'Alle Verkaufsjournale und Buchungszeilen einbeziehen',
            's2' => '2. Rechnungen und Gutschriften',
            's2_1' => 'Lückenlose fortlaufende Nummerierung (Artikel 61 LIVA)',
            's2_2' => 'PDF/A-Archive mit Zeitstempel für die letzten 10 Jahre',
            's2_3' => 'Pflichtangaben vorhanden (MwSt, Adresse, Nummerierung, etc.)',
            's2_4' => 'Hinweis "Artikel 21 LIVA" auf B2B-Rechnungen innerhalb der EU',
            's2_5' => 'VIES-Validierungsprotokoll der Kunden-MwSt-Nummern',
            's3' => '3. MwSt-Erklärungen',
            's3_1' => 'Monatliche oder vierteljährliche Erklärungen pünktlich eingereicht',
            's3_2' => 'Übereinstimmung mit FAIA-Summen (netto, MwSt, brutto)',
            's3_3' => 'Zusammenfassende Meldung für B2B-Geschäfte innerhalb der EU',
            's3_4' => 'OSS-Meldungen bei grenzüberschreitendem B2C über der Schwelle',
            's4' => '4. Dokumentation und Belege',
            's4_1' => 'Kontoauszüge (10 Jahre)',
            's4_2' => 'Eingangsrechnungen und Ausgaben mit MwSt-Belegen',
            's4_3' => 'Änderungsprotokoll der Buchungen',
            's4_4' => 'Sicherungsnachweise (tägliche Backups, 30+ Tage)',
            's5' => '5. Am Tag der Prüfung',
            's5_1' => 'Empfang der Benachrichtigung innerhalb von 24 Stunden bestätigen',
            's5_2' => 'Bei Bedarf Fristverlängerung beantragen (häufig gewährt)',
            's5_3' => 'Alle Unterlagen im verlangten Format bereitstellen (XML, PDF/A, CSV)',
            's5_4' => 'Notizen aller Kommunikation festhalten (Datum, Uhrzeit, Ansprechpartner)',
            's5_5' => 'Bei komplexer Prüfung einen Treuhänder hinzuziehen',
            'cta' => '💡 Mit faktur.lu erstellen Sie Ihre FAIA-Datei mit einem Klick und bereiten eine Prüfung in wenigen Minuten vor. 14 Tage gratis testen: faktur.lu',
            'footer' => 'Kostenlose Vorlage',
        ],
        'en' => [
            'title' => 'AED Tax Audit Preparation Checklist',
            'subtitle' => 'Luxembourg — 2026',
            'deadline_box' => '⚠ <strong>Deadline:</strong> the AED typically gives <strong>1-2 weeks</strong> to provide the requested documents. Prepare in advance to avoid panic.',
            's1' => '1. FAIA file',
            's1_1' => 'Generate FAIA file in version 2.01 (mandatory since 2017)',
            's1_2' => 'Validate against the official AED XSD schema',
            's1_3' => 'Cover the period requested by the auditor',
            's1_4' => 'Include all sales journals and accounting lines',
            's2' => '2. Invoices and credit notes',
            's2_1' => 'Continuous sequential numbering (Article 61 LIVA)',
            's2_2' => 'PDF/A archives with timestamps for the last 10 years',
            's2_3' => 'Mandatory mentions present (VAT number, address, sequential, etc.)',
            's2_4' => '"Article 21 LIVA" mention on B2B intra-EU invoices',
            's2_5' => 'VIES validation log of client VAT numbers',
            's3' => '3. VAT returns',
            's3_1' => 'Monthly or quarterly returns submitted on time',
            's3_2' => 'Match with FAIA totals (net, VAT, gross)',
            's3_3' => 'EC sales list for intra-EU B2B operations',
            's3_4' => 'OSS declarations if cross-border B2C above threshold',
            's4' => '4. Documentation and supporting documents',
            's4_1' => 'Bank statements (10 years)',
            's4_2' => 'Purchase invoices and expenses with VAT receipts',
            's4_3' => 'Modification log on accounting entries',
            's4_4' => 'Backup proofs (daily backups for 30+ days)',
            's5' => '5. Day of the audit',
            's5_1' => 'Acknowledge receipt of the notification within 24h',
            's5_2' => 'Request an extension if needed (often granted)',
            's5_3' => 'Provide all documents in the format requested (XML, PDF/A, CSV)',
            's5_4' => 'Take notes of all communications (date, time, contact)',
            's5_5' => 'Contact a fiduciary if the audit is complex',
            'cta' => '💡 With faktur.lu, generate your FAIA file in 1 click and prepare an audit in minutes. Free 14-day trial: faktur.lu',
            'footer' => 'Free template',
        ],
        'lb' => [
            'title' => 'Checklist Virbereedung AED Steierprüfung',
            'subtitle' => 'Lëtzebuerg — 2026',
            'deadline_box' => '⚠ <strong>Delai:</strong> d\'AED gëtt typesch <strong>1 bis 2 Wochen</strong> fir déi gefuerdert Dokumenter ze liwweren. Bereet Iech am Viraus vir.',
            's1' => '1. FAIA-Datei',
            's1_1' => 'FAIA-Datei a Versioun 2.01 generéieren (obligatoresch zanter 2017)',
            's1_2' => 'Géint de offiziellen AED XSD-Schema validéieren',
            's1_3' => 'Déi vum Prüfer gefuerdert Period ofdecken',
            's1_4' => 'All Verkafsjournaler a Buchhalungslinnen abegraff',
            's2' => '2. Rechnungen a Crediter',
            's2_1' => 'Kontinuéierlech sequentiell Numeréierung (Artikel 61 LIVA)',
            's2_2' => 'PDF/A-Archiven mat Zäitstempel fir déi lescht 10 Joer',
            's2_3' => 'Obligatoresch gesetzlech Erwähnungen do (TVA, Adress, Sequenz, asw.)',
            's2_4' => 'Erwähnung "Artikel 21 LIVA" op B2B-Rechnungen bannent der EU',
            's2_5' => 'VIES-Validéierungsjournal vun de TVA-Nummeren vun de Clienten',
            's3' => '3. TVA-Deklaratiounen',
            's3_1' => 'Mounatlech oder trimestriell Deklaratioune pünktlech agereecht',
            's3_2' => 'Kohärenz mat de FAIA-Totaler (HT, TVA, TTC)',
            's3_3' => 'Rekapitulativ Lëscht fir intra-EU B2B-Operatiounen',
            's3_4' => 'OSS-Deklaratiounen wann d\'B2C-Operatioune cross-border iwwer der Schwell sinn',
            's4' => '4. Dokumentatioun a Justificatiifen',
            's4_1' => 'Banksolden (10 Joer)',
            's4_2' => 'Kafrechnungen an Ausgabe mat TVA-Justificatiifen',
            's4_3' => 'Journal vun den Ännerungen op de Buchungen',
            's4_4' => 'Backupbeweiser (deeglech Backups 30+ Deeg)',
            's5' => '5. Daag vun der Kontroll',
            's5_1' => 'Empfank vun der Notifikatioun bannent 24 Stonne bestätegen',
            's5_2' => 'Eng Verlängerung ufroen wann néideg (oft accordéiert)',
            's5_3' => 'All Dokumenter am gefuerderten Format liwweren (XML, PDF/A, CSV)',
            's5_4' => 'Notize vun all Kommunikatiounen huelen (Datum, Auerzäit, Kontakt)',
            's5_5' => 'Eng Fiduciaire kontaktéieren wann d\'Kontroll komplex ass',
            'cta' => '💡 Mat faktur.lu generéiert Dir Är FAIA-Datei mat engem Klick a bereet eng Kontroll a Minutten vir. Gratis Essai 14 Deeg: faktur.lu',
            'footer' => 'Gratis Modell',
        ],
        'pt' => [
            'title' => 'Lista de verificação preparação inspeção fiscal AED',
            'subtitle' => 'Luxemburgo — 2026',
            'deadline_box' => '⚠ <strong>Prazo:</strong> a AED concede normalmente <strong>1 a 2 semanas</strong> para fornecer os documentos solicitados. Prepare-se antecipadamente.',
            's1' => '1. Ficheiro FAIA',
            's1_1' => 'Gerar o ficheiro FAIA na versão 2.01 (obrigatório desde 2017)',
            's1_2' => 'Validar contra o esquema XSD oficial da AED',
            's1_3' => 'Abranger o período solicitado pelo auditor',
            's1_4' => 'Incluir todos os diários de vendas e linhas contabilísticas',
            's2' => '2. Faturas e notas de crédito',
            's2_1' => 'Numeração sequencial contínua (Artigo 61 LIVA)',
            's2_2' => 'Arquivos PDF/A com carimbo temporal dos últimos 10 anos',
            's2_3' => 'Menções legais obrigatórias presentes (IVA, morada, sequência, etc.)',
            's2_4' => 'Menção "Artigo 21 LIVA" nas faturas B2B intra-UE',
            's2_5' => 'Registo de validação VIES dos números de IVA dos clientes',
            's3' => '3. Declarações de IVA',
            's3_1' => 'Declarações mensais ou trimestrais submetidas a tempo',
            's3_2' => 'Coerência com os totais FAIA (líquido, IVA, total)',
            's3_3' => 'Mapa recapitulativo para operações B2B intra-UE',
            's3_4' => 'Declarações OSS se B2C transfronteiriço acima do limiar',
            's4' => '4. Documentação e comprovativos',
            's4_1' => 'Extratos bancários (10 anos)',
            's4_2' => 'Faturas de compras e despesas com comprovativos de IVA',
            's4_3' => 'Registo das alterações dos lançamentos contabilísticos',
            's4_4' => 'Provas de cópias de segurança (backups diários 30+ dias)',
            's5' => '5. Dia da inspeção',
            's5_1' => 'Acusar a receção da notificação dentro de 24h',
            's5_2' => 'Pedir extensão se necessário (frequentemente concedida)',
            's5_3' => 'Fornecer todos os documentos no formato solicitado (XML, PDF/A, CSV)',
            's5_4' => 'Tomar notas de todas as comunicações (data, hora, contacto)',
            's5_5' => 'Contactar uma fiduciária se a inspeção for complexa',
            'cta' => '💡 Com o faktur.lu, gere o seu ficheiro FAIA num clique e prepare uma inspeção em minutos. Avaliação gratuita de 14 dias: faktur.lu',
            'footer' => 'Modelo gratuito',
        ],
    ];
    $tr = $T[$lang] ?? $T['fr'];
@endphp

<div class="header">
    <h1>{{ $tr['title'] }}</h1>
    <p>{{ $tr['subtitle'] }}</p>
</div>

<div class="important">
    {!! $tr['deadline_box'] !!}
</div>

<h2>{{ $tr['s1'] }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $tr['s1_1'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s1_2'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s1_3'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s1_4'] }}</li>
</ul>

<h2>{{ $tr['s2'] }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $tr['s2_1'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s2_2'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s2_3'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s2_4'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s2_5'] }}</li>
</ul>

<h2>{{ $tr['s3'] }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $tr['s3_1'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s3_2'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s3_3'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s3_4'] }}</li>
</ul>

<h2>{{ $tr['s4'] }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $tr['s4_1'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s4_2'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s4_3'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s4_4'] }}</li>
</ul>

<h2>{{ $tr['s5'] }}</h2>
<ul>
    <li><span class="checkbox"></span> {{ $tr['s5_1'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s5_2'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s5_3'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s5_4'] }}</li>
    <li><span class="checkbox"></span> {{ $tr['s5_5'] }}</li>
</ul>

<div class="important">
    {{ $tr['cta'] }}
</div>

<div class="footer">
    <a href="https://faktur.lu" class="powered-by">faktur.lu</a> — {{ $tr['footer'] }}
</div>
</body>
</html>
