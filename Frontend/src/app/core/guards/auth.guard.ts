import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../service/auth.service';

export const AuthGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  const isAuthenticated = authService.isAuthenticated();
  const currentUser = authService.getCurrentUser();

  // Si pas connecté, rediriger vers connexion
  if (!isAuthenticated || !currentUser) {
    router.navigate(['/connexion'], {
      queryParams: { returnUrl: state.url }
    });
    return false;
  }

  // Vérifier les rôles si spécifiés dans la route
  const requiredRoles = route.data['roles'] as Array<string>;

  if (requiredRoles && requiredRoles.length > 0) {
    const userRole = normalizeRole(currentUser.role);
    const normalizedRequiredRoles = requiredRoles.map(normalizeRole);

    if (!normalizedRequiredRoles.includes(userRole)) {
      // Rediriger vers le dashboard correspondant au rôle
      redirectToDashboard(userRole, router);
      return false;
    }
  }

  return true;
};

/**
 * Rediriger vers le bon dashboard selon le rôle
 */
function redirectToDashboard(role: string, router: Router): void {
  const dashboardRoutes: { [key: string]: string } = {
    'admin': '/admin/dashboard',
    'proviseur': '/proviseur/dashboard',
    'surveillant': '/surveillant/dashboard',
    'surveillant_general': '/surveillant/dashboard',
    'surveillantgeneral': '/surveillant/dashboard',
    'operateur': '/operateur/dashboard'
  };

  const route = dashboardRoutes[role] || '/';
  router.navigate([route]);
}

function normalizeRole(role: any): string {
  if (!role) return '';
  return role
    .toString()
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, '_');
}
