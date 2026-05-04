import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../../core/service/auth.service';

export interface MenuItem {
  label: string;
  icon: string;
  route: string;
  badge?: number;
}

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './sidebar.html',
  styleUrl: './sidebar.scss',
})
export class Sidebar implements OnInit {
  @Input() role: 'admin' | 'proviseur' | 'surveillant' | 'operateur' = 'proviseur';
  
  menuItems: MenuItem[] = [];
  currentUser: any = null;
  notificationCount = 0;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit() {
    this.currentUser = this.authService.getCurrentUser();
    this.loadMenuItems();
    // Simuler des notifications (à remplacer par un vrai service)
    this.notificationCount = 3;
  }

  loadMenuItems() {
    switch (this.role) {
      case 'admin':
        this.menuItems = [
          { label: 'Dashboard', icon: 'bi-speedometer2', route: '/admin/dashboard' },
          { label: 'Établissements', icon: 'bi-building', route: '/admin/etablissements' },
          { label: 'Utilisateurs', icon: 'bi-people', route: '/admin/utilisateurs' },
          { label: 'Statistiques', icon: 'bi-graph-up', route: '/admin/statistiques' },
          { label: 'Rapports', icon: 'bi-file-earmark-text', route: '/admin/rapports' }
        ];
        break;
      
      case 'proviseur':
        this.menuItems = [
          { label: 'Dashboard', icon: 'bi-speedometer2', route: '/proviseur/dashboard' },
          { label: 'Élèves', icon: 'bi-people', route: '/proviseur/eleves' },
          { label: 'Classes', icon: 'bi-collection', route: '/proviseur/classes' },
          { label: 'Cartes', icon: 'bi-credit-card-2-front', route: '/proviseur/cartes' },
          { label: 'Utilisateurs', icon: 'bi-person-badge', route: '/proviseur/utilisateurs' },
          { label: 'Statistiques', icon: 'bi-graph-up', route: '/proviseur/statistiques' }
        ];
        break;
      
      case 'surveillant':
        this.menuItems = [
          { label: 'Dashboard', icon: 'bi-speedometer2', route: '/surveillant/dashboard' },
          { label: 'Élèves', icon: 'bi-people', route: '/surveillant/eleves' },
          { label: 'Photos', icon: 'bi-camera', route: '/surveillant/photos', badge: 5 }
        ];
        break;
      
      case 'operateur':
        this.menuItems = [
          { label: 'Dashboard', icon: 'bi-speedometer2', route: '/operateur/dashboard' },
          { label: 'Prise de Photo', icon: 'bi-camera-fill', route: '/operateur/prise-photo' }
        ];
        break;
    }
  }

  logout() {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}
