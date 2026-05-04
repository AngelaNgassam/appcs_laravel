import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AdminService } from '../../../core/service/admin.service';

@Component({
  selector: 'app-etablissements',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './etablissements.component.html',
  styleUrls: ['./etablissements.component.scss']
})
export class EtablissementsComponent implements OnInit {
  loading = true;
  error: string | null = null;
  
  etablissements: any[] = [];
  
  // Filtres
  search = '';
  ville = '';
  includeArchived = false;
  
  // Pagination
  currentPage = 1;
  totalPages = 1;
  total = 0;
  perPage = 15;

  constructor(
    private adminService: AdminService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadEtablissements();
  }

  loadEtablissements(): void {
    this.loading = true;
    this.error = null;

    this.adminService.getEtablissements({
      page: this.currentPage,
      per_page: this.perPage,
      search: this.search || undefined,
      ville: this.ville || undefined,
      include_archived: this.includeArchived
    }).subscribe({
      next: (res) => {
        if (res.success) {
          this.etablissements = res.data || [];
          if (res.pagination) {
            this.totalPages = res.pagination.last_page;
            this.total = res.pagination.total;
          }
        } else {
          this.error = res.message || 'Erreur chargement établissements';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement établissements', err);
        this.error = 'Impossible de charger les établissements';
        this.loading = false;
      }
    });
  }

  onFilter(): void {
    this.currentPage = 1;
    this.loadEtablissements();
  }

  goToPage(page: number): void {
    this.currentPage = page;
    this.loadEtablissements();
  }

  archiverEtablissement(id: number): void {
    if (!confirm('Voulez-vous vraiment archiver cet établissement ? Tous ses utilisateurs seront désactivés.')) {
      return;
    }

    this.adminService.archiverEtablissement(id).subscribe({
      next: (res) => {
        if (res.success) {
          alert('Établissement archivé avec succès');
          this.loadEtablissements();
        } else {
          alert(res.message || 'Erreur lors de l\'archivage');
        }
      },
      error: (err) => {
        console.error('Erreur archivage', err);
        alert('Erreur lors de l\'archivage');
      }
    });
  }

  restaurerEtablissement(id: number): void {
    if (!confirm('Voulez-vous vraiment restaurer cet établissement ?')) {
      return;
    }

    this.adminService.restaurerEtablissement(id).subscribe({
      next: (res) => {
        if (res.success) {
          alert('Établissement restauré avec succès');
          this.loadEtablissements();
        } else {
          alert(res.message || 'Erreur lors de la restauration');
        }
      },
      error: (err) => {
        console.error('Erreur restauration', err);
        alert('Erreur lors de la restauration');
      }
    });
  }

  retourDashboard(): void {
    this.router.navigate(['/admin/dashboard']);
  }
}
