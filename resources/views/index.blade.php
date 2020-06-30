@extends('layouts.app')
@section('content')
<section>
    <div class="card">
        <div class="card-header bg-info text-white">Installation Instructions</div>
        <div class="card-body">
            <div class="instruction-content">
                <p>Ship your products faster and cheaper by storing them closer to your customers.</p>
                <p>Use Warehouse.Space distribution centers to reduce your shipping costs. We pick, pack and dispatch your products to your customers with same day delivery in a number of major cities, and next day in many more cities.</p>
                <p>Sign up at <a target="_blank" href="https://warehouse.space">Warehouse.Space</a> for free, and get your license key and warehouse number and start shipping to customers world wide at local postal rates.</p>
                <p>Warehouse.Space - Your international order fulfillment partner.</p>
                <p>Use our warehouses in Europe, Asia, Oceania and America to reduce your shipping costs.</p>
                <p>Enter your license key and warehouse number at <a href="{{ route('warehouse.setting',$slug) }}">settings</a> to connect your Shopify store to Warehouse.Space.</p>
                <!--<p>We pick, pack and dispatch products with next day delivery.</p>-->
            </div>
        </div>
    </div><br>
    <div class="card">
        <div class="card-header bg-info text-white">Requirements</div>
        <div class="card-body">
            <div class="instruction-content">
                <div class="row">
                    <div class="col-md-6">
                        <img style="width: 100%;" src="{{ asset('/images/barcode.png') }}">
                    </div>
                    <div class="col-md-6">
                        <p>To help us pick, pack and dispatch the correct products to your customers we need your help</p>
                        <p>Every product in our warehouse needs a barcode. If a product doesn’t have a barcode when you ship it to one of our warehouses, then we will add a barcode to the product packaging, which means you will incur a small fee for us doing this.</p>
                        <p>This barcode is what we scan, so that we can confirm we have  correctly picked the product your customer has ordered.</p>
                        <p>To help us with this process we need you to ensure that every product you have in your store has a barcode value defined, that matches the barcode on the product. The barcode must be unique per product, including variants.</p>
                        <p>We are unable to receive a product into our warehouse that you have not defined its barcode value in Shopify.</p>
                        <p>Thanks for your assistance in helping us ship your products perfectly every time.</p>
                    </div>
                </div>
            </div>
            <!--<div class="card-footer">Support:-</div>-->
        </div>
</section>
@endsection
@push('scripts')
@endpush
