import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { UserService, CreateUserRequest } from '../../../core/service/user.service';
import { AuthService } from '../../../core/service/auth.service';

@Component({
  selector: 'app-create-user',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './create-user.component.html',
  styleUrls: ['./create-user.component.scss']
})
export class CreateUserComponent implements OnInit {

  // Formulaire
  formData: CreateUserRequest = {
    nom: '',
    prenom: '',
    email: '',
    password: '',
    role: 'surveillant',
    telephone: ''
  };

  // États
  loading = false;
  error: string | null = null;
  success: string | null = null;
  showPassword = false;

  // Validation
  errors: any = {};

  constructor(
    private userService: UserService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Vérifier les permissions
    const currentUser = this.authService.getCurrentUser();
    if (!currentUser || (currentUser.role !== 'proviseur' && currentUser.role !== 'admin')) {
      this.router.navigate(['/proviseur/dashboard']);
    }
  }

  /**
   * Validation du formulaire
   */
  validateForm(): boolean {
    this.errors = {};
    let isValid = true;

    // Nom
    if (!this.formData.nom || this.formData.nom.trim() === '') {
      this.errors.nom = 'Le nom est requis';
      isValid = false;
    }

    // Prénom
    if (!this.formData.prenom || this.formData.prenom.trim() === '') {
      this.errors.prenom = 'Le prénom est requis';
      isValid = false;
    }

    // Email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!this.formData.email || !emailRegex.test(this.formData.email)) {
      this.errors.email = 'Email invalide';
      isValid = false;
    }

    // Mot de passe
    if (!this.formData.password || this.formData.password.length < 8) {
      this.errors.password = 'Le mot de passe doit contenir au moins 8 caractères';
      isValid = false;
    }

    // Téléphone (optionnel mais si rempli, doit être valide)
    if (this.formData.telephone && this.formData.telephone.trim() !== '') {
      const phoneRegex = /^[0-9]{9,15}$/;
      if (!phoneRegex.test(this.formData.telephone.replace(/\s/g, ''))) {
        this.errors.telephone = 'Numéro de téléphone invalide';
        isValid = false;
      }
    }

    return isValid;
  }

  /**
   * Soumettre le formulaire
   */
  onSubmit(): void {
    this.error = null;
    this.success = null;

    if (!this.validateForm()) {
      this.error = 'Veuillez corriger les erreurs dans le formulaire';
      return;
    }

    this.loading = true;

    this.userService.createUser(this.formData).subscribe({
      next: (response) => {
        this.loading = false;
        if (response.success) {
          this.success = `Utilisateur ${this.formData.prenom} ${this.formData.nom} créé avec succès !`;

          // Réinitialiser le formulaire
          this.formData = {
            nom: '',
            prenom: '',
            email: '',
            password: '',
            role: 'surveillant',
            telephone: ''
          };

          // Rediriger après 2 secondes
          setTimeout(() => {
            this.router.navigate(['/proviseur/utilisateurs']);
          }, 2000);
        } else {
          this.error = response.message || 'Erreur lors de la création';
        }
      },
      error: (err) => {
        this.loading = false;
        console.error('Erreur création utilisateur', err);

        if (err.error?.errors) {
          this.errors = err.error.errors;
          this.error = 'Veuillez corriger les erreurs';
        } else {
          this.error = err.error?.message || 'Erreur lors de la création de l\'utilisateur';
        }
      }
    });
  }

  /**
   * Annuler et retourner
   */
  onCancel(): void {
    this.router.navigate(['/proviseur/utilisateurs']);
  }

  /**
   * Toggle affichage du mot de passe
   */
  togglePasswordVisibility(): void {
    this.showPassword = !this.showPassword;
  }

  /**
   * Générer un mot de passe aléatoire
   */
  generatePassword(): void {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
    let password = '';
    for (let i = 0; i < 12; i++) {
      password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    this.formData.password = password;
    this.showPassword = true;
  }
}
