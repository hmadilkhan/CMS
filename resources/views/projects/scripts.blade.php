@section("scripts")
<script type="text/javascript">
    $('.select2').select2();

    projectList("all");
    function projectList(value) {
        let search = $("#search").val();
        $.ajax({
            method: "POST",
            url: "{{ route('projects.list') }}",
            data : {"_token": "{{ csrf_token() }}",id:value,search:search},
            success: function(response) {
                $('#projectlist').empty();
                $('#projectlist').append(response);
            },
            error: function(error) {
                console.log(error.responseJSON.message);
            }
        })
    }

    $("#btnSearch").click(function(){
        projectList('all');
    })



    $("#openproject").click(function() {
        $("#createproject").modal("show");
        $.ajax({
            method: "GET",
            url: "{{ route('projects.create') }}",
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
        $("#createproject").modal("show");
        $.ajax({
            method: "GET",
            url: "{{ url('projects') }}" + "/" + id + "/edit",
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
                if (response.status == 200) {
                    $("#form")[0].reset();
                    $("#createproject").modal("hide");
                    projectList();
                }
            },
            error: function(error) {
                if (error) {
                    $.each(error.responseJSON.errors, function(index, value) {
                        $("#" + index + "_message").html(value[0])
                    });
                }
            }
        });
    });
</script>
@endsection