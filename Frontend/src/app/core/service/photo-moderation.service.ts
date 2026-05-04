import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface PhotoListParams {
  page?: number;
  per_page?: number;
  eleve_id?: number;
  statut?: 'brouillon' | 'validee' | 'refusee' | 'archivee';
  include_archived?: boolean;
}

export interface ApiPagination {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface PhotoListResponse {
  success: boolean;
  message?: string;
  data?: any[];
  pagination?: ApiPagination;
}

export interface PhotoActionResponse {
  success: boolean;
  message?: string;
  data?: any;
}

@Injectable({
  providedIn: 'root'
})
export class PhotoModerationService {
  private apiUrl = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });
  }

  listPhotos(params: PhotoListParams): Observable<PhotoListResponse> {
    let httpParams = new HttpParams();

    if (params.page) httpParams = httpParams.set('page', params.page.toString());
    if (params.per_page) httpParams = httpParams.set('per_page', params.per_page.toString());
    if (params.eleve_id) httpParams = httpParams.set('eleve_id', params.eleve_id.toString());
    if (params.statut) httpParams = httpParams.set('statut', params.statut);
    if (typeof params.include_archived === 'boolean') {
      httpParams = httpParams.set('include_archived', params.include_archived ? '1' : '0');
    }

    return this.http.get<PhotoListResponse>(`${this.apiUrl}/photos`, {
      headers: this.getHeaders(),
      params: httpParams
    });
  }

  validerPhoto(photoId: number): Observable<PhotoActionResponse> {
    return this.http.patch<PhotoActionResponse>(`${this.apiUrl}/photos/${photoId}/valider`, {}, {
      headers: this.getHeaders()
    });
  }

  refuserPhoto(photoId: number, motif: string): Observable<PhotoActionResponse> {
    return this.http.patch<PhotoActionResponse>(`${this.apiUrl}/photos/${photoId}/refuser`, { motif }, {
      headers: this.getHeaders()
    });
  }
}
