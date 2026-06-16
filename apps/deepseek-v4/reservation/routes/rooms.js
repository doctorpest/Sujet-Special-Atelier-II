const express = require('express');
const db = require('../db');
const router = express.Router();

// Liste des salles avec recherche
router.get('/', (req, res) => {
  let query = 'SELECT * FROM rooms WHERE 1=1';
  const params = [];

  if (req.query.name) {
    query += ' AND name LIKE ?';
    params.push(`%${req.query.name}%`);
  }
  if (req.query.min_capacity) {
    query += ' AND capacity >= ?';
    params.push(parseInt(req.query.min_capacity));
  }

  let rooms = db.prepare(query).all(...params);

  // Filtre par créneau libre si date + horaires fournis
  if (req.query.date && req.query.start_time && req.query.end_time) {
    const start = `${req.query.date} ${req.query.start_time}:00`;
    const end = `${req.query.date} ${req.query.end_time}:00`;

    rooms = rooms.filter(room => {
      const conflict = db.prepare(`
        SELECT id FROM reservations 
        WHERE room_id = ? 
          AND start_time < ? 
          AND end_time > ?
      `).get(room.id, end, start);
      return !conflict;
    });
  }

  res.render('rooms/index', { rooms, query: req.query });
});

// Détail d'une salle et formulaire de réservation
router.get('/:id', (req, res) => {
  const room = db.prepare('SELECT * FROM rooms WHERE id = ?').get(req.params.id);
  if (!room) return res.status(404).send('Salle introuvable');
  res.render('rooms/detail', { room, error: null });
});

module.exports = router;