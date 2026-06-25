<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Maintenance')</title>
</head>
<body>
    @yield('content')

    <!-- AI SUPPORT CHAT BOT -->
    @include('components.ai-chat-bot')
</body>
</html>

