@extends('front.layout.layout')

@section('content')
    <div class="card" style="max-width: 460px; margin: 16px auto">
        <h1 style="font-size: 1.4rem">Reset your password</h1>
        <p class="muted">Enter the email on your account and we'll send you a new password.</p>
        <form method="post" action="{{ route('password.forgot') }}" data-json-form data-reset-on-success>
            @csrf
            <div data-form-message hidden></div>
            <div class="field">
                <label for="forgot-email">Email</label>
                <input class="input" type="email" id="forgot-email" name="email" required autocomplete="email">
                <div class="field-error" data-error-for="email"></div>
            </div>
            <button type="submit" class="btn btn--primary btn--block">Send new password</button>
        </form>
        <p class="small" style="margin: 14px 0 0; text-align: center"><a class="link" href="{{ route('login') }}">Back to log in</a></p>
    </div>
@endsection
