import 'bootstrap';

const applyTheme = (theme) => {
    const body = document.body;
    const options = document.querySelectorAll('[data-theme-option]');

    body.dataset.theme = theme;

    options.forEach((button) => {
        const isActive = button.dataset.themeOption === theme;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-pressed', String(isActive));
    });

    localStorage.setItem('dapurHarianTheme', theme);
};

const preferredTheme = localStorage.getItem('dapurHarianTheme') || 'luxury';

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(preferredTheme);

    document.querySelectorAll('[data-theme-option]').forEach((button) => {
        button.addEventListener('click', () => applyTheme(button.dataset.themeOption));
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const passwordInput = button.parentElement.querySelector('[data-password-input]');
            const isVisible = passwordInput.type === 'text';

            passwordInput.type = isVisible ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!isVisible));
            button.setAttribute('aria-label', isVisible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
            button.innerHTML = `<i class="bi bi-eye${isVisible ? '' : '-slash'}" aria-hidden="true"></i>`;
        });
    });

    document.querySelectorAll('[data-admin-login-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const submitButton = form.querySelector('[data-admin-submit]');
            const submitLabel = form.querySelector('[data-admin-submit-label]');
            const spinner = form.querySelector('[data-admin-submit-spinner]');
            const arrow = form.querySelector('[data-admin-submit-arrow]');
            const status = form.querySelector('[data-admin-submit-status]');

            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
            submitLabel.textContent = 'MEMPROSES...';
            spinner.classList.remove('d-none');
            arrow.classList.add('d-none');
            status.textContent = 'Sedang memproses login.';
        });
    });
});
