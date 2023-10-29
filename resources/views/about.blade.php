<!DOCTYPE html>
<html lang="en">

<head>
    @include("layouts.website.header")
</head>

<body>
    <div class="page-wrapper">
        <!-- Preloader -->
        <div class="preloader"></div>

        <!-- Main Header-->
        <header class="main-header header-style-one">
            <!-- Header Top -->
            <div class="header-top">
                
            </div>
            <!-- Header Top -->

            <!-- Header Lower -->
            <div class="header-lower">
                <!-- Main box -->
                <div class="main-box">
                    <div class="logo-box">
                        <div class="logo">
                            <a href="index.html"><img src="{{asset('website/images/logo-2.png')}}" alt="" title="Tronis" /></a>
                        </div>
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
                            <a href="{{url('/')}}"><img src="{{asset('website/images/logo-2.png')}}" alt="" title="Fesho" /></a>
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
        <section class="page-title" style="background-image: url(images/background/page-title-bg.png);">
            <div class="auto-container">
                <div class="title-outer text-center">
                    <h1 class="title">About Us</h1>
                    <ul class="page-breadcrumb">
                        <li><a href="{{url('/')}}">Home</a></li>
                        <li>About Us</li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- end main-content -->

        <!-- About Section Two / Style Two -->
        <section class="about-section-two mt-lg-5">
            <figure class="floating-home2-about"><img src="{{asset('website/images/icons/layout1-left.png')}}" alt=""></figure>
            <div class="auto-container">
                <div class="row">
                    <div class="content-column col-lg-6 col-md-12 col-sm-12 order-2 wow fadeInRight" data-wow-delay="600ms">
                        <div class="inner-column ps-lg-5">
                            <div class="sec-title mb-30">
                                <span class="sub-title">Get To Know Us</span>
                                <h2>Providing Renewable Energy Solutions</h2>
                                <div class="text">Geothermal energy is a clean and reliable source of renewable energy that uses heat from the earth’s core to generate electricity.</div>
                            </div>
                            <ul class="list-style-two">
                                <li><i class="fa fa-check-circle"></i> Solutions can help reduce the risk of oil spills</li>
                                <li><i class="fa fa-check-circle"></i> Help reduce the impact of climate change</li>
                            </ul>
                            <div class="d-sm-flex align-items-sm-center justify-content-sm-between mt-20">
                                <div class="home2-support-1">
                                    <h5 class="title">24/7 Support</h5>
                                    <p class="text mb-30">24/7 support refers to customer service or technical assistance</p>
                                    <a href="page-about.html" class="theme-btn btn-style-one hvr-light"><span class="btn-title">Explore Now</span></a>
                                </div>
                                <div class="icon-box1-home2 mt-4 mt-sm-0">
                                    <i class="icon flaticon-quality"></i>
                                    <h6 class="title">We’re Certified Solar Experts</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Column -->
                    <div class="col-lg-6 col-md-12 col-sm-12">
                        <div class="home2-about1-img-col">
                            <figure class="image-1"><img src="{{asset('website/images/resource/about-1.jpg')}}" alt=""></figure>
                            <figure class="image-2 bounce-y d-none d-sm-block"><img src="{{asset('website/images/resource/about-3.jpg')}}" alt=""></figure>
                            <figure class="image-3"><img src="{{asset('website/images/resource/favicon_big.png')}}" alt=""></figure>
                            <figure class="image-4 bounce-y"><img src="{{asset('website/images/icons/dot.png')}}" alt=""></figure>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--Emd About Section Two -->

        <!-- Services Section -->
        <section class="services-section-home1 pb-lg-0">
            <div class="auto-container">
                <div class="sec-title text-center">
                    <span class="sub-title">SPECIALISE IN THE ENERGY SERVICE</span>
                    <h2>Sustainable Energy Services</h2>
                </div>
                <div class="row">
                    <!-- Service Block -->
                    <div class="service-block col-lg-3 col-sm-6 wow fadeInUp">
                        <div class="inner-box">
                            <div class="content-box">
                                <i class="icon flaticon-wind-energy-1"></i>
                                <span class="sub-title">01 Service</span>
                                <h4 class="title"><a href="page-service-details.html">Wind Turbines</a></h4>
                                <div class="text">Hybrid energy refers to the use of multiple sources ...</div>
                                <a href="" class="read-more"><i class="fa fa-chevron-right"></i></a>
                            </div>
                            <div class="image-box">
                                <figure class="image">
                                    <a href="page-service-details.html"><img src="{{asset('website/images/resource/service-1.jpg')}}" alt="" /></a>
                                </figure>
                            </div>
                        </div>
                    </div>
                    <!-- Service Block -->
                    <div class="service-block col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="inner-box">
                            <div class="content-box">
                                <i class="icon flaticon-settings-2"></i>
                                <span class="sub-title">02 Service</span>
                                <h4 class="title"><a href="page-service-details.html">Maintenance</a></h4>
                                <div class="text">Wind turbines are devices that convert wind energy ...</div>
                                <a href="" class="read-more"><i class="fa fa-chevron-right"></i></a>
                            </div>
                            <div class="image-box">
                                <figure class="image">
                                    <a href="page-service-details.html"><img src="{{asset('website/images/resource/service-2.jpg')}}" alt="" /></a>
                                </figure>
                            </div>
                        </div>
                    </div>
                    <!-- Service Block -->
                    <div class="service-block col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="400ms">
                        <div class="inner-box">
                            <div class="content-box">
                                <i class="icon flaticon-windmill"></i>
                                <span class="sub-title">03 Service</span>
                                <h4 class="title"><a href="page-service-details.html">Wind Generators</a></h4>
                                <div class="text">Wind generators are devices that harness the ...</div>
                                <a href="" class="read-more"><i class="fa fa-chevron-right"></i></a>
                            </div>
                            <div class="image-box">
                                <figure class="image">
                                    <a href="page-service-details.html"><img src="{{asset('website/images/resource/service-3.jpg')}}" alt="" /></a>
                                </figure>
                            </div>
                        </div>
                    </div>
                    <!-- Service Block -->
                    <div class="service-block col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="600ms">
                        <div class="inner-box">
                            <div class="content-box">
                                <i class="icon flaticon-solar-panel"></i>
                                <span class="sub-title">04 Service</span>
                                <h4 class="title text-white"><a href="page-service-details.html">Solar PV Systems</a></h4>
                                <div class="text ">A Solar PV (photovoltaic) system is a type of ...</div>
                                <a href="" class="read-more"><i class="fa fa-chevron-right"></i></a>
                            </div>
                            <div class="image-box">
                                <figure class="image">
                                    <a href="page-service-details.html"><img src="{{asset('website/images/resource/service-4.jpg')}}" alt="" /></a>
                                </figure>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Services Section-->

        <!-- Why Choose Us -->
        <section class="why-choose-us pb-lg-0">
            <div class="bg-image" style="background-image: url(website/images/icons/bg-pattern-1.png)"></div>
            <div class="auto-container">
                <div class="row">
                    <!-- Content Column -->
                    <div class="content-column col-lg-6">
                        <div class="inner-column wow fadeInRight">
                            <div class="sec-title light">
                                <span class="sub-title">Why Choose Us</span>
                                <h2>We are Building a Sustainable Future</h2>
                            </div>
                            <!-- Feature Block -->
                            <div class="feature-block-two pb-30">
                                <div class="inner-box">
                                    <i class="icon fas fa-check"></i>
                                    <h4 class="title">Best energy solution</h4>
                                    <p class="text">The best energy solution depends on several factors, including your specific needs, location, budget, and environmental considerations.</p>
                                </div>
                            </div>
                            <!-- Feature Block -->
                            <div class="feature-block-two border-bottom-0">
                                <div class="inner-box">
                                    <i class="icon fas fa-check"></i>
                                    <h4 class="title">24/7 Technical Support</h4>
                                    <p class="text">At Sustainable Energy Services, we understand the importance of reliable and uninterrupted access to technical support when it comes to sustainable energy systems.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- form Column -->
                    <div class="form-column col-lg-6">
                        <div class="inner-column">
                            <p class="fs-6 mb-5 text-light">We believe in a future where renewable energy sources play a vital role in reducing carbon emissions and creating a sustainable planet.</p>
                            <!-- Contact Form -->
                            <div class="contact-form wow fadeInLeft">
                                <!--Contact Form-->
                                <form method="post" action="get" id="contact-form">
                                    <div class="row">
                                        <div class="col-lg-12 form-group">
                                            <input type="text" name="full_name" placeholder="Your Name" required />
                                        </div>
                                        <div class="col-lg-12 form-group">
                                            <input type="text" name="Email" placeholder="Your Email" required />
                                        </div>
                                        <div class="col-lg-12 form-group">
                                            <input type="text" name="Phone" placeholder="Phone No" required />
                                        </div>
                                        <div class="col-lg-12 form-group">
                                            <textarea name="form_message" class="form-control required" rows="6" placeholder="Enter Message"></textarea>
                                        </div>
                                        <div class="col-lg-12 col-md-12 col-sm-12 form-group">
                                            <button class="theme-btn btn-style-three" type="submit" name="submit-form"><span class="btn-title">Submit Request</span></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <!--End Contact Form -->
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Why Choose Us -->

        <!-- Pie Chart -->
        <section class="bg-white pt-60">
            <div class="auto-container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="graph-box d-md-flex align-items-center justify-content-md-between wow fadeInRight">
                            <!-- Pie Graph -->
                            <div class="pie-graph d-sm-flex align-items-center text-center text-sm-start mb-4 mb-md-0">
                                <div class="graph-outer">
                                    <input type="text" class="dial" data-fgColor="#ff6d2e" data-bgColor="#f9f9f9" data-width="125" data-height="125" data-linecap="normal" value="90">
                                    <div class="inner-text count-box"><span class="count-text txt" data-stop="99" data-speed="2000"></span>%</div>
                                </div>
                                <h4 class="title mt-0 ms-4">Projects Completed</h4>
                            </div>
                            <!-- Pie Graph -->
                            <div class="pie-graph d-sm-flex align-items-center text-center text-sm-start">
                                <div class="graph-outer">
                                    <input type="text" class="dial" data-fgColor="#ff6d2e" data-bgColor="#f9f9f9" data-width="125" data-height="125" data-linecap="normal" value="50">
                                    <div class="inner-text count-box"><span class="count-text txt" data-stop="50" data-speed="2000"></span>%</div>
                                </div>
                                <h4 class="title mt-0 ms-4">Clients Satisfied</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Pie Chart -->

        <!-- Project Section -->
        <section class="project-section">
            <div class="large-container">
                <div class="sec-title text-center">
                    <span class="sub-title">LASTEST PROJECT</span>
                    <h2>Our Latest Projects</h2>
                </div>
                <!-- Prject Carousel -->
                <div class="project-carousel owl-carousel owl-theme wow fadeInUp">
                    <!-- Project Block -->
                    <div class="project-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image">
                                    <a href="{{asset('website/images/resource/project-1.jpg')}}" class="lightbox-image"><img src="{{asset('website/images/resource/project-1.jpg')}}" alt="" /></a>
                                </figure>
                                <a href="page-project-details.html" class="icon"><i class="fa fa-plus"></i></a>
                            </div>
                            <div class="content-box">
                                <span class="sub-title">Solar Energy</span>
                                <h4 class="title"><a href="page-project-details.html">Maximizing Solar ROI</a></h4>
                            </div>
                        </div>
                    </div>
                    <!-- Project Block -->
                    <div class="project-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image">
                                    <a href="{{asset('website/images/resource/project-2.jpg')}}" class="lightbox-image"><img src="{{asset('website/images/resource/project-2.jpg')}}" alt="" /></a>
                                </figure>
                                <a href="page-project-details.html" class="icon"><i class="fa fa-plus"></i></a>
                            </div>
                            <div class="content-box">
                                <span class="sub-title">Solar Energy</span>
                                <h4 class="title"><a href="page-project-details.html">Diversifying Your Solar</a></h4>
                            </div>
                        </div>
                    </div>
                    <!-- Project Block -->
                    <div class="project-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image">
                                    <a href="{{asset('website/images/resource/project-3.jpg')}}" class="lightbox-image"><img src="{{asset('website/images/resource/project-3.jpg')}}" alt="" /></a>
                                </figure>
                                <a href="page-project-details.html" class="icon"><i class="fa fa-plus"></i></a>
                            </div>
                            <div class="content-box">
                                <span class="sub-title">Solar Energy</span>
                                <h4 class="title"><a href="page-project-details.html">The Benefits of Solar</a></h4>
                            </div>
                        </div>
                    </div>
                    <!-- Project Block -->
                    <div class="project-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image">
                                    <a href="{{asset('website/images/resource/project-4.jpg')}}" class="lightbox-image"><img src="{{asset('website/images/resource/project-4.jpg')}}" alt="" /></a>
                                </figure>
                                <a href="page-project-details.html" class="icon"><i class="fa fa-plus"></i></a>
                            </div>
                            <div class="content-box">
                                <span class="sub-title">Solar Energy</span>
                                <h4 class="title"><a href="page-project-details.html">Shining a Light</a></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--End Project Section -->

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
                            <p>&copy; Copyright 2023 by <a href="url('/')">Company.com</a></p>
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

    </div>
    <!-- End Page Wrapper -->
    <!-- Scroll To Top -->
    <div class="scroll-to-top scroll-to-target" data-target="html"><span class="fa fa-angle-up"></span></div>
    @include("layouts.website.scripts")
</body>

</html>