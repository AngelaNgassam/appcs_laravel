import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface CreateEleveRequest {
  classe_id: number;
  matricule: string;
  nom: string;
  prenom: string;
  date_naissance: string;
  lieu_naissance?: string;
  sexe: 'M' | 'F';
  contact_parent?: string;
  nom_parent?: string;
}

export interface EleveResponse {
  success: boolean;
  message?: string;
  data?: any;
}

// ✅ CORRIGÉ: Ajout de la propriété message
export interface ElevesListResponse {
  success: boolean;
  message?: string; // ✅ AJOUTÉ
  data: any[];
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

@Injectable({
  providedIn: 'root'
})
export class EleveService {
  private apiUrl = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    });
  }

  /**
   * Obtenir la liste de tous les élèves avec paramètres
   */
  getEleves(params?: any): Observable<ElevesListResponse> {
    let httpParams = new HttpParams();

    if (params) {
      Object.keys(params).forEach(key => {
        if (params[key] !== undefined && params[key] !== null) {
          httpParams = httpParams.set(key, params[key].toString());
        }
      });
    }

    return this.http.get<ElevesListResponse>(`${this.apiUrl}/eleves`, {
      headers: this.getHeaders(),
      params: httpParams
    });
  }

  /**
   * Créer un élève
   */
  createEleve(data: CreateEleveRequest): Observable<EleveResponse> {
    return this.http.post<EleveResponse>(`${this.apiUrl}/eleves`, data, {
      headers: this.getHeaders()
    });
  }

  /**
   * Obtenir un élève par ID
   */
  getEleve(id: number): Observable<EleveResponse> {
    return this.http.get<EleveResponse>(`${this.apiUrl}/eleves/${id}`, {
      headers: this.getHeaders()
    });
  }

  /**
   * Mettre à jour un élève
   */
  updateEleve(id: number, data: Partial<CreateEleveRequest>): Observable<EleveResponse> {
    return this.http.put<EleveResponse>(`${this.apiUrl}/eleves/${id}`, data, {
      headers: this.getHeaders()
    });
  }

  /**
   * Supprimer un élève
   */
  deleteEleve(id: number): Observable<EleveResponse> {
    return this.http.delete<EleveResponse>(`${this.apiUrl}/eleves/${id}`, {
      headers: this.getHeaders()
    });
  }

  /**
   * Archiver un élève
   */
  archiver(id: number): Observable<EleveResponse> {
    return this.http.patch<EleveResponse>(`${this.apiUrl}/eleves/${id}/archiver`, {}, {
      headers: this.getHeaders()
    });
  }

  /**
   * Désarchiver un élève
   */
  desarchiver(id: number): Observable<EleveResponse> {
    return this.http.patch<EleveResponse>(`${this.apiUrl}/eleves/${id}/desarchiver`, {}, {
      headers: this.getHeaders()
    });
  }
}
