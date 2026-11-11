function get_filter(class_name) {
    var filter = [];
    $('.' + class_name + ':checked').each(function () {
        filter.push($(this).val());
    });
    return filter;
}

function addSubscriber() {

    var subscriber_email = $('#subscriber_email').val();
    var mailFormat = /\S+@\S+\.\S+/;
    if (subscriber_email.match(mailFormat)) {

    } else {
        alert("Please enter a valid Email!");
        return false;
    }

    $.ajax({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/add-subscriber-email',
        type: 'post',
        data: { subscriber_email: subscriber_email },
        success: function (resp) {

            if (resp == 'Email already exists') {
                alert('Your email already exists for Newsletter Subscription!');

            } else if (resp == 'Email saved in our database') {
                alert('Thanks for subscribing!');
            }
        },
        error: function () {
            alert('Error');
        }
    });
}

$(document).ready(function () {

    $('#getPrice').change(function () {

        var size = $(this).val();
        var product_id = $(this).attr('product-id');
        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/get-product-price',
            type: 'post',
            data: { size: size, product_id: product_id },
            success: function (resp) {
                if (resp.discount > 0) {
                    $('.getAttributePrice').html(
                        '<span class="old-price original-price">৳' + resp.product_price + '</span><span class="new-price price">৳' + resp.final_price + '</span>'
                    );
                } else {
                    $('.getAttributePrice').html(
                        '<span class="product-price price">৳' + resp.final_price + '</span>'
                    );
                }
            },
            error: function () {
                // alert('Error');
            }
        });
    });

    $(document).on('click', '.updateCartItem', function () {
        if ($(this).hasClass('plus-a')) {
            var quantity = $(this).data('qty');
            new_qty = parseInt(quantity) + 1;
        }

        if ($(this).hasClass('minus-a')) {
            var quantity = $(this).data('qty');


            if (quantity <= 1) {
                alert('Item quantity must be 1 or greater!');
                return false;
            }
            new_qty = parseInt(quantity) - 1;
        }

        var cartid = $(this).data('cartid');

        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { cartid: cartid, qty: new_qty },
            url: '/cart/update',
            type: 'post',
            success: function (resp) {
                $('.totalCartItems').html(resp.totalCartItems);
                if (resp.status == false) {
                    alert(resp.message);
                }
                $('#appendCartItems').html(resp.view);
                $('#appendHeaderCartItems').html(resp.headerview);
            },
            error: function () {
                // alert('Error');
            }
        });
    });

    $(document).on('click', '.deleteCartItem', function () {
        var cartid = $(this).data('cartid');

        var result = confirm('Are you sure you want to delete this Cart Item?');
        if (result) {
            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { cartid: cartid },
                url: '/cart/delete',
                type: 'post',
                success: function (resp) {
                    $('.totalCartItems').html(resp.totalCartItems);
                    $('#appendCartItems').html(resp.view);
                    $('#appendHeaderCartItems').html(resp.headerview);
                },
                error: function () {
                    alert('Error');
                }
            });
        }
    });

    $(document).on('click', '#placeOrder', function () {
        $('.loader').show();
    });

    $('#registerForm').submit(function () {
        $('.loader').show();
        var formdata = $(this).serialize();

        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/user/register',
            type: 'POST',
            data: formdata,
            success: function (resp) {
                if (resp.type == 'error') {

                    $('.loader').hide();
                    $.each(resp.errors, function (i, error) {
                        $('#register-' + i).attr('style', 'color: red');
                        $('#register-' + i).html(error);
                        setTimeout(function () {
                            $('#register-' + i).css({
                                'display': 'none'
                            });
                        }, 3000);
                    });

                } else if (resp.type == 'success') {

                    $('.loader').hide();

                    $('#register-success').attr('style', 'color: green');
                    $('#register-success').html(resp.message);
                }
            },
            error: function (resp) {
                console.log(resp);
            }
        });
    });

    $('#loginForm').submit(function () {
        var formdata = $(this).serialize();

        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/user/login',
            type: 'POST',
            data: formdata,
            success: function (resp) {
                if (resp.type == 'error') {
                    $.each(resp.errors, function (i, error) {

                        $('#login-' + i).attr('style', 'color: red');
                        $('#login-' + i).html(error);
                        setTimeout(function () {
                            $('#login-' + i).css({
                                'display': 'none'
                            });
                        }, 3000);

                    });

                } else if (resp.type == 'incorrect') {
                    $('#login-error').attr('style', 'color: red');
                    $('#login-error').html(resp.message);

                } else if (resp.type == 'inactive') {
                    $('#login-error').attr('style', 'color: red');
                    $('#login-error').html(resp.message);

                } else if (resp.type == 'success') {

                    window.location.href = resp.url;
                }
            },
            error: function () {
                alert('Error');
            }
        });
    });

    $('#forgotForm').submit(function () {
        // $('.loader').show();
        var formdata = $(this).serialize();
        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/user/forgot-password',
            type: 'POST',
            data: formdata,
            success: function (resp) {

                if (resp.type == 'error') {
                    // $('.loader').hide();
                    $.each(resp.errors, function (i, error) {

                        $('#forgot-' + i).attr('style', 'color: red');
                        $('#forgot-' + i).html(error);
                        setTimeout(function () {
                            $('#forgot-' + i).css({

                                'display': 'none'
                            });
                        }, 3000);

                    });

                } else if (resp.type == 'success') {

                    // $('.loader').hide();
                    $('#forgot-success').attr('style', 'color: green');
                    $('#forgot-success').html(resp.message);
                }
            },
            error: function () {
                alert('Error');
            }
        });
    });

    $('#accountForm').submit(function () {

        $('.loader').show();
        var formdata = $(this).serialize();
        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/user/account',
            type: 'POST',
            data: formdata,
            success: function (resp) {

                if (resp.type == 'error') {

                    $('.loader').hide();
                    $.each(resp.errors, function (i, error) {

                        $('#account-' + i).attr('style', 'color: red');
                        $('#account-' + i).html(error);
                        setTimeout(function () {
                            $('#account-' + i).css({
                                'display': 'none'
                            });
                        }, 3000);

                    });

                } else if (resp.type == 'success') {

                    $('.loader').hide();

                    $('#account-success').attr('style', 'color: green');
                    $('#account-success').html(resp.message);
                    setTimeout(function () {
                        $('#account-success').css({
                            'display': 'none'
                        });
                    }, 3000);
                }
            },
            error: function () {
                alert('Error');
            }
        });
    });

    $('#passwordForm').submit(function () {

        $('.loader').show();
        var formdata = $(this).serialize();

        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/user/update-password',
            type: 'POST',
            data: formdata,
            success: function (resp) {

                if (resp.type == 'error') {

                    $('.loader').hide();

                    $.each(resp.errors, function (i, error) {
                        $('#password-' + i).attr('style', 'color: red');
                        $('#password-' + i).html(error);

                        setTimeout(function () {
                            $('#password-' + i).css({
                                'display': 'none'
                            });
                        }, 3000);

                    });

                } else if (resp.type == 'incorrect') {

                    $('.loader').hide();

                    $('#password-error').attr('style', 'color: red');
                    $('#password-error').html(resp.message);

                    setTimeout(function () {
                        $('#password-error').css({
                            'display': 'none'
                        });
                    }, 3000);

                } else if (resp.type == 'success') {

                    $('.loader').hide();

                    $('#password-success').attr('style', 'color: green');
                    $('#password-success').html(resp.message);


                    setTimeout(function () {
                        $('#password-success').css({
                            'display': 'none'
                        });
                    }, 3000);
                }
            },
            error: function () {
                alert('Error');
            }
        });
    });

    $('#applyCoupon').submit(function () {
        var user = $(this).attr('user');

        if (user == 1) {

        } else {
            alert('Please login to apply Coupon!');
            return false;
        }

        var code = $('#code').val();
        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/apply-coupon',
            type: 'post',
            data: { code: code },
            success: function (resp) {

                if (resp.message != '') {
                    alert(resp.message);
                }

                $('.totalCartItems').html(resp.totalCartItems);
                $('#appendCartItems').html(resp.view);
                $('#appendHeaderCartItems').html(resp.headerview);


                if (resp.couponAmount > 0) {
                    $('.couponAmount').text('$' + resp.couponAmount);
                } else {
                    $('.couponAmount').text('$ 0');
                }

                if (resp.grand_total > 0) {
                    $('.grand_total').text('$' + resp.grand_total);
                }
            },
            error: function () {
                alert('Error');
            }
        });
    });

    $(document).on('click', '.editAddress', function () {
        var addressid = $(this).data('addressid');
        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/get-delivery-address',
            type: 'post',
            data: { addressid: addressid },
            success: function (resp) {

                $('#showdifferent').removeClass('collapse');
                $('.newAddress').hide();
                $('.deliveryText').text('Edit Delivery Address');

                $('[name="delivery_id"]').val(resp.address['id']);
                $('[name="delivery_name"]').val(resp.address['name']);
                $('[name="delivery_address"]').val(resp.address['address']);
                $('[name="delivery_city"]').val(resp.address['city']);
                $('[name="delivery_state"]').val(resp.address['state']);
                $('[name="delivery_country"]').val(resp.address['country']);
                $('[name="delivery_pincode"]').val(resp.address['pincode']);
                $('[name="delivery_mobile"]').val(resp.address['mobile']);
            },
            error: function () {
                alert('Error');
            }
        });
    });

    $(document).on('click', '.removeAddress', function () {
        if (confirm('Are you sure you want to remove this?')) {
            var addressid = $(this).data('addressid');

            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: '/remove-delivery-address',
                type: 'post',
                data: { addressid: addressid },
                success: function (resp) {

                    $('#deliveryAddresses').html(resp.view);

                    window.location.href = 'checkout';
                },
                error: function () {
                    alert('Error');
                }
            });
        }
    });

    $(document).on('submit', '#addressAddEditForm', function () {

        var formdata = $('#addressAddEditForm').serialize();
        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/save-delivery-address',
            type: 'post',
            data: formdata,
            success: function (resp) {

                if (resp.type == 'error') {

                    $('.loader').hide();

                    $.each(resp.errors, function (i, error) {
                        $('#delivery-' + i).attr('style', 'color: red');
                        $('#delivery-' + i).html(error);
                        setTimeout(function () {
                            $('#delivery-' + i).css({
                                'display': 'none'
                            });
                        }, 3000);
                    });
                } else {
                    $('#deliveryAddresses').html(resp.view);

                    window.location.href = 'checkout';
                }
            },
            error: function () {
                alert('Error');
            }
        });
    });

    $('input[name=address_id]').bind('change', function () {

        var shipping_charges = $(this).attr('shipping_charges');
        var total_price = $(this).attr('total_price');
        var coupon_amount = $(this).attr('coupon_amount');
        $('.shipping_charges').html('$' + shipping_charges);
        var codpincodeCount = $(this).attr('codpincodeCount');
        var prepaidpincodeCount = $(this).attr('prepaidpincodeCount');
        if (codpincodeCount > 0) {
            $('.codMethod').show();
        } else {
            $('.codMethod').hide();
        }

        if (prepaidpincodeCount > 0) {
            $('.prepaidMethod').show();
        } else {
            $('.prepaidMethod').hide();
        }

        if (coupon_amount == '') {
            coupon_amount = 0;
        }

        $('.couponAmount').html('$' + coupon_amount);

        var grand_total = parseInt(total_price) + parseInt(shipping_charges) - parseInt(coupon_amount);

        $('.grand_total').html('$' + grand_total);
    });

    $('#checkPincode').click(function () {

        var pincode = $('#pincode').val();
        if (pincode == '') {
            alert('Please enter Pincode');

            return false;
        }

        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '/check-pincode',
            type: 'post',
            data: { pincode: pincode },
            success: function (resp) {
                alert(resp);
            },
            error: function () {
                alert('Error');
            }
        });

    });

});