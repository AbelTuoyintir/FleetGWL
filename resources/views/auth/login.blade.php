<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>GWCL | Asset Portal Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    body { background-color: #f4f4f4; font-family: 'Inter', 'Roboto', sans-serif; color: #202124; }
    .google-login-card { background: #ffffff; border: 1px solid #dadce0; border-radius: 8px; }
    .google-btn { background-color: #1a73e8; color: #ffffff; transition: background-color .2s; }
    .google-btn:hover { background-color: #1b66c9; }
    .google-btn:focus { background-color: #1a73e8; box-shadow: 0 1px 2px 0 rgba(60,64,67,.3), 0 1px 3px 1px rgba(60,64,67,.15); }
    .google-input { border: 1px solid #dadce0; border-radius: 4px; padding: 13px 15px; font-size: 16px; transition: border .2s; }
    .google-input:focus { border: 2px solid #1a73e8; outline: none; padding: 12px 14px; }
    /* Car-track loader */
    .car-track{position:relative;width:80px;height:40px;margin:auto;}
    .car{position:absolute;width:40px;height-20px;background:#1a73e8;border-radius:4px;animation:drive 1.8s linear infinite;}
    .track{position:absolute;bottom:0;left:0;right:0;height-2px;background:rgba(26,115,232,.3);overflow:hidden;}
    .track::after{content:'';position:absolute;width:20px;height-2px;background:rgba(26,115,232,.6);animation:track 0.9s linear infinite;}
    @keyframes drive{0%{left:-40px;}100%{left:calc(100% + 40px);}}
    @keyframes track{0%{left:-20px;}100%{left:100%;}}
  </style>
  @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/css/app.css')
  @endif
</head>
<body class="min-h-screen flex items-center justify-center p-4">

<!-- LOGIN CARD -->
<div class="w-full max-w-[450px] google-login-card p-10 space-y-6">
  <!-- Logo / Title -->
  <div class="text-center mb-8">
    <div class="w-12 h-12 flex items-center justify-center mx-auto mb-4">
        <img src="{{ asset('images/gwcl-logo.png') }}" alt="GWL Logo" class="w-full h-full object-contain">
    </div>
    <h1 class="text-2xl font-normal mb-2">Sign in</h1>
    <p class="text-base text-[#202124]">Use your GWC Asset Account</p>
  </div>

  <!-- FORM -->
  <form id="loginForm" method="POST" action="{{ route('login.submit') }}" class="space-y-6">
    @csrf
    <!-- Email -->
    <div>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="Email" class="w-full google-input"/>
      @error('email')
        <p class="text-[#d93025] text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
      @enderror
    </div>

    <!-- Password -->
    <div>
      <label for="password" class="block text-sm font-medium mb-1">Password</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-white/60"><i class="fas fa-lock"></i></span>
        <input type="password" id="password" name="password" required class="w-full pl-10 pr-10 py-2 bg-white/10 border border-white/20 rounded-lg placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white"/>
        <button type="button" onclick="togglePassword()" id="togglePasswordBtn" aria-label="Show password" title="Show password" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/60 hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-300 rounded">
          <i id="eyeIcon" class="fas fa-eye"></i>
        </button>
      </div>
      @error('password')
        <p class="text-[#d93025] text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
      @enderror
    </div>

    <!-- Remember + Forgot -->
    <div class="flex items-center justify-between text-sm pt-2">
      <label class="flex items-center gap-2 cursor-pointer text-gray-600">
        <input type="checkbox" id="remember" name="remember" class="rounded border-gray-300 text-[#1a73e8] focus:ring-[#1a73e8]"/>
        <span>Remember me</span>
      </label>
      <a href="{{ route('password.request') }}" class="text-[#1a73e8] font-medium hover:underline">Forgot password?</a>
    </div>

    <!-- Submit -->
    <div class="flex justify-between items-center pt-6">
      <a href="#" class="text-[#1a73e8] font-medium hover:underline text-sm">Create account</a>
      <button type="submit" id="submitBtn" class="google-btn px-6 py-2 rounded font-medium text-sm transition-all flex items-center justify-center gap-2 min-w-[100px]">
        <span id="btnText">Next</span>
        <div id="loader" class="hidden car-track scale-50"><div class="car"></div><div class="track"></div></div>
      </button>
    </div>

    <!-- Footer -->
    <p class="text-center text-[12px] text-gray-500 pt-10">
      © 2024 Ghana Water Company Limited
    </p>
  </form>
</div>

<!-- SCRIPTS -->
<script>
  /* ---------- Toggle Password ---------- */
  function togglePassword() {
    const pwd = document.getElementById('password');
    const eye = document.getElementById('eyeIcon');
    const btn = document.getElementById('togglePasswordBtn');

    if (pwd.type === 'password') {
      pwd.type = 'text';
      eye.classList.remove('fa-eye');
      eye.classList.add('fa-eye-slash');
      btn.setAttribute('aria-label', 'Hide password');
      btn.setAttribute('title', 'Hide password');
    } else {
      pwd.type = 'password';
      eye.classList.remove('fa-eye-slash');
      eye.classList.add('fa-eye');
      btn.setAttribute('aria-label', 'Show password');
      btn.setAttribute('title', 'Show password');
    }
  }

  /* ---------- Handle Login Submission ---------- */
  document.getElementById('loginForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const loader = document.getElementById('loader');

    // Show loading state
    btnText.classList.add('hidden');
    loader.classList.remove('hidden');
    btn.disabled = true;
    btn.classList.add('opacity-80', 'cursor-not-allowed');

    // Save remember preference
    const email = document.getElementById('email').value;
    if (document.getElementById('remember').checked) {
      localStorage.setItem('gwcl_remember', email);
    } else {
      localStorage.removeItem('gwcl_remember');
    }

    // The form will now proceed with its default POST submission
  });

  /* ---------- Auto-fill remembered email ---------- */
  window.addEventListener('DOMContentLoaded', () => {
    const remembered = localStorage.getItem('gwcl_remember');
    if (remembered) {
      document.getElementById('email').value = remembered;
      document.getElementById('remember').checked = true;
    }
  });
</script>
@include('components.ai-chat-bot')
</body>
</html>
