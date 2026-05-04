import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { DashboardService } from '../../../core/service/dashboard.service';
import { AuthService } from '../../../core/service/auth.service';
import { User } from '../../../core/models/user';
import { Etablissement } from '../../../core/models/etablissement';

@Component({
  selector: 'app-operateur-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class OperateurDashboardComponent implements OnInit {

  loading = true;
  error: string | null = null;
  currentUser: User | null = null;
  etablissement: Etablissement | null = null;
  dashboardData: any = null;

  constructor(
    private dashboardService: DashboardService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadUserData();
    this.loadDashboardData();
  }

  private loadUserData(): void {
    this.currentUser = this.authService.getCurrentUser();
    this.etablissement = this.authService.getCurrentEtablissement();
  }

  loadDashboardData(): void {
    this.loading = true;
    this.error = null;

    this.dashboardService.getOperateurDashboard().subscribe({
      next: (response) => {
        if (response.success) {
          this.dashboardData = response.data;
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement dashboard', err);
        this.error = 'Impossible de charger les données';
        this.loading = false;
      }
    });
  }

  refresh(): void {
    this.loadDashboardData();
  }

  navigateTo(route: string): void {
    this.router.navigate([route]);
  }

  formatNumber(num: number): string {
    return num?.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') || '0';
  }

  getProgressPercentage(): number {
    if (!this.dashboardData) return 0;
    const total = this.dashboardData.kpis.mes_photos + this.dashboardData.kpis.eleves_restants;
    if (total === 0) return 0;
    return Math.round((this.dashboardData.kpis.mes_photos / total) * 100);
  }

  getProgressColor(): string {
    const percentage = this.getProgressPercentage();
    if (percentage >= 80) return 'success';
    if (percentage >= 50) return 'warning';
    return 'danger';
  }

  getGreeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) return 'Bonjour';
    if (hour < 18) return 'Bon après-midi';
    return 'Bonsoir';
  }

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/connexion']);
  }
}
