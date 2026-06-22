<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>GWCL | Join the Asset Portal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .glass{background:rgba(255,255,255,.15);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.2);}
    .gradient-bg{background:linear-gradient(-45deg,#0ea5e9,#3b82f6,#6366f1,#06b6d4);background-size:400% 400%;animation:gradient 15s ease infinite;}
    @keyframes gradient{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
    .fade-in{animation:fadeIn .8s ease forwards;}
    @keyframes fadeIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
  </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4 text-white fade-in">

<div class="w-full max-w-lg glass rounded-2xl shadow-2xl p-8 space-y-6">
  <div class="text-center">
    <h1 class="text-3xl font-bold">Create Account</h1>
    <p class="text-sm text-white/70 mt-1">Join the GWCL learning ecosystem</p>
  </div>

  <form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Name -->
        <div class="space-y-1">
          <label for="name" class="block text-xs font-medium uppercase tracking-wider">Full Name</label>
          <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                 class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white placeholder-white/30" placeholder="John Doe"/>
          @error('name') <p class="text-red-300 text-[10px]">{{ $message }}</p> @enderror
        </div>

        <!-- Role (Hidden or selectable based on requirements, here we allow selection for simulation) -->
        <div class="space-y-1">
          <label for="role" class="block text-xs font-medium uppercase tracking-wider">Role</label>
          <select id="role" name="role" class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white">
              <option value="student" class="text-slate-900">Student</option>
              <option value="driver" class="text-slate-900">Driver</option>
              <option value="technician" class="text-slate-900">Technician</option>
          </select>
        </div>
    </div>

    <!-- Email -->
    <div class="space-y-1">
      <label for="email" class="block text-xs font-medium uppercase tracking-wider">Email Address</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required
             class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white placeholder-white/30" placeholder="john@example.com"/>
      @error('email') <p class="text-red-300 text-[10px]">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Password -->
        <div class="space-y-1">
          <label for="password" class="block text-xs font-medium uppercase tracking-wider">Password</label>
          <input type="password" id="password" name="password" required
                 class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white"/>
          @error('password') <p class="text-red-300 text-[10px]">{{ $message }}</p> @enderror
        </div>

        <!-- Confirm Password -->
        <div class="space-y-1">
          <label for="password_confirmation" class="block text-xs font-medium uppercase tracking-wider">Confirm</label>
          <input type="password" id="password_confirmation" name="password_confirmation" required
                 class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-300 text-white"/>
        </div>
    </div>

    <button type="submit" class="w-full bg-cyan-400 hover:bg-cyan-300 text-slate-900 font-bold py-3 rounded-lg transition-all shadow-lg mt-4">
      Create Account
    </button>

    <p class="text-center text-sm text-white/60">
      Already have an account? <a href="{{ route('login') }}" class="text-cyan-300 hover:underline">Log in</a>
    </p>
  </form>
</div>
</body>
</html>
