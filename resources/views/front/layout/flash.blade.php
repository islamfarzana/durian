@if (Session::has('success_message'))
    <div class="alert alert--success" role="status"><x-shop.icon name="check" />{{ Session::get('success_message') }}</div>
@endif
@if (Session::has('error_message'))
    <div class="alert alert--error" role="alert"><x-shop.icon name="info" />{{ Session::get('error_message') }}</div>
@endif
