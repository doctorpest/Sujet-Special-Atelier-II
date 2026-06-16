# Employee Admin Dashboard API — FastAPI + SQLite

Application web API complète pour gérer une flotte d'employés avec FastAPI, SQLite, JWT, rôles, documents PDF, export CSV, statistiques PNG et accès API externe par clé API.

## 1. Installation

```bash
cd employee_admin_dashboard
python -m venv venv
```

### Windows

```bash
venv\Scripts\activate
```

### macOS / Linux

```bash
source venv/bin/activate
```

Installe les dépendances :

```bash
pip install -r requirements.txt
```

## 2. Lancement

```bash
uvicorn main:app --reload
```

Puis ouvre :

```text
http://127.0.0.1:8000/docs
```

## 3. Comptes créés automatiquement au premier lancement

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | admin@example.com | admin123 |
| Manager | manager@example.com | manager123 |
| Employé | employe@example.com | employe123 |

## 4. Authentification JWT

Endpoint :

```http
POST /auth/login
```

Body JSON :

```json
{
  "email": "admin@example.com",
  "password": "admin123"
}
```

La réponse retourne un token JWT :

```json
{
  "access_token": "...",
  "token_type": "bearer"
}
```

Dans Swagger, clique sur `Authorize` puis renseigne :

```text
Bearer TON_TOKEN
```

## 5. Permissions par rôle

| Fonction | Employé | Manager | Administrateur |
|---|---:|---:|---:|
| Voir les employés | Oui | Oui | Oui |
| Créer un employé | Non | Oui | Oui |
| Modifier un employé | Non | Oui | Oui |
| Supprimer un employé | Non | Non | Oui |
| Upload PDF | Non | Oui | Oui |
| Télécharger PDF | Oui | Oui | Oui |
| Export CSV | Non | Oui | Oui |
| Statistiques PNG | Non | Oui | Oui |
| Créer utilisateur | Non | Non | Oui |

## 6. API externe par clé API

Endpoint :

```http
GET /external/employees
```

Header requis :

```text
X-API-Key: change-this-api-key
```

Tu peux modifier la clé dans `app/config.py` ou via variable d'environnement.

## 7. Exemples d'endpoints

### Créer un employé

```http
POST /employees/
```

```json
{
  "first_name": "Ayoub",
  "last_name": "EL ANOUAR",
  "email": "ayoub@example.com",
  "department": "Finance",
  "position": "Chef Comptable",
  "salary": 25000,
  "is_active": true
}
```

### Filtrer par département

```http
GET /employees/?department=Finance
```

### Export CSV

```http
GET /employees/export/csv?department=Finance
```

### Statistiques PNG

```http
GET /stats/employees-by-department
```

```http
GET /stats/salary-by-department
```

### Upload document PDF

```http
POST /documents/{employee_id}/upload
```

Le champ fichier doit être nommé `file`.

### Télécharger document PDF

```http
GET /documents/{document_id}/download
```

## 8. Structure du projet

```text
employee_admin_dashboard/
│
├── main.py
├── requirements.txt
├── README.md
├── .env.example
├── uploads/
│
└── app/
    ├── __init__.py
    ├── config.py
    ├── database.py
    ├── models.py
    ├── schemas.py
    ├── security.py
    ├── dependencies.py
    └── routes/
        ├── __init__.py
        ├── auth.py
        ├── users.py
        ├── employees.py
        ├── documents.py
        ├── stats.py
        └── external.py
```

## 9. Notes importantes

- SQLite est utilisé pour simplifier le démarrage local.
- En production, change obligatoirement `SECRET_KEY` et `EXTERNAL_API_KEY`.
- Les mots de passe sont hashés avec bcrypt.
- Les documents acceptés sont uniquement des fichiers PDF.
- Le dossier `uploads/` est créé automatiquement si nécessaire.
