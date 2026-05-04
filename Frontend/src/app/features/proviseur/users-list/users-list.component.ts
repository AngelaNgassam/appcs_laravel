import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { UserService } from '../../../core/service/user.service';

@Component({
  selector: 'app-users-list',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './users-list.component.html',
  styleUrls: ['./users-list.component.scss']
})
export class UsersListComponent implements OnInit {

  users: any[] = [];
  loading = true;
  error: string | null = null;

  // Filtres
  searchTerm = '';
  selectedRole = '';

  // Pagination
  currentPage = 1;
  totalPages = 1;
  totalUsers = 0;

  constructor(
    private userService: UserService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadUsers();
  }

  /**
   * Charger la liste des utilisateurs
   */
  loadUsers(): void {
    this.loading = true;
    this.error = null;

    this.userService.getUsers({
      page: this.currentPage,
      per_page: 15,
      role: this.selectedRole || undefined,
      search: this.searchTerm || undefined
    }).subscribe({
      next: (response) => {
        if (response.success) {
          this.users = response.data || [];
          if (response.pagination) {
            this.totalPages = response.pagination.last_page;
            this.totalUsers = response.pagination.total;
          }
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

  /**
   * Recherche
   */
  onSearch(): void {
    this.currentPage = 1;
    this.loadUsers();
  }

  /**
   * Filtre par rôle
   */
  onRoleFilter(): void {
    this.currentPage = 1;
    this.loadUsers();
  }

  /**
   * Pagination
   */
  goToPage(page: number): void {
    this.currentPage = page;
    this.loadUsers();
  }

  /**
   * Activer/Désactiver
   */
  toggleActive(user: any): void {
    if (confirm(`Voulez-vous vraiment ${user.actif ? 'désactiver' : 'activer'} ${user.prenom} ${user.nom} ?`)) {
      this.userService.toggleActive(user.id).subscribe({
        next: (response) => {
          if (response.success) {
            this.loadUsers();
          }
        },
        error: (err) => console.error('Erreur toggle active', err)
      });
    }
  }

  /**
   * Naviguer vers création
   */
  createUser(): void {
    this.router.navigate(['/proviseur/utilisateurs/nouveau']);
  }

  /**
   * Badge de rôle
   */
  getRoleBadgeClass(role: string): string {
    const classes: any = {
      'admin': 'bg-danger',
      'proviseur': 'bg-primary',
      'surveillant': 'bg-success',
      'operateur': 'bg-info'
    };
    return classes[role] || 'bg-secondary';
  }

  /**
   * Icône de rôle
   */
  getRoleIcon(role: string): string {
    const icons: any = {
      'admin': 'bi-shield-fill-check',
      'proviseur': 'bi-person-badge-fill',
      'surveillant': 'bi-shield-check',
      'operateur': 'bi-camera-fill'
    };
    return icons[role] || 'bi-person';
  }
}
