import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ClasseService } from '../../../core/service/classe.service';
import { AuthService } from '../../../core/service/auth.service';
import { Classe } from '../../../core/models/classe';

@Component({
  selector: 'app-import-eleves',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './import-eleves.component.html',
  styleUrls: ['./import-eleves.component.scss']
})
export class ImportElevesComponent implements OnInit {

  // États
  loading = false;
  loadingClasses = false;
  error: string | null = null;
  success: string | null = null;

  // Données
  classes: Classe[] = [];
  selectedClasseId: number | null = null;
  selectedFile: File | null = null;
  fileName: string = '';

  // Résultats de l'import
  importResults: any = null;
  showResults = false;

  // Étapes
  currentStep: 'select-classe' | 'upload-file' | 'results' = 'select-classe';

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

    this.loadClasses();
  }

  /**
   * Charger la liste des classes
   */
  loadClasses(): void {
    this.loadingClasses = true;
    this.error = null;

    this.classeService.getClasses({ annee_active: true }).subscribe({
      next: (response) => {
        if (response.success) {
          this.classes = response.data || [];
        }
        this.loadingClasses = false;
      },
      error: (err) => {
        console.error('Erreur chargement classes', err);
        this.error = 'Impossible de charger les classes';
        this.loadingClasses = false;
      }
    });
  }

  /**
   * Sélectionner une classe
   */
  selectClasse(classeId: number): void {
    this.selectedClasseId = classeId;
    this.currentStep = 'upload-file';
    this.error = null;
  }

  /**
   * Retour à la sélection de classe
   */
  backToClasseSelection(): void {
    this.currentStep = 'select-classe';
    this.selectedClasseId = null;
    this.selectedFile = null;
    this.fileName = '';
    this.error = null;
  }

  /**
   * Gestion du changement de fichier
   */
  onFileSelected(event: any): void {
    const file = event.target.files[0];

    if (file) {
      // Vérifier le type de fichier
      const allowedExtensions = ['xlsx', 'xls', 'csv'];
      const fileExtension = file.name.split('.').pop()?.toLowerCase();

      if (!fileExtension || !allowedExtensions.includes(fileExtension)) {
        this.error = 'Format de fichier non supporté. Utilisez .xlsx, .xls ou .csv';
        this.selectedFile = null;
        this.fileName = '';
        return;
      }

      // Vérifier la taille (max 5MB)
      const maxSize = 5 * 1024 * 1024; // 5MB
      if (file.size > maxSize) {
        this.error = 'Le fichier est trop volumineux (max 5MB)';
        this.selectedFile = null;
        this.fileName = '';
        return;
      }

      this.selectedFile = file;
      this.fileName = file.name;
      this.error = null;
    }
  }

  /**
   * Déclencher le sélecteur de fichier
   */
  triggerFileInput(): void {
    const fileInput = document.getElementById('fileInput') as HTMLInputElement;
    fileInput?.click();
  }

  /**
   * Supprimer le fichier sélectionné
   */
  removeFile(): void {
    this.selectedFile = null;
    this.fileName = '';
    const fileInput = document.getElementById('fileInput') as HTMLInputElement;
    if (fileInput) {
      fileInput.value = '';
    }
  }

  /**
   * Lancer l'import
   */
  startImport(): void {
    if (!this.selectedClasseId || !this.selectedFile) {
      this.error = 'Veuillez sélectionner une classe et un fichier';
      return;
    }

    this.loading = true;
    this.error = null;
    this.success = null;

    this.classeService.importEleves(this.selectedClasseId, this.selectedFile).subscribe({
      next: (response) => {
        this.loading = false;
        if (response.success) {
          this.success = response.message;
          this.importResults = response.data;
          this.showResults = true;
          this.currentStep = 'results';
        } else {
          this.error = response.message || 'Erreur lors de l\'import';
        }
      },
      error: (err) => {
        this.loading = false;
        console.error('Erreur import', err);
        this.error = err.error?.message || 'Erreur lors de l\'import des élèves';
      }
    });
  }

  /**
   * Télécharger le modèle Excel
   */
  downloadTemplate(): void {
    // Créer un lien de téléchargement vers le template
    window.open('/assets/templates/modele_import_eleves.xlsx', '_blank');
  }

  /**
   * Terminer et retourner
   */
  finish(): void {
    this.router.navigate(['/proviseur/classes']);
  }

  /**
   * Obtenir le nom de la classe sélectionnée
   */
  getSelectedClasseName(): string {
    if (!this.selectedClasseId) return '';
    const classe = this.classes.find(c => c.id === this.selectedClasseId);
    return classe ? classe.nom : '';
  }

  /**
   * Recommencer l'import
   */
  restartImport(): void {
    this.currentStep = 'select-classe';
    this.selectedClasseId = null;
    this.selectedFile = null;
    this.fileName = '';
    this.importResults = null;
    this.showResults = false;
    this.error = null;
    this.success = null;
  }
}
