import { Routes } from '@angular/router';
import { AuthGuard } from './core/guards/auth.guard';
import { SurveillantDashboardComponent } from './features/surveillant/dashboard/dashboard.component';
import { OperateurDashboardComponent } from './features/operateur/dashboard/dashboard.component';
import { ImportElevesComponent } from './features/proviseur/import-eleves/import-eleves.component';
import { CreateClasseComponent } from './features/proviseur/create-classe/create-classe.component';
import { ClasseDetailsComponent } from './features/proviseur/classe-details/classe-details.component';
import { ClassesListComponent } from './features/proviseur/classes-list/classes-list.component';
import { CreateUserComponent } from './features/proviseur/create-user/create-user.component';
import { UsersListComponent } from './features/proviseur/users-list/users-list.component';
import { ProviseurDashboardComponent } from './features/proviseur/dashboard/dashboard.component';
import { AddEleveComponent } from './features/proviseur/add-eleve/add-eleve.component';
import { AdminDashboardComponent } from './features/admin/dashboard/dashboard.component';



export const routes: Routes = [
  // ========== PAGES PUBLIQUES ==========
  {
    path: '',
    loadComponent: () => import('./features/publics/landing/landing.component')
      .then(m => m.LandingComponent)
  },
  {
    path: 'connexion',
    loadComponent: () => import('./features/publics/login/login.component')
      .then(m => m.LoginComponent)
  },
  {
    path: 'inscription',
    loadComponent: () => import('./features/publics/inscription/inscription.component')
      .then(m => m.InscriptionComponent)
  },

  // ========== ADMIN ==========
  {
    path: 'admin/dashboard',
    component: AdminDashboardComponent,
    canActivate: [AuthGuard],
    data: { roles: ['admin'] }
  },
  {
    path: 'admin/etablissements',
    loadComponent: () => import('./features/admin/etablissements/etablissements.component')
      .then(m => m.EtablissementsComponent),
    canActivate: [AuthGuard],
    data: { roles: ['admin'] }
  },
  {
    path: 'admin/utilisateurs',
    loadComponent: () => import('./features/admin/utilisateurs/utilisateurs.component')
      .then(m => m.UtilisateursComponent),
    canActivate: [AuthGuard],
    data: { roles: ['admin'] }
  },
  {
    path: 'admin/statistiques',
    loadComponent: () => import('./features/admin/statistiques/statistiques.component')
      .then(m => m.StatistiquesComponent),
    canActivate: [AuthGuard],
    data: { roles: ['admin'] }
  },
  {
    path: 'admin/rapports',
    loadComponent: () => import('./features/admin/rapports/rapports.component')
      .then(m => m.RapportsComponent),
    canActivate: [AuthGuard],
    data: { roles: ['admin'] }
  },

   // ========== PROVISEUR ==========
  {
    path: 'proviseur/dashboard',
    component: ProviseurDashboardComponent,
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },
  {
    path: 'proviseur/utilisateurs',
    component: UsersListComponent,
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },
  {
    path: 'proviseur/utilisateurs/nouveau',
    component: CreateUserComponent,
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },

  // Dans app.routes.ts, ajoutez ces routes :

  // ========== PROVISEUR - GESTION DES UTILISATEURS ==========
  {
    path: 'proviseur/utilisateurs',
    loadComponent: () => import('./features/proviseur/users-list/users-list.component')
      .then(m => m.UsersListComponent),
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },
  {
    path: 'proviseur/utilisateurs/nouveau',
    loadComponent: () => import('./features/proviseur/create-user/create-user.component')
      .then(m => m.CreateUserComponent),
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },

  {
    path: 'proviseur/classes',
    component: ClassesListComponent,
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },
  {
    path: 'proviseur/classes/nouvelle',
    component: CreateClasseComponent,
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },
  {
    path: 'proviseur/classes/:id',
    component: ClasseDetailsComponent,
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },
  {
    path: 'proviseur/classes/:id/importer',
    component: ImportElevesComponent,
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },
  {
    path: 'proviseur/eleves/importer',
    component: ImportElevesComponent,
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },
  {
    path: 'proviseur/eleves/nouveau',
    component: AddEleveComponent,
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },

  {
    path: 'proviseur/cartes',
    loadComponent: () => import('./features/proviseur/cartes/cartes.component')
      .then(m => m.ProviseurCartesComponent),
    canActivate: [AuthGuard],
    data: { roles: ['proviseur', 'admin'] }
  },

  // ========== SURVEILLANT ==========
  {
    path: 'surveillant/dashboard',
    component: SurveillantDashboardComponent,
    canActivate: [AuthGuard],
    data: { roles: ['surveillant', 'admin'] }
  },

  {
    path: 'surveillant/eleves',
    loadComponent: () => import('./features/surveillant/eleves/eleves.component')
      .then(m => m.SurveillantElevesComponent),
    canActivate: [AuthGuard],
    data: { roles: ['surveillant', 'admin'] }
  },

  {
    path: 'surveillant/photos',
    loadComponent: () => import('./features/surveillant/photos/photos.component')
      .then(m => m.SurveillantPhotosComponent),
    canActivate: [AuthGuard],
    data: { roles: ['surveillant', 'admin'] }
  },

  // ========== OPÉRATEUR ==========
  {
    path: 'operateur/dashboard',
    component: OperateurDashboardComponent,
    canActivate: [AuthGuard],
    data: { roles: ['operateur', 'admin'] }
  },
  {
    path: 'operateur/photographier',
    loadComponent: () => import('./features/operateur/prise-photo/prise-photo.component')
      .then(m => m.PrisePhotoComponent),
    canActivate: [AuthGuard],
    data: { roles: ['operateur', 'admin'] }
  },

  // ========== REDIRECTION ==========
  {
    path: '**',
    redirectTo: ''
  }
];
