@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-9 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Marketplace settings</h4>
                            <p class="card-description">These apply to every store. Delivery rates per city and weight are under <a href="{{ url('admin/shipping-charges') }}">Shipping Charges</a>.</p>
                            @include('admin.layout.alerts')
                            <form class="forms-sample" method="post" action="{{ url('admin/marketplace-settings') }}">
                                @csrf
                                <h5 class="mt-2 mb-3">Storefront</h5>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="store_name">Marketplace name</label>
                                        <input class="form-control" id="store_name" name="store_name" maxlength="60" required value="{{ old('store_name', $settings['store_name']) }}">
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label for="tagline">Tagline</label>
                                        <input class="form-control" id="tagline" name="tagline" maxlength="160" value="{{ old('tagline', $settings['tagline']) }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="hotline">Hotline</label>
                                        <input class="form-control" id="hotline" name="hotline" maxlength="30" value="{{ old('hotline', $settings['hotline']) }}" placeholder="Shown in the footer">
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label for="support_email">Support email</label>
                                        <input type="email" class="form-control" id="support_email" name="support_email" maxlength="120" value="{{ old('support_email', $settings['support_email']) }}">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="currency_symbol">Currency symbol</label>
                                        <input class="form-control" id="currency_symbol" name="currency_symbol" maxlength="5" required value="{{ old('currency_symbol', $settings['currency_symbol']) }}">
                                    </div>
                                </div>

                                <h5 class="mt-3 mb-3">Delivery</h5>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="free_delivery_threshold">Free delivery from (0 = never free)</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="free_delivery_threshold" name="free_delivery_threshold" required value="{{ old('free_delivery_threshold', $settings['free_delivery_threshold']) }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="default_delivery_fee">Fee when no shipping rate matches</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="default_delivery_fee" name="default_delivery_fee" required value="{{ old('default_delivery_fee', $settings['default_delivery_fee']) }}">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="delivery_days_ahead">Days to choose from</label>
                                        <input type="number" min="1" max="14" class="form-control" id="delivery_days_ahead" name="delivery_days_ahead" required value="{{ old('delivery_days_ahead', $settings['delivery_days_ahead']) }}">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="slot_lead_minutes">Slot cut-off (min)</label>
                                        <input type="number" min="0" max="1440" class="form-control" id="slot_lead_minutes" name="slot_lead_minutes" required value="{{ old('slot_lead_minutes', $settings['slot_lead_minutes']) }}">
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label class="mb-0"><input type="checkbox" name="cod_enabled" value="1" @checked(old('cod_enabled', $settings['cod_enabled']) == '1')> &nbsp;Offer cash on delivery</label>
                                    </div>
                                </div>

                                <h5 class="mt-3 mb-3">Vendors</h5>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="min_payout_amount">Minimum payout request</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="min_payout_amount" name="min_payout_amount" required value="{{ old('min_payout_amount', $settings['min_payout_amount']) }}">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save settings</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layout.footer')
    </div>
@endsection
