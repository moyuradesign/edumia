<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Confirm newsletter</title></head><body>
<p>Someone requested a subscription to the Edumia newsletter.</p>
<p>To receive newsletter updates, confirm your request within 48 hours:</p>
<p><a href="{{ $confirmationUrl }}">Confirm newsletter subscription</a></p>
<p>If you did not request this, you can ignore the message.</p>
<p>If you prefer to opt out now: <a href="{{ $unsubscribeUrl }}">Unsubscribe</a>.</p>
<p>Edumia — {{ config('app.name') }}</p>
</body></html>
