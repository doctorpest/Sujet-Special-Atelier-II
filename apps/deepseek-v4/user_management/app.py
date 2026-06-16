import sqlite3
import secrets
from datetime import datetime, timedelta
from functools import wraps
from flask import Flask, render_template, request, redirect, url_for, session, flash, g
from werkzeug.security import generate_password_hash, check_password_hash
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

app = Flask(__name__)
app.secret_key = secrets.token_hex(16)
app.config['DATABASE'] = 'database.db'
app.config['PERMANENT_SESSION_LIFETIME'] = timedelta(hours=24)

# Configuration email (à adapter selon votre serveur SMTP)
EMAIL_HOST = 'smtp.gmail.com'
EMAIL_PORT = 587
EMAIL_USER = 'your-email@gmail.com'
EMAIL_PASSWORD = 'your-app-password'
EMAIL_FROM = 'noreply@userapp.com'

# Initialisation de la base de données
def init_db():
    with app.app_context():
        db = get_db()
        with app.open_resource('schema.sql', mode='r') as f:
            db.cursor().executescript(f.read())
        db.commit()
        
        # Créer un utilisateur admin par défaut si la table est vide
        cursor = db.execute('SELECT COUNT(*) FROM users')
        count = cursor.fetchone()[0]
        if count == 0:
            admin_password = generate_password_hash('admin123')
            db.execute(
                'INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, ?)',
                ('admin@example.com', admin_password, 'Administrateur', 'admin')
            )
            db.commit()

def get_db():
    if 'db' not in g:
        g.db = sqlite3.connect(app.config['DATABASE'])
        g.db.row_factory = sqlite3.Row
        g.db.execute('PRAGMA foreign_keys = ON')
    return g.db

@app.teardown_appcontext
def close_db(error):
    db = g.pop('db', None)
    if db is not None:
        db.close()

# Fonction d'envoi d'email
def send_reset_email(email, token, expiration_hours=1):
    reset_link = url_for('reset_password', token=token, _external=True)
    
    subject = "Réinitialisation de votre mot de passe"
    body = f"""
    Bonjour,
    
    Vous avez demandé la réinitialisation de votre mot de passe.
    
    Cliquez sur le lien suivant pour réinitialiser votre mot de passe :
    {reset_link}
    
    Ce lien expire dans {expiration_hours} heure(s).
    
    Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
    
    Cordialement,
    L'équipe de gestion des utilisateurs
    """
    
    try:
        msg = MIMEMultipart()
        msg['From'] = EMAIL_FROM
        msg['To'] = email
        msg['Subject'] = subject
        msg.attach(MIMEText(body, 'plain'))
        
        server = smtplib.SMTP(EMAIL_HOST, EMAIL_PORT)
        server.starttls()
        server.login(EMAIL_USER, EMAIL_PASSWORD)
        server.send_message(msg)
        server.quit()
        return True
    except Exception as e:
        print(f"Erreur d'envoi d'email: {e}")
        return False

# Décorateurs d'authentification
def login_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('Veuillez vous connecter pour accéder à cette page.', 'warning')
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated_function

def admin_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('Veuillez vous connecter.', 'warning')
            return redirect(url_for('login'))
        
        db = get_db()
        user = db.execute('SELECT role FROM users WHERE id = ?', (session['user_id'],)).fetchone()
        if not user or user['role'] != 'admin':
            flash('Accès non autorisé.', 'danger')
            return redirect(url_for('index'))
        
        return f(*args, **kwargs)
    return decorated_function

# Route principale
@app.route('/')
def index():
    return render_template('index.html')

