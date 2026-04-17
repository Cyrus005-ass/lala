# FINOVA — Site de financement non remboursable
## Guide d'installation complet

---

## 🗂️ Structure des fichiers

```
banksite/
├── index.php              ← Page d'accueil publique
├── apply.php              ← Formulaire de candidature
├── track.php              ← Suivi des candidatures
├── database.sql           ← Script SQL à importer en premier
├── includes/
│   └── config.php         ← ⚠️ À CONFIGURER EN PREMIER
├── assets/
│   ├── css/main.css
│   └── js/main.js
├── lang/
│   ├── fr.php             ← Français
│   ├── en.php             ← English
│   ├── es.php             ← Español
│   ├── ar.php             ← العربية (RTL)
│   ├── pt.php             ← Português
│   ├── zh.php             ← 中文
│   └── ru.php             ← Русский
├── api/
│   ├── submit_application.php
│   ├── track.php
│   └── contact.php
├── admin/
│   ├── login.php          ← Connexion admin
│   ├── index.php          ← Tableau de bord
│   ├── applications.php   ← Liste des candidatures
│   ├── application_detail.php ← Détail + actions
│   ├── programs.php       ← Gestion des programmes
│   ├── messages.php       ← Messages de contact
│   ├── settings.php       ← Paramètres du site
│   └── logout.php
└── uploads/               ← Créé automatiquement (documents)
```

---

## ⚙️ ÉTAPE 1 — Configuration

Ouvrez `includes/config.php` et modifiez :

```php
define('DB_HOST', 'localhost');      // Votre host MySQL
define('DB_NAME', 'finova_bank');    // Nom de la base
define('DB_USER', 'root');           // Votre utilisateur MySQL
define('DB_PASS', '');               // Votre mot de passe MySQL
define('SITE_URL', 'http://localhost/banksite'); // Votre URL
```

---

## 🗄️ ÉTAPE 2 — Base de données

1. Créez une base MySQL nommée `finova_bank`
2. Importez le fichier `database.sql` :
   - Via phpMyAdmin : Importer > Choisir le fichier
   - Via terminal : `mysql -u root -p finova_bank < database.sql`

---

## 🔐 ÉTAPE 3 — Premier accès Admin

URL : `http://votre-domaine.com/banksite/admin/login.php`

- **Identifiant :** `admin`
- **Mot de passe :** `Admin@2024!`

⚠️ **CHANGEZ CE MOT DE PASSE immédiatement** depuis l'admin > Administrateurs

---

## 🔑 ÉTAPE 4 — Changer le mot de passe admin

Dans phpMyAdmin ou via terminal :
```sql
UPDATE admins SET password_hash = '$2y$12$VOTRE_HASH' WHERE username = 'admin';
```

Générez le hash avec PHP :
```php
echo password_hash('VotreNouveauMotDePasse', PASSWORD_BCRYPT);
```

---

## 📁 ÉTAPE 5 — Dossier uploads

Créez le dossier `uploads/` à la racine et donnez les permissions :
```bash
mkdir uploads
chmod 755 uploads
```

---

## ✏️ CE QUE VOUS DEVEZ COMPLÉTER

### Dans l'admin (http://votre-site/admin/) :

#### 1. Paramètres (settings.php)
- ✅ Nom de l'organisation
- ✅ Slogans dans toutes les langues
- ✅ Couleurs (fond bleu marine + or)
- ✅ Email de contact
- ✅ Téléphone
- ✅ Adresse physique
- ✅ Compteurs (total financé, organisations, pays)

#### 2. Programmes (programs.php)
- ✅ Modifier les 3 programmes d'exemple
- ✅ Ajouter vos vrais programmes
- ✅ Compléter les traductions dans toutes les langues
- ✅ Définir les montants min/max
- ✅ Définir les dates limites

#### 3. FAQ (faq.php)
- ✅ Modifier/ajouter les questions fréquentes
- ✅ Les ajouter dans toutes les langues

### Dans les fichiers de langue (lang/*.php) :
- ✅ Tous les textes sont déjà traduits
- ✅ Personnalisez le nom FINOVA par le vôtre
- ✅ Adaptez les descriptions à votre organisation

---

## 🌍 Langues disponibles

| Code | Langue       | RTL |
|------|-------------|-----|
| fr   | Français     | Non |
| en   | English      | Non |
| es   | Español      | Non |
| ar   | العربية      | Oui |
| pt   | Português    | Non |
| zh   | 中文          | Non |
| ru   | Русский      | Non |

Pour changer la langue par défaut, modifiez dans `config.php` :
```php
define('DEFAULT_LANG', 'fr');
```

---

## 🛡️ Sécurité — Points importants

1. **Changez le mot de passe admin** immédiatement
2. **Protégez le dossier `/admin/`** avec un `.htaccess` si possible
3. **Sauvegardez régulièrement** la base de données
4. **HTTPS obligatoire** en production (certificat SSL)
5. **Limitez les IPs** autorisées à accéder à `/admin/` si possible

### .htaccess recommandé pour /admin/ :
```apache
Options -Indexes
<FilesMatch "\.php$">
  Order Allow,Deny
  Allow from VOTRE_IP
  Deny from all
</FilesMatch>
```

---

## 📧 Configuration email (optionnel)

Pour envoyer des emails de confirmation, modifiez `api/submit_application.php` et ajoutez :
```php
// Après l'insertion réussie
mail($contact_email, 'Confirmation - Ref: '.$reference, $emailBody, $headers);
```

Ou intégrez PHPMailer/SendGrid pour une meilleure délivrabilité.

---

## 📞 Support

Pour toute question sur l'installation, référez-vous à la documentation ou contactez votre développeur.

---

*Généré par FINOVA Site Builder — Version 1.0*
