<!DOCTYPE html>
<html lang="en">
@section("title","Track Your Project")
<head>
    @include("layouts.website.header")
</head>

<body>

    <div class="page-wrapper">
        <!-- Preloader -->
        <div class="preloader"></div>

        <!-- Main Header-->
        @include("layouts.website.main_header")
        <!--End Main Header -->

        <!-- Start main-content -->
        <section class="page-title" style="background-image: url(website/images/background/page-title-bg.png);">
            <div class="auto-container">
                <div class="title-outer text-center">
                    <h1 class="title">Track Your Project</h1>
                    <ul class="page-breadcrumb">
                        <li><a href="{{url('/')}}">Home</a></li>
                        <li>Track Your Project</li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- end main-content -->

        <!--Contact Details Start-->
        <section class="contact-details">
            <div class="container ">
                <div class="row">
                    <div class="col-xl-7 col-lg-6">
                        <div class="sec-title">
                            <span class="sub-title">Track Your Project </span>
                            <h2>Check your project progress</h2>
                        </div>
                        <!-- Contact Form -->
                        <form id="contact_form" name="contact_form" class="" action="includes/sendmail.php" method="post">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <input name="job_id" class="form-control" type="text" placeholder="Enter Job Id">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <input name="form_email" class="form-control required email" type="email" placeholder="Enter Email">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-5">
                                <input name="form_botcheck" class="form-control" type="hidden" value="" />
                                <button type="submit" class="theme-btn btn-style-one mb-3 mb-sm-0" data-loading-text="Please wait..."><span class="btn-title">Fetch Details</span></button>
                                <button type="reset" class="theme-btn btn-style-one bg-theme-color5"><span class="btn-title">Reset</span></button>
                            </div>
                        </form>
                        <!-- Contact Form Validation-->
                    </div>
                </div>
            </div>
        </section>
        <!--Contact Details End-->

        <!-- Main Footer -->
        @include("layouts.website.footer_bottom")
        <!--End Main Footer -->
    </div><!-- End Page Wrapper -->
    <!-- Scroll To Top -->
    @include("layouts.website.scripts")
    <!-- form submit -->
    <script src="{{asset('website/js/jquery.validate.min.js')}}"></script>
    <script src="{{asset('website/js/jquery.form.min.js')}}"></script>
    <script>
        (function($) {
            $("#contact_form").validate({
                submitHandler: function(form) {
                    var form_btn = $(form).find('button[type="submit"]');
                    var form_result_div = '#form-result';
                    $(form_result_div).remove();
                    form_btn.before('<div id="form-result" class="alert alert-success" role="alert" style="display: none;"></div>');
                    var form_btn_old_msg = form_btn.html();
                    form_btn.html(form_btn.prop('disabled', true).data("loading-text"));
                    $(form).ajaxSubmit({
                        dataType: 'json',
                        success: function(data) {
                            if (data.status == 'true') {
                                $(form).find('.form-control').val('');
                            }
                            form_btn.prop('disabled', false).html(form_btn_old_msg);
                            $(form_result_div).html(data.message).fadeIn('slow');
                            setTimeout(function() {
                                $(form_result_div).fadeOut('slow')
                            }, 6000);
                        }
                    });
                }
            });
        })(jQuery);
    </script>
</body>

</html>