import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ClasseService } from '../../../core/service/classe.service';
import { AuthService } from '../../../core/service/auth.service';

interface NiveauOption {
  value: string;
  label: string;
  series?: string[];
}

@Component({
  selector: 'app-create-classe',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './create-classe.component.html',
  styleUrls: ['./create-classe.component.scss']
})
export class CreateClasseComponent implements OnInit {

  // Formulaire
  formData = {
    nom: '',
    niveau: '',
    serie: ''
  };

  // États
  loading = false;
  error: string | null = null;
  success: string | null = null;

  // Validation
  errors: any = {};

  // Options de niveaux
  niveauxOptions: NiveauOption[] = [
    { value: '6ème', label: '6ème (Sixième)', series: [] },
    { value: '5ème', label: '5ème (Cinquième)', series: [] },
    { value: '4ème', label: '4ème (Quatrième)', series: [] },
    { value: '3ème', label: '3ème (Troisième)', series: [] },
    { value: '2nde', label: '2nde (Seconde)', series: ['A', 'C', 'D'] },
    { value: '1ère', label: '1ère (Première)', series: ['A', 'C', 'D'] },
    { value: 'Tle', label: 'Tle (Terminale)', series: ['A', 'C', 'D'] },
  ];

  seriesDisponibles: string[] = [];
  showSerieField = false;

  constructor(
    private classeService: ClasseService,
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
   * Gestion du changement de niveau
   */
  onNiveauChange(): void {
    const niveauSelectionne = this.niveauxOptions.find(n => n.value === this.formData.niveau);

    if (niveauSelectionne && niveauSelectionne.series && niveauSelectionne.series.length > 0) {
      this.showSerieField = true;
      this.seriesDisponibles = niveauSelectionne.series;
    } else {
      this.showSerieField = false;
      this.seriesDisponibles = [];
      this.formData.serie = '';
    }

    // Générer automatiquement le nom si vide
    // if (!this.formData.nom) {
    //   this.generateNom();
    // }
  }

  /**
   * Générer automatiquement le nom de la classe
   */
  generateNom(): void {
    if (this.formData.niveau) {
      // Format : "6ème A", "2nde C", etc.
      this.formData.nom = this.formData.niveau;
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
      this.errors.nom = 'Le nom de la classe est requis';
      isValid = false;
    }

    // Niveau
    if (!this.formData.niveau) {
      this.errors.niveau = 'Le niveau est requis';
      isValid = false;
    }

    // Série (si champ affiché)
    if (this.showSerieField && !this.formData.serie) {
      this.errors.serie = 'La série est requise pour ce niveau';
      isValid = false;
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

    // Construire le nom complet
    let nomComplet = this.formData.nom;
    if (this.formData.serie) {
      nomComplet = `${this.formData.niveau} ${this.formData.serie}`;
    }

    const dataToSend = {
      nom: nomComplet,
      niveau: this.formData.niveau,
      serie: this.formData.serie || undefined
    };

    this.classeService.createClasse(dataToSend).subscribe({
      next: (response) => {
        this.loading = false;
        if (response.success) {
          this.success = `Classe ${nomComplet} créée avec succès !`;

          // Réinitialiser le formulaire
          this.formData = {
            nom: '',
            niveau: '',
            serie: ''
          };
          this.showSerieField = false;

          // Rediriger après 2 secondes
          setTimeout(() => {
            this.router.navigate(['/proviseur/classes']);
          }, 2000);
        } else {
          this.error = response.message || 'Erreur lors de la création';
        }
      },
      error: (err) => {
        this.loading = false;
        console.error('Erreur création classe', err);

        if (err.error?.errors) {
          this.errors = err.error.errors;
          this.error = 'Veuillez corriger les erreurs';
        } else {
          this.error = err.error?.message || 'Erreur lors de la création de la classe';
        }
      }
    });
  }

  /**
   * Annuler et retourner
   */
  onCancel(): void {
    this.router.navigate(['/proviseur/classes']);
  }
}
