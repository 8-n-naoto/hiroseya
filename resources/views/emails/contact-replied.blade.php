{{ $contact->name }} 様

{{ $reply->body }}

──────────────────────────────
{{ $store->name }}
{{ $store->formattedPostalCode() }} {{ $store->fullAddress() }}
TEL {{ $store->tel }}
{{ url('/') }}
──────────────────────────────
