const express = require('express');
const router = express.Router();
const { db } = require('./db');
const { requireAuth } = require('./middleware');

// List rooms with search
router.get('/', requireAuth, (req, res) => {
  const { q, capacity, date, start, end } = req.query;
  let rooms = db.prepare('SELECT * FROM rooms').all();

  // Filter by name
  if (q) {
    const lq = q.toLowerCase();
    rooms = rooms.filter(r => r.name.toLowerCase().includes(lq) || (r.description || '').toLowerCase().includes(lq));
  }

  // Filter by capacity
  if (capacity) {
    rooms = rooms.filter(r => r.capacity >= parseInt(capacity));
  }

  // Filter by availability slot
  if (date && start && end) {
    rooms = rooms.filter(r => {
      const conflict = db.prepare(`
        SELECT id FROM reservations
        WHERE room_id = ? AND date = ? AND status != 'cancelled'
        AND start_time < ? AND end_time > ?
      `).get(r.id, date, end, start);
      return !conflict;
    });
  }

  res.render('rooms/index', { rooms, query: req.query });
});

// Room detail
router.get('/:id', requireAuth, (req, res) => {
  const room = db.prepare('SELECT * FROM rooms WHERE id = ?').get(req.params.id);
  if (!room) {
    req.session.error = 'Salle introuvable.';
    return res.redirect('/rooms');
  }

  // Get today's reservations for this room
  const today = new Date().toISOString().split('T')[0];
  const reservations = db.prepare(`
    SELECT r.*, u.name as user_name FROM reservations r
    JOIN users u ON r.user_id = u.id
    WHERE r.room_id = ? AND r.date >= ? AND r.status != 'cancelled'
    ORDER BY r.date, r.start_time
    LIMIT 20
  `).all(room.id, today);

  res.render('rooms/show', { room, reservations });
});

module.exports = router;
