import os
import sqlite3
import secrets
from functools import wraps
from werkzeug.security import generate_password_hash, check_password_hash
from flask import Flask, render_template, request, redirect, url_for, session, flash, g

app = Flask(__name__)
app.secret_key = secrets.token_hex(16)
DATABASE = 'database.db'

# --- GESTION DE LA BASE DE DONNÉES ---
def get_db():
    db = getattr(g, '_database', None)
    if db is None:
        db = g._database = sqlite3.connect(DATABASE)
        db.row_factory = sqlite3.Row
    return db

@app.teardown_appcontext
def close_connection(exception):
    db = getattr(g, '_database', None)
    if db is not None:
        db.close()

def init_db():
    """Initialise la base de données si elle n'existe pas et crée un admin par défaut."""
    if not os.path.exists(DATABASE):
        with app.app_context():
            db = get_db()
            with app.open_resource('schema.sql', mode='r') as f:
                db.cursor().executescript(f.read())
            db.commit()
            # Création d'un administrateur par défaut
            admin_pw = generate_password_hash('admin123')
            db.execute('INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)',
                       ('admin@test.com', admin_pw, 'Administrateur', 'admin'))
            db.commit()
            print("=> Base de données créée avec succès.")
            print("=> Compte Admin créé: admin@test.com / admin123")

# --- DÉCORATEURS D'ACCÈS ---
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
        if 'user_id' not in session or session.get('role') != 'admin':
            flash('Accès refusé. Droits administrateur requis.', 'danger')
            return redirect(url_for('profile'))
        return f(*args, **kwargs)
    return decorated_function

# --- ROUTES ---
@app.route('/')
def index():
    if 'user_id' in session:
        return redirect(url_for('profile'))
    return redirect(url_for('login'))

@app.route('/register', methods=('GET', 'POST'))
def register():
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']
        full_name = request.form['full_name']
        db = get_db()
        
        user = db.execute('SELECT * FROM users WHERE email = ?', (email,)).fetchone()
        if user:
            flash('Cet email est déjà utilisé.', 'danger')
        else:
            db.execute('INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)',
                       (email, generate_password_hash(password), full_name))
            db.commit()
            flash('Inscription réussie. Vous pouvez maintenant vous connecter.', 'success')
            return redirect(url_for('login'))
            
    return render_template('register.html')

@app.route('/login', methods=('GET', 'POST'))
def login():
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']
        db = get_db()
        user = db.execute('SELECT * FROM users WHERE email = ?', (email,)).fetchone()
        
        if user and check_password_hash(user['password_hash'], password):
            session['user_id'] = user['id']
            session['email'] = user['email']
            session['full_name'] = user['full_name']
            session['role'] = user['role']
            flash('Connexion réussie.', 'success')
            return redirect(url_for('profile'))
        else:
            flash('Email ou mot de passe incorrect.', 'danger')
            
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    flash('Vous avez été déconnecté.', 'info')
    return redirect(url_for('login'))

@app.route('/profile')
@login_required
def profile():
    return render_template('profile.html')

# --- ADMINISTRATION ---
@app.route('/admin')
@admin_required
def admin():
    db = get_db()
    users = db.execute('SELECT * FROM users').fetchall()
    return render_template('admin.html', users=users)

@app.route('/admin/role/<int:user_id>', methods=['POST'])
@admin_required
def change_role(user_id):
    new_role = request.form['role']
    db = get_db()
    # Empêcher l'admin de modifier son propre rôle pour éviter de se bloquer
    if user_id == session['user_id']:
        flash('Vous ne pouvez pas modifier votre propre rôle.', 'danger')
        return redirect(url_for('admin'))
        
    db.execute('UPDATE users SET role = ? WHERE id = ?', (new_role, user_id))
    db.commit()
    flash(f"Le rôle de l'utilisateur a été mis à jour.", 'success')
    return redirect(url_for('admin'))

@app.route('/admin/delete/<int:user_id>', methods=['POST'])
@admin_required
def delete_user(user_id):
    db = get_db()
    if user_id == session['user_id']:
        flash('Vous ne pouvez pas supprimer votre propre compte.', 'danger')
        return redirect(url_for('admin'))
        
    db.execute('DELETE FROM users WHERE id = ?', (user_id,))
    db.commit()
    flash('Utilisateur supprimé.', 'success')
    return redirect(url_for('admin'))

# --- RÉINITIALISATION MOT DE PASSE ---
@app.route('/reset_request', methods=('GET', 'POST'))
def reset_request():
    if request.method == 'POST':
        email = request.form['email']
        db = get_db()
        user = db.execute('SELECT * FROM users WHERE email = ?', (email,)).fetchone()
        
        if user:
            token = secrets.token_urlsafe(32)
            db.execute('UPDATE users SET reset_token = ? WHERE email = ?', (token, email))
            db.commit()
            
            # SIMULATION D'ENVOI D'EMAIL
            reset_link = url_for('reset_password', token=token, _external=True)
            print("\n" + "="*50)
            print(f"EMAIL DE RÉINITIALISATION POUR : {email}")
            print(f"Lien : {reset_link}")
            print("="*50 + "\n")
            
        # On affiche toujours le même message pour des raisons de sécurité
        flash('Si un compte existe avec cet email, un lien de réinitialisation y a été envoyé (Regardez la console Python).', 'info')
        return redirect(url_for('login'))
        
    return render_template('reset_request.html')

@app.route('/reset_password/<token>', methods=('GET', 'POST'))
def reset_password(token):
    db = get_db()
    user = db.execute('SELECT * FROM users WHERE reset_token = ?', (token,)).fetchone()
    
    if not user:
        flash('Le lien de réinitialisation est invalide ou expiré.', 'danger')
        return redirect(url_for('login'))
        
    if request.method == 'POST':
        new_password = request.form['password']
        hashed_password = generate_password_hash(new_password)
        
        db.execute('UPDATE users SET password_hash = ?, reset_token = NULL WHERE id = ?', 
                   (hashed_password, user['id']))
        db.commit()
        flash('Votre mot de passe a été mis à jour avec succès.', 'success')
        return redirect(url_for('login'))
        
    return render_template('reset_password.html')

if __name__ == '__main__':
    init_db()
    app.run(debug=True)