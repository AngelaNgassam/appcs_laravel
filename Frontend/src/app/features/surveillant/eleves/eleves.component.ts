import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { EleveService } from '../../../core/service/eleve.service';

@Component({
  selector: 'app-surveillant-eleves',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './eleves.component.html',
  styleUrls: ['./eleves.component.scss']
})
export class SurveillantElevesComponent implements OnInit {
  loading = true;
  error: string | null = null;

  eleves: any[] = [];

  // Filtres
  search = '';
  showArchived = false;

  // Pagination
  currentPage = 1;
  totalPages = 1;
  total = 0;

  constructor(private eleveService: EleveService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = null;

    this.eleveService.getEleves({
      page: this.currentPage,
      per_page: 20,
      search: this.search || undefined,
      archive: this.showArchived
    }).subscribe({
      next: (res) => {
        if (res.success) {
          this.eleves = res.data || [];
          if (res.pagination) {
            this.totalPages = res.pagination.last_page;
            this.total = res.pagination.total;
          }
        } else {
          this.error = res.message || 'Erreur chargement élèves';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement élèves', err);
        this.error = 'Impossible de charger les élèves';
        this.loading = false;
      }
    });
  }

  onSearch(): void {
    this.currentPage = 1;
    this.load();
  }

  goToPage(page: number): void {
    this.currentPage = page;
    this.load();
  }

  getPhotoStatutBadge(eleve: any): string {
    const statut = eleve?.photo_active?.statut;
    const map: any = {
      brouillon: 'bg-warning text-dark',
      validee: 'bg-success',
      refusee: 'bg-danger',
      archivee: 'bg-secondary'
    };
    return map[statut] || (eleve?.photo_active ? 'bg-secondary' : 'bg-secondary');
  }

  getPhotoStatutLabel(eleve: any): string {
    if (!eleve?.photo_active) return 'Aucune';
    return eleve?.photo_active?.statut || 'inconnu';
  }
}
