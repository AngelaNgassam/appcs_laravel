import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface CarteListParams {
  page?: number;
  per_page?: number;
  statut?: 'en_attente' | 'generee' | 'imprimee' | 'distribuee';
  classe_id?: number;
}

export interface ApiPagination {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface CarteListResponse {
  success: boolean;
  message?: string;
  data?: any[];
  pagination?: ApiPagination;
}

export interface CarteActionResponse {
  success: boolean;
  message?: string;
  data?: any;
}

@Injectable({
  providedIn: 'root'
})
export class CarteService {
  private apiUrl = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });
  }

  listCartes(params?: CarteListParams): Observable<CarteListResponse> {
    let httpParams = new HttpParams();

    if (params?.page) httpParams = httpParams.set('page', params.page.toString());
    if (params?.per_page) httpParams = httpParams.set('per_page', params.per_page.toString());
    if (params?.statut) httpParams = httpParams.set('statut', params.statut);
    if (params?.classe_id) httpParams = httpParams.set('classe_id', params.classe_id.toString());

    return this.http.get<CarteListResponse>(`${this.apiUrl}/cartes`, {
      headers: this.getHeaders(),
      params: httpParams
    });
  }

  genererCarte(eleveId: number, modeleId?: number): Observable<CarteActionResponse> {
    const body: any = {};
    if (modeleId) body.modele_id = modeleId;

    return this.http.post<CarteActionResponse>(`${this.apiUrl}/cartes/generer/${eleveId}`, body, {
      headers: this.getHeaders()
    });
  }

  genererCartesClasse(classeId: number, modeleId?: number): Observable<CarteActionResponse> {
    const body: any = {};
    if (modeleId) body.modele_id = modeleId;

    return this.http.post<CarteActionResponse>(`${this.apiUrl}/cartes/generer-classe/${classeId}`, body, {
      headers: this.getHeaders()
    });
  }

  telechargerCarte(carteId: number): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/cartes/${carteId}/telecharger`, {
      headers: this.getHeaders(),
      responseType: 'blob'
    });
  }

  /**
   * ✨ NOUVEAU : Prévisualiser une carte dans un nouvel onglet
   */
  previsualiserCarte(carteId: number): void {
    const token = localStorage.getItem('token');
    const url = `${this.apiUrl}/cartes/${carteId}/previsualiser`;

    // Ouvrir dans un nouvel onglet avec le token dans l'URL
    window.open(`${url}?token=${token}`, '_blank');
  }

  /**
   * Obtenir les élèves d'une classe avec leurs cartes
   */
  getElevesClasse(classeId: number): Observable<CarteActionResponse> {
    return this.http.get<CarteActionResponse>(`${this.apiUrl}/cartes/classe/${classeId}`, {
      headers: this.getHeaders()
    });
  }

  /**
   * Générer une planche d'impression (plusieurs cartes sur une page A4)
   */
  genererPlanche(eleveIds: number[], modeleId?: number): Observable<CarteActionResponse> {
    const body: any = { eleve_ids: eleveIds };
    if (modeleId) body.modele_id = modeleId;

    return this.http.post<CarteActionResponse>(`${this.apiUrl}/cartes/generer-planche`, body, {
      headers: this.getHeaders()
    });
  }

  /**
   * Marquer une carte comme imprimée
   */
  marquerCommeImprimee(carteId: number): Observable<CarteActionResponse> {
    return this.http.patch<CarteActionResponse>(`${this.apiUrl}/cartes/${carteId}/imprimer`, {}, {
      headers: this.getHeaders()
    });
  }

  /**
   * Marquer une carte comme distribuée
   */
  marquerCommeDistribuee(carteId: number): Observable<CarteActionResponse> {
    return this.http.patch<CarteActionResponse>(`${this.apiUrl}/cartes/${carteId}/distribuer`, {}, {
      headers: this.getHeaders()
    });
  }

  /**
   * Obtenir les statistiques des cartes
   */
  getStatistiques(): Observable<CarteActionResponse> {
    return this.http.get<CarteActionResponse>(`${this.apiUrl}/cartes/stats/global`, {
      headers: this.getHeaders()
    });
  }

  /**
   * Obtenir les détails d'une carte
   */
  getCarte(carteId: number): Observable<CarteActionResponse> {
    return this.http.get<CarteActionResponse>(`${this.apiUrl}/cartes/${carteId}`, {
      headers: this.getHeaders()
    });
  }
}
