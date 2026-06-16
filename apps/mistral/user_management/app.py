# app.py
import os
from datetime import datetime, timedelta
from flask import Flask, render_template, redirect, url_for, request, flash, session
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from flask_wtf import FlaskForm
from wtforms import StringField, PasswordField, SubmitField, SelectField
from wtforms.validators import DataRequired, Email, Length, EqualTo, ValidationError
from flask_mail import Mail, Message
import secrets
from dotenv import load_dotenv

# Charger les variables d'environnement
load_dotenv()

app = Flask(__name__)
app.config['SECRET_KEY'] = os.getenv('SECRET_KEY', 'ta-secrete-key-ici')
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///users.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

# Configuration pour l'envoi d'emails (exemple avec Gmail)
app.config['MAIL_SERVER'] = 'smtp.gmail.com'
app.config['MAIL_PORT'] = 587
app.config['MAIL_USE_TLS'] = True
app.config['MAIL_USERNAME'] = os.getenv('MAIL_USERNAME')
app.config['MAIL_PASSWORD'] = os.getenv('MAIL_PASSWORD')

db = SQLAlchemy(app)
login_manager = LoginManager(app)
login_manager.login_view = 'login'
mail = Mail(app)

# Modèle Utilisateur
class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password = db.Column(db.String(200), nullable=False)
    full_name = db.Column(db.String(100), nullable=False)
    role = db.Column(db.String(20), nullable=False, default='user')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    def __repr__(self):
        return f'<User {self.email}>'

# Modèle pour les tokens de réinitialisation
class PasswordResetToken(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    token = db.Column(db.String(100), unique=True, nullable=False)
    expires_at = db.Column(db.DateTime, nullable=False)

# Formulaire d'inscription
class RegistrationForm(FlaskForm):
    email = StringField('Email', validators=[DataRequired(), Email()])
    full_name = StringField('Nom complet', validators=[DataRequired(), Length(min=2, max=100)])
    password = PasswordField('Mot de passe', validators=[DataRequired(), Length(min=6)])
    confirm_password = PasswordField('Confirmer le mot de passe', validators=[DataRequired(), EqualTo('password')])
    submit = SubmitField('S\'inscrire')

    def validate_email(self, email):
        user = User.query.filter_by(email=email.data).first()
        if user:
            raise ValidationError('Cet email est déjà utilisé.')

# Formulaire de connexion
class LoginForm(FlaskForm):
    email = StringField('Email', validators=[DataRequired(), Email()])
    password = PasswordField('Mot de passe', validators=[DataRequired()])
    submit = SubmitField('Se connecter')

# Formulaire de réinitialisation de mot de passe (demande)
class ResetPasswordRequestForm(FlaskForm):
    email = StringField('Email', validators=[DataRequired(), Email()])
    submit = SubmitField('Envoyer le lien de réinitialisation')

# Formulaire de réinitialisation de mot de passe
class ResetPasswordForm(FlaskForm):
    password = PasswordField('Nouveau mot de passe', validators=[DataRequired(), Length(min=6)])
    confirm_password = PasswordField('Confirmer le nouveau mot de passe', validators=[DataRequired(), EqualTo('password')])
    submit = SubmitField('Réinitialiser le mot de passe')

# Formulaire pour modifier le rôle d'un utilisateur
class UpdateRoleForm(FlaskForm):
    role = SelectField('Rôle', choices=[('user', 'Utilisateur'), ('admin', 'Administrateur')])
    submit = SubmitField('Mettre à jour')

# Charger l'utilisateur pour Flask-Login
@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

# Initialiser la base de données
def init_db():
    with app.app_context():
        db.create_all()
        # Créer un admin par défaut si aucun utilisateur n'existe
        if not User.query.filter_by(role='admin').first():
            admin = User(
                email='admin@example.com',
                password='admin123',  # À changer en production !
                full_name='Administrateur',
                role='admin'
            )
            db.session.add(admin)
            db.session.commit()

# Routes
@app.route('/')
def index():
    return render_template('index.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if current_user.is_authenticated:
        return redirect(url_for('profile'))
    form = RegistrationForm()
    if form.validate_on_submit():
        # En production, utilisez un hachage sécurisé (ex: bcrypt)
        user = User(
            email=form.email.data,
            password=form.password.data,  # À remplacer par un hachage en production
            full_name=form.full_name.data,
            role='user'
        )
        db.session.add(user)
        db.session.commit()
        flash('Inscription réussie ! Vous pouvez maintenant vous connecter.', 'success')
        return redirect(url_for('login'))
    return render_template('register.html', form=form)

@app.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('profile'))
    form = LoginForm()
    if form.validate_on_submit():
        user = User.query.filter_by(email=form.email.data).first()
        if user and user.password == form.password.data:  # À remplacer par une vérification de hachage en production
            login_user(user)
            return redirect(url_for('profile'))
        else:
            flash('Email ou mot de passe incorrect.', 'danger')
    return render_template('login.html', form=form)

@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('index'))

