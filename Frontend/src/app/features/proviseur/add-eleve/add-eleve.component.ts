import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { EleveService } from '../../../core/service/eleve.service';
import { ClasseService } from '../../../core/service/classe.service';

@Component({
  selector: 'app-add-eleve',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './add-eleve.component.html',
  styleUrls: ['./add-eleve.component.scss']
})
export class AddEleveComponent implements OnInit {

  formData = {
    classe_id: 0,
    matricule: '',
    nom: '',
    prenom: '',
    date_naissance: '',
    lieu_naissance: '',
    sexe: 'M' as 'M' | 'F',
    contact_parent: '',
    nom_parent: ''
  };

  loading = false;
  error: string | null = null;
  success: string | null = null;
  errors: any = {};

  classes: any[] = [];
  loadingClasses = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private eleveService: EleveService,
    private classeService: ClasseService
  ) {}

  ngOnInit(): void {
    // Récupérer classe_id depuis query params
    this.route.queryParams.subscribe(params => {
      if (params['classe_id']) {
        this.formData.classe_id = +params['classe_id'];
      }
    });

    this.loadClasses();
  }

  loadClasses(): void {
    this.loadingClasses = true;
    this.classeService.getClasses({ annee_active: true }).subscribe({
      next: (response) => {
        if (response.success) {
          this.classes = response.data || [];
        }
        this.loadingClasses = false;
      },
      error: () => {
        this.loadingClasses = false;
      }
    });
  }

  validateForm(): boolean {
    this.errors = {};
    let isValid = true;

    if (!this.formData.classe_id) {
      this.errors.classe_id = 'La classe est requise';
      isValid = false;
    }

    if (!this.formData.matricule.trim()) {
      this.errors.matricule = 'Le matricule est requis';
      isValid = false;
    }

    if (!this.formData.nom.trim()) {
      this.errors.nom = 'Le nom est requis';
      isValid = false;
    }

    if (!this.formData.prenom.trim()) {
      this.errors.prenom = 'Le prénom est requis';
      isValid = false;
    }

    if (!this.formData.date_naissance) {
      this.errors.date_naissance = 'La date de naissance est requise';
      isValid = false;
    }

    if (!this.formData.sexe) {
      this.errors.sexe = 'Le sexe est requis';
      isValid = false;
    }

    return isValid;
  }

  onSubmit(): void {
    this.error = null;
    this.success = null;

    if (!this.validateForm()) {
      this.error = 'Veuillez corriger les erreurs dans le formulaire';
      return;
    }

    this.loading = true;

    this.eleveService.createEleve(this.formData).subscribe({
      next: (response) => {
        this.loading = false;
        if (response.success) {
          this.success = `Élève ${this.formData.prenom} ${this.formData.nom} créé avec succès !`;

          setTimeout(() => {
            this.router.navigate(['/proviseur/classes', this.formData.classe_id]);
          }, 2000);
        } else {
          this.error = response.message || 'Erreur lors de la création';
        }
      },
      error: (err) => {
        this.loading = false;
        console.error('Erreur création élève', err);

        if (err.error?.errors) {
          this.errors = err.error.errors;
          this.error = 'Veuillez corriger les erreurs';
        } else {
          this.error = err.error?.message || 'Erreur lors de la création de l\'élève';
        }
      }
    });
  }

  onCancel(): void {
    if (this.formData.classe_id) {
      this.router.navigate(['/proviseur/classes', this.formData.classe_id]);
    } else {
      this.router.navigate(['/proviseur/classes']);
    }
  }
}
