@extends('layouts.app')
@section('content')
<section>
    <style>
        .main-container { position: relative;min-height: 700px; }
        .row img.logo { max-width: 100px; }
        .invoice p { margin: 0 0 4px; }
        .panel { margin-bottom: 16px;background-color: #fff;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;-webkit-box-shadow: none;box-shadow: none;border: 1px solid #c9d9ea; }
        .panel-body { padding: 15px;background: #fff;-webkit-border-radius: 0 0 2px 2px;-moz-border-radius: 0 0 2px 2px; }
        .order-item { border-top-left-radius: .25rem;border-top-right-radius: .25rem;padding: .75rem 1.25rem;margin-bottom: -1px;background-color: #fff;border: 1px solid rgba(0,0,0,.125); }
        .order-item .item-info { display: inline-block;vertical-align: middle;max-width: 70%; }
    </style>
    <div class="main-container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="invoice">
                    <div class="panel no-margin">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-lg-3 col-md-3 col-sm-4 col-xs-12">
                                    <a href="{{ route('dashboard',$slug) }}"><img class="logo" src="{{ asset('/images/WSLogo.png') }}" alt="logo"></a>
                                </div>
                                <div class="col-md-9 col-sm-8 col-xs-12">
                                    <div class="float-right">
                                        <p><b>Invoice ID</b> - {{ $order_details->order_id }}</p>
                                        <p><b>Order date</b> - {{ $order_details->order_date }}</p>
                                        <p><b>Payment status</b><span class="label label-danger"> - {{ ucfirst($order_details->payment_status) }}</span></p>
                                        <p><b>Order status</b><span class="label label-danger"> - {{ $order_details->order_status }}</span></p>
                                    </div>
                                </div>
                            </div>
                            <hr><br>
                            <div class="row">
                                <div class="col-md-12">
                                    @foreach($order_details->items as $key=>$value)
                                    <div class="order-item">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <!--<img src="{{ asset('/images/WSLogo.png') }}" alt="product-image">-->
                                                <div class="item-info">
                                                    <p><a target="_blank" href="{{ $value->product_link }}">{{ $value->product_name }}</a></p>
                                                    <span><p>{{ $value->variant_title }}</p></span>
                                                    <span><p>{{ $value->sku }}</p></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="">
                                                    <p><b>Shipper</b> - {{ $value->Shipper }}</p>
                                                    <p><b>Packer Name</b> - {{ $value->PackerName }}</p>
                                                    <p><b>Packed At</b> - {{ $value->PackingEndTime }}</p>
                                                    <p><b>Dispached At</b> - {{ $value->DispatchTime }}</p>
                                                    <p><b>Tracking Number</b> - {{ $value->TrackingNumber }}</p>
                                                    @if($value->TrackingUrl)
                                                    <p><b>Tracking Url</b> - <a href="{{ $value->TrackingUrl }}" target="_blank">{{ $value->TrackingNumber }}</a></p>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                @if($value->YoutubeUrl !='')
                                                <!--https://www.youtube.com/embed/ddzU-rkzKF0-->
                                                <iframe width="200" height="140" src="{{ $value->YoutubeUrl }}" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen="0"></iframe><br>
                                                <a target="__blank" href="{{ $value->YoutubeUrl }}">View full screen</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div><br>
                                    @endforeach
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <strong>Note:</strong>  For delivery related issues please contact the shipping company with the tracking number. Many shipping companies provide an online track and track facility on their web sites.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>  
    </div>
</section>
@endsection
