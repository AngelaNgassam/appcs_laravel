import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface CreateUserRequest {
  nom: string;
  prenom: string;
  email: string;
  password: string;
  role: 'surveillant' | 'operateur';
  telephone?: string;
}

export interface UpdateUserRequest {
  nom?: string;
  prenom?: string;
  telephone?: string;
  actif?: boolean;
}

export interface UserListParams {
  page?: number;
  per_page?: number;
  role?: string;
  search?: string;
}

export interface UserResponse {
  success: boolean;
  message?: string;
  data?: any;
  pagination?: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number;
    to: number;
  };
}

@Injectable({
  providedIn: 'root'
})
export class UserService {
  private apiUrl = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  /**
   * Obtenir les headers avec token
   */
  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    });
  }

  /**
   * Lister les utilisateurs avec filtres
   */
  getUsers(params?: UserListParams): Observable<UserResponse> {
    let httpParams = new HttpParams();

    if (params) {
      if (params.page) httpParams = httpParams.set('page', params.page.toString());
      if (params.per_page) httpParams = httpParams.set('per_page', params.per_page.toString());
      if (params.role) httpParams = httpParams.set('role', params.role);
      if (params.search) httpParams = httpParams.set('search', params.search);
    }

    return this.http.get<UserResponse>(`${this.apiUrl}/users`, {
      headers: this.getHeaders(),
      params: httpParams
    });
  }

  /**
   * Obtenir un utilisateur par ID
   */
  getUser(id: number): Observable<UserResponse> {
    return this.http.get<UserResponse>(`${this.apiUrl}/users/${id}`, {
      headers: this.getHeaders()
    });
  }

  /**
   * Créer un utilisateur (surveillant ou opérateur)
   */
  createUser(userData: CreateUserRequest): Observable<UserResponse> {
    return this.http.post<UserResponse>(`${this.apiUrl}/users`, userData, {
      headers: this.getHeaders()
    });
  }

  /**
   * Mettre à jour un utilisateur
   */
  updateUser(id: number, userData: UpdateUserRequest): Observable<UserResponse> {
    return this.http.put<UserResponse>(`${this.apiUrl}/users/${id}`, userData, {
      headers: this.getHeaders()
    });
  }

  /**
   * Activer/Désactiver un utilisateur
   */
  toggleActive(id: number): Observable<UserResponse> {
    return this.http.patch<UserResponse>(`${this.apiUrl}/users/${id}/toggle-active`, {}, {
      headers: this.getHeaders()
    });
  }

  /**
   * Supprimer un utilisateur (soft delete)
   */
  deleteUser(id: number): Observable<UserResponse> {
    return this.http.delete<UserResponse>(`${this.apiUrl}/users/${id}`, {
      headers: this.getHeaders()
    });
  }
}
