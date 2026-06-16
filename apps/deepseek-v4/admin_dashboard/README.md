# Fleet Employee Dashboard

Application FastAPI de gestion d'employés avec authentification JWT, rôles, CRUD, documents PDF, export CSV, statistiques et API externe.

## Prérequis
- Python 3.9+
- pip

## Installation

1. **Créer un environnement virtuel (recommandé)**
   ```bash
   python -m venv venv
   source venv/bin/activate  # Linux/Mac
   venv\Scripts\activate     # Windows
   ```

2. **Installer les dépendances**
   ```bash
   pip install -r requirements.txt
   ```

3. **Créer le premier administrateur**
   ```bash
   python create_admin.py
   ```
   Cela crée un utilisateur `admin` / `admin123`.

4. **Lancer l'application**
   ```bash
   uvicorn main:app --reload
   ```
   L'API sera accessible sur http://127.0.0.1:8000.
   Documentation interactive : http://127.0.0.1:8000/docs

## Utilisation

### Authentification
- `POST /auth/login` avec `{"username":"...", "password":"..."}` → reçoit un token JWT.
- Utilisez le token dans l'en-tête `Authorization: Bearer <token>`.

### Rôles et permissions
| Action                | Admin | Manager (dépt) | Employé (profil) |
|-----------------------|-------|----------------|------------------|
| Lister employés       | tous  | son département| lui-même        |
| Créer employé         | tous  | son département (role employé) | ❌   |
| Lire/modifier employé | tous  | son département (pas admins) | son propre profil (sans changer rôle/dépt) |
| Supprimer employé     | tous  | ❌             | ❌              |
| Gérer clés API        | oui   | ❌             | ❌              |
| Export CSV            | oui   | son département| ❌              |
| Statistiques          | oui   | oui            | ❌              |

### Documents PDF
- `POST /employees/{id}/documents` : téléverser un PDF (multipart).
- `GET /employees/{id}/documents` : lister les documents.
- `GET /employees/{id}/documents/{doc_id}` : télécharger un PDF.

### Export CSV
- `GET /export/employees/csv?department=IT` (admin/manager).

### Statistiques
- `GET /statistics/employees/by-department` → image PNG (bar chart).

### API externe
- `GET /external/employees` avec en-tête `X-API-Key: <votre_clé>`.
- Gestion des clés : `POST /admin/api-keys/` (admin).

## Structure du projet
```
.
├── main.py
├── database.py
├── models.py
├── schemas.py
├── dependencies.py
├── routers/
│   ├── __init__.py
│   ├── auth.py
│   ├── employees.py
│   ├── documents.py
│   ├── statistics.py
│   ├── export.py
│   ├── external.py
│   └── api_keys.py
├── requirements.txt
├── create_admin.py
└── README.md
```