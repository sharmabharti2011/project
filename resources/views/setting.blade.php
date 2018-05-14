@extends('layouts.app')
@section('content')
<section>
    <!-- Nav tabs -->
    <div class="tabs-wrapper">
        <ul class="nav classic-tabs tabs-cyan" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#panel_dev" role="tab" aria-selected="false">Developer Setting</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#panel_api" role="tab" aria-selected="false">Api Setting</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#panel_sync" role="tab" aria-selected="false">Resynchronize Products</a>
            </li>
        </ul>
    </div>
    <!-- Tab panels -->
    <div class="tab-content card">


        <div class="tab-pane fade in show active" id="panel_dev" role="tabpanel">
            <form action="{{ route('warehouse.api.setting')}}" method="post">
                {{ csrf_field()}}
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="wsdl_url">WSDL URI</label>
                        <input class="form-control{{ $errors->has('wsdl_url') ? ' is-invalid' : '' }}" type="text" name="wsdl_url" placeholder="WSDL URI">
                        @if ($errors->has('wsdl_url'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('wsdl_url') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="percentage_product">Percent of synchronized products</label>
                        <input class="form-control{{ $errors->has('percentage_product') ? ' is-invalid' : '' }}" type="text" name="percentage_product" placeholder="Percent of synchronized products">
                        @if ($errors->has('percentage_product'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('percentage_product') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="page_size">Page Size</label>
                        <input class="form-control{{ $errors->has('page_size') ? ' is-invalid' : '' }}" type="text" name="page_size" placeholder="Page Size">
                        @if ($errors->has('page_size'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('page_size') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="Offset">Offset</label>
                        <input class="form-control{{ $errors->has('offset') ? ' is-invalid' : '' }}" type="text" name="offset" placeholder="Offset">
                        @if ($errors->has('offset'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('offset') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="save">
                </div>
            </form>
        </div>
        <div class="tab-pane fade" id="panel_api" role="tabpanel">
            <div class="form-group">
                <div class="col-md-6">
                    <label for="material_bulk">MaterialBulk enabled</label>
                    <select class="form-control" name="material_bulk">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="OrderStatus">ChangeOrderStatus enabled</label>
                    <select class="form-control" name="order_status">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="orderDetail">OrderDetail enabled</label>
                    <select class="form-control" name="order_detail">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="completedOrderItems">CompletedOrderItems enabled</label>
                    <select class="form-control" name="order_item_complete">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="deleteCompletedOrderItems">DeleteCompletedOrderItems enabled</label>
                    <select class="form-control" name="delete_order_item_complete">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="stockItems">StockItems enabled</label>
                    <select class="form-control" name="stock_item">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="deleteStockItems">DeleteStockItems enabled</label>
                    <select class="form-control" name="stock_item_delete">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="getShipmentRate">GetShipmentRate enabled</label>
                    <select class="form-control" name="ship_rate">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="shippingWarehouseOptions">ShippingWarehouseOptions enabled</label>
                    <select class="form-control" name="warehouse_option">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="getOrderTrackingInfo">getOrderTrackingInfo enabled</label>
                    <select class="form-control" name="track_order">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <label for="getStock">getStock enabled</label>
                    <select class="form-control" name="stock">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <buttom type="submit" class="btn btn-primary">save</button>
            </div>
        </div>

        <div class="tab-pane fade" id="panel_sync" role="tabpanel">
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nihil odit magnam minima, soluta doloribus
                reiciendis molestiae placeat unde eos molestias. Quisquam aperiam, pariatur. Tempora, placeat ratione
                porro voluptate odit minima.</p>
        </div>
    </div>
</section>
@endsection
@push('scripts')
@endpush
