<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>GWCL | Reset Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  <style>
    /* Glass-morphism */
    .glass {
      background: rgba(255,255,255,.15);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,.2);
    }

    /* Animated gradient background */
    .gradient-bg {
      background: linear-gradient(-45deg,#0ea5e9,#3b82f6,#6366f1,#06b6d4);
      background-size: 400% 400%;
      animation: gradient 15s ease infinite;
    }

    @keyframes gradient {
      0% { background-position: 0% 50% }
      50% { background-position: 100% 50% }
      100% { background-position: 0% 50% }
    }

    /* Fade-in animation */
    .fade-in {
      animation: fadeIn .8s ease forwards;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center p-4 text-white fade-in">

<!-- RESET PASSWORD CARD -->
<div class="w-full max-w-md glass rounded-2xl shadow-2xl p-8 space-y-6">

  <!-- Logo / Title -->
  <div class="text-center">
    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg">
      <i class="fas fa-lock text-3xl text-cyan-300"></i>
    </div>
    <h1 class="text-2xl font-bold">Reset Password</h1>
    <p class="text-sm text-white/70">Create a new secure password</p>
  </div>

  <!-- FORM -->
  <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
    @csrf

    <!-- Token -->
    <input type="hidden" name="token" value="{{ $token }}">

    <!-- Email -->
    <div>
      <label class="block text-sm font-medium mb-1">Email</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-white/60">
          <i class="fas fa-envelope"></i>
        </span>
        <input
          type="email"
          name="email"
          value="{{ $email }}"
          required
          readonly
          class="w-full pl-10 pr-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none"
        >
      </div>
      @error('email')
        <p class="text-red-300 text-sm">{{ $message }}</p>
      @enderror
    </div>

    <!-- New Password -->
    <div>
      <label class="block text-sm font-medium mb-1">New Password</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-white/60">
          <i class="fas fa-key"></i>
        </span>
        <input
          type="password"
          name="password"
          required
          class="w-full pl-10 pr-4 py-2 bg-white/10 border border-white/20 rounded-lg placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white"
        >
      </div>
      @error('password')
        <p class="text-red-300 text-sm">{{ $message }}</p>
      @enderror
    </div>

    <!-- Confirm Password -->
    <div>
      <label class="block text-sm font-medium mb-1">Confirm Password</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-white/60">
          <i class="fas fa-check-circle"></i>
        </span>
        <input
          type="password"
          name="password_confirmation"
          required
          class="w-full pl-10 pr-4 py-2 bg-white/10 border border-white/20 rounded-lg placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white"
        >
      </div>
    </div>

    <!-- Submit -->
    <button
      type="submit"
      class="w-full bg-cyan-400 hover:bg-cyan-300 text-slate-900 font-semibold py-2.5 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl"
    >
      Reset Password
    </button>
  </form>

  <!-- Footer -->
  <p class="text-center text-xs text-white/60">
    © 2024 Ghana Water Company Limited. All rights reserved.
  </p>

</div>
@include('components.ai-chat-bot')
</body>
</html>
