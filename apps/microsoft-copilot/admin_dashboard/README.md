# Fleet Admin Dashboard (FastAPI + SQLite)

## Description
Application de gestion d'une flotte d'employés avec :
- Authentification JWT
- Trois rôles : employe, manager, administrateur
- CRUD complet sur les employés
- Téléversement / téléchargement de documents PDF par employé
- Export CSV filtré par département
- Endpoint de statistiques retournant un PNG (matplotlib)
- API externe protégée par clé API

## Installation
1. Créez et activez un environnement virtuel
   - Linux / macOS:
     ```bash
     python3 -m venv venv
     source venv/bin/activate
     ```
   - Windows:
     ```powershell
     python -m venv venv
     venv\Scripts\activate
     ```

2. Installez les dépendances
   ```bash
   pip install -r requirements.txt
