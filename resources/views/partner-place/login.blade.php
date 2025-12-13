@extends('partner-place.layout')

@section('title', 'Вход в партнёрский кабинет')

@section('content')
    <div class="login-container">
        <h2>Вход</h2>

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('partner-place.login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label">Логин</label>
                <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Войти</button>
        </form>
    </div>
@endsection

