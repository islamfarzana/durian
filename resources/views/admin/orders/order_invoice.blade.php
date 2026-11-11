<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
<!------ Include the above in your HEAD tag ---------->

<div class="container">
    <div class="row">
        <div class="col-xs-12">
            <div class="invoice-title">
                <h2>Invoice</h2>
                <h3 class="pull-right">
                    Order # {{ $orderDetails['id'] }}


                    @php
                        echo DNS1D::getBarcodeHTML($orderDetails['id'], 'C39');

                    @endphp
                </h3>
            </div>
            <hr>
            <div class="row">
                <div class="col-xs-6">
                    <address>
                        <strong>Billed To:</strong><br>
                        {{ $userDetails['name'] }}<br>

                        @if (!empty($userDetails['address']))
                            {{ $userDetails['address'] }}<br>
                        @endif
                        @if (!empty($userDetails['city']))
                            {{ $userDetails['city'] }}<br>
                        @endif
                        @if (!empty($userDetails['state']))
                            {{ $userDetails['state'] }}<br>
                        @endif
                        @if (!empty($userDetails['country']))
                            {{ $userDetails['country'] }}<br>
                        @endif
                        @if (!empty($userDetails['pincode']))
                            {{ $userDetails['pincode'] }}<br>
                        @endif

                        {{ $userDetails['mobile'] }}<br>
                    </address>
                </div>
                <div class="col-xs-6 text-right">
                    <address>
                        <strong>Shipped To:</strong><br>
                        {{ $orderDetails['name'] }}<br>
                        {{ $orderDetails['address'] }}<br>
                        {{ $orderDetails['city'] }}, {{ $orderDetails['state'] }}<br>
                        {{ $orderDetails['country'] }}-{{ $orderDetails['pincode'] }}<br>
                        {{ $userDetails['mobile'] }}<br>
                    </address>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-6">
                    <address>
                        <strong>Payment Method:</strong><br>
                        {{ $orderDetails['payment_method'] }}
                    </address>
                </div>
                <div class="col-xs-6 text-right">
                    <address>
                        <strong>Order Date:</strong><br>
                        {{ date('Y-m-d h:i:s', strtotime($orderDetails['created_at'])) }}<br><br>
                    </address>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><strong>Order summary</strong></h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <td><strong>Product Code</strong></td>
                                    <td class="text-center"><strong>Size</strong></td>
                                    <td class="text-center"><strong>Color</strong></td>
                                    <td class="text-center"><strong>Price</strong></td>
                                    <td class="text-center"><strong>Quantity</strong></td>
                                    <td class="text-right"><strong>Totals</strong></td>
                                </tr>
                            </thead>
                            <tbody>

                                @php
                                    $subTotal = 0;
                                @endphp

                                @foreach ($orderDetails['orders_products'] as $product)
                                    <tr>
                                        <td>
                                            {{ $product['product_code'] }}

                                            @php
                                                echo DNS1D::getBarcodeHTML($product['product_code'], 'C39');
                                            @endphp
                                        </td>
                                        <td class="text-center">{{ $product['product_size'] }}</td>
                                        <td class="text-center">{{ $product['product_color'] }}</td>
                                        <td class="text-center">BDT {{ $product['product_price'] }}</td>
                                        <td class="text-center">{{ $product['product_qty'] }}</td>
                                        <td class="text-right">BDT
                                            {{ $product['product_price'] * $product['product_qty'] }}</td>
                                    </tr>

                                    @php
                                        $subTotal = $subTotal + $product['product_price'] * $product['product_qty'];
                                    @endphp
                                @endforeach

                                <tr>
                                    <td class="thick-line"></td>
                                    <td class="thick-line"></td>
                                    <td class="thick-line"></td>
                                    <td class="thick-line"></td>
                                    <td class="thick-line text-right"><strong>Subtotal</strong></td>
                                    <td class="thick-line text-right">BDT {{ $subTotal }}</td>
                                </tr>
                                <tr>
                                    <td class="no-line"></td>
                                    <td class="no-line"></td>
                                    <td class="no-line"></td>
                                    <td class="no-line"></td>
                                    <td class="no-line text-right"><strong>Delivery Charges</strong></td>
                                    <td class="no-line text-right">BDT {{ $orderDetails['shipping_charges'] }}</td>
                                </tr>
                                <tr>
                                    <td class="no-line"></td>
                                    <td class="no-line"></td>
                                    <td class="no-line"></td>
                                    <td class="no-line"></td>
                                    <td class="no-line text-right"><strong>Grand Total</strong></td>
                                    <td class="no-line text-right">
                                        <strong>BDT {{ $orderDetails['grand_total'] }}</strong>
                                        <br>

                                        @if ($orderDetails['payment_method'] == 'COD')
                                            <font color=red>(Already Paid)</font>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
