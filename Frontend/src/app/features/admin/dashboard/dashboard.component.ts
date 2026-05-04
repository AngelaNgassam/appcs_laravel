import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { AdminService } from '../../../core/service/admin.service';
import { AuthService } from '../../../core/service/auth.service';
import { Router } from '@angular/router';
import { Footer } from '../../../shared/components/footer/footer';
import { Sidebar } from '../../../shared/components/sidebar/sidebar';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, Footer, Sidebar],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class AdminDashboardComponent implements OnInit {
  loading = true;
  error: string | null = null;
  
  currentUser: any = null;
  dashboardData: any = null;
  
  // Statistiques
  statistiques: any = null;
  evolutionEtablissements: any[] = [];
  evolutionCartes: any[] = [];
  topEtablissements: any[] = [];
  activitesRecentes: any[] = [];
  repartitionGeographique: any[] = [];

  constructor(
    private adminService: AdminService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadCurrentUser();
    this.loadDashboard();
  }

  loadCurrentUser(): void {
    this.currentUser = this.authService.getCurrentUser();
    
    // Vérifier que l'utilisateur est bien admin
    if (!this.currentUser) {
      this.router.navigate(['/login']);
      return;
    }
    
    if (this.currentUser.role !== 'admin') {
      this.router.navigate(['/']);
    }
  }

  loadDashboard(): void {
    this.loading = true;
    this.error = null;

    this.adminService.getDashboard().subscribe({
      next: (res) => {
        if (res.success) {
          this.dashboardData = res.data;
          this.statistiques = res.data.statistiques;
          this.evolutionEtablissements = res.data.evolution_etablissements || [];
          this.evolutionCartes = res.data.evolution_cartes || [];
          this.topEtablissements = res.data.top_etablissements || [];
          this.activitesRecentes = res.data.activites_recentes || [];
          this.repartitionGeographique = res.data.repartition_geographique || [];
        } else {
          this.error = res.message || 'Erreur chargement dashboard';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement dashboard', err);
        this.error = 'Impossible de charger le dashboard';
        this.loading = false;
      }
    });
  }

  logout(): void {
    if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
      this.authService.logout().subscribe({
        next: () => {
          this.router.navigate(['/login']);
        },
        error: (err) => {
          console.error('Erreur logout', err);
          // Déconnecter quand même côté client
          localStorage.removeItem('token');
          this.router.navigate(['/login']);
        }
      });
    }
  }

  navigateTo(route: string): void {
    this.router.navigate([route]);
  }

  // Helpers pour les graphiques
  getMaxValue(data: any[], key: string): number {
    if (!data || data.length === 0) return 100;
    return Math.max(...data.map(d => d[key] || 0));
  }

  getBarHeight(value: number, maxValue: number): number {
    if (maxValue === 0) return 0;
    return (value / maxValue) * 100;
  }

  // Helper pour les badges de rôle
  getRoleBadgeClass(role: string): string {
    switch (role) {
      case 'admin': return 'bg-danger';
      case 'proviseur': return 'bg-primary';
      case 'surveillant': return 'bg-info';
      case 'operateur': return 'bg-success';
      default: return 'bg-secondary';
    }
  }

  // Helper pour formater les rôles
  formatRole(role: string): string {
    switch (role) {
      case 'admin': return 'Admin';
      case 'proviseur': return 'Proviseur';
      case 'surveillant': return 'Surveillant';
      case 'operateur': return 'Opérateur';
      default: return role;
    }
  }

  // Helper pour les icônes d'activité
  getActivityIcon(type: string): string {
    switch (type) {
      case 'creation_etablissement': return 'bi-building-add';
      case 'creation_utilisateur': return 'bi-person-plus';
      case 'generation_carte': return 'bi-credit-card';
      case 'validation_photo': return 'bi-check-circle';
      case 'modification': return 'bi-pencil';
      case 'suppression': return 'bi-trash';
      default: return 'bi-circle';
    }
  }

  // Helper pour les classes d'icône d'activité
  getActivityIconClass(type: string): string {
    switch (type) {
      case 'creation_etablissement': return 'bg-primary';
      case 'creation_utilisateur': return 'bg-info';
      case 'generation_carte': return 'bg-success';
      case 'validation_photo': return 'bg-warning';
      case 'modification': return 'bg-secondary';
      case 'suppression': return 'bg-danger';
      default: return 'bg-light';
    }
  }
}
