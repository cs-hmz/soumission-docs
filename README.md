# Plateforme Étudiante de Soumission de Documents

Application web sécurisée — HTML5, CSS3, PHP, MySQL, AJAX, PHPMailer, mPDF.

## Structure du projet

```
project/
├── accueil.php           → Page d'accueil
├── register.php          → Inscription étudiant
├── verify_code.php       → Activation AJAX du compte
├── create_password.php   → Création du mot de passe
├── login.php             → Connexion (étudiant + admin)
├── dashboard.php         → Espace étudiant (upload + PDF)
├── upload.php            → Traitement upload AJAX
├── generate_pdf.php      → PDF personnel étudiant
├── admin_dashboard.php   → Interface administrateur
├── admin_fichiers.php    → Fichiers d'un étudiant (admin)
├── admin_pdf.php         → PDF global (admin)
├── logout.php            → Déconnexion
├── db.php                → Connexion PDO
├── database.sql          → Script SQL
├── composer.json         → Dépendances
├── css/style.css         → Styles
├── js/main.js            → Scripts AJAX
├── uploads/              → Fichiers uploadés (créé auto)
└── vendor/               → Bibliothèques (après composer install)
```

## Installation

### 1. Base de données

```bash
mysql -u root -p < database.sql
```

Ou importez `database.sql` dans phpMyAdmin.

Compte admin par défaut : `admin@example.com` / `Admin1234`

### 2. Dépendances PHP

```bash
composer install
```

Installe **mPDF** (génération PDF) et **PHPMailer** (envoi email).

### 3. Configuration

#### db.php — Base de données
```php
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_mot_de_passe');
```

#### register.php — Email SMTP (PHPMailer)
```php
$mail->Host     = 'smtp.gmail.com';
$mail->Username = 'votre@gmail.com';
$mail->Password = 'votre_app_password';
```

> Pour Gmail : activez la validation 2 étapes, puis générez un "App Password" dans les paramètres Google.

### 4. Dossier uploads

```bash
mkdir uploads
chmod 755 uploads
```

### 5. Serveur local (XAMPP/WAMP/MAMP)

Placez le dossier `project/` dans `htdocs/` (XAMPP) ou `www/` (WAMP).

Accédez à : `http://localhost/project/`

---

## Flux utilisateur

```
register.php → email avec code → verify_code.php (AJAX)
→ create_password.php → login.php → dashboard.php
```

## Sécurité implémentée

- Requêtes PDO préparées (protection injection SQL)
- `password_hash` / `password_verify` (BCRYPT)
- Vérification MIME réelle des fichiers uploadés
- `session_regenerate_id()` après connexion
- `htmlspecialchars()` sur toutes les sorties HTML
- Contrôle de rôle sur chaque page protégée