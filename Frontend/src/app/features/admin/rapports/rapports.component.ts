import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AdminService } from '../../../core/service/admin.service';

@Component({
  selector: 'app-rapports',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './rapports.component.html',
  styleUrls: ['./rapports.component.scss']
})
export class RapportsComponent implements OnInit {
  loading = false;
  error: string | null = null;
  
  dateDebut = '';
  dateFin = '';
  rapport: any = null;

  constructor(
    private adminService: AdminService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Définir les dates par défaut (dernier mois)
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    
    this.dateFin = today.toISOString().split('T')[0];
    this.dateDebut = lastMonth.toISOString().split('T')[0];
  }

  genererRapport(): void {
    if (!this.dateDebut || !this.dateFin) {
      alert('Veuillez sélectionner les dates');
      return;
    }

    this.loading = true;
    this.error = null;

    this.adminService.getRapportActivite(this.dateDebut, this.dateFin).subscribe({
      next: (res) => {
        if (res.success) {
          this.rapport = res.data;
        } else {
          this.error = res.message || 'Erreur génération rapport';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur génération rapport', err);
        this.error = 'Impossible de générer le rapport';
        this.loading = false;
      }
    });
  }

  retourDashboard(): void {
    this.router.navigate(['/admin/dashboard']);
  }
}
