@extends('admin/layouts.app')

@section('content')
<div class="row form-group">
    <div class="col-md-12">
        <!--<a href="" class="btn btn-primary">Add New</a>-->
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <table class="ui celled table" id="users-table">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created At</th>
                </tr>
                @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->created_at }}</td>
                    <td><a href="{{ route('customer.login',$user->id) }}" class="btn btn-outline-success">Login</a></td>
                </tr>
                @empty
                <tr>
                    <td><p>No records found !</p></td>
                </tr>
                @endforelse

            </thead>
        </table>
        <div class="pagination_main_wrapper">{{ $users->appends($_GET)->links() }}</div>

    </div>
</div>
@push('scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $('#users-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('users.index') }}",
            columns: [
                {data: 'id', name: 'id'},
                {data: 'name', name: 'name'},
                {data: 'email', name: 'email'},
                {data: 'status', name: 'status'},
                {data: 'created_at', name: 'created_at'}
            ]
        });

        $(document).on('click', '.status-toggle', function (e) {
            e.preventDefault(); // does not go through with the link.
            $(".alert-danger").remove();
            $(".alert-success").remove();
            var $this = $(this);
            $this.find('.btn').toggleClass('active');

            if ($this.find('.btn-primary').length > 0) {
                $this.find('.btn').toggleClass('btn-primary');
            }
            if ($this.find('.btn-default').length > 0) {
                $this.find('.btn').toggleClass('btn-default');
            }

            $.post({
                data: {'id': $this.data('id'), 'status': $this.find('.active').data('value')},
                url: $this.data('url')
            }).done(function (data) {
                var HTML = '<div class="alert alert-success">';
                HTML += '<a href="javascript:void(0);" onclick="$(this).parent().remove();" class="close" title="close">×</a>';
                HTML += '<strong>Success! </strong>' + data.messages + '</div>';
                $("#page-wrapper .container-fluid").prepend(HTML);
                $(window).scrollTop(0);
            }).fail(function (data) {
                var HTML = '<div class="alert alert-danger">';
                HTML += '<a href="javascript:void(0);" onclick="$(this).parent().remove();" class="close" title="close">×</a>';
                HTML += '<strong>Error! </strong>' + data.responseJSON.error + '</div>';
                $("#page-wrapper .container-fluid").prepend(HTML);
                $(window).scrollTop(0);
            });
        });
    });
</script>
@endpush
@endsection
