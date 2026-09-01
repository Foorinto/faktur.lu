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
    $lang = $language ?? 'fr';
    $T = [
        'fr' => [
            'title' => 'Modèles de relance impayés',
            'subtitle' => 'Trois niveaux d\'escalade - Luxembourg',
            'l1_title' => 'Niveau 1 - Rappel amical',
            'l1_tag' => 'Après 7 jours de retard',
            'l1_greeting' => 'Bonjour',
            'l1_client' => 'Nom du client',
            'l1_p1_a' => 'J\'espère que vous allez bien. Je me permets de vous rappeler que la facture',
            'l1_invoice_no' => 'Numéro de facture',
            'l1_p1_b' => 'du',
            'l1_date' => 'Date',
            'l1_p1_c' => ', d\'un montant de',
            'l1_amount' => 'Montant] EUR',
            'l1_p1_d' => ', était à régler le',
            'l1_due' => 'Date d\'échéance',
            'l1_p1_e' => '.',
            'l1_p2' => 'Il s\'agit peut-être simplement d\'un oubli. Pouvez-vous me confirmer le paiement, ou procéder au virement dans les meilleurs délais ?',
            'l1_p3' => 'Si vous avez des questions ou souhaitez recevoir une copie de la facture, n\'hésitez pas à me contacter.',
            'l1_signoff' => 'Cordialement,',
            'l1_yourname' => 'Votre nom',
            'l2_title' => 'Niveau 2 - Mise en demeure',
            'l2_tag' => 'Après 30 jours de retard',
            'l2_p1_a' => 'Malgré ma relance du',
            'l2_p1_first_date' => 'Date 1ère relance',
            'l2_p1_b' => ', la facture',
            'l2_p1_c' => 'd\'un montant de',
            'l2_p1_d' => 'reste impayée plus de 30 jours après la date d\'échéance du',
            'l2_p1_e' => '.',
            'l2_p2_a' => 'Par la présente, je vous mets en demeure de régler cette créance dans un délai de',
            'l2_p2_strong' => '15 jours',
            'l2_p2_b' => 'à compter de la réception de cette lettre.',
            'l2_p3' => 'Je vous rappelle qu\'en vertu de la loi du 18 avril 2004 sur les retards de paiement dans les transactions commerciales, tout retard donne lieu de plein droit à l\'application d\'intérêts moratoires au taux légal (actuellement BCE + 8 points) et à une indemnité forfaitaire de 40 EUR pour frais de recouvrement.',
            'l2_p4' => 'À défaut de paiement dans le délai imparti, je me réserve le droit d\'engager toute procédure judiciaire utile au recouvrement de cette créance.',
            'l3_title' => 'Niveau 3 - Mise en demeure avant action judiciaire',
            'l3_tag' => 'Après 60 jours de retard - recommandé avec AR',
            'l3_intro' => 'Envoyée par lettre recommandée avec accusé de réception',
            'l3_p1_a' => 'Malgré mes relances précédentes des',
            'l3_p1_date1' => 'Date 1',
            'l3_p1_and' => 'et',
            'l3_p1_date2' => 'Date 2',
            'l3_p1_b' => ', la facture',
            'l3_p1_c' => 'd\'un montant de',
            'l3_p1_d' => ', augmenté des intérêts moratoires, reste impayée.',
            'l3_p2_a' => 'Par la présente, je vous mets',
            'l3_p2_strong1' => 'une dernière fois en demeure',
            'l3_p2_b' => 'de régler la somme totale de',
            'l3_p2_total' => 'Montant total avec intérêts] EUR',
            'l3_p2_c' => 'dans un délai de',
            'l3_p2_strong2' => '8 jours',
            'l3_p2_d' => 'à compter de la réception de cette lettre.',
            'l3_p3' => 'À défaut de paiement, je transmettrai le dossier sans autre préavis :',
            'l3_li1' => 'À une société de recouvrement de créances',
            'l3_li2' => 'Au tribunal compétent du Grand-Duché de Luxembourg pour une procédure judiciaire',
            'l3_li3' => 'En réclamant, outre le montant principal : les intérêts légaux, l\'indemnité forfaitaire de 40 EUR et les frais de recouvrement supplémentaires',
            'l3_p4' => 'J\'espère que vous préférerez éviter cette escalade en réglant la dette dans le délai imparti.',
            'cta' => '💡 Remplacez les [placeholders] par vos informations. Avec faktur.lu, automatisez vos relances : envoi programmé, suivi des paiements, calcul automatique des intérêts. faktur.lu',
            'footer' => 'Modèle gratuit',
        ],
        'de' => [
            'title' => 'Mahnvorlagen für unbezahlte Rechnungen',
            'subtitle' => 'Drei Eskalationsstufen - Luxemburg',
            'l1_title' => 'Stufe 1 - Freundliche Erinnerung',
            'l1_tag' => 'Nach 7 Tagen Verzug',
            'l1_greeting' => 'Sehr geehrte/r',
            'l1_client' => 'Name des Kunden',
            'l1_p1_a' => 'Ich hoffe, es geht Ihnen gut. Ich möchte Sie freundlich daran erinnern, dass die Rechnung',
            'l1_invoice_no' => 'Rechnungsnummer',
            'l1_p1_b' => 'vom',
            'l1_date' => 'Datum',
            'l1_p1_c' => ', in Höhe von',
            'l1_amount' => 'Betrag] EUR',
            'l1_p1_d' => ', am',
            'l1_due' => 'Fälligkeitsdatum',
            'l1_p1_e' => 'fällig war.',
            'l1_p2' => 'Es handelt sich möglicherweise um ein Versehen. Können Sie mir die Zahlung bestätigen oder die Überweisung zeitnah veranlassen?',
            'l1_p3' => 'Bei Fragen oder wenn Sie eine Kopie der Rechnung wünschen, zögern Sie nicht, mich zu kontaktieren.',
            'l1_signoff' => 'Mit freundlichen Grüßen,',
            'l1_yourname' => 'Ihr Name',
            'l2_title' => 'Stufe 2 - Förmliche Mahnung',
            'l2_tag' => 'Nach 30 Tagen Verzug',
            'l2_p1_a' => 'Trotz meiner Mahnung vom',
            'l2_p1_first_date' => 'Datum der 1. Mahnung',
            'l2_p1_b' => 'ist die Rechnung',
            'l2_p1_c' => 'in Höhe von',
            'l2_p1_d' => 'mehr als 30 Tage nach dem Fälligkeitsdatum',
            'l2_p1_e' => 'noch unbezahlt.',
            'l2_p2_a' => 'Hiermit fordere ich Sie förmlich auf, diese Forderung innerhalb einer Frist von',
            'l2_p2_strong' => '15 Tagen',
            'l2_p2_b' => 'ab Erhalt dieses Schreibens zu begleichen.',
            'l2_p3' => 'Ich weise Sie darauf hin, dass gemäß luxemburgischem Recht (Loi du 18 avril 2004) Zahlungsverzug im Geschäftsverkehr automatisch Verzugszinsen zum gesetzlichen Zinssatz (derzeit EZB + 8 Punkte) sowie eine pauschale Entschädigung von 40 EUR für Inkassokosten auslöst.',
            'l2_p4' => 'Bei ausbleibender Zahlung innerhalb der genannten Frist behalte ich mir das Recht vor, alle erforderlichen gerichtlichen Schritte zur Forderungseinziehung einzuleiten.',
            'l3_title' => 'Stufe 3 - Letzte Mahnung vor Gerichtsverfahren',
            'l3_tag' => 'Nach 60 Tagen Verzug - Einschreiben mit Rückschein',
            'l3_intro' => 'Versendet per Einschreiben mit Rückschein',
            'l3_p1_a' => 'Trotz meiner vorherigen Mahnungen vom',
            'l3_p1_date1' => 'Datum 1',
            'l3_p1_and' => 'und',
            'l3_p1_date2' => 'Datum 2',
            'l3_p1_b' => 'ist die Rechnung',
            'l3_p1_c' => 'in Höhe von',
            'l3_p1_d' => 'zuzüglich der aufgelaufenen Zinsen unbezahlt.',
            'l3_p2_a' => 'Hiermit fordere ich Sie',
            'l3_p2_strong1' => 'ein letztes Mal förmlich auf',
            'l3_p2_b' => ', den Gesamtbetrag von',
            'l3_p2_total' => 'Gesamtbetrag mit Zinsen] EUR',
            'l3_p2_c' => 'innerhalb einer Frist von',
            'l3_p2_strong2' => '8 Tagen',
            'l3_p2_d' => 'ab Erhalt dieses Schreibens zu begleichen.',
            'l3_p3' => 'Bei ausbleibender Zahlung werde ich ohne weitere Vorankündigung:',
            'l3_li1' => 'Den Fall an ein Inkassounternehmen weitergeben',
            'l3_li2' => 'Ein Verfahren beim zuständigen Gericht in Luxemburg einleiten',
            'l3_li3' => 'Zusätzlich zum Hauptbetrag fordern: gesetzliche Zinsen, die pauschale Entschädigung von 40 EUR sowie zusätzliche Inkassokosten',
            'l3_p4' => 'Ich vertraue darauf, dass Sie diese Eskalation vermeiden möchten, indem Sie die Schuld innerhalb der genannten Frist begleichen.',
            'cta' => '💡 Ersetzen Sie die [Platzhalter] durch Ihre spezifischen Informationen. Mit faktur.lu automatisieren Sie Ihre Mahnungen: geplante E-Mails, Zahlungsverfolgung, Verzugszinsberechnung. faktur.lu',
            'footer' => 'Kostenlose Vorlage',
        ],
        'en' => [
            'title' => 'Unpaid invoice reminder templates',
            'subtitle' => 'Three escalation levels - Luxembourg',
            'l1_title' => 'Level 1 - Friendly reminder',
            'l1_tag' => 'After 7 days late',
            'l1_greeting' => 'Dear',
            'l1_client' => 'Client name',
            'l1_p1_a' => 'I hope this message finds you well. I\'m writing as a friendly reminder regarding invoice',
            'l1_invoice_no' => 'Invoice number',
            'l1_p1_b' => 'dated',
            'l1_date' => 'Date',
            'l1_p1_c' => 'for the amount of',
            'l1_amount' => 'Amount] EUR',
            'l1_p1_d' => ', which was due on',
            'l1_due' => 'Due date',
            'l1_p1_e' => '.',
            'l1_p2' => 'I understand that things can sometimes slip through the cracks. Could you please confirm whether the payment has been processed? If not, I would appreciate it if you could arrange the transfer at your earliest convenience.',
            'l1_p3' => 'If you have any questions or require a copy of the invoice, please don\'t hesitate to contact me.',
            'l1_signoff' => 'Best regards,',
            'l1_yourname' => 'Your name',
            'l2_title' => 'Level 2 - Formal reminder',
            'l2_tag' => 'After 30 days late',
            'l2_p1_a' => 'Despite my reminder dated',
            'l2_p1_first_date' => 'Date of first reminder',
            'l2_p1_b' => ', invoice',
            'l2_p1_c' => 'for',
            'l2_p1_d' => 'remains unpaid more than 30 days after the due date of',
            'l2_p1_e' => '.',
            'l2_p2_a' => 'I hereby formally request settlement of this debt within',
            'l2_p2_strong' => '15 days',
            'l2_p2_b' => 'from receipt of this letter.',
            'l2_p3' => 'Please note that, in accordance with Luxembourg law (Loi du 18 avril 2004), late payment in commercial transactions automatically accrues interest at the legal rate (currently 8% above the ECB rate) plus a fixed 40 EUR indemnity for collection costs.',
            'l2_p4' => 'Failing payment within the stated timeframe, I reserve the right to initiate any legal proceedings necessary to recover the amount due.',
            'l3_title' => 'Level 3 - Final notice before legal action',
            'l3_tag' => 'After 60 days late - registered letter',
            'l3_intro' => 'Sent by registered letter with acknowledgment of receipt',
            'l3_p1_a' => 'Despite my previous reminders dated',
            'l3_p1_date1' => 'Date 1',
            'l3_p1_and' => 'and',
            'l3_p1_date2' => 'Date 2',
            'l3_p1_b' => ', invoice',
            'l3_p1_c' => 'for the amount of',
            'l3_p1_d' => ', plus accumulated interest, remains unpaid.',
            'l3_p2_a' => 'This is a',
            'l3_p2_strong1' => 'final formal notice',
            'l3_p2_b' => 'requiring you to pay',
            'l3_p2_total' => 'Total amount with interest] EUR',
            'l3_p2_c' => 'within',
            'l3_p2_strong2' => '8 days',
            'l3_p2_d' => 'from receipt of this letter.',
            'l3_p3' => 'Failing payment, I will, without further notice:',
            'l3_li1' => 'Refer the matter to a debt collection company',
            'l3_li2' => 'Initiate proceedings before the competent Luxembourg court',
            'l3_li3' => 'Claim, in addition to the principal amount: legal interest, the 40 EUR fixed indemnity, and additional collection costs',
            'l3_p4' => 'I trust that you will prefer to avoid this escalation by settling the debt within the stated timeframe.',
            'cta' => '💡 Replace [placeholders] with your specific information. With faktur.lu, automate your reminders: scheduled emails, invoice tracking, late interest calculation. faktur.lu',
            'footer' => 'Free template',
        ],
        'lb' => [
            'title' => 'Modeller fir Rappeller bei onbezuelten Rechnungen',
            'subtitle' => 'Dräi Eskalatiounsniveauen - Lëtzebuerg',
            'l1_title' => 'Niveau 1 - Frëndlechen Rappel',
            'l1_tag' => 'No 7 Deeg Retard',
            'l1_greeting' => 'Léif',
            'l1_client' => 'Numm vum Client',
            'l1_p1_a' => 'Ech hoffen et geet Iech gutt. Ech erlaben mech, Iech drun ze erënneren, datt d\'Rechnung',
            'l1_invoice_no' => 'Rechnungsnummer',
            'l1_p1_b' => 'vum',
            'l1_date' => 'Datum',
            'l1_p1_c' => ', mat engem Betrag vun',
            'l1_amount' => 'Betrag] EUR',
            'l1_p1_d' => ', den',
            'l1_due' => 'Echéance',
            'l1_p1_e' => 'ze bezuelen war.',
            'l1_p2' => 'Et kéint einfach e Vergiess sinn. Kënnt Dir mir d\'Bezuelung bestätegen, oder d\'Iwwerweisung sou séier wéi méiglech maachen?',
            'l1_p3' => 'Wann Dir Froen hutt oder eng Kopie vun der Rechnung wëllt, zéckt net mech ze kontaktéieren.',
            'l1_signoff' => 'Mat frëndleche Gréiss,',
            'l1_yourname' => 'Äre Numm',
            'l2_title' => 'Niveau 2 - Förmlech Mahnung',
            'l2_tag' => 'No 30 Deeg Retard',
            'l2_p1_a' => 'Trotz mengem Rappel vum',
            'l2_p1_first_date' => 'Datum 1. Rappel',
            'l2_p1_b' => 'bleift d\'Rechnung',
            'l2_p1_c' => 'mat engem Betrag vun',
            'l2_p1_d' => 'méi wéi 30 Deeg no der Echéance vum',
            'l2_p1_e' => 'onbezuelt.',
            'l2_p2_a' => 'Hei mat fuerderen ech Iech förmlech op, dës Schold bannent enger Fräscht vun',
            'l2_p2_strong' => '15 Deeg',
            'l2_p2_b' => 'no Empfank vun dësem Bréif ze bezuelen.',
            'l2_p3' => 'Ech erënneren Iech, datt no Lëtzebuerger Gesetz (Loi du 18 avril 2004) iwwer Bezuelungsretarder am Handel, all Retard automatesch zu Moratoiresinteressi um gesetzleche Sazz (aktuell BCE + 8 Punkten) an enger forfaitärer Entschiedegung vun 40 EUR fir Recouvrementkäschten féiert.',
            'l2_p4' => 'Bei feelender Bezuelung bannent der genannter Fräscht, behalen ech mer d\'Recht vir, all néideg geriichtlech Schrëtt fir d\'Recouvrement vun dëser Forderung anzeleeden.',
            'l3_title' => 'Niveau 3 - Lescht Mahnung virum geriichtlechen Aktioun',
            'l3_tag' => 'No 60 Deeg Retard - recommandéiert mat AR',
            'l3_intro' => 'Geschéckt per recommandéierten Bréif mat Empfangsbestätegung',
            'l3_p1_a' => 'Trotz mengen virege Rappeller vum',
            'l3_p1_date1' => 'Datum 1',
            'l3_p1_and' => 'an',
            'l3_p1_date2' => 'Datum 2',
            'l3_p1_b' => ', bleift d\'Rechnung',
            'l3_p1_c' => 'mat engem Betrag vun',
            'l3_p1_d' => ', plus opgelaf Interessen, onbezuelt.',
            'l3_p2_a' => 'Hei mat fuerderen ech Iech',
            'l3_p2_strong1' => 'eng lescht Kéier förmlech op',
            'l3_p2_b' => ', de Gesamtbetrag vun',
            'l3_p2_total' => 'Gesamtbetrag mat Interessen] EUR',
            'l3_p2_c' => 'bannent enger Fräscht vun',
            'l3_p2_strong2' => '8 Deeg',
            'l3_p2_d' => 'no Empfank vun dësem Bréif ze bezuelen.',
            'l3_p3' => 'Bei feelender Bezuelung wäert ech ouni weider Virwarnung:',
            'l3_li1' => 'D\'Dossier un eng Recouvrementgesellschaft weiderginn',
            'l3_li2' => 'Eng Prozedur viru kompetenten Geriicht vu Lëtzebuerg aleeden',
            'l3_li3' => 'Niewt dem Haaptbetrag fuerderen: gesetzlech Interessen, déi forfaitär Entschiedegung vu 40 EUR an zousätzlech Recouvrementkäschten',
            'l3_p4' => 'Ech hoffen, datt Dir dës Eskalatioun léiwer vermeit, andeems Dir d\'Schold bannent der genannter Fräscht bezuelt.',
            'cta' => '💡 Ersetzt d\'[Platzhalter] duerch Är spezifesch Informatiounen. Mat faktur.lu automatiséiert Dir Är Rappeller: programméiert Mailen, Suivi vun de Bezuelungen, automatesch Berechnung vun den Interessen. faktur.lu',
            'footer' => 'Gratis Modell',
        ],
        'pt' => [
            'title' => 'Modelos de aviso de faturas em atraso',
            'subtitle' => 'Três níveis de escalada - Luxemburgo',
            'l1_title' => 'Nível 1 - Aviso amigável',
            'l1_tag' => 'Após 7 dias de atraso',
            'l1_greeting' => 'Caro/a',
            'l1_client' => 'Nome do cliente',
            'l1_p1_a' => 'Espero que esteja bem. Escrevo apenas para lembrar que a fatura',
            'l1_invoice_no' => 'Número da fatura',
            'l1_p1_b' => 'de',
            'l1_date' => 'Data',
            'l1_p1_c' => ', no valor de',
            'l1_amount' => 'Valor] EUR',
            'l1_p1_d' => ', tinha vencimento em',
            'l1_due' => 'Data de vencimento',
            'l1_p1_e' => '.',
            'l1_p2' => 'Pode tratar-se apenas de um esquecimento. Pode confirmar o pagamento ou efetuar a transferência o mais brevemente possível?',
            'l1_p3' => 'Caso tenha alguma questão ou pretenda uma cópia da fatura, não hesite em contactar-me.',
            'l1_signoff' => 'Com os melhores cumprimentos,',
            'l1_yourname' => 'O seu nome',
            'l2_title' => 'Nível 2 - Notificação formal',
            'l2_tag' => 'Após 30 dias de atraso',
            'l2_p1_a' => 'Apesar do meu aviso de',
            'l2_p1_first_date' => 'Data do 1º aviso',
            'l2_p1_b' => ', a fatura',
            'l2_p1_c' => 'no valor de',
            'l2_p1_d' => 'continua por liquidar há mais de 30 dias após a data de vencimento de',
            'l2_p1_e' => '.',
            'l2_p2_a' => 'Pela presente, notifico-o formalmente para regularizar esta dívida no prazo de',
            'l2_p2_strong' => '15 dias',
            'l2_p2_b' => 'a contar da receção desta carta.',
            'l2_p3' => 'Recordo que, ao abrigo da lei luxemburguesa (Loi du 18 avril 2004) sobre atrasos de pagamento nas transações comerciais, qualquer atraso dá origem automaticamente a juros moratórios à taxa legal (atualmente BCE + 8 pontos) e a uma indemnização forfetária de 40 EUR pelas despesas de cobrança.',
            'l2_p4' => 'Não havendo pagamento no prazo indicado, reservo-me o direito de iniciar qualquer procedimento judicial necessário para a cobrança desta dívida.',
            'l3_title' => 'Nível 3 - Notificação final antes de ação judicial',
            'l3_tag' => 'Após 60 dias de atraso - carta registada com AR',
            'l3_intro' => 'Enviada por carta registada com aviso de receção',
            'l3_p1_a' => 'Apesar dos meus avisos anteriores de',
            'l3_p1_date1' => 'Data 1',
            'l3_p1_and' => 'e',
            'l3_p1_date2' => 'Data 2',
            'l3_p1_b' => ', a fatura',
            'l3_p1_c' => 'no valor de',
            'l3_p1_d' => ', acrescido dos juros moratórios, continua por liquidar.',
            'l3_p2_a' => 'Pela presente, notifico-o',
            'l3_p2_strong1' => 'uma última vez',
            'l3_p2_b' => 'a pagar o valor total de',
            'l3_p2_total' => 'Valor total com juros] EUR',
            'l3_p2_c' => 'no prazo de',
            'l3_p2_strong2' => '8 dias',
            'l3_p2_d' => 'a contar da receção desta carta.',
            'l3_p3' => 'Não havendo pagamento, transmitirei o processo sem outro pré-aviso:',
            'l3_li1' => 'A uma empresa de cobrança de dívidas',
            'l3_li2' => 'Ao tribunal competente do Grão-Ducado do Luxemburgo para procedimento judicial',
            'l3_li3' => 'Reclamando, além do montante principal: os juros legais, a indemnização forfetária de 40 EUR e os custos adicionais de cobrança',
            'l3_p4' => 'Espero que prefira evitar esta escalada regularizando a dívida no prazo indicado.',
            'cta' => '💡 Substitua os [placeholders] pelas suas informações. Com o faktur.lu, automatize os seus avisos: envio programado, acompanhamento de pagamentos, cálculo automático de juros. faktur.lu',
            'footer' => 'Modelo gratuito',
        ],
    ];
    $tr = $T[$lang] ?? $T['fr'];
