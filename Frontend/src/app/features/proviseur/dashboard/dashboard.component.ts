import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../../core/service/auth.service';
import { ClasseService } from '../../../core/service/classe.service';
import { EleveService } from '../../../core/service/eleve.service';

interface DashboardStats {
  totalClasses: number;
  totalEleves: number;
  elevesAvecPhoto: number;
  elevesSansPhoto: number;
  cartesGenerees: number;
  progressionPhotos: number;
  progressionCartes: number;
}

interface QuickAction {
  icon: string;
  title: string;
  description: string;
  route: string;
  color: string;
}

interface RecentActivity {
  icon: string;
  title: string;
  description: string;
  time: string;
  type: 'success' | 'info' | 'warning';
}

@Component({
  selector: 'app-proviseur-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class ProviseurDashboardComponent implements OnInit {
  currentUser: any = null;
  stats: DashboardStats = {
    totalClasses: 0,
    totalEleves: 0,
    elevesAvecPhoto: 0,
    elevesSansPhoto: 0,
    cartesGenerees: 0,
    progressionPhotos: 0,
    progressionCartes: 0
  };

  quickActions: QuickAction[] = [
    {
      icon: 'bi-grid-3x3',
      title: 'Créer une classe',
      description: 'Ajouter une nouvelle classe',
      route: '/proviseur/classes/nouvelle',
      color: 'primary'
    },
    {
      icon: 'bi-people',
      title: 'Gérer les utilisateurs',
      description: 'Voir et gérer les utilisateurs',
      route: '/proviseur/utilisateurs',
      color: 'success'
    },
    {
      icon: 'bi-file-earmark-spreadsheet',
      title: 'Importer des élèves',
      description: 'Import Excel des élèves',
      route: '/proviseur/eleves/importer',
      color: 'info'
    },
    {
      icon: 'bi-credit-card',
      title: 'Gérer les cartes',
      description: 'Modèles et impression',
      route: '/proviseur/cartes',
      color: 'warning'
    }
  ];

  recentActivities: RecentActivity[] = [];
  loading = true;
  error: string | null = null;

  constructor(
    private router: Router,
    private authService: AuthService,
    private classeService: ClasseService,
    private eleveService: EleveService
  ) {}

  ngOnInit(): void {
    this.currentUser = this.authService.getCurrentUser();
    this.loadDashboardData();
    this.loadRecentActivities();
  }

  /**
   * Charger les données du dashboard
   */
  loadDashboardData(): void {
    this.loading = true;
    this.error = null;

    // Charger les statistiques des classes
    this.classeService.getClasses().subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.stats.totalClasses = response.data.length;

          // Calculer les statistiques globales
          let totalEleves = 0;
          let elevesAvecPhoto = 0;
          let elevesSansPhoto = 0;

          response.data.forEach((classe: any) => {
            totalEleves += classe.effectif || 0;
            elevesAvecPhoto += classe.eleves_avec_photo || 0;
            elevesSansPhoto += classe.eleves_sans_photo || 0;
          });

          this.stats.totalEleves = totalEleves;
          this.stats.elevesAvecPhoto = elevesAvecPhoto;
          this.stats.elevesSansPhoto = elevesSansPhoto;
          this.stats.progressionPhotos = totalEleves > 0
            ? Math.round((elevesAvecPhoto / totalEleves) * 100)
            : 0;
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement dashboard', err);
        this.error = 'Impossible de charger les statistiques';
        this.loading = false;
      }
    });
  }

  /**
   * Charger les activités récentes (simulées pour l'instant)
   */
  loadRecentActivities(): void {
    // TODO: Charger depuis l'API d'historique
    this.recentActivities = [
      {
        icon: 'bi-person-plus',
        title: 'Nouvel élève ajouté',
        description: 'Jean BIKORO - 3èmeE2',
        time: 'Il y a 5 minutes',
        type: 'success'
      },
      {
        icon: 'bi-camera',
        title: 'Photo validée',
        description: '5 nouvelles photos validées',
        time: 'Il y a 15 minutes',
        type: 'info'
      },
      {
        icon: 'bi-credit-card',
        title: 'Cartes générées',
        description: 'Classe 3èmeE2 - 48 cartes',
        time: 'Il y a 1 heure',
        type: 'success'
      }
    ];
  }

  /**
   * Navigation vers une action rapide
   */
  navigateTo(route: string): void {
    this.router.navigate([route]);
  }

  /**
   * Obtenir le message de bienvenue
   */
  getGreeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) return 'Bonjour';
    if (hour < 18) return 'Bon après-midi';
    return 'Bonsoir';
  }

  /**
   * Obtenir la classe CSS pour une activité
   */
  getActivityClass(type: string): string {
    switch (type) {
      case 'success': return 'activity-success';
      case 'info': return 'activity-info';
      case 'warning': return 'activity-warning';
      default: return 'activity-info';
    }
  }

  /**
   * Déconnexion
   */
  logout(): void {
    this.authService.logout();
    this.router.navigate(['/connexion']);
  }
}
