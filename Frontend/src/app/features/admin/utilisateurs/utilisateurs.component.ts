import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AdminService } from '../../../core/service/admin.service';

@Component({
  selector: 'app-utilisateurs',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './utilisateurs.component.html',
  styleUrls: ['./utilisateurs.component.scss']
})
export class UtilisateursComponent implements OnInit {
  loading = true;
  error: string | null = null;
  
  utilisateurs: any[] = [];
  
  // Filtres
  search = '';
  role = '';
  actif: any = '';
  
  // Pagination
  currentPage = 1;
  totalPages = 1;
  total = 0;
  perPage = 15;

  roles = [
    { value: '', label: 'Tous les rôles' },
    { value: 'admin', label: 'Admin' },
    { value: 'proviseur', label: 'Proviseur' },
    { value: 'surveillant', label: 'Surveillant' },
    { value: 'operateur', label: 'Opérateur' }
  ];

  constructor(
    private adminService: AdminService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadUtilisateurs();
  }

  loadUtilisateurs(): void {
    this.loading = true;
    this.error = null;

    this.adminService.getUtilisateurs({
      page: this.currentPage,
      per_page: this.perPage,
      search: this.search || undefined,
      role: this.role || undefined,
      actif: this.actif !== '' ? this.actif === 'true' : undefined
    }).subscribe({
      next: (res) => {
        if (res.success) {
          this.utilisateurs = res.data || [];
          if (res.pagination) {
            this.totalPages = res.pagination.last_page;
            this.total = res.pagination.total;
          }
        } else {
          this.error = res.message || 'Erreur chargement utilisateurs';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement utilisateurs', err);
        this.error = 'Impossible de charger les utilisateurs';
        this.loading = false;
      }
    });
  }

  onFilter(): void {
    this.currentPage = 1;
    this.loadUtilisateurs();
  }

  goToPage(page: number): void {
    this.currentPage = page;
    this.loadUtilisateurs();
  }

  getRoleBadgeClass(role: string): string {
    switch (role) {
      case 'admin': return 'bg-danger';
      case 'proviseur': return 'bg-primary';
      case 'surveillant': return 'bg-info';
      case 'operateur': return 'bg-success';
      default: return 'bg-secondary';
    }
  }

  formatRole(role: string): string {
    switch (role) {
      case 'admin': return 'Admin';
      case 'proviseur': return 'Proviseur';
      case 'surveillant': return 'Surveillant';
      case 'operateur': return 'Opérateur';
      default: return role;
    }
  }

  retourDashboard(): void {
    this.router.navigate(['/admin/dashboard']);
  }
}
