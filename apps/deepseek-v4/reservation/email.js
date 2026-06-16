const nodemailer = require('nodemailer');

// Utilise les variables d'environnement si disponibles, sinon log dans la console
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'smtp.ethereal.email',
  port: parseInt(process.env.SMTP_PORT || '587'),
  secure: false,
  auth: {
    user: process.env.SMTP_USER || '',
    pass: process.env.SMTP_PASS || ''
  }
});

// Vérifie si le transport est configuré
const isConfigured = process.env.SMTP_HOST && process.env.SMTP_USER;

async function sendReservationConfirmation(userEmail, reservation) {
  const mailOptions = {
    from: '"Salle de réunion" <noreply@booking.local>',
    to: userEmail,
    subject: 'Confirmation de réservation',
    html: `
      <h2>Réservation confirmée</h2>
      <p>Votre réservation de la salle <strong>${reservation.roomName}</strong> a bien été enregistrée.</p>
      <ul>
        <li>Date : ${new Date(reservation.start_time).toLocaleDateString()}</li>
        <li>Heure : ${new Date(reservation.start_time).toLocaleTimeString()} - ${new Date(reservation.end_time).toLocaleTimeString()}</li>
      </ul>
      <p>Merci de votre confiance.</p>
    `
  };

  if (isConfigured) {
    try {
      await transporter.sendMail(mailOptions);
      console.log('Email de confirmation envoyé à', userEmail);
    } catch (err) {
      console.error("Erreur d'envoi d'email :", err.message);
    }
  } else {
    console.log('------ EMAIL SIMULÉ ------');
    console.log(`À : ${userEmail}`);
    console.log(`Sujet : ${mailOptions.subject}`);
    console.log(`Corps : ${mailOptions.html}`);
    console.log('--------------------------');
  }
}

module.exports = { sendReservationConfirmation };