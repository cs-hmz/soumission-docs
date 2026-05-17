// ============================================
// main.js — Scripts AJAX de la plateforme
// ============================================

/**
 * Activation du compte via AJAX (verify_code.php)
 */
function activerCompte() {
    const email = document.getElementById('email')?.value.trim();
    const code  = document.getElementById('code')?.value.trim();
    const msgEl = document.getElementById('message');

    if (!email || !code) {
        afficherMessage(msgEl, 'Veuillez remplir tous les champs.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('email', email);
    formData.append('code', code);

    fetch('verify_code.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            afficherMessage(msgEl, data.message, data.success ? 'success' : 'error');
            if (data.success && data.redirect) {
                setTimeout(() => { window.location.href = data.redirect; }, 1500);
            }
        })
        .catch(() => afficherMessage(msgEl, 'Erreur réseau. Réessayez.', 'error'));
}

/**
 * Upload de fichier via AJAX (dashboard.php)
 */
function uploaderFichier() {
    const input  = document.getElementById('fichier');
    const msgEl  = document.getElementById('upload-message');

    if (!input || !input.files.length) {
        afficherMessage(msgEl, 'Veuillez sélectionner un fichier.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('fichier', input.files[0]);

    fetch('upload.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            afficherMessage(msgEl, data.message, data.success ? 'success' : 'error');
            if (data.success) {
                input.value = '';
                setTimeout(() => window.location.reload(), 1200);
            }
        })
        .catch(() => afficherMessage(msgEl, 'Erreur réseau. Réessayez.', 'error'));
}

/**
 * Affiche un message stylisé dans un conteneur
 */
function afficherMessage(el, texte, type) {
    if (!el) return;
    el.className = 'alert alert-' + (type === 'success' ? 'success' : 'error');
    el.textContent = texte;
    el.style.display = 'block';
}