CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    profile_photo TEXT
);

CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    capacity INTEGER NOT NULL,
    equipment TEXT,
    photo TEXT
);

CREATE TABLE IF NOT EXISTS reservations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    room_id INTEGER NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Salles de réunion par défaut
INSERT OR IGNORE INTO rooms (name, capacity, equipment, photo) VALUES
('Salle Alpha', 8, 'Visioconférence, Tableau blanc', NULL),
('Salle Beta', 12, 'Projecteur, Paperboard', NULL),
('Salle Gamma', 20, 'Visioconférence, Écran tactile', NULL),
('Salle Delta', 4, 'Tableau blanc', NULL);