const express = require('express');
const db = require('../db');

const router = express.Router();

// Liste + recherche
router.get('/', (req, res) => {
  const { name, min_capacity, date, start_time, end_time } = req.query;

  let params = [];
  let where = 'WHERE 1=1';

  if (name) {
    where += ' AND name LIKE ?';
    params.push('%' + name + '%');
  }
  if (min_capacity) {
    where += ' AND capacity >= ?';
    params.push(Number(min_capacity));
  }

  const baseQuery = `SELECT * FROM rooms ${where}`;

  // Recherche par créneau libre
  if (date && start_time && end_time) {
    const query = `
      SELECT * FROM rooms
      WHERE id NOT IN (
        SELECT room_id FROM reservations
        WHERE date = ?
          AND NOT (end_time <= ? OR start_time >= ?)
      )
      ${name || min_capacity ? 'AND id IN (SELECT id FROM rooms ' + where + ')' : ''}
    `;
    const timeParams = [date, start_time, end_time];
    const finalParams = timeParams.concat(params);
    db.all(query, finalParams, (err, rooms) => {
      if (err) {
        return res.status(500).send('Erreur serveur');
      }
      res.render('rooms', {
        rooms,
        filters: { name, min_capacity, date, start_time, end_time }
      });
    });
  } else {
    db.all(baseQuery, params, (err, rooms) => {
      if (err) {
        return res.status(500).send('Erreur serveur');
      }
      res.render('rooms', {
        rooms,
        filters: { name, min_capacity, date, start_time, end_time }
      });
    });
  }
});

module.exports = router;
