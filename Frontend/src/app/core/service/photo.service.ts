import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface UploadPhotoResponse {
  success: boolean;
  message?: string;
  data?: any;
}

@Injectable({
  providedIn: 'root'
})
export class PhotoService {
  private apiUrl = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  uploadPhoto(eleveId: number, photo: Blob): Observable<UploadPhotoResponse> {
    const formData = new FormData();
    formData.append('eleve_id', String(eleveId));
    formData.append('photo', photo, 'capture.jpg');

    return this.http.post<UploadPhotoResponse>(`${this.apiUrl}/photos`, formData);
  }
}
