# HR Dashboard API

API REST de gestion de flotte d'employés — **FastAPI + SQLite + JWT**.

---

## Stack

| Composant | Technologie |
|-----------|-------------|
| Framework | FastAPI 0.111 |
| Base de données | SQLite (SQLAlchemy async) |
| Auth | JWT via `python-jose` |
| Hash mot de passe | bcrypt via `passlib` |
| Export | pandas (CSV), matplotlib (PNG) |
| Upload | `python-multipart` |

---

## Installation

```bash
# 1. Cloner / copier le projet
cd hr_dashboard

# 2. Créer un environnement virtuel
python -m venv venv
source venv/bin/activate        # Windows : venv\Scripts\activate

# 3. Installer les dépendances
pip install -r requirements.txt

# 4. Configurer l'environnement
cp .env.example .env
# Éditer .env : SECRET_KEY, EXTERNAL_API_KEYS

# 5. Lancer le serveur
uvicorn main:app --reload
```

L'API est disponible sur **http://127.0.0.1:8000**

- Swagger UI : http://127.0.0.1:8000/docs
- ReDoc     : http://127.0.0.1:8000/redoc

---

## Compte admin par défaut

Au premier démarrage, un compte administrateur est créé automatiquement :

| Champ | Valeur |
|-------|--------|
| Email | `admin@company.com` |
| Mot de passe | `admin1234` |
| Rôle | `admin` |

> ⚠️ Changer le mot de passe immédiatement via `PATCH /employees/{id}` en production.

---

## Rôles et permissions

| Endpoint | employee | manager | admin |
|----------|:--------:|:-------:|:-----:|
| `GET /employees/` (liste) | ✗ | ✓ | ✓ |
| `GET /employees/{id}` (soi-même) | ✓ | ✓ | ✓ |
| `POST /employees/` | ✗ | ✗ | ✓ |
| `PATCH /employees/{id}` (soi-même, sans rôle) | ✓ | ✓ | ✓ |
| `DELETE /employees/{id}` | ✗ | ✗ | ✓ |
| Upload/suppression documents | ✗ | ✓ | ✓ |
| Téléchargement documents (soi-même) | ✓ | ✓ | ✓ |
| Export CSV | ✗ | ✓ | ✓ |
| Stats PNG/JSON | ✗ | ✓ | ✓ |
| API externe (`X-API-Key`) | — | — | — |

---

## Authentification JWT

```bash
# Login
curl -X POST http://localhost:8000/auth/login \
  -d "username=admin@company.com&password=admin1234" \
  -H "Content-Type: application/x-www-form-urlencoded"

# Réponse
# { "access_token": "eyJ...", "token_type": "bearer", "role": "admin" }

# Utilisation
curl http://localhost:8000/employees/ \
  -H "Authorization: Bearer eyJ..."
```

---

## Endpoints principaux

### Authentification
| Méthode | Route | Description |
|---------|-------|-------------|
| `POST` | `/auth/login` | Login → JWT |
| `GET` | `/auth/me` | Profil connecté |

### Employés (CRUD)
| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/employees/` | Liste (filtres : `department`, `role`, `active_only`) |
| `POST` | `/employees/` | Créer un employé |
| `GET` | `/employees/{id}` | Détail |
| `PATCH` | `/employees/{id}` | Modifier (partiel) |
| `DELETE` | `/employees/{id}` | Supprimer |

### Documents PDF
| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/employees/{id}/documents/` | Lister les documents |
| `POST` | `/employees/{id}/documents/` | Téléverser un PDF (max 10 MB) |
| `GET` | `/employees/{id}/documents/{doc_id}/download` | Télécharger |
| `DELETE` | `/employees/{id}/documents/{doc_id}` | Supprimer |

### Export & Statistiques
| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/export/csv` | Export CSV (filtre `?department=IT`) |
| `GET` | `/stats/chart` | Graphique PNG (`?chart_type=department\|role\|activity`) |
| `GET` | `/stats/summary` | Résumé JSON |

### API Externe
| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/api/v1/employees` | Liste employés via `X-API-Key` header |

```bash
# Exemple API externe
curl http://localhost:8000/api/v1/employees \
  -H "X-API-Key: ext-key-demo-1234"
```

---

## Structure du projet

```
hr_dashboard/
├── main.py                   # Point d'entrée FastAPI
├── requirements.txt
├── .env.example
├── core/
│   ├── config.py             # Variables d'environnement
│   ├── database.py           # Engine SQLAlchemy async + init_db
│   └── security.py           # JWT, bcrypt, dépendances de rôle
├── models/
│   ├── employee.py           # Modèle Employee + schemas Pydantic
│   └── document.py           # Modèle Document + schemas Pydantic
├── routers/
│   ├── auth.py               # /auth/login, /auth/me
│   ├── employees.py          # CRUD /employees
│   ├── documents.py          # Upload/download /employees/{id}/documents
│   ├── export.py             # /export/csv, /stats/chart, /stats/summary
│   └── external_api.py       # /api/v1/employees (clé API)
└── uploads/                  # Fichiers PDF (créé automatiquement)
```

---

## Notes de sécurité pour la production

- Remplacer `SECRET_KEY` par une valeur aléatoire forte (32+ bytes hex)
- Restreindre `CORS allow_origins` à vos domaines
- Utiliser PostgreSQL plutôt que SQLite
- Mettre un reverse proxy (nginx) devant uvicorn
- Activer HTTPS
- Stocker les fichiers uploadés sur S3/GCS plutôt que le disque local
