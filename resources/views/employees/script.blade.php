@section('scripts')
    <script type="text/javascript">
        $('.select2').select2();
        $("#openemployee").click(function() {
            $("#createemp").modal("show");
            $.ajax({
                method: "GET",
                url: "{{ route('employees.create') }}",
                success: function(response) {
                    $('#empform').empty();
                    $('#empform').append(response);
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        });

        function edit(id) {
            $("#createemp").modal("show");
            $.ajax({
                method: "GET",
                url: "{{ url('employees') }}" + "/" + id + "/edit",
                success: function(response) {
                    $('#empform').empty();
                    $('#empform').append(response);
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }

        function SubmitForm() {
            $("#btnFormSubmit").click();
        }

        $(document).on('submit', '#form', function(e) {
            e.preventDefault();

            let assignRoute = $("#route").val();
            let userId = $("#user_id").val();
            let method = (userId == "" ? "POST" : "PUT");

            // Empty all messages before sending requests again
            $("div.message").html('');

            $.ajax({
                url: assignRoute,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                method: "POST",
                data: new FormData(this),
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    // console.log(response)
                    if (response.status == 200) {
                        $("#form")[0].reset();
                        $("#createemp").modal("hide");
                        location.reload();
                    }
                },
                error: function(error) {
                    console.log(error)
                    if (error) {
                        $.each(error.responseJSON.errors, function(index, value) {
                            $("#" + index + "_message").html(value[0])
                        });
                    }
                }
            });
        });

        function deleteEmployee(id) {
            $("#deleteId").val(id);
            $("#deleteproject").modal("show")
        }

        function deleteEmployeeCall() {
            let deleteId = $("#deleteId").val();
            $.ajax({
                method: "DELETE",
                url: "{{ url('/employees') }}" + "/" + deleteId,
                data: {
                    _token: "{{ csrf_token() }}",
                    id: deleteId
                },
                success: function(response) {
                    if (response.status == 200) {
                        location.reload();
                    }
                }
            });
        }
    </script>
@endsection
