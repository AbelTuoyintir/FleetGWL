<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>GWCL | Asset Portal Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    /* Glass-morphism */
    .glass{background:rgba(255,255,255,.15);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.2);}
    /* Animated gradient background */
    .gradient-bg{background:linear-gradient(-45deg,#0ea5e9,#3b82f6,#6366f1,#06b6d4);background-size:400% 400%;animation:gradient 15s ease infinite;}
    @keyframes gradient{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
    /* Car-track loader */
    .car-track{position:relative;width:80px;height:40px;margin:auto;}
    .car{position:absolute;width:40px;height-20px;background:#fff;border-radius:4px;animation:drive 1.8s linear infinite;}
    .track{position:absolute;bottom:0;left:0;right:0;height-2px;background:rgba(255,255,255,.3);overflow:hidden;}
    .track::after{content:'';position:absolute;width:20px;height-2px;background:rgba(255,255,255,.6);animation:track 0.9s linear infinite;}
    @keyframes drive{0%{left:-40px;}100%{left:calc(100% + 40px);}}
    @keyframes track{0%{left:-20px;}100%{left:100%;}}
    /* Fade-in animation */
    .fade-in{animation:fadeIn .8s ease forwards;}
    @keyframes fadeIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
  </style>
   @vite('resources/css/app.css')
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4 text-white fade-in">

<!-- LOGIN CARD -->
<div class="w-full max-w-md glass rounded-2xl shadow-2xl p-8 space-y-6">
  <!-- Logo / Title -->
  <div class="text-center">
    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg">
        <img src="{{ asset('images/gwcl-logo.png') }}" alt="GWCL Logo" class="w-full h-full object-cover rounded-xl">
    </div>
    <h1 class="text-2xl font-bold">GWC Asset Portal</h1>
    <p class="text-sm text-white/70">Sign in to manage fleet & assets</p>
  </div>

  <!-- FORM -->
  <form id="loginForm" method="POST" action="{{ route('login.submit') }}" class="space-y-5">
    @csrf
    <!-- Email -->
    <div>
      <label for="email" class="block text-sm font-medium mb-1">Email</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-white/60"><i class="fas fa-envelope"></i></span>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required class="w-full pl-10 pr-4 py-2 bg-white/10 border border-white/20 rounded-lg placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white"/>
      </div>
      @error('email')
        <p class="text-red-300 text-sm">{{ $message }}</p>
      @enderror
    </div>

    <!-- Password -->
    <div>
      <label for="password" class="block text-sm font-medium mb-1">Password</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-white/60"><i class="fas fa-lock"></i></span>
        <input type="password" id="password" name="password" required class="w-full pl-10 pr-10 py-2 bg-white/10 border border-white/20 rounded-lg placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white"/>
        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/60 hover:text-white">
          <i id="eyeIcon" class="fas fa-eye"></i>
        </button>
      </div>
      @error('password')
        <p class="text-red-300 text-sm">{{ $message }}</p>
      @enderror
    </div>

    <!-- Remember + Forgot -->
    <div class="flex items-center justify-between text-sm">
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" id="remember" name="remember" class="rounded border-white/30 bg-white/10 text-cyan-300 focus:ring-cyan-300"/>
        <span>Remember me</span>
      </label>
      <a href="{{ route('password.request') }}" class="text-cyan-300 hover:underline">Forgot password?</a>
    </div>

    <!-- Submit -->
    <button type="submit" id="submitBtn" class="w-full bg-cyan-400 hover:bg-cyan-300 text-slate-900 font-semibold py-2.5 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl">
      <span id="btnText">Sign In</span>
      <div id="loader" class="hidden car-track"><div class="car"></div><div class="track"></div></div>
    </button>

    <!-- Footer -->
    <p class="text-center text-xs text-white/60">
      © 2024 Ghana Water Company Limited. All rights reserved.
    </p>
  </form>
</div>

<!-- SCRIPTS -->
<script>
  /* ---------- Toggle Password ---------- */
  function togglePassword() {
    const pwd = document.getElementById('password');
    const eye = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      eye.classList.remove('fa-eye');
      eye.classList.add('fa-eye-slash');
    } else {
      pwd.type = 'password';
      eye.classList.remove('fa-eye-slash');
      eye.classList.add('fa-eye');
    }
  }

  /* ---------- Handle Login ---------- */
  function handleLogin(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const loader = document.getElementById('loader');

    // Show loader
    btnText.classList.add('hidden');
    loader.classList.remove('hidden');
    btn.disabled = true;

    // Simulate auth
    setTimeout(() => {
      // Save remember preference
      if (document.getElementById('remember').checked) {
        localStorage.setItem('gwcl_remember', document.getElementById('email').value);
      } else {
        localStorage.removeItem('gwcl_remember');
      }

      // Redirect (replace with your dashboard URL)
      window.location.href = 'vehicle-manage.html'; // ← change to your route
    }, 1500);
  }

  /* ---------- Auto-fill remembered email ---------- */
  window.addEventListener('DOMContentLoaded', () => {
    const remembered = localStorage.getItem('gwcl_remember');
    if (remembered) {
      document.getElementById('email').value = remembered;
      document.getElementById('remember').checked = true;
    }
  });
</script>
</body>
</html>