@app.route('/profile')
@login_required
def profile():
    return render_template('profile.html', user=current_user)

@app.route('/admin')
@login_required
def admin():
    if current_user.role != 'admin':
        flash('Accès refusé. Réservé aux administrateurs.', 'danger')
        return redirect(url_for('index'))
    users = User.query.all()
    return render_template('admin.html', users=users)

@app.route('/admin/update_role/<int:user_id>', methods=['POST'])
@login_required
def update_role(user_id):
    if current_user.role != 'admin':
        flash('Accès refusé.', 'danger')
        return redirect(url_for('index'))
    user = User.query.get_or_404(user_id)
    form = UpdateRoleForm()
    if form.validate_on_submit():
        user.role = form.role.data
        db.session.commit()
        flash(f'Rôle de {user.full_name} mis à jour en {user.role}.', 'success')
    return redirect(url_for('admin'))

@app.route('/admin/delete_user/<int:user_id>', methods=['POST'])
@login_required
def delete_user(user_id):
    if current_user.role != 'admin':
        flash('Accès refusé.', 'danger')
        return redirect(url_for('index'))
    user = User.query.get_or_404(user_id)
    if user.id == current_user.id:
        flash('Vous ne pouvez pas supprimer votre propre compte.', 'danger')
    else:
        db.session.delete(user)
        db.session.commit()
        flash(f'Utilisateur {user.full_name} supprimé.', 'success')
    return redirect(url_for('admin'))

@app.route('/reset_password_request', methods=['GET', 'POST'])
def reset_password_request():
    if current_user.is_authenticated:
        return redirect(url_for('profile'))
    form = ResetPasswordRequestForm()
    if form.validate_on_submit():
        user = User.query.filter_by(email=form.email.data).first()
        if user:
            # Générer un token unique
            token = secrets.token_urlsafe(32)
            expires_at = datetime.utcnow() + timedelta(hours=1)
            # Supprimer les anciens tokens
            PasswordResetToken.query.filter_by(user_id=user.id).delete()
            # Ajouter le nouveau token
            reset_token = PasswordResetToken(
                user_id=user.id,
                token=token,
                expires_at=expires_at
            )
            db.session.add(reset_token)
            db.session.commit()
            # Envoyer l'email
            reset_url = url_for('reset_password', token=token, _external=True)
            msg = Message('Réinitialisation de mot de passe', sender=app.config['MAIL_USERNAME'], recipients=[user.email])
            msg.body = f'''Pour réinitialiser votre mot de passe, cliquez sur le lien suivant :
{reset_url}

Ce lien expirera dans 1 heure.
'''
            mail.send(msg)
        flash('Si cet email existe, un lien de réinitialisation a été envoyé.', 'info')
        return redirect(url_for('login'))
    return render_template('reset_password_request.html', form=form)

@app.route('/reset_password/<token>', methods=['GET', 'POST'])
def reset_password(token):
    if current_user.is_authenticated:
        return redirect(url_for('profile'))
    reset_token = PasswordResetToken.query.filter_by(token=token).first()
    if not reset_token or reset_token.expires_at < datetime.utcnow():
        flash('Lien de réinitialisation invalide ou expiré.', 'danger')
        return redirect(url_for('reset_password_request'))
    form = ResetPasswordForm()
    if form.validate_on_submit():
        user = User.query.get(reset_token.user_id)
        if user:
            user.password = form.password.data  # À remplacer par un hachage en production
            db.session.delete(reset_token)
            db.session.commit()
            flash('Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.', 'success')
            return redirect(url_for('login'))
    return render_template('reset_password.html', form=form)

if __name__ == '__main__':
    with app.app_context():
        init_db()
    app.run(debug=True)