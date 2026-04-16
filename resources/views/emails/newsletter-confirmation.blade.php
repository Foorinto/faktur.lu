<x-mail::message>
@if($subscriber->locale === 'en')
# Confirm your subscription

Thank you for subscribing to the faktur.lu newsletter!

Click the button below to confirm your email address and start receiving our tips on invoicing and taxation in Luxembourg.

<x-mail::button :url="$confirmUrl" color="primary">
Confirm my subscription
</x-mail::button>

If you didn't subscribe, simply ignore this email.
@elseif($subscriber->locale === 'de')
# Bestätigen Sie Ihre Anmeldung

Vielen Dank für Ihre Anmeldung zum faktur.lu Newsletter!

Klicken Sie auf den Button, um Ihre E-Mail-Adresse zu bestätigen und unsere Tipps zur Rechnungsstellung und Besteuerung in Luxemburg zu erhalten.

<x-mail::button :url="$confirmUrl" color="primary">
Anmeldung bestätigen
</x-mail::button>

Falls Sie sich nicht angemeldet haben, ignorieren Sie diese E-Mail.
@elseif($subscriber->locale === 'lb')
# Bestätegt Är Umeldung

Merci fir Är Umeldung zum faktur.lu Newsletter!

Klickt op de Button fir Är E-Mail-Adress ze bestätegen an eis Tipps zur Rechnungsstellung a Besteierung zu Lëtzebuerg ze kréien.

<x-mail::button :url="$confirmUrl" color="primary">
Umeldung bestätegen
</x-mail::button>

Wann Dir Iech net ugemellt hutt, ignoréiert dës E-Mail.
@else
# Confirmez votre inscription

Merci de vous être inscrit à la newsletter faktur.lu !

Cliquez sur le bouton ci-dessous pour confirmer votre adresse email et commencer à recevoir nos conseils sur la facturation et la fiscalité au Luxembourg.

<x-mail::button :url="$confirmUrl" color="primary">
Confirmer mon inscription
</x-mail::button>

Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement cet email.
@endif

Cordialement,<br>
L'équipe faktur.lu
</x-mail::message>
