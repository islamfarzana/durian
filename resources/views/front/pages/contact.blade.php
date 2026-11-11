@extends('front.layout.layout')

@section('content')
    @include('front.layout.breadcrumbs', ['breadcrumbs' => [['label' => 'Contact us', 'url' => null]]])

    <div class="page-grid">
        <div class="card">
            <h1 style="font-size: 1.5rem">How can we help?</h1>
            <p class="muted">Questions about an order, a store, or selling with us — send a message and we'll reply by email.</p>
            @if ($errors->any())
                <div class="alert alert--error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="post" action="{{ route('contact') }}">
                @csrf
                <div class="field-row">
                    <div class="field">
                        <label for="contact-name">Your name</label>
                        <input class="input" id="contact-name" name="name" value="{{ old('name', $customer?->name) }}" required maxlength="100">
                    </div>
                    <div class="field">
                        <label for="contact-email">Email</label>
                        <input class="input" type="email" id="contact-email" name="email" value="{{ old('email', $customer?->email) }}" required maxlength="150">
                    </div>
                </div>
                <div class="field">
                    <label for="contact-subject">Subject</label>
                    <input class="input" id="contact-subject" name="subject" value="{{ old('subject') }}" required maxlength="200">
                </div>
                <div class="field">
                    <label for="contact-message">Message</label>
                    <textarea class="textarea" id="contact-message" name="message" required>{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="btn btn--primary">Send message</button>
            </form>
        </div>
        <aside>
            <div class="card">
                <h2 class="card__title">Other ways to reach us</h2>
                @if ($marketplace['hotline'] !== '')
                    <p><x-shop.icon name="phone" class="icon--sm" /> <a class="link" href="tel:{{ preg_replace('/[^0-9+]/', '', $marketplace['hotline']) }}">{{ $marketplace['hotline'] }}</a></p>
                @endif
                @if ($marketplace['email'] !== '')
                    <p><x-shop.icon name="mail" class="icon--sm" /> <a class="link" href="mailto:{{ $marketplace['email'] }}">{{ $marketplace['email'] }}</a></p>
                @endif
                <p class="small muted">For an existing order, include the order number. You can also cancel a cash-on-delivery order yourself from <a class="link" href="{{ route('orders') }}">My orders</a> until it ships.</p>
            </div>
        </aside>
    </div>
@endsection
