<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Maintenance') | Ghana Water Limited</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind + Fonts + Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', 'Roboto', sans-serif; }
        body { background: #f4f4f4; color: #202124; }
        .google-card { background: #ffffff; border: 1px solid #e8eaed; border-radius: 8px; box-shadow: 0 1px 2px 0 rgba(60,64,67,.3), 0 1px 3px 1px rgba(60,64,67,.15); }
    </style>
</head>
<body class="text-gray-800 antialiased">
    @yield('content')

    <!-- AI SUPPORT CHAT BOT -->
    @include('components.ai-chat-bot')
</body>
</html>
