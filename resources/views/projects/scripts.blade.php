@section("scripts")
<script type="text/javascript">
    $('.select2').select2();

    // A Funding-Manager-only user has no Operational tab, so nothing to load.
    @unless (auth()->user()->isZoneOnlyUser())
    projectList("{{$selectedDepartment}}");
    @endunless
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

    // $("#btnSearch").click(function(){
    //     projectList('all');
    // })
    
    let projectSearchTimer;
    $("#search").on("input", function() {
        clearTimeout(projectSearchTimer);
        projectSearchTimer = setTimeout(function() {
            projectList('all');
        }, 250);
    });



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

    @can('View Zones')
    /* ------------------------------------------------------------ Zones tab
       The board is a fragment of its own: it is fetched the first time the tab
       is opened, and re-fetched whenever a filter changes or a project moves.
       Its search box, department filter and Archived toggle are inside the
       fragment, so every handler here is delegated. */
    let zoneBoardLoaded = false;
    let zoneBoardSearchTimer;
    // The fragment is replaced on every fetch, so the filters live out here
    // rather than being read back off the markup.
    const zoneBoard = { search: "", department: "all", archived: false };

    function loadZoneBoard(keepSearchFocus) {
        const container = $("#zoneBoardContainer");

        if (!container.length) {
            return;
        }

        zoneBoardLoaded = true;

        $.ajax({
            method: "GET",
            url: "{{ route('zones.board') }}",
            data: {
                search: zoneBoard.search,
                department: zoneBoard.department,
                archived: zoneBoard.archived ? 1 : 0,
            },
            success: function(response) {
                container.html(response);

                // Typing re-renders the board, which would otherwise steal the
                // caret out of the search box mid-word.
                if (keepSearchFocus) {
                    const input = document.getElementById("zoneBoardSearch");

                    if (input) {
                        input.focus();
                        input.setSelectionRange(input.value.length, input.value.length);
                    }
                }
            },
            error: function(error) {
                container.html('<div class="alert alert-danger">The zones board could not be loaded.</div>');
                console.log(error);
            }
        });
    }

    $(document).on("shown.bs.tab", "#workspace-tab-zones", function() {
        if (!zoneBoardLoaded) {
            loadZoneBoard();
        }
    });

    $(document).on("input", "#zoneBoardSearch", function() {
        zoneBoard.search = $(this).val();
        clearTimeout(zoneBoardSearchTimer);
        zoneBoardSearchTimer = setTimeout(function() {
            loadZoneBoard(true);
        }, 300);
    });

    $(document).on("click", "[data-zone-department]", function() {
        zoneBoard.department = String($(this).data("zone-department"));
        loadZoneBoard();
    });

    $(document).on("click", "[data-zone-board-archived]", function() {
        zoneBoard.archived = $(this).data("zone-board-archived") == 1;
        loadZoneBoard();
    });

    // Fired by the zone move modal once a move has been written.
    document.addEventListener("zone:moved", function() {
        loadZoneBoard();
    });

    // A page opened straight on the Zones tab loads it immediately.
    $(function() {
        if ($("#workspace-tab-zones").hasClass("active")) {
            loadZoneBoard();
        }
    });
    @endcan
</script>
@endsection