# Inscription
@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        email = request.form['email'].strip().lower()
        password = request.form['password']
        confirm_password = request.form['confirm_password']
        full_name = request.form['full_name'].strip()
        role = 'user'  # Par défaut, les nouveaux utilisateurs sont "user"
        
        # Validation
        if not all([email, password, confirm_password, full_name]):
            flash('Tous les champs sont obligatoires.', 'danger')
            return render_template('register.html')
        
        if password != confirm_password:
            flash('Les mots de passe ne correspondent pas.', 'danger')
            return render_template('register.html')
        
        if len(password) < 6:
            flash('Le mot de passe doit contenir au moins 6 caractères.', 'danger')
            return render_template('register.html')
        
        db = get_db()
        
        # Vérifier si l'email existe déjà
        existing_user = db.execute('SELECT id FROM users WHERE email = ?', (email,)).fetchone()
        if existing_user:
            flash('Cet email est déjà utilisé.', 'danger')
            return render_template('register.html')
        
        try:
            # Créer l'utilisateur
            hashed_password = generate_password_hash(password)
            db.execute(
                'INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, ?)',
                (email, hashed_password, full_name, role)
            )
            db.commit()
            flash('Inscription réussie ! Vous pouvez maintenant vous connecter.', 'success')
            return redirect(url_for('login'))
        except Exception as e:
            flash(f'Erreur lors de l\'inscription: {str(e)}', 'danger')
            return render_template('register.html')
    
    return render_template('register.html')

# Connexion
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form['email'].strip().lower()
        password = request.form['password']
        
        if not email or not password:
            flash('Email et mot de passe sont requis.', 'danger')
            return render_template('login.html')
        
        db = get_db()
        user = db.execute('SELECT * FROM users WHERE email = ?', (email,)).fetchone()
        
        if user and check_password_hash(user['password'], password):
            session.clear()
            session['user_id'] = user['id']
            session['user_email'] = user['email']
            session['user_name'] = user['full_name']
            session['user_role'] = user['role']
            session.permanent = True
            
            flash(f'Bienvenue {user["full_name"]} !', 'success')
            
            # Redirection selon le rôle
            if user['role'] == 'admin':
                return redirect(url_for('admin'))
            return redirect(url_for('profile'))
        else:
            flash('Email ou mot de passe incorrect.', 'danger')
    
    return render_template('login.html')

# Déconnexion
@app.route('/logout')
def logout():
    session.clear()
    flash('Vous avez été déconnecté.', 'info')
    return redirect(url_for('index'))

# Profil utilisateur
@app.route('/profile')
@login_required
def profile():
    db = get_db()
    user = db.execute('SELECT * FROM users WHERE id = ?', (session['user_id'],)).fetchone()
    return render_template('profile.html', user=user)

# Administration
@app.route('/admin')
@admin_required
def admin():
    db = get_db()
    users = db.execute('SELECT * FROM users ORDER BY created_at DESC').fetchall()
    return render_template('admin.html', users=users)

# Modification du rôle (admin)
@app.route('/admin/update-role/<int:user_id>', methods=['POST'])
@admin_required
def update_role(user_id):
    db = get_db()
    
    # Empêcher de modifier son propre rôle
    if user_id == session['user_id']:
        flash('Vous ne pouvez pas modifier votre propre rôle.', 'danger')
        return redirect(url_for('admin'))
    
    new_role = request.form['role']
    
    if new_role not in ['user', 'admin']:
        flash('Rôle invalide.', 'danger')
        return redirect(url_for('admin'))
    
    db.execute('UPDATE users SET role = ? WHERE id = ?', (new_role, user_id))
    db.commit()
    flash('Rôle mis à jour avec succès.', 'success')
    return redirect(url_for('admin'))

# Suppression d'utilisateur (admin)
@app.route('/admin/delete/<int:user_id>', methods=['POST'])
@admin_required
def delete_user(user_id):
    db = get_db()
    
    # Empêcher de se supprimer soi-même
    if user_id == session['user_id']:
        flash('Vous ne pouvez pas supprimer votre propre compte.', 'danger')
        return redirect(url_for('admin'))
    
    # Vérifier si c'est le dernier admin
    if db.execute('SELECT role FROM users WHERE id = ?', (user_id,)).fetchone()['role'] == 'admin':
        admin_count = db.execute('SELECT COUNT(*) as count FROM users WHERE role = ?', ('admin',)).fetchone()['count']
        if admin_count <= 1:
            flash('Impossible de supprimer le dernier administrateur.', 'danger')
            return redirect(url_for('admin'))
    
    db.execute('DELETE FROM users WHERE id = ?', (user_id,))
    db.commit()
    flash('Utilisateur supprimé avec succès.', 'success')
    return redirect(url_for('admin'))

