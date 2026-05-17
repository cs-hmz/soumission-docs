(function () {
    'use strict';

    function showMessage(type, text, timeout = 4000) {
        let container = document.getElementById('app-messages');
        if (!container) {
            container = document.createElement('div');
            container.id = 'app-messages';
            container.style.position = 'fixed';
            container.style.top = '16px';
            container.style.right = '16px';
            container.style.zIndex = 10000;
            document.body.appendChild(container);
        }

        const el = document.createElement('div');
        el.className = 'app-message ' + (type === 'success' ? 'app-success' : 'app-error');
        el.textContent = text;
        el.style.marginTop = '8px';
        el.style.padding = '10px 14px';
        el.style.borderRadius = '10px';
        el.style.boxShadow = '0 6px 18px rgba(0,0,0,0.08)';
        el.style.background = type === 'success' ? '#ecfdf5' : '#fff1f2';
        el.style.color = type === 'success' ? '#065f46' : '#991b1b';
        el.style.border = type === 'success' ? '1px solid rgba(16,185,129,0.15)' : '1px solid rgba(239,68,68,0.12)';
        el.style.cursor = 'pointer';

        el.addEventListener('click', function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        });

        container.appendChild(el);

        if (timeout > 0) {
            setTimeout(() => {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, timeout);
        }
    }

    async function verifyActivation(email, code) {
        try {
            const fd = new FormData();
            fd.append('email', email);
            fd.append('code_activation', code);

            const res = await fetch('verify_code.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd,
            });

            if (!res.ok) {
                showMessage('error', 'Erreur réseau lors de la vérification.');
                return { status: 'error' };
            }

            const data = await res.json();
            if (data.status === 'success') {
                showMessage('success', 'Code vérifié — compte activé.');
            } else {
                showMessage('error', 'Code invalide ou compte introuvable.');
            }
            return data;
        } catch (err) {
            showMessage('error', 'Erreur lors de la vérification : ' + err.message);
            return { status: 'error' };
        }
    }

    async function uploadFile(file) {
        try {
            const fd = new FormData();
            fd.append('file', file);

            const res = await fetch('upload.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd,
            });

            if (!res.ok) {
                showMessage('error', 'Erreur réseau lors de l\'upload.');
                return { status: 'error' };
            }

            const json = await res.json();
            if (json.status === 'success') {
                showMessage('success', json.message || 'Fichier téléversé.');
                return json;
            }

            showMessage('error', json.message || 'Erreur lors du téléversement.');
            return json;
        } catch (err) {
            showMessage('error', 'Erreur lors du téléversement : ' + err.message);
            return { status: 'error' };
        }
    }

    // Auto-bind activation forms (supports forms with data-verify-activation attribute)
    function bindActivationForms() {
        const forms = document.querySelectorAll('form[data-verify-activation]');
        forms.forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = (form.querySelector('input[name="email"]') || {}).value || '';
                const code = (form.querySelector('input[name="code_activation"], input[name="code"]') || {}).value || '';
                if (!email || !code) {
                    showMessage('error', 'Merci de renseigner l\'email et le code.');
                    return;
                }
                const result = await verifyActivation(email, code);
                if (result.status === 'success') {
                    // Optionally disable the form
                    form.querySelectorAll('input, button').forEach(i => i.disabled = true);
                }
            });
        });
    }

    // Auto-bind upload forms: look for forms with data-ajax-upload or input[name=document]
    function bindUploadForms() {
        const forms = document.querySelectorAll('form[data-ajax-upload], form');
        forms.forEach(form => {
            const fileInput = form.querySelector('input[type="file"]');
            if (!fileInput) return;
            // If form has attribute data-native-upload allow normal submit
            if (form.hasAttribute('data-native-upload')) return;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const file = fileInput.files && fileInput.files[0];
                if (!file) {
                    showMessage('error', 'Aucun fichier sélectionné.');
                    return;
                }
                const res = await uploadFile(file);
                if (res && res.status === 'success') {
                    // If dashboard contains a table/list, attempt to refresh it by reloading the page fragment
                    // Minimal approach: reload page to show new file (optional)
                    if (form.dataset.refreshOnSuccess === 'true') {
                        window.location.reload();
                    }
                }
            });
        });
    }

    // Expose utilities globally for manual use
    window.app = window.app || {};
    window.app.verifyActivation = verifyActivation;
    window.app.uploadFile = uploadFile;
    window.app.showMessage = showMessage;

    // Initialize bindings on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        bindActivationForms();
        bindUploadForms();
    });

})();
