<!DOCTYPE html>
<html lang="en">
@section("title","Home")
<head>
    @include("layouts.website.header")
</head>

<body>

    <div class="page-wrapper">

        <!-- Preloader -->
        <div class="preloader"></div>

        <!-- Main Header-->
        <header class="main-header header-style-two">
            <!-- Header Lower -->
            <div class="header-lower">
                <!-- Main box -->
                <div class="main-box">
                    <div class="logo-box">
                        <div class="logo"><a href="index.html"><img src="{{asset('website/images/logo-2.png')}}" alt="" title="Tronis"></a></div>
                    </div>

                    <!--Nav Box-->
                    <div class="nav-outer">
                        <div class="nav-outer">
                            @include("layouts.website.navbar")
                            <!-- Main Menu End-->

                            <div class="outer-box">
                                <a href="#" class="theme-btn btn-style-one hvr-light"><span class="btn-title">Get A Quote</span></a>
                                <!-- Mobile Nav toggler -->
                                <div class="mobile-nav-toggler"><span class="icon lnr-icon-bars"></span></div>
                            </div>
                        </div>
                        <!-- Main Menu End-->
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
                        <div class="nav-logo"><a href="index.html"><img src="images/logo-2.png" alt="" title="Fesho"></a></div>
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
                        <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="#"><i class="fab fa-pinterest"></i></a></li>
                        <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                    </ul>
                </nav>
            </div><!-- End Mobile Menu -->

            <!-- Header Search -->
            <div class="search-popup">
                <span class="search-back-drop"></span>
                <button class="close-search"><span class="fa fa-times"></span></button>

                <div class="search-inner">
                    <form method="post" action="index.html">
                        <div class="form-group">
                            <input type="search" name="search-field" value="" placeholder="Search..." required="">
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
                            <a href="index.html" title=""><img src="{{asset('website/images/logo-2.png')}}" alt="" title=""></a>
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
            </div><!-- End Sticky Menu -->
        </header>
        <!--End Main Header -->

        <!-- Banner Section Two-->
        <section class="banner-section">
            <div class="banner-carousel owl-carousel owl-theme">
                <!-- Slide Item -->
                <div class="slide-item">
                    <div class="bg-image" style="background-image: url(website/images/main-slider/1.jpg);"></div>
                    <div class="auto-container">
                        <div class="row">
                            <div class="content-column col-lg-12 text-center">
                                <div class="content-box">
                                    <h6 class="sub-title text-light fw-normal animate-2">Solution For All Type Of Solar Energy</h6>
                                    <h2 class="title animate-1">Renewable<br> Energy Solution</h2>
                                    <div class="btn-box animate-3"><a href="page-about.html" class="theme-btn btn-style-one"><span class="btn-title">Explore Now</span></a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide Item -->
                <div class="slide-item">
                    <div class="bg-image" style="background-image: url(website/images/main-slider/3.jpg);"></div>
                    <div class="auto-container">
                        <div class="row">
                            <div class="content-column col-lg-12 text-center">
                                <div class="content-box">
                                    <h6 class="sub-title text-light fw-normal animate-2">Solution For Environmental Protection</h6>
                                    <h2 class="title animate-3">A Clean Energy <br> Revolution</h2>
                                    <div class="btn-box animate-4"><a href="page-about.html" class="theme-btn btn-style-one"><span class="btn-title">Explore Now</span></a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--END Banner Section Two -->

        <!-- Features Section Two -->
        <section class="features-section-two mt-md-100 pt-lg-0">
            <div class="auto-container">
                <div class="row">
                    <div class="col-lg-4 col-sm-6">
                        <!-- Feature Block -->
                        <div class="feature-block1-home2">
                            <div class="inner">
                                <i class="icon flaticon-plant flex-shrink-0"></i>
                                <h5 class="title text-white">Maximize Green Resources</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <!-- Feature Block -->
                        <div class="feature-block1-home2">
                            <div class="inner">
                                <i class="icon flaticon-wind-energy-1 flex-shrink-0"></i>
                                <h5 class="title text-white">Future with Green Energy</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <!-- Feature Block -->
                        <div class="feature-block1-home2">
                            <div class="inner">
                                <i class="icon flaticon-solar-panel flex-shrink-0"></i>
                                <h5 class="title text-white">Clean & Renewable Energy</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Features Section-->

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
                                    <h5 class="title text-white">24/7 Support</h5>
                                    <p class="text mb-30">24/7 support refers to customer service or technical assistance</p>
                                    <a href="page-about.html" class="theme-btn btn-style-one hvr-light"><span class="btn-title">Explore Now</span></a>
                                </div>
                                <div class="icon-box1-home2 mt-4 mt-sm-0">
                                    <i class="icon flaticon-quality"></i>
                                    <h6 class="title text-white">We’re Certified Solar Experts</h6>
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

        <!-- Services Section Two -->
        <section class="services-section-home2">
            <div class="auto-container">
                <div class="row">
                    <div class="col-lg-5">
                        <div class="sec-title">
                            <span class="sub-title">Specialise In The Transportation</span>
                            <h2>Offering Sustainable Energy Services</h2>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="sec-title">
                            <p class="text mt-0">By offering sustainable energy services, you can help reduce greenhouse gas emissions, promote energy independence, and provide customers with clean, reliable energy solutions that can save them money and help protect the environment.</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="home2-service-slider owl-carousel owl-theme">
                            <!-- Service Block Two -->
                            <div class="service-block-home2 wow fadeInUp">
                                <figure class="image"><a href="page-service-details.html"><img src="{{asset('website/images/resource/service-5.jpg')}}" alt=""></a></figure>
                                <div class="inner-box ">
                                    <i class="icon flaticon-solar-panel"></i>
                                    <h4 class="title mt-0"><a class="text-white" href="page-service-details.html">Hybrid Energy</a></h4>
                                    <div class="text">Hybrid energy refers to the use of multiple sources of energy to meet our energy needs.</div>
                                    <a href="page-service-details.html" class="read-more">Read More</a>
                                </div>
                            </div>
                            <!-- Service Block Two -->
                            <div class="service-block-home2 wow fadeInUp">
                                <figure class="image"><a href="page-service-details.html"><img src="{{asset('website/images/resource/service-1.jpg')}}" alt=""></a></figure>
                                <div class="inner-box ">
                                    <i class="icon flaticon-wind-turbine"></i>
                                    <h4 class="title mt-0"><a class="text-white" href="page-service-details.html">Wind Turbines</a></h4>
                                    <div class="text">Hybrid energy refers to the use of multiple sources of energy to meet our energy needs.</div>
                                    <a href="page-service-details.html" class="read-more">Read More</a>
                                </div>
                            </div>
                            <!-- Service Block Two -->
                            <div class="service-block-home2 wow fadeInUp">
                                <figure class="image"><a href="page-service-details.html"><img src="{{asset('website/images/resource/service-2.jpg')}}" alt=""></a></figure>
                                <div class="inner-box ">
                                    <i class="icon flaticon-settings"></i>
                                    <h4 class="title mt-0"><a class="text-white" href="page-service-details.html">Maintenance</a></h4>
                                    <div class="text">Hybrid energy refers to the use of multiple sources of energy to meet our energy needs.</div>
                                    <a href="page-service-details.html" class="read-more">Read More</a>
                                </div>
                            </div>
                            <!-- Service Block Two -->
                            <div class="service-block-home2 wow fadeInUp">
                                <figure class="image"><a href="page-service-details.html"><img src="{{asset('website/images/resource/service-3.jpg')}}" alt=""></a></figure>
                                <div class="inner-box ">
                                    <i class="icon flaticon-windmill"></i>
                                    <h4 class="title mt-0"><a class="text-white" href="page-service-details.html">Wind Generators</a></h4>
                                    <div class="text">Hybrid energy refers to the use of multiple sources of energy to meet our energy needs.</div>
                                    <a href="page-service-details.html" class="read-more">Read More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--End Services Section -->

        <!-- Why Choose Us -->
        <section class="why-choose-us-home2">
            <div class="bg-image d-none d-xl-block" style="background-image: url(./website/images/background/home-2-divider-bg.jpg)"></div>
            <div class="bg-shape">
                <div class="shape" style="background-image: url(./website/images/background/9.jpg)"></div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <!-- Image Column -->
                    <div class="image-column col-xl-7">
                        <div class="image-box hide-desktop"><img src="{{asset('website/images/background/home-2-divider-bg.jpg')}}" alt=""></div>
                    </div>
                    <!-- Content Column -->
                    <div class="content-column col-xl-4 col-lg-8">
                        <div class="inner-column wow fadeInLeft">
                            <div class="sec-title light">
                                <span class="sub-title">Why Choose Us</span>
                                <h2>We are Building Reliable & <br>Affordable Energy!</h2>
                                <div class="text">Develop energy storage systems such as batteries and other technologies that can store excess renewable energy for use later.</div>
                                <div class="text">Invest in energy-efficient technologies that can reduce energy consumption and save money on energy bills.</div>
                            </div>
                            <div class="row">
                                <!-- Feature Block Four-->
                                <div class="feature-block-home2 mb-4 mb-sm-0 col-6">
                                    <div class="inner-box d-sm-flex align-items-sm-center">
                                        <i class="icon mr-20 flaticon-solar-panel"></i>
                                        <h5 class="title mt-3 mt-sm-0 my-0 text-white">Quality Energy <br>Solution</h5>
                                    </div>
                                </div>
                                <!-- Feature Block Four -->
                                <div class="feature-block-home2 col-6">
                                    <div class="inner-box d-sm-flex align-items-sm-center">
                                        <i class="icon mr-20 flaticon-renewable-energy"></i>
                                        <h5 class="title mt-3 mt-sm-0 my-0 text-white">Fast Technical <br>Services</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Why Choose Us -->

        <!-- Pricing Section -->
        <section class="pricing-section">
            <div class="bg-layer d-none d-lg-block"></div>
            <div class="auto-container">
                <div class="row">
                    <!-- Title column -->
                    <div class="title-column col-lg-4">
                        <div class="sec-title">
                            <span class="sub-title">Pricing & Plans</span>
                            <h2>Effective & affordable plans</h2>
                            <ul class="list-style-three">
                                <li><i class="fa fa-check-circle mr-15"></i> Get Right Solutions for Shipment</li>
                                <li><i class="fa fa-check-circle mr-15"></i> Expert Logistics Team Members</li>
                            </ul>
                            <div class="text">Effective plans can be a game-changer for anyone looking to achieve their goals, whether they are personal or professional.</div>
                        </div>
                    </div>
                    <div class="pricing-column col-lg-8">
                        <div class="row">
                            <!-- Pricing Block -->
                            <div class="pricing-block col-sm-6 wow fadeInRight">
                                <div class="inner-box">
                                    <span class="title">Basic Plan</span>
                                    <div class="price-box">
                                        <h4 class="price"><sup>$</sup>49.00</h4>
                                        <span class="validaty">Per Month</span>
                                    </div>
                                    <figure class="image"><img src="{{asset('website/images/icons/plan1.png')}}" alt=""></figure>
                                    <ul class="features">
                                        <li>1 Installation</li>
                                        <li>Wind Generators</li>
                                        <li>Real Solar PV Systems</li>
                                        <li>100% Energy Saver</li>
                                        <li>Technical team of enthusiastic</li>
                                    </ul>
                                    <div class="btn-box">
                                        <a href="page-pricing.html" class="theme-btn btn-style-three"><span class="btn-title">Select Now</span></a>
                                    </div>
                                </div>
                            </div>
                            <!-- Pricing Block -->
                            <div class="pricing-block col-sm-6 wow fadeInRight" data-wow-delay="600ms">
                                <div class="inner-box">
                                    <span class="title">Standard Plan</span>
                                    <div class="price-box">
                                        <h4 class="price"><sup>$</sup>99.00</h4>
                                        <span class="validaty">Per Month</span>
                                    </div>
                                    <figure class="image"><img src="{{asset('website/images/icons/plan2.png')}}" alt=""></figure>
                                    <ul class="features">
                                        <li>1 Installation</li>
                                        <li>Wind Generators</li>
                                        <li>Real Solar PV Systems</li>
                                        <li>100% Energy Saver</li>
                                        <li>Technical team of enthusiastic</li>
                                    </ul>
                                    <div class="btn-box">
                                        <a href="page-pricing.html" class="theme-btn btn-style-three"><span class="btn-title">Select Now</span></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Pricing Section -->

        <!-- Team Section Seven -->
        <section class="team-section before_none pb-xs--0 pt-0">
            <div class="auto-container">
                <div class="sec-title text-center">
                    <span class="sub-title">MEET PROFESSIONALS</span>
                    <h2>Our Expert Members</h2>
                </div>
                <div class="four-items-carousel owl-carousel owl-theme default-navs wow fadeInUp">
                    <!-- Team block Seven -->
                    <div class="team-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image"><a href="page-team-details.html"><img src="{{asset('website/images/resource/team-1.jpg')}}" alt=""></a></figure>
                            </div>
                            <div class="info-box">
                                <h4 class="name"><a href="page-team-details.html">Kevin Hardson</a></h4>
                                <span class="designation">Engineer</span>
                                <div class="social-links">
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                    <a href="#"><i class="fab fa-instagram"></i></a>
                                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Team block Seven -->
                    <div class="team-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image"><a href="page-team-details.html"><img src="{{asset('website/images/resource/team-2.jpg')}}" alt=""></a></figure>
                            </div>
                            <div class="info-box">
                                <h4 class="name"><a href="page-team-details.html">Jerome Bell</a></h4>
                                <span class="designation">Project Manager</span>
                                <div class="social-links">
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                    <a href="#"><i class="fab fa-instagram"></i></a>
                                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Team block Seven -->
                    <div class="team-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image"><a href="page-team-details.html"><img src="{{asset('website/images/resource/team-3.jpg')}}" alt=""></a></figure>
                            </div>
                            <div class="info-box">
                                <h4 class="name"><a href="page-team-details.html">John Brown</a></h4>
                                <span class="designation">Engineer</span>
                                <div class="social-links">
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                    <a href="#"><i class="fab fa-instagram"></i></a>
                                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Team block Seven -->
                    <div class="team-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image"><a href="page-team-details.html"><img src="{{asset('website/images/resource/team-4.jpg')}}" alt=""></a></figure>
                            </div>
                            <div class="info-box">
                                <h4 class="name"><a href="page-team-details.html">Courtney Henry</a></h4>
                                <span class="designation">Engineer</span>
                                <div class="social-links">
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                    <a href="#"><i class="fab fa-instagram"></i></a>
                                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Team block Seven -->
                    <div class="team-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image"><a href="page-team-details.html"><img src="{{asset('website/images/resource/team-2.jpg')}}" alt=""></a></figure>
                            </div>
                            <div class="info-box">
                                <h4 class="name"><a href="page-team-details.html">Jerome Bell</a></h4>
                                <span class="designation">Project Manager</span>
                                <div class="social-links">
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                    <a href="#"><i class="fab fa-instagram"></i></a>
                                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Team block Seven -->
                    <div class="team-block">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image"><a href="page-team-details.html"><img src="{{asset('website/images/resource/team-3.jpg')}}" alt=""></a></figure>
                            </div>
                            <div class="info-box">
                                <h4 class="name"><a href="page-team-details.html">Jenny Wilson</a></h4>
                                <span class="designation">Team Leader</span>
                                <div class="social-links">
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                    <a href="#"><i class="fab fa-instagram"></i></a>
                                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Team Section -->

        <!-- Company Benefits -->
        <section class="why-choose-us-home2-2">
            <div class="bg-image d-none d-xl-block" style="background-image: url(website/images/background/home-2-divider-bg-2.jpg)"></div>
            <div class="bg-shape">
                <div class="shape" style="background-image: url(website/images/background/about1-bg-home1-dark.jpg)"></div>
            </div>
            <div class="container-fluid">
                <div class="row">
                    <!-- Image Column -->
                    <div class="image-column col-xl-7">
                        <div class="image-box hide-desktop"><img src="{{asset('website/images/background/home-2-divider-bg.jpg')}}" alt=""></div>
                    </div>
                    <!-- Content Column -->
                    <div class="content-column col-xl-4 col-lg-8">
                        <div class="inner-column wow fadeInLeft">
                            <div class="sec-title">
                                <span class="sub-title">Company Benefits</span>
                                <h2>Greener Tomorrow for <br>Your Business!</h2>
                                <div class="text">The amount of energy you use will depend on the type of business you run. However, there are lots of ways that you can save energy no matter what you do.</div>
                            </div>
                            <ul class="list-style-four d-sm-flex align-items-sm-center mb-40">
                                <li class="mr-20"><i class="fa fa-check-circle text-white mr-15"></i> Technical Support</li>
                                <li><i class="fa fa-check-circle text-white mr-15"></i> Best Energy Solutions</li>
                            </ul>
                            <!--Skills-->
                            <div class="skills">
                                <!--Skill Item-->
                                <div class="skill-item">
                                    <div class="skill-header">
                                        <div class="skill-title text-white">Wind Turbines</div>
                                    </div>
                                    <div class="skill-bar">
                                        <div class="bar-inner">
                                            <div class="bar progress-line" data-width="73">
                                                <div class="skill-percentage">
                                                    <div class="count-box text-white"><span class="count-text text-white" data-speed="3000" data-stop="73">0</span>%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Skills-->
                            <div class="skills">
                                <!--Skill Item-->
                                <div class="skill-item">
                                    <div class="skill-header">
                                        <div class="skill-title text-white">Hybrid Energy</div>
                                    </div>
                                    <div class="skill-bar">
                                        <div class="bar-inner">
                                            <div class="bar progress-line" data-width="86">
                                                <div class="skill-percentage">
                                                    <div class="count-box text-white"><span class="count-text text-white" data-speed="3000" data-stop="86">0</span>%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Skills-->
                            <div class="skills">
                                <!--Skill Item-->
                                <div class="skill-item">
                                    <div class="skill-header">
                                        <div class="skill-title text-white">Solar Panels</div>
                                    </div>
                                    <div class="skill-bar">
                                        <div class="bar-inner">
                                            <div class="bar progress-line" data-width="57">
                                                <div class="skill-percentage">
                                                    <div class="count-box text-white"><span class="count-text text-white" data-speed="3000" data-stop="57">0</span>%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Company Benefits -->

        <!-- Project Section -->
        <section class="project-section pb-0 pt-xs--0">
            <div class="auto-container">
                <div class="sec-title text-center">
                    <span class="sub-title">LASTEST PROJECT</span>
                    <h2>Our Latest Projects</h2>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="project-block-home2 text-center position-relative overflow-hidden">
                            <img class="w-100 img-fluid" src="{{asset('website/images/resource/video-home2.jpg')}}" alt="">
                            <div class="video-box">
                                <a href="https://www.youtube.com/watch?v=Fvae8nxzVz4" class="play-now-two lightbox-image"><i class="icon fa fa-play"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--End Project Section -->

        <!-- FAQ Section -->
        <section class="faqs-section-home2" style="background-image: url(./website/images/background/home-2-faq-bg-dark.jpg)">
            <div class="auto-container">
                <div class="row">
                    <!-- FAQ Column -->
                    <div class="faq-column col-lg-6">
                        <div class="inner-column mb-md-50">
                            <ul class="accordion-box style-two bg-transparent p-0 wow fadeInLeft">
                                <!--Block-->
                                <li class="accordion block active-block pl-30 pr-30">
                                    <div class="acc-btn border-bottom-0 active">Produce Your Own Clean Save The Environment
                                        <div class="icon fa fa-plus"></div>
                                    </div>
                                    <div class="acc-content current">
                                        <div class="content border-bottom-0 pt-0">
                                            <div class="text">Reduce, reuse, and recycle: This is a classic but effective way to reduce waste and conserve resources. Try to use reusable bags, containers, and water bottles, and recycle items that can't be reused.</div>
                                        </div>
                                    </div>
                                </li>
                                <!--Block-->
                                <li class="accordion block pl-30 pr-30">
                                    <div class="acc-btn border-bottom-0">On-Site Service And Support For Certification
                                        <div class="icon fa fa-plus"></div>
                                    </div>
                                    <div class="acc-content">
                                        <div class="content border-bottom-0 pt-0">
                                            <div class="text">Reduce, reuse, and recycle: This is a classic but effective way to reduce waste and conserve resources. Try to use reusable bags, containers, and water bottles, and recycle items that can't be reused.</div>
                                        </div>
                                    </div>
                                </li>
                                <!--Block-->
                                <li class="accordion block pl-30 pr-30">
                                    <div class="acc-btn border-bottom-0">Light Source For Stable Conversion Efficiency
                                        <div class="icon fa fa-plus"></div>
                                    </div>
                                    <div class="acc-content">
                                        <div class="content border-bottom-0 pt-0">
                                            <div class="text">Reduce, reuse, and recycle: This is a classic but effective way to reduce waste and conserve resources. Try to use reusable bags, containers, and water bottles, and recycle items that can't be reused.</div>
                                        </div>
                                    </div>
                                </li>
                                <!--Block-->
                                <li class="accordion block pl-30 pr-30">
                                    <div class="acc-btn border-bottom-0">Do You Give Guarantee And After Sales Service?
                                        <div class="icon fa fa-plus"></div>
                                    </div>
                                    <div class="acc-content">
                                        <div class="content border-bottom-0 pt-0">
                                            <div class="text">Reduce, reuse, and recycle: This is a classic but effective way to reduce waste and conserve resources. Try to use reusable bags, containers, and water bottles, and recycle items that can't be reused.</div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- Content Column -->
                    <div class="content-column col-lg-6">
                        <div class="inner-column wow fadeInRight">
                            <div class="sec-title mb-40">
                                <span class="sub-title">Company Benefits</span>
                                <h2>Give Your Dream a Success</h2>
                            </div>
                            <div class="home-2-tabs">
                                <ul class="nav nav-tabs" id="myTab" role="tablist">
                                    <li class="nav-item mr-20" role="presentation">
                                        <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Always Clean Energy</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Life Sustainable</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="myTabContent">
                                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                        <div class="d-sm-flex align-items-sm-center mt-40">
                                            <div class="img flex-shrink-0 mr-20"><img class="rounded-circle" src="{{asset('website/images/resource/home-2-tab-img-1.jpg')}}" alt=""></div>
                                            <div class="content">
                                                <h5 class="text-white">Maximize Green & Clean Resources</h5>
                                                <p class="text"> Maximizing green and clean resources is essential for promoting sustainable development and reducing our impact on the environment.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                        <div class="d-sm-flex align-items-sm-center mt-40">
                                            <div class="img flex-shrink-0 mr-20"><img class="rounded-circle" src="{{asset('website/images/resource/home-2-tab-img-2.jpg')}}" alt=""></div>
                                            <div class="content">
                                                <h5 class="text-white">Maximize Green & Clean Resources</h5>
                                                <p class="text"> Maximizing green and clean resources is essential for promoting sustainable development and reducing our impact on the environment.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--End FAQ Section -->

        <!-- Call To Action Two -->
        <section class="call-to-action style-two" style="background-image: url(./website/images/main-slider/1.jpg)">
            <div class="auto-container">
                <div class="outer-box d-md-flex align-items-md-center justify-content-md-between">
                    <div class="sec-title light mb-0">
                        <h2 class="my-0">Fastest & secure way to get <br> clean, safe and renewable <br> energy</h2>
                    </div>
                    <a href="page-contact.html" class="theme-btn btn-style-three hvr-light"><span class="btn-title">Get Free A Quote</span></a>
                </div>
            </div>
        </section>
        <!--End Call To Action Two -->

        <!-- Testimonial Section -->
        <section class="testimonial-section-home2">
            <div class="float-image"><img src="{{asset('website/images/resource/icon_tesimonial.png')}}" alt=""></div>
            <div class="auto-container">
                <div class="sec-title text-center">
                    <span class="sub-title">CLIENT & TESTIMONIAL</span>
                    <h2>Here is Some Clients <br>Feedbacks</h2>
                </div>
                <div class="row">
                    <!-- Testimonial Column -->
                    <div class="col-lg-4 col-sm-6">
                        <div class="testimonial-block-two">
                            <div class="inner-content">
                                <div class="text">“All of our lorries are fitted with Satellite Tracking and Temperature Controlled monitoring systems so that the transportation of high value and temperature sensitive loads can be monitored at all times.</div>
                                <div class="reviews">
                                    <div class="stars"></div>
                                </div>
                                <div class="testi-quote"><i class="fas fa-quote-right"></i></div>
                            </div>
                            <div class="img-content d-flex align-items-end">
                                <div class="testi-img"><img src="{{asset('website/images/resource/client1.jpg')}}"></div>
                                <div class="testi-holder ml-15">
                                    <div class="text">Co Founder</div>
                                    <h5 class="my-0 text-white">Jhon D. William</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Testimonial Column -->
                    <div class="col-lg-4 col-sm-6">
                        <div class="testimonial-block-two">
                            <div class="inner-content">
                                <div class="text">“All of our lorries are fitted with Satellite Tracking and Temperature Controlled monitoring systems so that the transportation of high value and temperature sensitive loads can be monitored at all times.</div>
                                <div class="reviews">
                                    <div class="stars"></div>
                                </div>
                                <div class="testi-quote"><i class="fas fa-quote-right"></i></div>
                            </div>
                            <div class="img-content d-flex align-items-end">
                                <div class="testi-img"><img src="{{asset('website/images/resource/client2.jpg')}}"></div>
                                <div class="testi-holder ml-15">
                                    <div class="text">Co Founder</div>
                                    <h5 class="my-0 text-white">Aleesha Brown</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Testimonial Column -->
                    <div class="col-lg-4 col-sm-6">
                        <div class="testimonial-block-two">
                            <div class="inner-content">
                                <div class="text">“All of our lorries are fitted with Satellite Tracking and Temperature Controlled monitoring systems so that the transportation of high value and temperature sensitive loads can be monitored at all times.</div>
                                <div class="reviews">
                                    <div class="stars"></div>
                                </div>
                                <div class="testi-quote"><i class="fas fa-quote-right"></i></div>
                            </div>
                            <div class="img-content d-flex align-items-end">
                                <div class="testi-img"><img src="{{asset('website/images/resource/client3.jpg')}}"></div>
                                <div class="testi-holder ml-15">
                                    <div class="text">Co Founder</div>
                                    <h5 class="my-0 text-white">Mike Hardon</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Testimonial Section -->

        <!-- Why Chose Section -->
        <section class="why-choose-home2">
            <div class="bg-image" style="background-image: url(website/images/background/5.jpg)"></div>
            <div class="auto-container">
                <div class="row">
                    <!-- Why Chose Column -->
                    <div class="col-lg-6 col-md-12 col-sm-12">
                        <div class="sec-title mt-100">
                            <span class="sub-title">WHY CHOOSE US</span>
                            <h2>We Create <br>Opportunity to Reach Potential</h2>
                        </div>
                        <div class="featire-block-1 d-flex align-items-center mb-30">
                            <div class="icon mr-30 flex-shrink-0"><span class="flaticon-solar-panel-1"></span></div>
                            <div class="fb-content">
                                <h5 class="mb-0 text-white">Safety and Reliability</h5>
                                <div class="text">Aenean placerat ut lacus nec pulvinar. Donec eu leo, ante at, commodo diam</div>
                            </div>
                        </div>
                        <div class="featire-block-1 d-flex align-items-center mb-md-30">
                            <div class="icon mr-30 flex-shrink-0"><span class="flaticon-solar-energy-2"></span></div>
                            <div class="fb-content">
                                <h5 class="mb-0 text-white">Best energy solution</h5>
                                <div class="text">Interdum et malesuada fames ac ante ipsum primis in faucibus donec tempor nisi neque.</div>
                            </div>
                        </div>
                    </div>
                    <!-- form Column -->
                    <div class="form-column col-lg-6 col-md-12 col-sm-12">
                        <div class="inner-column">
                            <!-- Contact Form -->
                            <div class="contact-form home2-style">
                                <!--Contact Form-->
                                <form method="post" action="get" id="contact-form">
                                    <div class="row">
                                        <div class="col-md-12 form-group">
                                            <label>Your Name:</label>
                                            <input type="text" name="full_name" placeholder="" required>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>Your Email:</label>
                                            <input type="text" name="Email" placeholder="" required>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>Phone No::</label>
                                            <input type="text" name="Phone" placeholder="" required>
                                        </div>
                                        <div class="col-md-12 form-group">
                                            <label>Number of Panels</label>
                                            <div class="range-slider-one">
                                                <input type="text" class="range-amount" name="field-name" readonly>
                                                <div class="distance-range-slider"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>Panel type:</label>
                                            <select class="custom-select">
                                                <option value="">Select</option>
                                                <option value="">Monocrystalline</option>
                                                <option value="">Polycrystalline</option>
                                                <option value="">Thin-film</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>Capacity (VA):</label>
                                            <select class="custom-select">
                                                <option value="">Select</option>
                                                <option value="">100 Watt (W)</option>
                                                <option value="">1 Kilowatt (kW)</option>
                                                <option value="">1 Gigawatt (GW)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 form-group">
                                            <button class="theme-btn btn-style-three hvr-light" type="submit" name="submit-form"><span class="btn-title">Submit Request</span></button>
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
        <!-- End Testimonial Section -->

        <!-- News Section -->
        <section class="news-section pb-0">
            <div class="auto-container">
                <div class="sec-title text-center">
                    <span class="sub-title">Our News From Blog</span>
                    <h2>Latest News, Advices & Best <br>Posts from the Blog</h2>
                </div>

                <div class="row">
                    <!-- News Block -->
                    <div class="news-block-two col-lg-4 col-md-6">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image"><a href="news-details.html"><img src="{{asset('website/images/resource/news-4.jpg')}}" alt=""></a></figure>
                                <span class="date"><b>10</b>Jan</span>
                            </div>
                            <div class="lower-content">
                                <ul class="post-info mb-10">
                                    <li><i class="fa fa-user"></i> by Christine Eve</li>
                                    <li><i class="fa fa-comments"></i> 01 Comment</li>
                                </ul>
                                <h4 class="title mb-0"><a href="news-details.html">Winds of in the Turbine Service Industry</a></h4>
                            </div>
                        </div>
                    </div>
                    <!-- News Block -->
                    <div class="news-block-two col-lg-4 col-md-6">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image"><a href="news-details.html"><img src="{{asset('website/images/resource/news-5.jpg')}}" alt=""></a></figure>
                                <span class="date"><b>10</b>Jan</span>
                            </div>
                            <div class="lower-content">
                                <ul class="post-info mb-10">
                                    <li><i class="fa fa-user"></i> by Christine Eve</li>
                                    <li><i class="fa fa-comments"></i> 01 Comment</li>
                                </ul>
                                <h4 class="title mb-0"><a href="news-details.html">Powering Asia Pacific’s Energy Transition</a></h4>
                            </div>
                        </div>
                    </div>
                    <!-- News Block -->
                    <div class="news-block-two col-lg-4 col-md-6">
                        <div class="inner-box">
                            <div class="image-box">
                                <figure class="image"><a href="news-details.html"><img src="{{asset('website/images/resource/news-6.jpg')}}" alt=""></a></figure>
                                <span class="date"><b>10</b>Jan</span>
                            </div>
                            <div class="lower-content">
                                <ul class="post-info mb-10">
                                    <li><i class="fa fa-user"></i> by Christine Eve</li>
                                    <li><i class="fa fa-comments"></i> 01 Comment</li>
                                </ul>
                                <h4 class="title mb-0"><a href="news-details.html">Helping Companies in Their Green Transition</a></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--End News Section -->

        <!-- Clients Section   -->
        <section class="clients-section-home2">
            <div class="auto-container">
                <!-- Sponsors Outer -->
                <div class="sponsors-outer">
                    <!--clients carousel-->
                    <ul class="clients-carousel owl-carousel owl-theme">
                        <li class="slide-item">
                            <a href="#"><img src="{{asset('website/images/clients/1.jpg')}}" alt="" /></a>
                        </li>
                        <li class="slide-item">
                            <a href="#"><img src="{{asset('website/images/clients/2.jpg')}}" alt="" /></a>
                        </li>
                        <li class="slide-item">
                            <a href="#"><img src="{{asset('website/images/clients/3.jpg')}}" alt="" /></a>
                        </li>
                        <li class="slide-item">
                            <a href="#"><img src="{{asset('website/images/clients/4.jpg')}}" alt="" /></a>
                        </li>
                        <li class="slide-item">
                            <a href="#"><img src="{{asset('website/images/clients/5.jpg')}}" alt="" /></a>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
        <!--End Clients Section -->

        <!-- Main Footer -->
        <footer class="main-footer">
            <div class="bg-image" style="background-image: url(./webite/images/background/5.jpg)"></div>

            <!-- Contact info -->
            <div class="contacts-outer">
                <div class="auto-container">
                    <div class="row">
                        <!-- Contact Info Block -->
                        <div class="contact-info-block col-lg-4 col-sm-6 wow fadeInRight">
                            <div class="inner-box">
                                <div class="icon-box"><i class="icon flaticon-location"></i></div>
                                <h4 class="title">Address</h4>
                                <div class="text">30 St Kilda Road, Jackson Store, Australia</div>
                            </div>
                        </div>
                        <!-- Contact Info Block -->
                        <div class="contact-info-block col-lg-4 col-sm-6 wow fadeInRight" data-wow-delay="300ms">
                            <div class="inner-box">
                                <div class="icon-box"><i class="icon flaticon-e-mail-envelope"></i></div>
                                <h4 class="title">Contact</h4>
                                <div class="text">
                                    <a href="mailto:needhelp@company.com">needhelp@company.com</a>
                                    <a href="tel:+92(8800)48720">+92 (8800) 48720</a>
                                </div>
                            </div>
                        </div>
                        <!-- Contact Info Block -->
                        <div class="contact-info-block col-lg-4 col-sm-6 wow fadeInRight" data-wow-delay="600ms">
                            <div class="inner-box">
                                <div class="icon-box"><i class="icon flaticon-time"></i></div>
                                <h4 class="title">Timing</h4>
                                <div class="text">Mon - Sat: 8 am - 5 pm, Sunday: CLOSED</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Contact info -->

            <!--Widgets Section-->
            <div class="widgets-section">
                <div class="auto-container">
                    <div class="row">
                        <!--Footer Column-->
                        <div class="footer-column col-xl-3 col-lg-12 col-md-6 col-sm-12">
                            <div class="footer-widget about-widget">
                                <div class="logo"><a href="index.html"><img src="{{asset('website/images/logo-2.png')}}" alt=""></a></div>
                                <div class="text">We work with a passion of taking challenges and creating new ones in advertising sector.</div>
                                <a href="page-about.html" class="theme-btn btn-style-one hvr-light small"><span class="btn-title">About</span></a>
                            </div>
                        </div>

                        <!--Footer Column-->
                        <div class="footer-column col-xl-3 col-lg-3 col-md-6 col-sm-12">
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
                        <div class="footer-column col-xl-3 col-lg-4 col-md-6 col-sm-12">
                            <div class="footer-widget gallery-widget">
                                <h3 class="widget-title">Projects</h3>
                                <div class="widget-content">
                                    <div class="outer clearfix">
                                        <figure class="image">
                                            <a href="#"><img src="{{asset('website/images/resource/project-thumb-1.jpg')}}" alt=""></a>
                                        </figure>

                                        <figure class="image">
                                            <a href="#"><img src="{{asset('website/images/resource/project-thumb-2.jpg')}}" alt=""></a>
                                        </figure>

                                        <figure class="image">
                                            <a href="#"><img src="{{asset('website/images/resource/project-thumb-3.jpg')}}" alt=""></a>
                                        </figure>

                                        <figure class="image">
                                            <a href="#"><img src="{{asset('website/images/resource/project-thumb-4.jpg')}}" alt=""></a>
                                        </figure>

                                        <figure class="image">
                                            <a href="#"><img src="{{asset('website/images/resource/project-thumb-5.jpg')}}" alt=""></a>
                                        </figure>

                                        <figure class="image">
                                            <a href="#"><img src="{{asset('website/images/resource/project-thumb-6.jpg')}}" alt=""></a>
                                        </figure>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--Footer Column-->
                        <div class="footer-column col-xl-3 col-lg-5 col-md-6 col-sm-12">
                            <div class="footer-widget">
                                <h3 class="widget-title">Newsletter</h3>
                                <div class="widget-content">
                                    <div class="subscribe-form">
                                        <div class="text">Subscribe our newsletter to get our latest update & news</div>
                                        <form method="post" action="#">
                                            <div class="form-group">
                                                <input type="email" name="email" class="email" value="" placeholder="Email Address" required="">
                                                <button type="button" class="theme-btn btn-style-one bg-dark"><span class="btn-title"><i class="fa fa-paper-plane"></i></span></button>
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
                            <p>&copy; Copyright 2023 by <a href="index.html">Company.com</a></p>
                        </div>

                        <ul class="social-icon-two">
                            <li><a href="#"><i class="fab fa-facebook"></i></a></li>
                            <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fab fa-pinterest"></i></a></li>
                            <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
        <!--End Main Footer -->

    </div><!-- End Page Wrapper -->

    <!-- Scroll To Top -->
    <div class="scroll-to-top scroll-to-target" data-target="html"><span class="fa fa-angle-up"></span></div>

    @include("layouts.website.scripts")
</body>

</html>