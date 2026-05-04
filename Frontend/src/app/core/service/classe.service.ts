import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Classe {
  id: number;
  nom: string;
  niveau: string;
  serie?: string;
  effectif: number;
  etablissement_id: number;
  annee_academique_id: number;
  created_at: string;
  updated_at: string;
}

export interface CreateClasseRequest {
  nom: string;
  niveau: string;
  serie?: string;
}

export interface ClasseResponse {
  success: boolean;
  message?: string;
  data?: any;
  statistiques?: {  // ✅ Ajoutez cette propriété
    total_eleves: number;
    eleves_avec_photo: number;
    eleves_sans_photo: number;
  };
  pagination?: any;
}

@Injectable({
  providedIn: 'root'
})
export class ClasseService {
  private apiUrl = `${environment.apiUrl}`;
  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });
  }

  /**
   * 📋 Liste des classes
   */
  getClasses(params?: any): Observable<ClasseResponse> {
    let httpParams = new HttpParams();

    if (params) {
      Object.keys(params).forEach(key => {
        if (params[key]) {
          httpParams = httpParams.set(key, params[key]);
        }
      });
    }

    return this.http.get<ClasseResponse>(`${this.apiUrl}/classes`, {
      headers: this.getHeaders(),
      params: httpParams
    });
  }

  /**
   * ➕ Créer une classe
   */
  createClasse(data: CreateClasseRequest): Observable<ClasseResponse> {
    return this.http.post<ClasseResponse>(`${this.apiUrl}/classes`, data, {
      headers: this.getHeaders()
    });
  }

  /**
   * 🔍 Détails d'une classe
   */
  getClasse(id: number): Observable<ClasseResponse> {
    return this.http.get<ClasseResponse>(`${this.apiUrl}/classes/${id}`, {
      headers: this.getHeaders()
    });
  }

  /**
   * ✏️ Modifier une classe
   */
  updateClasse(id: number, data: Partial<CreateClasseRequest>): Observable<ClasseResponse> {
    return this.http.put<ClasseResponse>(`${this.apiUrl}/classes/${id}`, data, {
      headers: this.getHeaders()
    });
  }

  /**
   * 🗑️ Supprimer une classe
   */
  deleteClasse(id: number): Observable<ClasseResponse> {
    return this.http.delete<ClasseResponse>(`${this.apiUrl}/classes/${id}`, {
      headers: this.getHeaders()
    });
  }

  /**
   * 📥 Importer des élèves
   */
  importEleves(classeId: number, file: File): Observable<any> {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    const formData = new FormData();
    formData.append('file', file);
    formData.append('classe_id', classeId.toString());

    return this.http.post(`${this.apiUrl}/eleves/import`, formData, { headers });
  }
}
