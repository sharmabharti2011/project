@extends('layouts.app')
@section('content')
<section>
    <div class="card">
        <div class="card-header bg-info text-white">Settings</div>
        <form id="syncForm" action="{{ route('warehouse.dev.setting')}}" method="post">
            {{ csrf_field()}}
            <div class="card-body">
                <div class="form-group">
                    <div class="col-md-12">
                        <h4 class="lebel-msg">Connect your Shopify Store to Warehouse.Space</h4>
                        <p>Register at <a target="_blank" href="https://warehouse.space">https://warehouse.space</a> to get your license key and warehouse number.</p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="warehouse_number">Warehouse Number</label>
                        <input class="form-control{{ $errors->has('warehouse_number') ? ' is-invalid' : '' }}" type="text" value="{{ isset($users->get_dev_setting->warehouse_number)?$users->get_dev_setting->warehouse_number:'' }}" name="warehouse_number" placeholder="Warehouse Number">
                        @if ($errors->has('warehouse_number'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('warehouse_number') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="account_key">License Key</label>
                        <input class="form-control{{ $errors->has('account_key') ? ' is-invalid' : '' }}" type="text" value="{{ isset($users->get_dev_setting->account_key)?$users->get_dev_setting->account_key:'' }}" name="account_key" placeholder="Warehouse License Key">
                        @if ($errors->has('account_key'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('account_key') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>
                @if(isset($users->get_dev_setting))
                <!--                <div class="form-group">
                                    <div class="col-md-6">
                                        <button type="button" id="sync_product" class="btn btn-outline-success">Synchronize Products</button><br>
                                    </div>
                                </div>-->
                @endif
                <div class="form-group">
                    <div class="col-md-6">
                        <p>Pressing the Save button, will begin the process of syncing your products to your Warehouse.Space account.</p>
                        <p>If you have a large number of products, this process may take some time</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="col-md-6">
                    <button type="button" id="devSubmit" class="btn btn-outline-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
@push('scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $(document).on('click', '#devSubmit', function (e) {
            $(this).prop('disabled', true);
            $('#loaderOverlay').show();
            document.getElementById("syncForm").submit();
        });
        $(document).on('click', '#sync_product', function (e) {
            $(this).prop('disabled', true);
            var url = "{{ route('warehouse.product.sync') }}";
            window.location.href = url;
            $('#loaderOverlay').show();
        });
    });
</script>
@endpush
