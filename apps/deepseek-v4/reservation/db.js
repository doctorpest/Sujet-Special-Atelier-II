const Database = require('better-sqlite3');
const fs = require('fs');
const path = require('path');

const db = new Database('booking.db');
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

// Exécute le script SQL d'initialisation
const initSQL = fs.readFileSync(path.join(__dirname, 'init.sql'), 'utf8');
db.exec(initSQL);

module.exports = db;