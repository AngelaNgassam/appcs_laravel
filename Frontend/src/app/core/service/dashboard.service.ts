import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface DashboardKPIs {
  total_eleves: number;
  total_classes: number;
  total_utilisateurs: number;
  eleves_avec_photo: number;
  cartes_generees: number;
  cartes_imprimees: number;
  cartes_distribuees: number;
}

export interface ClasseStat {
  id: number;
  nom: string;
  niveau: string;
  effectif: number;
  avec_photo: number;
  sans_photo: number;
  taux_completion: number;
}

export interface ActiviteRecente {
  id: number;
  action: string;
  utilisateur: string;
  role: string;
  details: string;
  date: string;
  date_relative: string;
}

export interface ProgressionData {
  date: string;
  total: number;
}

export interface DashboardData {
  kpis: DashboardKPIs;
  statistiques: {
    eleves: {
      total: number;
      avec_photo: number;
      sans_photo: number;
      archives: number;
    };
    photos: { [key: string]: number };
    cartes: { [key: string]: number };
    utilisateurs: { [key: string]: number };
    taux_completion: {
      photos: number;
      cartes_generees: number;
      cartes_imprimees: number;
      cartes_distribuees: number;
    };
  };
  classes: ClasseStat[];
  activite_recente: ActiviteRecente[];
  progression: {
    photos: ProgressionData[];
    cartes: ProgressionData[];
  };
}

export interface DashboardResponse {
  success: boolean;
  data: DashboardData;
}

@Injectable({
  providedIn: 'root'
})
export class DashboardService {
  private apiUrl = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  /**
   * Obtenir les statistiques du dashboard proviseur
   */
  getProviseurDashboard(): Observable<DashboardResponse> {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get<DashboardResponse>(`${this.apiUrl}/dashboard/proviseur`, { headers });
  }

  /**
   * Obtenir les statistiques du dashboard surveillant
   */
  getSurveillantDashboard(): Observable<DashboardResponse> {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get<DashboardResponse>(`${this.apiUrl}/dashboard/surveillant`, { headers });
  }


  /**
   * Obtenir les statistiques du dashboard opérateur
   */
  getOperateurDashboard(): Observable<DashboardResponse> {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get<DashboardResponse>(`${this.apiUrl}/dashboard/operateur`, { headers });
  }
}




