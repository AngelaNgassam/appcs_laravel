import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClasseService } from '../../../core/service/classe.service';
import { AuthService } from '../../../core/service/auth.service';
import { Classe } from '../../../core/models/classe';


@Component({
  selector: 'app-classes-list',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './classes-list.component.html',
  styleUrls: ['./classes-list.component.scss']
})
export class ClassesListComponent implements OnInit {

  classes: Classe[] = [];
  loading = true;
  error: string | null = null;

  // Filtres
  searchTerm = '';
  selectedNiveau = '';

  // Pagination
  currentPage = 1;
  totalPages = 1;
  totalClasses = 0;

  // Niveaux disponibles
  niveaux = ['6ème', '5ème', '4ème', '3ème', '2nde', '1ère', 'Tle'];

  constructor(
    private classeService: ClasseService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadClasses();
  }

  /**
   * Charger la liste des classes
   */
  loadClasses(): void {
    this.loading = true;
    this.error = null;

    this.classeService.getClasses({
      page: this.currentPage,
      per_page: 12,
      niveau: this.selectedNiveau || undefined,
      search: this.searchTerm || undefined,
      annee_active: true
    }).subscribe({
      next: (response) => {
        if (response.success) {
          this.classes = response.data || [];
          if (response.pagination) {
            this.totalPages = response.pagination.last_page;
            this.totalClasses = response.pagination.total;
          }
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement classes', err);
        this.error = 'Impossible de charger les classes';
        this.loading = false;
      }
    });
  }

  /**
   * Recherche
   */
  onSearch(): void {
    this.currentPage = 1;
    this.loadClasses();
  }

  /**
   * Filtre par niveau
   */
  onNiveauFilter(): void {
    this.currentPage = 1;
    this.loadClasses();
  }

  /**
   * Pagination
   */
  goToPage(page: number): void {
    this.currentPage = page;
    this.loadClasses();
  }

  /**
   * Créer une classe
   */
  createClasse(): void {
    this.router.navigate(['/proviseur/classes/nouvelle']);
  }

  /**
   * Voir les détails d'une classe
   */
  viewClasse(id: number): void {
    this.router.navigate(['/proviseur/classes', id]);
  }

  /**
   * Importer des élèves
   */
  importEleves(classeId: number): void {
    this.router.navigate(['/proviseur/classes', classeId, 'importer']);
  }

  /**
   * Supprimer une classe
   */
  deleteClasse(classe: Classe): void {
    if (classe.effectif > 0) {
      alert('Impossible de supprimer une classe contenant des élèves');
      return;
    }

    if (confirm(`Voulez-vous vraiment supprimer la classe ${classe.nom} ?`)) {
      this.classeService.deleteClasse(classe.id).subscribe({
        next: (response) => {
          if (response.success) {
            this.loadClasses();
          }
        },
        error: (err) => {
          console.error('Erreur suppression', err);
          alert(err.error?.message || 'Erreur lors de la suppression');
        }
      });
    }
  }

  /**
   * Obtenir la couleur selon le niveau
   */
  getNiveauColor(niveau: string): string {
    const colors: any = {
      '6ème': 'primary',
      '5ème': 'success',
      '4ème': 'info',
      '3ème': 'warning',
      '2nde': 'danger',
      '1ère': 'purple',
      'Tle': 'dark'
    };
    return colors[niveau] || 'secondary';
  }

  /**
   * Obtenir le taux de remplissage
   */
  getTauxRemplissage(classe: Classe): number {
    const capaciteMax = 50; // Capacité standard
    return Math.round((classe.effectif / capaciteMax) * 100);
  }

  /**
   * Obtenir la classe CSS du taux
   */
  getTauxClass(taux: number): string {
    if (taux >= 90) return 'text-danger';
    if (taux >= 70) return 'text-warning';
    return 'text-success';
  }

  /**
 * Renommer une classe
 */
renameClasse(classe: Classe): void {
  const nouveauNom = prompt(`Nouveau nom pour la classe "${classe.nom}" :`, classe.nom);

  if (nouveauNom && nouveauNom.trim() !== '' && nouveauNom !== classe.nom) {
    this.classeService.updateClasse(classe.id, { nom: nouveauNom.trim() }).subscribe({
      next: (response) => {
        if (response.success) {
          this.loadClasses();
          alert(`Classe renommée avec succès : ${nouveauNom}`);
        }
      },
      error: (err) => {
        console.error('Erreur renommage', err);
        alert(err.error?.message || 'Erreur lors du renommage');
      }
    });
  }
}
}
