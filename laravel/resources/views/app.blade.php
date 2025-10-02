<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Timetable App</title>

    <!-- Vite: Loads our CSS/JS bundle (Vue + Router) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Vue Mount Point: App.vue renders here (nav + <router-view>) -->
    <div id="app"></div>
</body>
</html>
