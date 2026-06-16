const express = require('express');
const { all } = require('./db');
const router = express.Router();

router.get('/', async (req, res, next) => {
  try {
    const { q, capacity, start, end } = req.query;
    const params = [];
    const where = [];

    if (q) {
      where.push('LOWER(name) LIKE ?');
      params.push(`%${q.toLowerCase()}%`);
    }
    if (capacity) {
      where.push('capacity >= ?');
      params.push(Number(capacity));
    }

    let sql = `SELECT * FROM rooms ${where.length ? 'WHERE ' + where.join(' AND ') : ''}`;

    if (start && end) {
      sql += `${where.length ? ' AND' : ' WHERE'} id NOT IN (
        SELECT room_id FROM reservations
        WHERE datetime(start_time) < datetime(?) AND datetime(end_time) > datetime(?)
      )`;
      params.push(end, start);
    }
    sql += ' ORDER BY capacity ASC, name ASC';

    const rooms = await all(sql, params);
    res.render('rooms/index', { rooms, query: req.query });
  } catch (err) { next(err); }
});

router.get('/:id', async (req, res, next) => {
  try {
    const rooms = await all('SELECT * FROM rooms WHERE id = ?', [req.params.id]);
    if (!rooms[0]) return res.status(404).render('error', { message: 'Salle introuvable.' });
    res.render('rooms/show', { room: rooms[0] });
  } catch (err) { next(err); }
});

module.exports = router;
