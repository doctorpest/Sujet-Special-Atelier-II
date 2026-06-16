# MeetRooms - Réservation de salles

Application Node.js Express + SQLite + EJS.

## Lancement

```bash
npm install
node server.js
```

Puis ouvrez `http://localhost:3000`.

## Emails

Sans configuration SMTP, l'application utilise `jsonTransport` de Nodemailer et affiche l'email simulé dans la console. Pour envoyer de vrais emails, copiez `.env.example` vers `.env` et renseignez les variables SMTP.

## Base de données

La base SQLite est créée automatiquement dans `db/app.sqlite` au démarrage à partir de `db/init.sql`.
