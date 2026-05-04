import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { PhotoModerationService } from '../../../core/service/photo-moderation.service';

@Component({
  selector: 'app-surveillant-photos',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './photos.component.html',
  styleUrls: ['./photos.component.scss']
})
export class SurveillantPhotosComponent implements OnInit {
  loading = true;
  error: string | null = null;

  photos: any[] = [];

  // Filtres
  statut: 'brouillon' | 'validee' | 'refusee' | 'archivee' = 'brouillon';
  searchMatricule = '';

  // Pagination
  currentPage = 1;
  totalPages = 1;
  total = 0;

  // Action
  actionLoadingId: number | null = null;

  // Modal motif
  showRefusModal = false;
  refusPhoto: any = null;
  refusMotif = '';

  constructor(private photoModerationService: PhotoModerationService) {}

  ngOnInit(): void {
    this.loadPhotos();
  }

  loadPhotos(): void {
    this.loading = true;
    this.error = null;

    this.photoModerationService.listPhotos({
      page: this.currentPage,
      per_page: 20,
      statut: this.statut,
      include_archived: false
    }).subscribe({
      next: (res) => {
        if (res.success) {
          let data = res.data || [];

          // Filtre matricule côté UI (simple)
          if (this.searchMatricule) {
            const term = this.searchMatricule.toLowerCase();
            data = data.filter(p => (p?.eleve?.matricule || '').toLowerCase().includes(term));
          }

          this.photos = data;
          if (res.pagination) {
            this.totalPages = res.pagination.last_page;
            this.total = res.pagination.total;
          }
        } else {
          this.error = res.message || 'Erreur chargement photos';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement photos', err);
        this.error = 'Impossible de charger les photos';
        this.loading = false;
      }
    });
  }

  onFilterChange(): void {
    this.currentPage = 1;
    this.loadPhotos();
  }

  goToPage(page: number): void {
    this.currentPage = page;
    this.loadPhotos();
  }

  valider(photo: any): void {
    if (!photo?.id) return;
    this.actionLoadingId = photo.id;

    this.photoModerationService.validerPhoto(photo.id).subscribe({
      next: (res) => {
        this.actionLoadingId = null;
        if (res.success) {
          this.loadPhotos();
        } else {
          alert(res.message || 'Erreur validation');
        }
      },
      error: (err) => {
        console.error('Erreur validation', err);
        this.actionLoadingId = null;
        alert('Erreur validation');
      }
    });
  }

  openRefus(photo: any): void {
    this.refusPhoto = photo;
    this.refusMotif = '';
    this.showRefusModal = true;
  }

  closeRefus(): void {
    this.showRefusModal = false;
    this.refusPhoto = null;
    this.refusMotif = '';
  }

  confirmerRefus(): void {
    if (!this.refusPhoto?.id) return;
    if (!this.refusMotif.trim()) {
      alert('Le motif est requis');
      return;
    }

    const id = this.refusPhoto.id;
    this.actionLoadingId = id;

    this.photoModerationService.refuserPhoto(id, this.refusMotif.trim()).subscribe({
      next: (res) => {
        this.actionLoadingId = null;
        if (res.success) {
          this.closeRefus();
          this.loadPhotos();
        } else {
          alert(res.message || 'Erreur refus');
        }
      },
      error: (err) => {
        console.error('Erreur refus', err);
        this.actionLoadingId = null;
        alert('Erreur refus');
      }
    });
  }

  getStatutBadge(statut: string): string {
    const map: any = {
      brouillon: 'bg-warning text-dark',
      validee: 'bg-success',
      refusee: 'bg-danger',
      archivee: 'bg-secondary'
    };
    return map[statut] || 'bg-secondary';
  }
}
