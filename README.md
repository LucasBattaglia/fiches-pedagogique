# 📚 Fiches Pédagogiques

Application web PHP/PostgreSQL pour créer, organiser et partager des fiches pédagogiques (séquence, séance, situation) alignées avec les programmes officiels français — rentrée 2025.

---

## Fonctionnalités

- **Fiches de séquence** : titre, domaine, cycle/classe, tâche finale, objectifs, programme officiel
- **Fiches de séance** : déroulement (tableau enseignant/élèves), compétences, critères, variables didactiques
- **Fiches de situation** : objectifs moteur/cognitif/socio-affectif, dispositif, variables d'évolution
- **Hiérarchie imbriquée** : Séquence → Séances → Situations
- **Export PDF** complet (séquence + toutes ses séances/situations)
- **Programmes officiels 2025** intégrés (C1, C2, C3) avec sélection interactive des compétences
- **Authentification** email+mot de passe ou Google OAuth
- **Partage public** des fiches + duplication (fork)
- **Explorer** les fiches publiques de la communauté

---

## Prérequis

- PHP 8.1+
- PostgreSQL 13+
- Composer
- Extension PHP : `pdo_pgsql`, `curl`, `mbstring`

---

## Installation

### 1. Cloner / dézipper le projet

```bash
cd /var/www/html
unzip fiches-pedagogiques.zip
cd fiches-pedagogiques
```

### 2. Installer les dépendances PHP

```bash
composer install --no-dev
```

### 3. Créer la base de données PostgreSQL

```bash
psql -U postgres -c "CREATE DATABASE fiches_pedagogiques;"
psql -U postgres -d fiches_pedagogiques -f sql/init.sql
```

### 4. Configurer les variables d'environnement

```bash
cp .env.example .env
nano .env
```

Renseigner :
```
DB_HOST=localhost
DB_PORT=5432
DB_NAME=fiches_pedagogiques
DB_USER=postgres
DB_PASSWORD=votre_mdp

APP_URL=https://votre-domaine.fr
APP_SECRET=une_cle_aleatoire_longue

# OAuth Google (optionnel)
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxx
```

### 5. Charger les variables d'environnement

Ajouter dans votre `index.php` (ligne 1) ou dans votre VirtualHost Apache :

```php
// Pour développement local avec un fichier .env :
// composer require vlucas/phpdotenv
// puis ajouter dans index.php avant la config :
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();
```

Ou directement dans Apache VirtualHost :
```apache
SetEnv DB_HOST localhost
SetEnv DB_NAME fiches_pedagogiques
...
```

### 6. Configurer Apache

```apache
<VirtualHost *:80>
    ServerName fiches.monecole.fr
    DocumentRoot /var/www/html/fiches-pedagogiques
    
    <Directory /var/www/html/fiches-pedagogiques>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/fiches-error.log
    CustomLog ${APACHE_LOG_DIR}/fiches-access.log combined
</VirtualHost>
```

Activer mod_rewrite :
```bash
a2enmod rewrite
systemctl restart apache2
```

### 7. (Optionnel) Google OAuth

1. Aller sur [console.cloud.google.com](https://console.cloud.google.com)
2. Créer un projet → Identifiants → ID client OAuth 2.0
3. Type : Application Web
4. URI de redirection autorisée : `https://votre-domaine.fr/auth/google/callback`
5. Copier Client ID et Client Secret dans `.env`

---

## Structure du projet

```
fiches-pedagogiques/
├── index.php                  ← Routeur principal
├── composer.json
├── .htaccess                  ← Réécriture URL Apache
├── .env.example               ← Variables d'environnement (modèle)
├── config/
│   └── config.php             ← Chargement de la config
├── sql/
│   └── init.sql               ← Schéma BDD + données programmes
├── src/
│   ├── DAO/
│   │   ├── ConnectionPool.php ← Connexion PDO PostgreSQL (singleton)
│   │   ├── UserDAO.php        ← Gestion utilisateurs
│   │   ├── ProgrammeDAO.php   ← Programmes officiels
│   │   ├── SequenceDAO.php    ← Fiches de séquence
│   │   ├── SeanceDAO.php      ← Fiches de séance
│   │   └── SituationDAO.php   ← Fiches de situation
│   └── Service/
│       ├── AuthService.php    ← Sessions, OAuth Google
│       └── PdfService.php     ← Export PDF (FPDF)
├── resources/
│   ├── static/
│   │   ├── css/app.css        ← Styles (Playfair Display + Source Sans 3)
│   │   └── js/app.js          ← Interactions dynamiques
│   └── views/
│       ├── home.php
│       ├── dashboard.php
│       ├── explorer.php
│       ├── programmes.php
│       ├── profil.php
│       ├── partials/
│       │   ├── layout_start.php
│       │   └── layout_end.php
│       ├── auth/
│       │   ├── login.php
│       │   └── register.php
│       ├── sequence/
│       │   ├── form.php       ← Création / édition
│       │   ├── index.php      ← Liste
│       │   └── show.php       ← Visualisation complète
│       ├── seance/
│       │   └── form.php       ← Création / édition
│       ├── situation/
│       │   └── form.php       ← Création / édition
│       └── errors/
│           └── 404.php
└── vendor/                    ← Dépendances Composer (après install)
```

---

## Programmes intégrés (rentrée 2025)

| Cycle | Matière | Statut |
|-------|---------|--------|
| C1 | Langage oral et écrit | 🟢 Nouveau 2025 |
| C1 | Premiers outils mathématiques | 🟢 Nouveau 2025 |
| C1 | Autres domaines | ⚪ Inchangé 2021 |
| C2 | Français (CP/CE1/CE2) | 🟢 Nouveau 2025 |
| C2 | Mathématiques (CP/CE1/CE2) | 🟢 Nouveau 2025 |
| C2 | EMC CE1 | 🟢 Nouveau 2025 |
| C2 | EVAR (CP/CE1/CE2) | 🟢 Nouveau 2025 |
| C3 | Français CM1, 6e | 🟢 Nouveau 2025 |
| C3 | Mathématiques CM1, 6e | 🟢 Nouveau 2025 |
| C3 | EMC CM2 | 🟢 Nouveau 2025 |
| C3 | EVAR CM1/CM2 | 🟢 Nouveau 2025 |
| C3 | LVE 6e | 🟢 Nouveau 2025 |
| C3 | EPS, Histoire-Géo, Sciences… | ⚪ Inchangé |

---

## Routes principales

| Route | Description |
|-------|-------------|
| `GET /` | Page d'accueil |
| `GET /dashboard` | Tableau de bord (auth) |
| `GET /sequence/create` | Nouvelle séquence |
| `GET /sequence/{id}` | Voir une séquence |
| `GET /sequence/{id}/edit` | Modifier une séquence |
| `GET /sequence/{id}/pdf` | Export PDF complet |
| `POST /sequence/{id}/fork` | Dupliquer une séquence |
| `GET /seance/create?sequence_id=X` | Nouvelle séance |
| `GET /seance/{id}/pdf` | PDF séance |
| `GET /situation/create?seance_id=X` | Nouvelle situation |
| `GET /situation/{id}/pdf` | PDF situation |
| `GET /explorer` | Fiches publiques |
| `GET /programmes` | Référentiel des programmes |
| `GET /auth/login` | Connexion |
| `GET /auth/register` | Inscription |
| `GET /auth/google` | OAuth Google |
| `GET /profil` | Mon profil |

---

## Licence

Projet libre — libre utilisation et modification pour usage éducatif.
