
# GSB‑FRAIS

Application web de gestion des frais pour le laboratoire **Galaxy Swiss Bourdin (GSB)**.

---

## 🧭 Contexte

GSB est une enseigne pharmaceutique fictive issue de la fusion des laboratoires Galaxy (virus, VIH, hépatites) et Swiss Bourdin (médicaments classiques).  
L’application permet aux visiteurs médicaux de remonter leurs frais engagés (déplacements, hébergement, repas), puis aux comptables de les valider et de suivre les remboursements.

---

## ⚙️ Fonctionnalités

### Pour le visiteur médical
- Authentification sécurisée (connexion/déconnexion).  
- Création, consultation, modification et suppression de fiches de frais, tant qu’elles ne sont pas validées.

### Pour le comptable
- Visualisation des fiches de frais soumises.  
- Validation / rejet et suivi des remboursements.

---

## 🛠️ Architecture & technologies

- Langages : **PHP**, avec potentiellement **Twig** pour les vues.  
- Framework : Symfony (d’après structure similaire), ou implémentation MVC.  
- Base de données : **MariaDB** / **MySQL**.  
- Conteneurisation : Docker + docker-compose (conteneurs `www`, `db`, `cron`).

---

## 🚀 Installation

1. Cloner le dépôt :
   ```bash
   git clone https://github.com/WhiteSwen/GSB-FRAIS.git
   cd GSB-FRAIS
   ```

2. (Optionnel) Construction via Docker :
   ```bash
   docker-compose up -d --build
   ```

3. Initialisation :
   ```bash
   chmod +x script/start.sh
   ./script/start.sh
   ```

4. Accès :  
   Rendez-vous sur `http://localhost:9973` dans votre navigateur.

5. (Facultatif) Repeupler la base de données :
   ```bash
   ./script/fixtures.sh
   ```

---

## 📚 Documentation

- **Documentation utilisateur** et **technique** à fournir dans `docs/` (non inclus actuellement).  
- Consultez les exemples de fonctionnement (fiches, paiements...) dans les fichiers PHP du répertoire racine (`cAccueil.php`, `cConsultFichesFrais.php`, `cSuivrePaiementFichesFrais.php`).  
- Voir la documentation d’un projet similaire pour inspiration.

---

## ✅ Pré-requis

- [ ] Docker & docker-compose ou PHP + Symfony + MariaDB installés  
- [ ] Composer (si utilisation sans Docker)  
- [ ] Permissions d’exécution sur `script/start.sh`

---

## 📝 Utilisation

1. Démarrer l’application (Docker ou serveur Symfony).  
2. Créer un compte visiteur.  
3. Ajouter/modifier vos frais (repas, transport, hébergement).  
4. Vous connecter en tant que comptable pour valider/rechercher les fiches.

---

## 🧑‍💻 Contribuer

- Forkez le projet.  
- Créez une branche descriptive : `feature/fonctionnalité` ou `bugfix/description`.  
- Soumettez une Pull Request.

---

## 📄 Licence

À définir (par défaut, suivre la licence d’un projet similaire : MIT ou GPL‑3.0 si souhaité).

---

## 🎯 À venir (idées)

- Module de reporting/suivi des remboursements.  
- Alertes automatisées via `cron`.  
- Tests unitaires via PHPUnit ou intégrés dans Symfony.

---

### 🔗 Références / Projets similaires

- [AladdineDev/GSB‑Frais](https://github.com/AladdineDev/GSB-Frais)  
- [ElMehdiElJamali/GSB‑Frais](https://github.com/elmehdieljamali/GSB-Frais)
