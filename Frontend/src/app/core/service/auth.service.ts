import { Injectable, Inject, PLATFORM_ID } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { isPlatformBrowser } from '@angular/common';
import { User } from '../models/user';
import { Etablissement } from '../models/etablissement';
import { AuthResponse } from '../models/auth-response';
import { LoginRequest } from '../models/login-request';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = `${environment.apiUrl}`;
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  // ✅ Ajouter un BehaviorSubject pour l'établissement
  private currentEtablissementSubject = new BehaviorSubject<Etablissement | null>(null);
  public currentEtablissement$ = this.currentEtablissementSubject.asObservable();

  // ✅ Identifiant unique pour cet onglet
  private tabId: string;

  constructor(
    private http: HttpClient,
    private router: Router,
    @Inject(PLATFORM_ID) private platformId: object
  ) {
    // Générer un ID unique pour cet onglet
    this.tabId = this.generateTabId();
    this.loadUserFromStorage();
    this.loadEtablissementFromStorage(); // ✅ Charger l'établissement
  }

  private generateTabId(): string {
    // Utiliser sessionStorage pour l'ID d'onglet (unique par onglet)
    if (this.isBrowser) {
      let tabId = sessionStorage.getItem('tabId');
      if (!tabId) {
        tabId = 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        sessionStorage.setItem('tabId', tabId);
      }
      return tabId;
    }
    return 'default';
  }

  private getStorageKey(key: string): string {
    return `${this.tabId}_${key}`;
  }

  private get isBrowser(): boolean {
    return isPlatformBrowser(this.platformId);
  }

  /**
   * Connexion
   */
  login(credentials: LoginRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/login`, credentials)
      .pipe(
        tap((response: AuthResponse) => {
          if (response.success) {
            this.handleAuthSuccess(response);
          }
        })
      );
  }

  /**
   * Inscription Proviseur avec FormData
   */
  registerWithFormData(formData: FormData): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/register`, formData)
      .pipe(
        tap((response: AuthResponse) => {
          if (response.success) {
            this.handleAuthSuccess(response);
          }
        })
      );
  }

  /**
   * Déconnexion
   */
  logout(): Observable<any> {
    const token = this.getToken();
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.post(`${this.apiUrl}/logout`, {}, { headers })
      .pipe(
        tap(() => {
          if (this.isBrowser) {
            localStorage.removeItem(this.getStorageKey('token'));
            localStorage.removeItem(this.getStorageKey('user'));
            localStorage.removeItem(this.getStorageKey('etablissement'));
          }
          this.currentUserSubject.next(null);
          this.currentEtablissementSubject.next(null);
          this.router.navigate(['/connexion']);
        })
      );
  }

  /**
   * Obtenir l'utilisateur connecté
   */
  me(): Observable<AuthResponse> {
    const token = this.getToken();
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get<AuthResponse>(`${this.apiUrl}/me`, { headers })
      .pipe(
        tap((response: AuthResponse) => {
          if (response.success && response.user) {
            this.currentUserSubject.next(response.user);
            if (response.etablissement) {
              this.currentEtablissementSubject.next(response.etablissement);
              if (this.isBrowser) {
                localStorage.setItem(this.getStorageKey('etablissement'), JSON.stringify(response.etablissement));
              }
            }
          }
        })
      );
  }

  /**
   * Vérifier si l'utilisateur est connecté
   */
  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  /**
   * Obtenir le token
   */
  getToken(): string | null {
    return this.isBrowser ? localStorage.getItem(this.getStorageKey('token')) : null;
  }

  /**
   * Obtenir l'utilisateur actuel
   */
  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  /**
   * ✅ Obtenir l'établissement actuel
   */
  getCurrentEtablissement(): Etablissement | null {
    return this.currentEtablissementSubject.value;
  }

  /**
   * Rediriger vers le dashboard selon le rôle
   */
  redirectToDashboard(role: string): void {
    const dashboardRoutes: { [key: string]: string } = {
      'admin': '/admin/dashboard',
      'proviseur': '/proviseur/dashboard',
      'surveillant': '/surveillant/dashboard',
      'operateur': '/operateur/dashboard'
    };

    const route = dashboardRoutes[role] || '/';
    this.router.navigate([route]);
  }

  /**
   * Gérer le succès de l'authentification
   */
  private handleAuthSuccess(response: AuthResponse): void {
    if (this.isBrowser) {
      // Sauvegarder le token dans localStorage avec tabId (indépendant par onglet)
      localStorage.setItem(this.getStorageKey('token'), response.token);

      // Sauvegarder l'utilisateur
      localStorage.setItem(this.getStorageKey('user'), JSON.stringify(response.user));

      // ✅ Sauvegarder l'établissement si présent
      if (response.etablissement) {
        localStorage.setItem(this.getStorageKey('etablissement'), JSON.stringify(response.etablissement));
        this.currentEtablissementSubject.next(response.etablissement);
      }
    }

    // Mettre à jour le subject
    this.currentUserSubject.next(response.user);

    // Rediriger vers le dashboard approprié
    this.redirectToDashboard(response.user.role);
  }

  /**
   * Charger l'utilisateur depuis localStorage avec tabId
   */
  private loadUserFromStorage(): void {
    if (!this.isBrowser) return;

    const userJson = localStorage.getItem(this.getStorageKey('user'));
    if (userJson) {
      try {
        const user = JSON.parse(userJson);
        this.currentUserSubject.next(new User(user));
      } catch (error) {
        console.error('Erreur lors du chargement de l\'utilisateur', error);
        localStorage.removeItem(this.getStorageKey('user'));
      }
    }
  }

  /**
   * ✅ Charger l'établissement depuis localStorage avec tabId
   */
  private loadEtablissementFromStorage(): void {
    if (!this.isBrowser) return;

    const etablissementJson = localStorage.getItem(this.getStorageKey('etablissement'));
    if (etablissementJson) {
      try {
        const etablissement = JSON.parse(etablissementJson);
        this.currentEtablissementSubject.next(new Etablissement(etablissement));
      } catch (error) {
        console.error('Erreur lors du chargement de l\'établissement', error);
        localStorage.removeItem(this.getStorageKey('etablissement'));
      }
    }
  }
}
