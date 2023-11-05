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
        @include("layouts.website.main_header")
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

        <!-- Main Footer -->
            <!--Footer Bottom-->
            @include("layouts.website.footer_bottom")
        <!--End Main Footer -->

    </div><!-- End Page Wrapper -->

    <!-- Scroll To Top -->
    <div class="scroll-to-top scroll-to-target" data-target="html"><span class="fa fa-angle-up"></span></div>

    @include("layouts.website.scripts")
</body>

</html>