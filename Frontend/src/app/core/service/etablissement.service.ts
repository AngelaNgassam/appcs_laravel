import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Etablissement } from '../models/etablissement';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class EtablissementService {
  private apiUrl = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  /**
   * Obtenir les détails d'un établissement
   */
  getEtablissement(id: number): Observable<any> {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get(`${this.apiUrl}/etablissements/${id}`, { headers });
  }

  /**
   * Mettre à jour un établissement
   */
  updateEtablissement(id: number, data: Partial<Etablissement>): Observable<any> {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.put(`${this.apiUrl}/etablissements/${id}`, data, { headers });
  }

  /**
   * Mettre à jour le logo
   */
  updateLogo(id: number, logo: File): Observable<any> {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    const formData = new FormData();
    formData.append('logo', logo);

    return this.http.post(`${this.apiUrl}/etablissements/${id}/logo`, formData, { headers });
  }

  /**
   * Obtenir les statistiques d'un établissement
   */
  getStatistiques(id: number): Observable<any> {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get(`${this.apiUrl}/etablissements/${id}/statistiques`, { headers });
  }
}
