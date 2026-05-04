import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface ModeleCarteListParams {
  page?: number;
  per_page?: number;
  actif?: boolean;
}

export interface ApiPagination {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface ModeleCarteListResponse {
  success: boolean;
  message?: string;
  data?: any[];
  pagination?: ApiPagination;
}

export interface ModeleCarteResponse {
  success: boolean;
  message?: string;
  data?: any;
}

@Injectable({
  providedIn: 'root'
})
export class ModeleCarteService {
  private apiUrl = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });
  }

  listModeles(params?: ModeleCarteListParams): Observable<ModeleCarteListResponse> {
    let httpParams = new HttpParams();

    if (params?.page) httpParams = httpParams.set('page', params.page.toString());
    if (params?.per_page) httpParams = httpParams.set('per_page', params.per_page.toString());
    if (typeof params?.actif === 'boolean') httpParams = httpParams.set('actif', params.actif ? '1' : '0');

    return this.http.get<ModeleCarteListResponse>(`${this.apiUrl}/modeles-cartes`, {
      headers: this.getHeaders(),
      params: httpParams
    });
  }

  definirParDefaut(id: number): Observable<ModeleCarteResponse> {
    return this.http.patch<ModeleCarteResponse>(`${this.apiUrl}/modeles-cartes/${id}/definir-par-defaut`, {}, {
      headers: this.getHeaders()
    });
  }
}
