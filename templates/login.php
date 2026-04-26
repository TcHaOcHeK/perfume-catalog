<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Авторизация и регистрация в парфюмерном каталоге">
    <title>Вход в систему | Парфюмерный каталог</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="img/favicon.svg">
</head>
<body class="login-page">
<main class="login-container">
    <div class="login-logo">
        <img src="../public/assets/img/logo.svg" alt="Логотип парфюмерного каталога" class="logo-image">
    </div>

    <div class="login-card">
        <form class="login-form" action="auth.php" method="POST" id="loginForm">
            <div class="form-group">
                <label for="login" class="form-label">Login</label>
                <input
                    type="text"
                    id="login"
                    name="login"
                    class="form-input"
                    required
                    autocomplete="username"
                    aria-required="true"
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    required
                    autocomplete="current-password"
                    aria-required="true"
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Register/Log in</button>
            </div>
        </form>
    </div>
</main>

<script src="js/login.js"></script>
</body>
</html>