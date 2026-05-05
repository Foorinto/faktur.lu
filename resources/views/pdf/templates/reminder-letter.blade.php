<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 2cm; }
        body { font-family: Helvetica, sans-serif; font-size: 11pt; color: #1a202c; line-height: 1.6; }
        .header { margin-bottom: 2em; }
        h1 { font-size: 18pt; color: #9b5de5; margin: 0 0 0.5em 0; }
        h2 { font-size: 14pt; color: #9b5de5; margin-top: 2em; padding: 0.3em 0; border-bottom: 2px solid #9b5de5; }
        .level-tag { display: inline-block; padding: 0.2em 0.8em; border-radius: 4px; font-size: 9pt; font-weight: bold; margin-bottom: 0.5em; }
        .level-1 { background: #dbeafe; color: #1e40af; }
        .level-2 { background: #fef3c7; color: #92400e; }
        .level-3 { background: #fee2e2; color: #991b1b; }
        .placeholder { color: #9b5de5; font-style: italic; font-weight: bold; }
        .footer { margin-top: 2em; text-align: center; font-size: 8pt; color: #718096; padding-top: 1em; border-top: 1px solid #e2e8f0; }
        .powered-by { color: #9b5de5; text-decoration: none; font-weight: bold; }
        .letter { margin-bottom: 3em; padding: 1em; border-left: 3px solid #9b5de5; background: #fafafa; }
    </style>
</head>
<body>
@php
    $isEN = ($language ?? 'fr') === 'en';
@endphp

<div class="header">
    <h1>{{ $isEN ? 'Unpaid invoice reminder templates' : 'Modèles de relance impayés' }}</h1>
    <p>{{ $isEN ? 'Three escalation levels — Luxembourg' : 'Trois niveaux d\'escalade — Luxembourg' }}</p>
</div>

{{-- LEVEL 1 --}}
<h2>{{ $isEN ? 'Level 1 — Friendly reminder' : 'Niveau 1 — Rappel amical' }}</h2>
<div class="level-tag level-1">{{ $isEN ? 'After 7 days late' : 'Après 7 jours de retard' }}</div>
<div class="letter">
@if ($isEN)
<p>Dear <span class="placeholder">[Client name]</span>,</p>
<p>I hope this message finds you well. I'm writing as a friendly reminder regarding invoice <span class="placeholder">[Invoice number]</span> dated <span class="placeholder">[Date]</span> for the amount of <span class="placeholder">[Amount] EUR</span>, which was due on <span class="placeholder">[Due date]</span>.</p>
<p>I understand that things can sometimes slip through the cracks. Could you please confirm whether the payment has been processed? If not, I would appreciate it if you could arrange the transfer at your earliest convenience.</p>
<p>If you have any questions or require a copy of the invoice, please don't hesitate to contact me.</p>
<p>Best regards,<br><span class="placeholder">[Your name]</span></p>
@else
<p>Bonjour <span class="placeholder">[Nom du client]</span>,</p>
<p>J'espère que vous allez bien. Je me permets de vous rappeler que la facture <span class="placeholder">[Numéro de facture]</span> du <span class="placeholder">[Date]</span>, d'un montant de <span class="placeholder">[Montant] EUR</span>, était à régler le <span class="placeholder">[Date d'échéance]</span>.</p>
<p>Il s'agit peut-être simplement d'un oubli. Pouvez-vous me confirmer le paiement, ou procéder au virement dans les meilleurs délais ?</p>
<p>Si vous avez des questions ou souhaitez recevoir une copie de la facture, n'hésitez pas à me contacter.</p>
<p>Cordialement,<br><span class="placeholder">[Votre nom]</span></p>
@endif
</div>

{{-- LEVEL 2 --}}
<h2>{{ $isEN ? 'Level 2 — Formal reminder' : 'Niveau 2 — Mise en demeure' }}</h2>
<div class="level-tag level-2">{{ $isEN ? 'After 30 days late' : 'Après 30 jours de retard' }}</div>
<div class="letter">
@if ($isEN)
<p>Dear <span class="placeholder">[Client name]</span>,</p>
<p>Despite my reminder dated <span class="placeholder">[Date of first reminder]</span>, invoice <span class="placeholder">[Invoice number]</span> for <span class="placeholder">[Amount] EUR</span> remains unpaid more than 30 days after the due date of <span class="placeholder">[Due date]</span>.</p>
<p>I hereby formally request settlement of this debt within <strong>15 days</strong> from receipt of this letter.</p>
<p>Please note that, in accordance with Luxembourg law (Loi du 18 avril 2004), late payment in commercial transactions automatically accrues interest at the legal rate (currently 8% above the ECB rate) plus a fixed €40 indemnity for collection costs.</p>
<p>Failing payment within the stated timeframe, I reserve the right to initiate any legal proceedings necessary to recover the amount due.</p>
<p>Best regards,<br><span class="placeholder">[Your name]</span></p>
@else
<p>Bonjour <span class="placeholder">[Nom du client]</span>,</p>
<p>Malgré ma relance du <span class="placeholder">[Date 1ère relance]</span>, la facture <span class="placeholder">[Numéro de facture]</span> d'un montant de <span class="placeholder">[Montant] EUR</span> reste impayée plus de 30 jours après la date d'échéance du <span class="placeholder">[Date d'échéance]</span>.</p>
<p>Par la présente, je vous mets en demeure de régler cette créance dans un délai de <strong>15 jours</strong> à compter de la réception de cette lettre.</p>
<p>Je vous rappelle qu'en vertu de la loi du 18 avril 2004 sur les retards de paiement dans les transactions commerciales, tout retard donne lieu de plein droit à l'application d'intérêts moratoires au taux légal (actuellement BCE + 8 points) et à une indemnité forfaitaire de 40 EUR pour frais de recouvrement.</p>
<p>À défaut de paiement dans le délai imparti, je me réserve le droit d'engager toute procédure judiciaire utile au recouvrement de cette créance.</p>
<p>Cordialement,<br><span class="placeholder">[Votre nom]</span></p>
@endif
</div>

{{-- LEVEL 3 --}}
<h2>{{ $isEN ? 'Level 3 — Final notice before legal action' : 'Niveau 3 — Mise en demeure avant action judiciaire' }}</h2>
<div class="level-tag level-3">{{ $isEN ? 'After 60 days late — registered letter' : 'Après 60 jours de retard — recommandé avec AR' }}</div>
<div class="letter">
@if ($isEN)
<p><strong>Sent by registered letter with acknowledgment of receipt</strong></p>
<p>Dear <span class="placeholder">[Client name]</span>,</p>
<p>Despite my previous reminders dated <span class="placeholder">[Date 1]</span> and <span class="placeholder">[Date 2]</span>, invoice <span class="placeholder">[Invoice number]</span> for the amount of <span class="placeholder">[Amount] EUR</span>, plus accumulated interest, remains unpaid.</p>
<p>This is a final formal notice requiring you to pay <span class="placeholder">[Total amount with interest] EUR</span> within <strong>8 days</strong> from receipt of this letter.</p>
<p>Failing payment, I will, without further notice:</p>
<ul>
    <li>Refer the matter to a debt collection company</li>
    <li>Initiate proceedings before the competent Luxembourg court</li>
    <li>Claim, in addition to the principal amount: legal interest, the €40 fixed indemnity, and additional collection costs</li>
</ul>
<p>I trust that you will prefer to avoid this escalation by settling the debt within the stated timeframe.</p>
<p>Best regards,<br><span class="placeholder">[Your name]</span></p>
@else
<p><strong>Envoyée par lettre recommandée avec accusé de réception</strong></p>
<p>Bonjour <span class="placeholder">[Nom du client]</span>,</p>
<p>Malgré mes relances précédentes des <span class="placeholder">[Date 1]</span> et <span class="placeholder">[Date 2]</span>, la facture <span class="placeholder">[Numéro de facture]</span> d'un montant de <span class="placeholder">[Montant] EUR</span>, augmenté des intérêts moratoires, reste impayée.</p>
<p>Par la présente, je vous mets <strong>une dernière fois en demeure</strong> de régler la somme totale de <span class="placeholder">[Montant total avec intérêts] EUR</span> dans un délai de <strong>8 jours</strong> à compter de la réception de cette lettre.</p>
<p>À défaut de paiement, je transmettrai le dossier sans autre préavis :</p>
<ul>
    <li>À une société de recouvrement de créances</li>
    <li>Au tribunal compétent du Grand-Duché de Luxembourg pour une procédure judiciaire</li>
    <li>En réclamant, outre le montant principal : les intérêts légaux, l'indemnité forfaitaire de 40 EUR et les frais de recouvrement supplémentaires</li>
</ul>
<p>J'espère que vous préférerez éviter cette escalade en réglant la dette dans le délai imparti.</p>
<p>Cordialement,<br><span class="placeholder">[Votre nom]</span></p>
@endif
</div>

<div style="margin-top: 1.5em; padding: 1em; background: #fef3c7; border-left: 3px solid #f59e0b; font-size: 9pt;">
    {{ $isEN
        ? '💡 Replace [placeholders] with your specific information. With faktur.lu, automate your reminders: scheduled emails, invoice tracking, late interest calculation. faktur.lu'
        : '💡 Remplacez les [placeholders] par vos informations. Avec faktur.lu, automatisez vos relances : envoi programmé, suivi des paiements, calcul automatique des intérêts. faktur.lu' }}
</div>

<div class="footer">
    <a href="https://faktur.lu" class="powered-by">faktur.lu</a> — {{ $isEN ? 'Free template' : 'Modèle gratuit' }}
</div>
</body>
</html>
