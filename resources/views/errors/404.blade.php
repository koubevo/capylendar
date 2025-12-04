<!DOCTYPE html>
<html lang="cs" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 | Capylendar</title>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="h-full flex items-center justify-center bg-gray-50 dark:bg-gray-950 antialiased font-sans">

<div class="max-w-lg w-full px-6 py-12 text-center">
    <div class="relative mx-auto w-40 h-40 mb-6">
        <div class="absolute inset-0 bg-yellow-100 dark:bg-yellow-900/30 rounded-full animate-pulse"></div>
        <img
            src="{{url('/images/capys/pink.jpg')}}"
            alt="Zmatená kapybara"
            class="relative w-full h-full object-contain drop-shadow-xl hover:rotate-6 transition-transform duration-300 rounded-full"
        >
    </div>

    <p class="text-sm font-bold text-pink-500 uppercase tracking-widest mb-2">404 Error</p>

    <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight sm:text-5xl mb-8">
        Tuhle stránku asi snědla kapybara.
    </h1>

    <div class="flex justify-center gap-4">
        <a
            href="/"
            class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-pink-500 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
        >
            Zpět do bezpečí
        </a>
    </div>
</div>

</body>
</html>
