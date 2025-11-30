<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Pickup')</title>
    <link rel="stylesheet" href="{{ asset('css/pickup.css') }}">
</head>
<body>

<div class="container">
    <header class="header">
        <h1>Пункт выдачи</h1>

        <!-- Кнопка «Выйти» в шапке справа -->
        <form action="{{ route('pickup.logout') }}" method="POST" class="logout-form-header">
            @csrf
            <button type="submit" class="btn btn-secondary">Выйти</button>
        </form>
    </header>

    <main class="main">
        @yield('content')
    </main>

    <footer class="footer">
        <p>© 2025, OMG life</p>
    </footer>
</div>

</body>
</html>
