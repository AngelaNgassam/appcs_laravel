import { Component, Inject, OnInit, PLATFORM_ID } from '@angular/core';
import { CommonModule, isPlatformBrowser } from '@angular/common';
import { Router } from '@angular/router';

@Component({
  selector: 'app-landing',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './landing.component.html',
  styleUrls: ['./landing.component.scss']
})
export class LandingComponent implements OnInit {
  contactEmail = 'contact@cartes-scolaires.com';
  contactPhone = '+237 6XX XXX XXX';

  features = [
    {
      icon: 'bi-camera-fill',
      title: 'Prise de Photo Rapide',
      description: 'Capturez les photos directement depuis l\'application avec recadrage automatique',
      color: 'success'
    },
    {
      icon: 'bi-credit-card-fill',
      title: 'Génération Automatique',
      description: 'Créez des cartes professionnelles en quelques clics avec QR Code intégré',
      color: 'primary'
    },
    {
      icon: 'bi-graph-up-arrow',
      title: 'Suivi en Temps Réel',
      description: 'Consultez les statistiques et suivez l\'avancement de la production',
      color: 'info'
    },
    {
      icon: 'bi-printer-fill',
      title: 'Impression Simplifiée',
      description: 'Imprimez par classe ou en masse avec des modèles personnalisables',
      color: 'warning'
    },
    {
      icon: 'bi-shield-check',
      title: 'Sécurité Renforcée',
      description: 'Gestion des rôles et traçabilité complète de toutes les actions',
      color: 'danger'
    },
    {
      icon: 'bi-archive-fill',
      title: 'Archivage Intelligent',
      description: 'Conservez l\'historique des cartes et réimprimez facilement',
      color: 'secondary'
    }
  ];

  stats = [
    { number: '500+', label: 'Écoles', icon: 'bi-building' },
    { number: '50k+', label: 'Cartes générées', icon: 'bi-credit-card' },
    { number: '99%', label: 'Satisfaction', icon: 'bi-emoji-smile' }
  ];

  processSteps = [
    {
      step: '01',
      title: 'Inscription',
      description: 'Créez votre compte proviseur en 2 minutes',
      icon: 'bi-person-plus-fill'
    },
    {
      step: '02',
      title: 'Import Élèves',
      description: 'Importez la liste via fichier Excel',
      icon: 'bi-file-earmark-spreadsheet'
    },
    {
      step: '03',
      title: 'Photographie',
      description: 'L\'opérateur capture les photos',
      icon: 'bi-camera-fill'
    },
    {
      step: '04',
      title: 'Impression',
      description: 'Générez et imprimez en masse',
      icon: 'bi-printer-fill'
    }
  ];

   constructor(
    private router: Router,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {}

  ngOnInit(): void {
    // ✅ Vérifie qu'on est dans le navigateur avant d'utiliser IntersectionObserver
    if (isPlatformBrowser(this.platformId)) {
      this.initScrollAnimations();
    }
  }

  navigateToInscription(): void {
    this.router.navigate(['/inscription']);
  }

  navigateToLogin(): void {
    this.router.navigate(['/connexion']);
  }

  scrollToSection(sectionId: string): void {
    const element = document.getElementById(sectionId);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  private initScrollAnimations(): void {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
          }
        });
      },
      { threshold: 0.1 }
    );

    setTimeout(() => {
      const elements = document.querySelectorAll('.animate-on-scroll');
      elements.forEach(el => observer.observe(el));
    }, 100);
  }
}
