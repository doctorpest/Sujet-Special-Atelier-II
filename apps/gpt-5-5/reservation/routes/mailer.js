const nodemailer = require('nodemailer');

function makeTransporter() {
  if (process.env.SMTP_HOST) {
    return nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port: Number(process.env.SMTP_PORT || 587),
      secure: process.env.SMTP_SECURE === 'true',
      auth: process.env.SMTP_USER ? { user: process.env.SMTP_USER, pass: process.env.SMTP_PASS } : undefined
    });
  }
  return nodemailer.createTransport({ jsonTransport: true });
}

async function sendReservationConfirmation(user, reservation, room) {
  const transporter = makeTransporter();
  const info = await transporter.sendMail({
    from: process.env.MAIL_FROM || 'Reservations <no-reply@example.test>',
    to: user.email,
    subject: `Confirmation de réservation - ${room.name}`,
    text: `Bonjour ${user.name},\n\nVotre réservation est confirmée.\nSalle: ${room.name}\nTitre: ${reservation.title}\nDébut: ${reservation.start_time}\nFin: ${reservation.end_time}\n\nMerci.`
  });
  if (info.message) console.log('Email simulé:', info.message.toString());
  return info;
}

module.exports = { sendReservationConfirmation };
