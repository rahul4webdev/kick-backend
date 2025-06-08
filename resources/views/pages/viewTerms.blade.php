@extends('admin_layouts/main')

@section('pageSpecificCss')
      <!-- include summernote css/js -->
      <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
@endsection

@section('content')
    <div class="card mt-3">
        <div class="card-header">
            <h4>{{ __('Terms Of Use') }}</h4>
        </div>
        <div class="card-body">

            <form Autocomplete="off" class="form-group form-border" action="" method="post" id="terms" required>
                @csrf

                <div class="form-group">
                    <label>{{ __('Content') }}</label>
                    <textarea id="summernote" class="summernote-simple" name="content">
        <?php
        echo $data;
        ?>

                    </textarea>

                </div>
                <div class="form-group">
                    <input @if (Session::get('admin_id') == 2) {{ 'disabled' }} @endif class="btn btn-primary mr-1" type="submit" value="Submit">
                </div>
            </form>
        </div>
    </div>
@endsection


@section('pageSpecificJs')

<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>

    <script src="{{ asset('assets/bundles/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/bundles/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/js/page/datatables.js') }}"></script>
    <script src="{{ asset('assets/bundles/izitoast/js/iziToast.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            let summernoteOptions = {
        height: 550,
    };
    $("#summernote").summernote(summernoteOptions);
            $("#terms").on("submit", function (event) {
        event.preventDefault();
        $(".loader").show();
            var formdata = new FormData($("#terms")[0]);
            $.ajax({
                url: '{{ route('updateTerms') }}',
                type: "POST",
                data: formdata,
                dataType: "json",
                contentType: false,
                cache: false,
                processData: false,
                success: function (response) {
                    // $(".loader").hide();
                    location.reload();
                },
                error: (error) => {
                    $(".loader").hide();
                    console.log(JSON.stringify(error));
                },
            });

    });

        });
    </script>

@endsection
