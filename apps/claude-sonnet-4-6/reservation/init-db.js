const { db } = require('./routes/db');
const fs = require('fs');
const path = require('path');

async function run() {
  const sql = fs.readFileSync(path.join(__dirname, 'init.sql'), 'utf8');
  const statements = sql.split(';').map(s => s.trim()).filter(Boolean);
  for (const stmt of statements) {
    try { db.prepare(stmt).run(); } catch(e) { /* skip duplicates */ }
  }
  console.log('✅ Base de données initialisée');
}

module.exports = run;
