<!doctype html>
<html class="no-js" lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <title>Solen Energy Construction - Signin</title>
    <link rel="icon" href="{{asset('storage/favicon.ico')}}" type="image/x-icon">
    <link rel="stylesheet" href="{{asset('assets/css/my-task.style.min.css')}}">
    <style>
        html, body {
            height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #0f0f1e 50%, #000000 100%);
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255, 165, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 107, 53, 0.1) 0%, transparent 50%);
            animation: bgShift 10s ease-in-out infinite;
        }
        @keyframes bgShift {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        #mytask-layout {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .solar-bg {
            background: linear-gradient(135deg, #FFA500 0%, #FF6B35 50%, #F7931E 100%);
            position: relative;
            overflow: hidden;
            min-height: 550px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .solar-bg::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            animation: glow 4s ease-in-out infinite;
        }
        @keyframes glow {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.2); opacity: 0.6; }
        }
        .solar-bg::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveGrid 20s linear infinite;
        }
        @keyframes moveGrid {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        .solar-panel {
            position: absolute;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: 4px solid #FFA500;
            border-radius: 8px;
            animation: float 6s ease-in-out infinite;
            opacity: 0.8;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 3px;
            padding: 6px;
        }
        .solar-panel::before {
            content: '';
            position: absolute;
            inset: 6px;
            background: 
                repeating-linear-gradient(90deg, transparent, transparent 30px, rgba(255,165,0,0.3) 30px, rgba(255,165,0,0.3) 32px),
                repeating-linear-gradient(0deg, transparent, transparent 30px, rgba(255,165,0,0.3) 30px, rgba(255,165,0,0.3) 32px);
            border-radius: 4px;
        }
        .solar-panel::after {
            content: '';
            position: absolute;
            inset: 6px;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.05) 100%);
            border-radius: 4px;
        }
        .panel-1 { top: 10%; left: 10%; animation-delay: 0s; }
        .panel-2 { top: 60%; left: 15%; animation-delay: 2s; }
        .panel-3 { top: 30%; right: 20%; animation-delay: 4s; }
        .panel-4 { bottom: 20%; right: 10%; animation-delay: 1s; }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        .sun-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 50%;
            position: relative;
            animation: pulse 3s ease-in-out infinite;
            box-shadow: 0 0 60px rgba(255, 215, 0, 0.6);
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 60px rgba(255, 215, 0, 0.6); }
            50% { transform: scale(1.1); box-shadow: 0 0 80px rgba(255, 215, 0, 0.8); }
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(20px);
            border-radius: 24px !important;
            box-shadow: 0 25px 70px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,165,0,0.1);
            animation: slideIn 0.8s ease-out;
            position: relative;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FFA500, #FF6B35, #FFA500);
            border-radius: 24px 24px 0 0;
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-control {
            border-radius: 12px;
            border: 2px solid #e8e8e8;
            transition: all 0.4s;
            background: #fafafa;
            padding: 12px 16px;
        }
        .form-control:focus {
            border-color: #FFA500;
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.15), 0 4px 12px rgba(255, 165, 0, 0.2);
            background: white;
            transform: translateY(-2px);
        }
        .btn-solar {
            background: linear-gradient(135deg, #FFA500 0%, #FF6B35 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            transition: all 0.4s;
            box-shadow: 0 6px 20px rgba(255, 165, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        .btn-solar::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn-solar:hover::before {
            width: 300px;
            height: 300px;
        }
        .btn-solar:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 165, 0, 0.7);
        }
        .company-title {
            background: linear-gradient(135deg, #FFA500 0%, #FF6B35 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            animation: fadeIn 1s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .energy-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255,255,255,0.8);
            border-radius: 50%;
            animation: particle 8s linear infinite;
        }
        @keyframes particle {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-600px) translateX(100px); opacity: 0; }
        }
        .particle-1 { left: 20%; animation-delay: 0s; }
        .particle-2 { left: 40%; animation-delay: 2s; }
        .particle-3 { left: 60%; animation-delay: 4s; }
        .particle-4 { left: 80%; animation-delay: 6s; }
        @media (max-width: 991px) {
            .solar-bg { min-height: 400px; }
            .sun-icon { width: 80px; height: 80px; }
            h1 { font-size: 1.8rem !important; }
        }
    </style>
</head>

<body>

<div id="mytask-layout">
    <div class="main p-2 py-3 p-xl-5">
        <div class="body d-flex p-0 p-xl-5">
            <div class="container-xxl">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6 d-none d-lg-flex solar-bg rounded-4" style="position: relative;">
                        <div class="energy-particle particle-1"></div>
                        <div class="energy-particle particle-2"></div>
                        <div class="energy-particle particle-3"></div>
                        <div class="energy-particle particle-4"></div>
                        <div class="solar-panel panel-1"></div>
                        <div class="solar-panel panel-2"></div>
                        <div class="solar-panel panel-3"></div>
                        <div class="solar-panel panel-4"></div>
                        <div class="text-center" style="position: relative; z-index: 10;">
                            <div class="d-flex justify-content-center mb-4">
                                <div class="sun-icon d-flex align-items-center justify-content-center">
                                    <svg width="60" height="60" fill="white" viewBox="0 0 16 16">
                                        <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/>
                                    </svg>
                                </div>
                            </div>
                            <h1 class="text-white fw-bold mb-3" style="font-size: 2.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">Solen Energy Co.</h1>
                            <p class="text-white fs-5" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">Powering Tomorrow with Solar Innovation</p>
                        </div>
                    </div>
                    <div class="col-lg-6 d-flex justify-content-center align-items-center">
                        <div class="w-100 p-4 p-md-5 card login-card border-0" style="max-width: 450px;">
                            <form class="row g-3" method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="col-12 text-center mb-3">
                                    <h2 class="company-title mb-2">Welcome Back</h2>
                                    <p class="text-muted">Sign in to your account</p>
                                </div>
                                @if ($errors->any())
                                    <div class="col-12">
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <strong>Error!</strong> {{ $errors->first() }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-dark">Username</label>
                                    <input type="text" name="username" class="form-control form-control-lg @error('username') is-invalid @enderror" placeholder="Enter your username" value="{{ old('username') }}" required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-semibold text-dark mb-0">Password</label>
                                        <a href="{{url('forgot-password')}}" style="color: #FFA500; text-decoration: none; font-size: 0.9rem;">Forgot Password?</a>
                                    </div>
                                    <input type="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" placeholder="Enter your password" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                        <label class="form-check-label text-dark" for="remember">Remember me</label>
                                    </div>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-solar btn-lg w-100 text-white text-uppercase">Sign In</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{asset('assets/bundles/libscripts.bundle.js')}}"></script>

</body>
</html>