# Demande de réinitialisation de mot de passe
@app.route('/reset-password', methods=['GET', 'POST'])
def reset_password_request():
    if request.method == 'POST':
        email = request.form['email'].strip().lower()
        
        if not email:
            flash('Veuillez entrer votre email.', 'danger')
            return render_template('reset_password_request.html')
        
        db = get_db()
        user = db.execute('SELECT * FROM users WHERE email = ?', (email,)).fetchone()
        
        if user:
            # Générer un token
            token = secrets.token_urlsafe(32)
            expiration = datetime.now() + timedelta(hours=1)
            
            # Supprimer les anciens tokens
            db.execute('DELETE FROM reset_tokens WHERE user_id = ?', (user['id'],))
            
            # Sauvegarder le nouveau token
            db.execute(
                'INSERT INTO reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)',
                (user['id'], token, expiration)
            )
            db.commit()
            
            # Envoyer l'email (ou afficher le lien en développement)
            if send_reset_email(email, token):
                flash('Un email de réinitialisation a été envoyé à votre adresse.', 'success')
            else:
                # En développement, on peut afficher le lien directement
                flash(f'Lien de réinitialisation (mode développement) : {url_for("reset_password", token=token, _external=True)}', 'info')
        else:
            # Pour des raisons de sécurité, on affiche le même message
            flash('Si cet email existe, un lien de réinitialisation a été envoyé.', 'info')
        
        return redirect(url_for('login'))
    
    return render_template('reset_password_request.html')

# Réinitialisation du mot de passe
@app.route('/reset-password/<token>', methods=['GET', 'POST'])
def reset_password(token):
    db = get_db()
    
    # Vérifier le token
    reset_data = db.execute(
        'SELECT rt.*, u.email FROM reset_tokens rt JOIN users u ON rt.user_id = u.id WHERE rt.token = ? AND rt.used = 0',
        (token,)
    ).fetchone()
    
    if not reset_data:
        flash('Lien de réinitialisation invalide.', 'danger')
        return redirect(url_for('login'))
    
    if datetime.strptime(reset_data['expires_at'], '%Y-%m-%d %H:%M:%S.%f') < datetime.now():
        flash('Le lien de réinitialisation a expiré.', 'danger')
        return redirect(url_for('reset_password_request'))
    
    if request.method == 'POST':
        password = request.form['password']
        confirm_password = request.form['confirm_password']
        
        if not password or not confirm_password:
            flash('Tous les champs sont obligatoires.', 'danger')
            return render_template('reset_password.html', token=token)
        
        if password != confirm_password:
            flash('Les mots de passe ne correspondent pas.', 'danger')
            return render_template('reset_password.html', token=token)
        
        if len(password) < 6:
            flash('Le mot de passe doit contenir au moins 6 caractères.', 'danger')
            return render_template('reset_password.html', token=token)
        
        try:
            # Mettre à jour le mot de passe
            hashed_password = generate_password_hash(password)
            db.execute('UPDATE users SET password = ? WHERE id = ?', 
                      (hashed_password, reset_data['user_id']))
            
            # Marquer le token comme utilisé
            db.execute('UPDATE reset_tokens SET used = 1 WHERE token = ?', (token,))
            db.commit()
            
            flash('Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.', 'success')
            return redirect(url_for('login'))
        except Exception as e:
            flash(f'Erreur lors de la réinitialisation: {str(e)}', 'danger')
    
    return render_template('reset_password.html', token=token)

# Initialisation de la base de données avant la première requête
@app.before_request
def before_request():
    if not hasattr(app, '_db_initialized'):
        init_db()
        app._db_initialized = True

if __name__ == '__main__':
    init_db()
    app.run(debug=True)