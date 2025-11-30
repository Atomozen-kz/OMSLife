@extends('pickup.layout')

@section('title', 'Вход в пункт выдачи')

@section('content')
    <div class="login-container">
        <h2>Вход</h2>

        @if($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('pickup.login') }}" method="POST" class="login-form">
            @csrf
            <div class="form-group">
                <label for="username">Логин:</label>
                <input type="text" name="username" id="username" value="{{ old('username') }}" required>
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" name="password" id="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Войти</button>
        </form>
    </div>
@endsection
