PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  profile_photo TEXT DEFAULT '/uploads/default-avatar.svg',
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  capacity INTEGER NOT NULL,
  equipment TEXT NOT NULL,
  photo TEXT NOT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reservations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  room_id INTEGER NOT NULL,
  start_time TEXT NOT NULL,
  end_time TEXT NOT NULL,
  title TEXT NOT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  CHECK (datetime(end_time) > datetime(start_time))
);

CREATE INDEX IF NOT EXISTS idx_reservations_room_time ON reservations(room_id, start_time, end_time);
CREATE INDEX IF NOT EXISTS idx_reservations_user ON reservations(user_id);

INSERT OR IGNORE INTO rooms (id, name, capacity, equipment, photo) VALUES
(1, 'Salle Atlas', 6, 'Écran 55 pouces, tableau blanc, visioconférence', '/uploads/room-atlas.svg'),
(2, 'Salle Boréal', 12, 'Projecteur, système audio, paperboard', '/uploads/room-boreal.svg'),
(3, 'Salle Cosmos', 20, 'Écran interactif, micros, caméra 4K', '/uploads/room-cosmos.svg'),
(4, 'Focus Room', 4, 'Tableau blanc, écran compact', '/uploads/room-focus.svg');
