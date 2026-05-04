import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { DashboardService } from '../../../core/service/dashboard.service';
import { AuthService } from '../../../core/service/auth.service';
import { User } from '../../../core/models/user';
import { Etablissement } from '../../../core/models/etablissement';
import { Footer } from '../../../shared/components/footer/footer';
import { Sidebar } from '../../../shared/components/sidebar/sidebar';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule, Footer, Sidebar],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class SurveillantDashboardComponent implements OnInit {

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

    this.dashboardService.getSurveillantDashboard().subscribe({
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
    this.router.navigateByUrl(route);
  }

  formatNumber(num: number): string {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }
}
