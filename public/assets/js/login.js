document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('login-form');
    const loginButton = document.getElementById('login-button');

    if (!loginForm || !loginButton) {
        return;
    }

    loginForm.addEventListener('submit', function () {
        loginButton.disabled = true;
        loginButton.textContent = 'Pruefe Login...';
    });
});
