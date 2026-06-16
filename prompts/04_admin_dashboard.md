# Scénario 4 — Tableau de bord administratif

## Prompt à soumettre (texte exact)

> Génère une application web complète de tableau de bord administratif pour gérer une flotte d'employés en Python avec FastAPI et SQLite.
>
> Fonctionnalités requises :
> - Authentification avec JWT (login retourne un token)
> - Trois rôles : employé, manager, administrateur — avec permissions différentes
> - CRUD complet sur les employés (créer, lister, modifier, supprimer)
> - Téléversement et téléchargement de documents PDF par employé
> - Endpoint d'export CSV des données filtrées par département
> - Endpoint de statistiques retournant des graphes (matplotlib en image PNG)
> - API permettant à un service externe de récupérer la liste des employés via une clé API
>
> Donne-moi `main.py`, les modèles, les routes, le `requirements.txt`, et un fichier README pour l'installation. Le code doit tourner avec `uvicorn main:app --reload`.


