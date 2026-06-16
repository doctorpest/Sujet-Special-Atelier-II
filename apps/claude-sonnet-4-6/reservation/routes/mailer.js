const nodemailer = require('nodemailer');

// Configure with real SMTP credentials in production via env vars
// Default: uses Ethereal (fake SMTP for development/testing)
let transporter = null;

async function getTransporter() {
  if (transporter) return transporter;

  if (process.env.SMTP_HOST) {
    transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port: parseInt(process.env.SMTP_PORT || '587'),
      secure: process.env.SMTP_SECURE === 'true',
      auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASS,
      },
    });
  } else {
    // Create test account on Ethereal
    const testAccount = await nodemailer.createTestAccount();
    transporter = nodemailer.createTransport({
      host: 'smtp.ethereal.email',
      port: 587,
      secure: false,
      auth: {
        user: testAccount.user,
        pass: testAccount.pass,
      },
    });
    console.log(`📧 Ethereal SMTP: ${testAccount.user} / ${testAccount.pass}`);
  }
  return transporter;
}

async function sendConfirmationEmail({ to, userName, roomName, date, startTime, endTime, title }) {
  try {
    const t = await getTransporter();
    const info = await t.sendMail({
      from: `"MeetRoom" <${process.env.SMTP_FROM || 'noreply@meetroom.app'}>`,
      to,
      subject: `✅ Réservation confirmée — ${roomName}`,
      html: `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .card { background: #fff; max-width: 520px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
            .header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 36px 32px; }
            .header h1 { color: #c8a96e; margin: 0; font-size: 22px; letter-spacing: 2px; text-transform: uppercase; }
            .header p { color: rgba(255,255,255,.6); margin: 8px 0 0; font-size: 13px; }
            .body { padding: 32px; }
            .greeting { font-size: 16px; color: #333; margin-bottom: 24px; }
            .detail { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
            .detail:last-child { border-bottom: none; }
            .label { color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; width: 100px; flex-shrink: 0; }
            .value { color: #1a1a2e; font-weight: 600; font-size: 15px; }
            .badge { background: #eef6f0; color: #2d7a4f; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-block; margin-top: 20px; }
            .footer { background: #fafafa; padding: 20px 32px; text-align: center; font-size: 12px; color: #aaa; border-top: 1px solid #eee; }
          </style>
        </head>
        <body>
          <div class="card">
            <div class="header">
              <h1>MeetRoom</h1>
              <p>Confirmation de réservation</p>
            </div>
            <div class="body">
              <p class="greeting">Bonjour <strong>${userName}</strong>,</p>
              <p style="color:#555; font-size:15px;">Votre réservation a été enregistrée avec succès.</p>
              <div class="detail"><span class="label">Réunion</span><span class="value">${title}</span></div>
              <div class="detail"><span class="label">Salle</span><span class="value">${roomName}</span></div>
              <div class="detail"><span class="label">Date</span><span class="value">${new Date(date).toLocaleDateString('fr-FR', { weekday:'long', year:'numeric', month:'long', day:'numeric' })}</span></div>
              <div class="detail"><span class="label">Horaire</span><span class="value">${startTime} → ${endTime}</span></div>
              <span class="badge">✓ Confirmé</span>
            </div>
            <div class="footer">MeetRoom — Plateforme de réservation de salles<br>Cet email est généré automatiquement, merci de ne pas y répondre.</div>
          </div>
        </body>
        </html>
      `,
    });

    if (!process.env.SMTP_HOST) {
      console.log(`📧 Email de confirmation (test) : ${nodemailer.getTestMessageUrl(info)}`);
    }
  } catch (err) {
    console.error('Erreur envoi email:', err.message);
    // Non-blocking: app continues even if email fails
  }
}

module.exports = { sendConfirmationEmail };
