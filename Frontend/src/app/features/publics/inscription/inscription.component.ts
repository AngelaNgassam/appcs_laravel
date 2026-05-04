import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, AbstractControl, ValidationErrors } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../../core/service/auth.service';

@Component({
  selector: 'app-inscription',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterModule],
  templateUrl: './inscription.component.html',
  styleUrls: ['./inscription.component.scss']
})
export class InscriptionComponent {
  inscriptionForm: FormGroup;
  currentStep = 1;
  totalSteps = 3;
  isLoading = false;
  errorMessage = '';
  showPassword = false;
  showConfirmPassword = false;
  logoFile: File | null = null; // ✅ Stocker le fichier logo
  logoPreview: string | null = null; // ✅ Aperçu du logo

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    public router: Router
  ) {
    this.inscriptionForm = this.fb.group({
      // Étape 1 : Informations personnelles
      nom: ['', [Validators.required, Validators.minLength(2)]],
      prenom: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      telephone: ['', [Validators.pattern(/^[0-9]{9,}$/)]],

      // Étape 2 : Sécurité
      mot_de_passe: ['', [Validators.required, Validators.minLength(8)]],
      confirm_password: ['', [Validators.required]],

      // Étape 3 : Informations établissement
      nom_etablissement: ['', [Validators.required, Validators.minLength(3)]],
      adresse_etablissement: ['', [Validators.required]],
      ville_etablissement: ['', [Validators.required]],
      telephone_etablissement: ['', [Validators.pattern(/^[0-9]{9,}$/)]],
      email_etablissement: ['', [Validators.email]],

      // Acceptation CGU
      acceptCGU: [false, [Validators.requiredTrue]]
    }, {
      validators: this.passwordMatchValidator
    });
  }

  passwordMatchValidator(control: AbstractControl): ValidationErrors | null {
    const password = control.get('mot_de_passe');
    const confirmPassword = control.get('confirm_password');

    if (!password || !confirmPassword) {
      return null;
    }

    return password.value === confirmPassword.value ? null : { passwordMismatch: true };
  }

  // ✅ Gestion du logo
  onLogoSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      const file = input.files[0];

      // Vérifier le type de fichier
      if (!file.type.match(/image\/(png|jpg|jpeg)/)) {
        this.errorMessage = 'Format de fichier non supporté. Utilisez PNG, JPG ou JPEG.';
        return;
      }

      // Vérifier la taille (max 2MB)
      if (file.size > 2 * 1024 * 1024) {
        this.errorMessage = 'Le fichier est trop volumineux. Maximum 2MB.';
        return;
      }

      this.logoFile = file;

      // Créer un aperçu
      const reader = new FileReader();
      reader.onload = (e: ProgressEvent<FileReader>) => {
        if (e.target?.result) {
          this.logoPreview = e.target.result as string;
        }
      };
      reader.readAsDataURL(file);

      this.errorMessage = '';
    }
  }

  removeLogo(): void {
    this.logoFile = null;
    this.logoPreview = null;
  }

  nextStep(): void {
    if (this.currentStep === 1 && this.isStep1Valid()) {
      this.currentStep++;
    } else if (this.currentStep === 2 && this.isStep2Valid()) {
      this.currentStep++;
    } else {
      this.markStepAsTouched(this.currentStep);
    }
  }

  previousStep(): void {
    if (this.currentStep > 1) {
      this.currentStep--;
    }
  }

  onSubmit(): void {
    if (this.inscriptionForm.invalid) {
      this.inscriptionForm.markAllAsTouched();
      return;
    }

    this.isLoading = true;
    this.errorMessage = '';

    // ✅ Créer FormData pour envoyer le fichier
    const formData = new FormData();

    // Ajouter les données textuelles
    formData.append('nom', this.inscriptionForm.value.nom);
    formData.append('prenom', this.inscriptionForm.value.prenom);
    formData.append('email', this.inscriptionForm.value.email);
    formData.append('mot_de_passe', this.inscriptionForm.value.mot_de_passe);
    formData.append('nom_etablissement', this.inscriptionForm.value.nom_etablissement);
    formData.append('adresse_etablissement', this.inscriptionForm.value.adresse_etablissement);
    formData.append('ville_etablissement', this.inscriptionForm.value.ville_etablissement);

    if (this.inscriptionForm.value.telephone) {
      formData.append('telephone', this.inscriptionForm.value.telephone);
    }
    if (this.inscriptionForm.value.telephone_etablissement) {
      formData.append('telephone_etablissement', this.inscriptionForm.value.telephone_etablissement);
    }
    if (this.inscriptionForm.value.email_etablissement) {
      formData.append('email_etablissement', this.inscriptionForm.value.email_etablissement);
    }

    // ✅ Ajouter le fichier logo
    if (this.logoFile) {
      formData.append('logo', this.logoFile);
    }

    // Appel au service avec FormData
    this.authService.registerWithFormData(formData).subscribe({
      next: () => {
        // Redirection automatique gérée par le service
      },
      error: (error: any) => {
        this.isLoading = false;
        this.errorMessage = error.error?.message || 'Une erreur est survenue lors de l\'inscription';
      }
    });
  }

  togglePasswordVisibility(): void {
    this.showPassword = !this.showPassword;
  }

  toggleConfirmPasswordVisibility(): void {
    this.showConfirmPassword = !this.showConfirmPassword;
  }

  navigateToLogin(): void {
    this.router.navigate(['/connexion']);
  }

  isStep1Valid(): boolean {
    return !!(this.inscriptionForm.get('nom')?.valid &&
             this.inscriptionForm.get('prenom')?.valid &&
             this.inscriptionForm.get('email')?.valid);
  }

  isStep2Valid(): boolean {
    return !!(this.inscriptionForm.get('mot_de_passe')?.valid &&
             this.inscriptionForm.get('confirm_password')?.valid &&
             !this.inscriptionForm.errors?.['passwordMismatch']);
  }

  isStep3Valid(): boolean {
    return !!(this.inscriptionForm.get('nom_etablissement')?.valid &&
             this.inscriptionForm.get('adresse_etablissement')?.valid &&
             this.inscriptionForm.get('ville_etablissement')?.valid &&
             this.inscriptionForm.get('acceptCGU')?.valid);
  }

  markStepAsTouched(step: number): void {
    if (step === 1) {
      this.inscriptionForm.get('nom')?.markAsTouched();
      this.inscriptionForm.get('prenom')?.markAsTouched();
      this.inscriptionForm.get('email')?.markAsTouched();
    } else if (step === 2) {
      this.inscriptionForm.get('mot_de_passe')?.markAsTouched();
      this.inscriptionForm.get('confirm_password')?.markAsTouched();
    } else if (step === 3) {
      this.inscriptionForm.get('nom_etablissement')?.markAsTouched();
      this.inscriptionForm.get('adresse_etablissement')?.markAsTouched();
      this.inscriptionForm.get('ville_etablissement')?.markAsTouched();
      this.inscriptionForm.get('acceptCGU')?.markAsTouched();
    }
  }

  getProgressPercentage(): number {
    return (this.currentStep / this.totalSteps) * 100;
  }

  get nom() { return this.inscriptionForm.get('nom'); }
  get prenom() { return this.inscriptionForm.get('prenom'); }
  get email() { return this.inscriptionForm.get('email'); }
  get telephone() { return this.inscriptionForm.get('telephone'); }
  get mot_de_passe() { return this.inscriptionForm.get('mot_de_passe'); }
  get confirm_password() { return this.inscriptionForm.get('confirm_password'); }
  get nom_etablissement() { return this.inscriptionForm.get('nom_etablissement'); }
  get adresse_etablissement() { return this.inscriptionForm.get('adresse_etablissement'); }
  get ville_etablissement() { return this.inscriptionForm.get('ville_etablissement'); }
  get telephone_etablissement() { return this.inscriptionForm.get('telephone_etablissement'); }
  get email_etablissement() { return this.inscriptionForm.get('email_etablissement'); }
  get acceptCGU() { return this.inscriptionForm.get('acceptCGU'); }

  formatFileSize(): string {
  if (!this.logoFile) return '';
  const size = this.logoFile.size;
  if (size < 1024) return size + ' o';
  if (size < 1024 * 1024) return (size / 1024).toFixed(1) + ' Ko';
  return (size / (1024 * 1024)).toFixed(1) + ' Mo';
}
}
