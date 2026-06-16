/**
 * db.js — sql.js wrapper with better-sqlite3-compatible API
 * sql.js is pure-JS SQLite (no native build required).
 */
const initSqlJs = require('sql.js');
const fs = require('fs');
const path = require('path');

const DB_PATH = path.join(__dirname, '..', 'meetroom.db');

let _db = null;

function persist() {
  if (_db) fs.writeFileSync(DB_PATH, Buffer.from(_db.export()));
}

class Stmt {
  constructor(db, sql) {
    this._db = db;
    this._sql = sql;
  }

  _flat(params) {
    // accept .get(a,b,c) or .get([a,b,c])
    if (params.length === 1 && Array.isArray(params[0])) return params[0];
    return params;
  }

  get(...params) {
    const stmt = this._db.prepare(this._sql);
    stmt.bind(this._flat(params));
    if (stmt.step()) {
      const row = stmt.getAsObject();
      stmt.free();
      return row;
    }
    stmt.free();
    return undefined;
  }

  all(...params) {
    const stmt = this._db.prepare(this._sql);
    const rows = [];
    stmt.bind(this._flat(params));
    while (stmt.step()) rows.push(stmt.getAsObject());
    stmt.free();
    return rows;
  }

  run(...params) {
    // Get next rowid BEFORE insert using sqlite_sequence or max(id)
    const stmt = this._db.prepare(this._sql);
    stmt.bind(this._flat(params));
    stmt.step();
    stmt.free();

    // Get last insert rowid via a separate query on the same connection
    const idStmt = this._db.prepare('SELECT last_insert_rowid() as id');
    idStmt.step();
    const lastInsertRowid = idStmt.getAsObject().id;
    idStmt.free();

    persist();
    return { lastInsertRowid };
  }
}

const db = {
  prepare(sql) {
    if (!_db) throw new Error('DB not initialized');
    return new Stmt(_db, sql);
  }
};

async function initDb() {
  const SQL = await initSqlJs();
  const buf = fs.existsSync(DB_PATH) ? fs.readFileSync(DB_PATH) : null;
  _db = buf ? new SQL.Database(buf) : new SQL.Database();
  _db.run('PRAGMA foreign_keys = ON;');
  return _db;
}

module.exports = { initDb, db };
