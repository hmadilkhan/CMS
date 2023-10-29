<!DOCTYPE html>
<html lang="en">
@section("title","Contact")
<head>
    @include("layouts.website.header")
</head>

<body>

    <div class="page-wrapper">
        <!-- Preloader -->
        <div class="preloader"></div>

        <!-- Main Header-->
        <header class="main-header  header-style-two">
            <!-- Header Top -->
            <div class="header-top">

            </div>
            <!-- Header Top -->

            <!-- Header Lower -->
            <div class="header-lower">
                <!-- Main box -->
                <div class="main-box">
                    <div class="logo-box">
                        <div class="logo"><a href="index.html"><img src="{{asset('website/images/logo-2.png')}}" alt="" title="Tronis"></a></div>
                    </div>

                    <!--Nav Box-->
                    <div class="nav-outer">
                        @include("layouts.website.navbar")
                        <!-- Main Menu End-->

                        <div class="outer-box">
                            <a href="page-contact.html" class="theme-btn btn-style-one alternate"><span class="btn-title">Get A Quote</span></a>

                            <!-- Mobile Nav toggler -->
                            <div class="mobile-nav-toggler"><span class="icon lnr-icon-bars"></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Header Lower -->

            <!-- Mobile Menu  -->
            <div class="mobile-menu">
                <div class="menu-backdrop"></div>

                <!--Here Menu Will Come Automatically Via Javascript / Same Menu as in Header-->
                <nav class="menu-box">
                    <div class="upper-box">
                        <div class="nav-logo">
                            <a href="index.html"><img src="{{asset('website/images/logo-2.png')}}" alt="" title="Fesho" /></a>
                        </div>
                        <div class="close-btn"><i class="icon fa fa-times"></i></div>
                    </div>

                    <ul class="navigation clearfix">
                        <!--Keep This Empty / Menu will come through Javascript-->
                    </ul>
                    <ul class="contact-list-one">
                        <li>
                            <!-- Contact Info Box -->
                            <div class="contact-info-box">
                                <i class="icon lnr-icon-phone-handset"></i>
                                <span class="title">Call Now</span>
                                <a href="tel:+92880098670">+92 (8800) - 98670</a>
                            </div>
                        </li>
                        <li>
                            <!-- Contact Info Box -->
                            <div class="contact-info-box">
                                <span class="icon lnr-icon-envelope1"></span>
                                <span class="title">Send Email</span>
                                <a href="mailto:help@company.com">help@company.com</a>
                            </div>
                        </li>
                        <li>
                            <!-- Contact Info Box -->
                            <div class="contact-info-box">
                                <span class="icon lnr-icon-clock"></span>
                                <span class="title">Send Email</span>
                                Mon - Sat 8:00 - 6:30, Sunday - CLOSED
                            </div>
                        </li>
                    </ul>

                    <ul class="social-links">
                        <li>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="fab fa-pinterest"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>
            <!-- End Mobile Menu -->

            <!-- Header Search -->
            <div class="search-popup">
                <span class="search-back-drop"></span>
                <button class="close-search"><span class="fa fa-times"></span></button>

                <div class="search-inner">
                    <form method="post" action="index.html">
                        <div class="form-group">
                            <input type="search" name="search-field" value="" placeholder="Search..." required="" />
                            <button type="submit"><i class="fa fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- End Header Search -->

            <!-- Sticky Header  -->
            <div class="sticky-header">
                <div class="auto-container">
                    <div class="inner-container">
                        <!--Logo-->
                        <div class="logo">
                            <a href="index.html" title=""><img src="{{asset('website/images/logo.png')}}" alt="" title="" /></a>
                        </div>

                        <!--Right Col-->
                        <div class="nav-outer">
                            <!-- Main Menu -->
                            <nav class="main-menu">
                                <div class="navbar-collapse show collapse clearfix">
                                    <ul class="navigation clearfix">
                                        <!--Keep This Empty / Menu will come through Javascript-->
                                    </ul>
                                </div>
                            </nav>
                            <!-- Main Menu End-->

                            <!--Mobile Navigation Toggler-->
                            <div class="mobile-nav-toggler"><span class="icon lnr-icon-bars"></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Sticky Menu -->
        </header>
        <!--End Main Header -->

        <!-- Start main-content -->
        <section class="page-title" style="background-image: url(website/images/background/page-title-bg.png);">
            <div class="auto-container">
                <div class="title-outer text-center">
                    <h1 class="title">Contact Us</h1>
                    <ul class="page-breadcrumb">
                        <li><a href="index.html">Home</a></li>
                        <li>Contact</li>
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
                            <span class="sub-title">Send us email</span>
                            <h2>Feel free to write</h2>
                        </div>
                        <!-- Contact Form -->
                        <form id="contact_form" name="contact_form" class="" action="includes/sendmail.php" method="post">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <input name="form_name" class="form-control" type="text" placeholder="Enter Name">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <input name="form_email" class="form-control required email" type="email" placeholder="Enter Email">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <input name="form_subject" class="form-control required" type="text" placeholder="Enter Subject">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <input name="form_phone" class="form-control" type="text" placeholder="Enter Phone">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <textarea name="form_message" class="form-control required" rows="7" placeholder="Enter Message"></textarea>
                            </div>
                            <div class="mb-5">
                                <input name="form_botcheck" class="form-control" type="hidden" value="" />
                                <button type="submit" class="theme-btn btn-style-one mb-3 mb-sm-0" data-loading-text="Please wait..."><span class="btn-title">Send message</span></button>
                                <button type="reset" class="theme-btn btn-style-one bg-theme-color5"><span class="btn-title">Reset</span></button>
                            </div>
                        </form>
                        <!-- Contact Form Validation-->
                    </div>
                    <div class="col-xl-5 col-lg-6">
                        <div class="contact-details__right">
                            <div class="sec-title">
                                <span class="sub-title">Need any help?</span>
                                <h2>Get in touch with us</h2>
                                <div class="text">Lorem ipsum is simply free text available dolor sit amet consectetur notted adipisicing elit sed do eiusmod tempor incididunt simply dolore magna.</div>
                            </div>
                            <ul class="list-unstyled contact-details__info">
                                <li class="d-block d-sm-flex align-items-sm-center ">
                                    <div class="icon bg-theme-color2">
                                        <span class="lnr-icon-phone-plus"></span>
                                    </div>
                                    <div class="text ml-xs--0 mt-xs-10">
                                        <h6>Have any question?</h6>
                                        <a href="tel:980089850"><span>Free</span> +92 (020)-9850</a>
                                    </div>
                                </li>
                                <li class="d-block d-sm-flex align-items-sm-center ">
                                    <div class="icon">
                                        <span class="lnr-icon-envelope1"></span>
                                    </div>
                                    <div class="text ml-xs--0 mt-xs-10">
                                        <h6>Write email</h6>
                                        <a href="mailto:needhelp@company.com">needhelp@company.com</a>
                                    </div>
                                </li>
                                <li class="d-block d-sm-flex align-items-sm-center ">
                                    <div class="icon">
                                        <span class="lnr-icon-location"></span>
                                    </div>
                                    <div class="text ml-xs--0 mt-xs-10">
                                        <h6>Visit anytime</h6>
                                        <span>66 broklyn golden street. New York</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--Contact Details End-->

        <!-- Map Section-->
        <section class="map-section">
            <iframe class="map w-100" src="https://maps.google.com/maps?width=100%25&amp;height=600&amp;hl=en&amp;q=1%20Grafton%20Street,%20Dublin,%20Ireland+(My%20Business%20Name)&amp;t=&amp;z=14&amp;ie=UTF8&amp;iwloc=B&amp;output=embed"></iframe>
        </section>
        <!--End Map Section-->

        <!-- Main Footer -->
        <footer class="main-footer style-one pt-0">
            <div class="bg-image" style="background-image: url(./website/images/background/5.jpg)"></div>
            <!--Widgets Section-->
            <div class="widgets-section">
                <div class="auto-container">
                    <div class="row">
                        <!--Footer Column-->
                        <div class="footer-column col-xl-3 col-sm-6">
                            <div class="footer-widget about-widget">
                                <div class="logo">
                                    <a href="index.html"><img src="{{asset('website/images/logo-2.png')}}" alt="" /></a>
                                </div>
                                <p class="text mb-2">012 Broklyn Street, 57 <br class="d-none d-xl-block"> New York, USA</p>
                                <p class="mb-2"><a class="text" href="mailto:needhelp@domain.com">needhelp@domain.com</a></p>
                                <p><a class="text-white" href="tel:9993330000">999 333 0000</a></p>
                            </div>
                        </div>
                        <!--Footer Column-->
                        <div class="footer-column col-xl-3 col-sm-6">
                            <div class="footer-widget">
                                <h3 class="widget-title">Service</h3>
                                <ul class="user-links">
                                    <li><a href="#">Reliability & Punctuality</a></li>
                                    <li><a href="#">Trusted Franchise</a></li>
                                    <li><a href="#">Warehoues Storage</a></li>
                                    <li><a href="#">Real Time Tracking</a></li>
                                    <li><a href="#">Transparent Pricing</a></li>
                                </ul>
                            </div>
                        </div>
                        <!--Footer Column-->
                        <div class="footer-column col-xl-3 col-sm-6">
                            <div class="footer-widget gallery-widget">
                                <h3 class="widget-title">Projects</h3>
                                <ul class="user-links two-column">
                                    <li><a href="#">About</a></li>
                                    <li><a href="#">New Projects</a></li>
                                    <li><a href="#">Our History</a></li>
                                    <li><a href="#">Contact</a></li>
                                    <li><a href="#">Blog Post</a></li>
                                    <li><a href="#">Press Release</a></li>
                                    <li><a href="#">Help Topics</a></li>
                                    <li><a href="#">Privacy Policy</a></li>
                                    <li><a href="#">Terms Of Use</a></li>
                                </ul>
                            </div>
                        </div>
                        <!--Footer Column-->
                        <div class="footer-column col-xl-3 col-sm-6">
                            <div class="footer-widget">
                                <h3 class="widget-title">Newsletter</h3>
                                <div class="widget-content">
                                    <div class="subscribe-form">
                                        <div class="text">Subscribe our newsletter to get our latest update & news</div>
                                        <form method="post" action="#">
                                            <div class="form-group">
                                                <input type="email" name="email" class="email" value="" placeholder="Email Address" required="" />
                                                <button type="button" class="theme-btn btn-style-one">
                                                    <span class="btn-title"><i class="fa fa-paper-plane"></i></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--Footer Bottom-->
            <div class="footer-bottom">
                <div class="auto-container">
                    <div class="inner-container">
                        <div class="copyright-text">
                            <p>&copy; Copyright 2023 by <a href="{{url('/')}}">Company.com</a></p>
                        </div>
                        <ul class="social-icon-two">
                            <li>
                                <a href="#"><i class="fab fa-facebook"></i></a>
                            </li>
                            <li>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </li>
                            <li>
                                <a href="#"><i class="fab fa-pinterest"></i></a>
                            </li>
                            <li>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
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