@endphp

<div class="header">
    <h1>{{ $tr['title'] }}</h1>
    <p>{{ $tr['subtitle'] }}</p>
</div>

{{-- LEVEL 1 --}}
<h2>{{ $tr['l1_title'] }}</h2>
<div class="level-tag level-1">{{ $tr['l1_tag'] }}</div>
<div class="letter">
<p>{{ $tr['l1_greeting'] }} <span class="placeholder">[{{ $tr['l1_client'] }}]</span>,</p>
<p>{{ $tr['l1_p1_a'] }} <span class="placeholder">[{{ $tr['l1_invoice_no'] }}]</span> {{ $tr['l1_p1_b'] }} <span class="placeholder">[{{ $tr['l1_date'] }}]</span>{{ $tr['l1_p1_c'] }} <span class="placeholder">[{{ $tr['l1_amount'] }}</span>{{ $tr['l1_p1_d'] }} <span class="placeholder">[{{ $tr['l1_due'] }}]</span>{{ $tr['l1_p1_e'] }}</p>
<p>{{ $tr['l1_p2'] }}</p>
<p>{{ $tr['l1_p3'] }}</p>
<p>{{ $tr['l1_signoff'] }}<br><span class="placeholder">[{{ $tr['l1_yourname'] }}]</span></p>
</div>

{{-- LEVEL 2 --}}
<h2>{{ $tr['l2_title'] }}</h2>
<div class="level-tag level-2">{{ $tr['l2_tag'] }}</div>
<div class="letter">
<p>{{ $tr['l1_greeting'] }} <span class="placeholder">[{{ $tr['l1_client'] }}]</span>,</p>
<p>{{ $tr['l2_p1_a'] }} <span class="placeholder">[{{ $tr['l2_p1_first_date'] }}]</span>{{ $tr['l2_p1_b'] }} <span class="placeholder">[{{ $tr['l1_invoice_no'] }}]</span> {{ $tr['l2_p1_c'] }} <span class="placeholder">[{{ $tr['l1_amount'] }}</span> {{ $tr['l2_p1_d'] }} <span class="placeholder">[{{ $tr['l1_due'] }}]</span>{{ $tr['l2_p1_e'] }}</p>
<p>{{ $tr['l2_p2_a'] }} <strong>{{ $tr['l2_p2_strong'] }}</strong> {{ $tr['l2_p2_b'] }}</p>
<p>{{ $tr['l2_p3'] }}</p>
<p>{{ $tr['l2_p4'] }}</p>
<p>{{ $tr['l1_signoff'] }}<br><span class="placeholder">[{{ $tr['l1_yourname'] }}]</span></p>
</div>

{{-- LEVEL 3 --}}
<h2>{{ $tr['l3_title'] }}</h2>
<div class="level-tag level-3">{{ $tr['l3_tag'] }}</div>
<div class="letter">
<p><strong>{{ $tr['l3_intro'] }}</strong></p>
<p>{{ $tr['l1_greeting'] }} <span class="placeholder">[{{ $tr['l1_client'] }}]</span>,</p>
<p>{{ $tr['l3_p1_a'] }} <span class="placeholder">[{{ $tr['l3_p1_date1'] }}]</span> {{ $tr['l3_p1_and'] }} <span class="placeholder">[{{ $tr['l3_p1_date2'] }}]</span>{{ $tr['l3_p1_b'] }} <span class="placeholder">[{{ $tr['l1_invoice_no'] }}]</span> {{ $tr['l3_p1_c'] }} <span class="placeholder">[{{ $tr['l1_amount'] }}</span>{{ $tr['l3_p1_d'] }}</p>
<p>{{ $tr['l3_p2_a'] }} <strong>{{ $tr['l3_p2_strong1'] }}</strong> {{ $tr['l3_p2_b'] }} <span class="placeholder">[{{ $tr['l3_p2_total'] }}</span> {{ $tr['l3_p2_c'] }} <strong>{{ $tr['l3_p2_strong2'] }}</strong> {{ $tr['l3_p2_d'] }}</p>
<p>{{ $tr['l3_p3'] }}</p>
<ul>
    <li>{{ $tr['l3_li1'] }}</li>
    <li>{{ $tr['l3_li2'] }}</li>
    <li>{{ $tr['l3_li3'] }}</li>
</ul>
<p>{{ $tr['l3_p4'] }}</p>
<p>{{ $tr['l1_signoff'] }}<br><span class="placeholder">[{{ $tr['l1_yourname'] }}]</span></p>
</div>

<div style="margin-top: 1.5em; padding: 1em; background: #fef3c7; border-left: 3px solid #f59e0b; font-size: 9pt;">
    {{ $tr['cta'] }}
</div>

<div class="footer">
    <a href="https://{{ config('marque.domaine') }}" class="powered-by">{{ config('marque.nom') }}</a> - {{ $tr['footer'] }}
</div>
</body>
</html>
