<footer class="main-footer">
    <div class="bg-image" style="background-image: url({{asset('website/images/background/5.jpg')}})"></div>

    <!-- Contact info -->
    <div class="contacts-outer">
        <div class="auto-container">
            <div class="row">
                <!-- Contact Info Block -->
                <div class="contact-info-block col-lg-4 col-sm-6 wow fadeInRight">
                    <div class="inner-box">
                        <div class="icon-box"><i class="icon flaticon-location"></i></div>
                        <h4 class="title">Address</h4>
                        <div class="text">6835 Song Sparrow Rd, Eastvale CA, 92880</div>
                    </div>
                </div>
                <!-- Contact Info Block -->
                <div class="contact-info-block col-lg-4 col-sm-6 wow fadeInRight" data-wow-delay="300ms">
                    <div class="inner-box">
                        <div class="icon-box"><i class="icon flaticon-e-mail-envelope"></i></div>
                        <h4 class="title">Contact</h4>
                        <div class="text">
                            <a href="mailto:needhelp@company.com">6835 Song Sparrow Rd, Eastvale CA, 92880</a>
                            <a href="tel:+92(8800)48720">909 567-6536</a>
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
                        <div class="logo"><a href="{{url('/')}}"><img src="{{asset('website/images/logo-2.png')}}" alt=""></a></div>
                        <div class="text">We work with a passion of taking challenges and creating new ones in advertising sector.</div>
                        <a href="page-about.html" class="theme-btn btn-style-one hvr-light small"><span class="btn-title">About</span></a>
                    </div>
                </div>


            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="auto-container">
            <div class="inner-container">
                <div class="copyright-text">
                    <p>&copy; Copyright {{date('Y')}} - {{date('Y', strtotime('+1 year'))}} by <a href="{{url('/')}}">Solen Energy Co.</a></p>
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