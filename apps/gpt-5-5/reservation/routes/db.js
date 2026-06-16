const sqlite3 = require('sqlite3').verbose();
const fs = require('fs');
const path = require('path');

const dbPath = path.join(__dirname, '..', 'db', 'app.sqlite');
const sqlPath = path.join(__dirname, '..', 'db', 'init.sql');
const db = new sqlite3.Database(dbPath);

function initializeDatabase() {
  const sql = fs.readFileSync(sqlPath, 'utf8');
  db.exec(sql, (err) => {
    if (err) throw err;
  });
}

function run(sql, params = []) {
  return new Promise((resolve, reject) => {
    db.run(sql, params, function (err) {
      if (err) reject(err);
      else resolve(this);
    });
  });
}

function get(sql, params = []) {
  return new Promise((resolve, reject) => {
    db.get(sql, params, (err, row) => {
      if (err) reject(err);
      else resolve(row);
    });
  });
}

function all(sql, params = []) {
  return new Promise((resolve, reject) => {
    db.all(sql, params, (err, rows) => {
      if (err) reject(err);
      else resolve(rows);
    });
  });
}

module.exports = { db, initializeDatabase, run, get, all };
