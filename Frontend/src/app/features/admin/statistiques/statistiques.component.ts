import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AdminService } from '../../../core/service/admin.service';

@Component({
  selector: 'app-statistiques',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './statistiques.component.html',
  styleUrls: ['./statistiques.component.scss']
})
export class StatistiquesComponent implements OnInit {
  loading = true;
  error: string | null = null;
  
  stats: any = null;

  constructor(
    private adminService: AdminService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadStatistiques();
  }

  loadStatistiques(): void {
    this.loading = true;
    this.error = null;

    this.adminService.getStatistiquesSysteme().subscribe({
      next: (res) => {
        if (res.success) {
          this.stats = res.data;
        } else {
          this.error = res.message || 'Erreur chargement statistiques';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement statistiques', err);
        this.error = 'Impossible de charger les statistiques';
        this.loading = false;
      }
    });
  }

  retourDashboard(): void {
    this.router.navigate(['/admin/dashboard']);
  }
}
