import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class AdminService {
  private apiUrl = `${environment.apiUrl}/admin`;

  constructor(private http: HttpClient) {}

  /**
   * Dashboard principal du super admin
   */
  getDashboard(): Observable<any> {
    return this.http.get(`${environment.apiUrl}/dashboard/admin`);
  }

  /**
   * Liste des établissements avec statistiques
   */
  getEtablissements(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    ville?: string;
    include_archived?: boolean;
  }): Observable<any> {
    let httpParams = new HttpParams();
    
    if (params) {
      if (params.page) httpParams = httpParams.set('page', params.page.toString());
      if (params.per_page) httpParams = httpParams.set('per_page', params.per_page.toString());
      if (params.search) httpParams = httpParams.set('search', params.search);
      if (params.ville) httpParams = httpParams.set('ville', params.ville);
      if (params.include_archived !== undefined) {
        httpParams = httpParams.set('include_archived', params.include_archived.toString());
      }
    }

    return this.http.get(`${this.apiUrl}/etablissements`, { params: httpParams });
  }

  /**
   * Archiver un établissement
   */
  archiverEtablissement(id: number): Observable<any> {
    return this.http.patch(`${this.apiUrl}/etablissements/${id}/archiver`, {});
  }

  /**
   * Restaurer un établissement archivé
   */
  restaurerEtablissement(id: number): Observable<any> {
    return this.http.patch(`${this.apiUrl}/etablissements/${id}/restaurer`, {});
  }

  /**
   * Liste des utilisateurs (tous établissements)
   */
  getUtilisateurs(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    role?: string;
    etablissement_id?: number;
    actif?: boolean;
  }): Observable<any> {
    let httpParams = new HttpParams();
    
    if (params) {
      if (params.page) httpParams = httpParams.set('page', params.page.toString());
      if (params.per_page) httpParams = httpParams.set('per_page', params.per_page.toString());
      if (params.search) httpParams = httpParams.set('search', params.search);
      if (params.role) httpParams = httpParams.set('role', params.role);
      if (params.etablissement_id) httpParams = httpParams.set('etablissement_id', params.etablissement_id.toString());
      if (params.actif !== undefined) httpParams = httpParams.set('actif', params.actif.toString());
    }

    return this.http.get(`${this.apiUrl}/utilisateurs`, { params: httpParams });
  }

  /**
   * Statistiques système avancées
   */
  getStatistiquesSysteme(): Observable<any> {
    return this.http.get(`${this.apiUrl}/statistiques-systeme`);
  }

  /**
   * Rapport d'activité global
   */
  getRapportActivite(dateDebut?: string, dateFin?: string): Observable<any> {
    let httpParams = new HttpParams();
    
    if (dateDebut) httpParams = httpParams.set('date_debut', dateDebut);
    if (dateFin) httpParams = httpParams.set('date_fin', dateFin);

    return this.http.get(`${this.apiUrl}/rapport-activite`, { params: httpParams });
  }
}
