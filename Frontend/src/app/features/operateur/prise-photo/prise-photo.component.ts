import { CommonModule, isPlatformBrowser } from '@angular/common';
import { Component, Inject, OnDestroy, OnInit, PLATFORM_ID } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { EleveService, ElevesListResponse } from '../../../core/service/eleve.service';
import { PhotoService } from '../../../core/service/photo.service';
import { Observable, Subject } from 'rxjs';
import { WebcamImage, WebcamModule } from 'ngx-webcam';
import { ImageCroppedEvent, ImageCropperComponent } from 'ngx-image-cropper';

@Component({
  selector: 'app-prise-photo',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule, WebcamModule, ImageCropperComponent],
  templateUrl: './prise-photo.component.html',
  styleUrls: ['./prise-photo.component.scss']
})
export class PrisePhotoComponent implements OnInit, OnDestroy {
  loading = true;
  error: string | null = null;

  searchTerm = '';

  currentPage = 1;
  totalPages = 1;
  totalEleves = 0;

  sortKey: string = 'nom';
  sortDir: 'asc' | 'desc' = 'asc';

  eleves: any[] = [];

  selectedEleve: any | null = null;
  cameraError: string | null = null;
  isCameraOpen = false;
  isUploading = false;

  // ngx-webcam
  private triggerSnapshot$ = new Subject<void>();
  triggerSnapshotObservable: Observable<void> = this.triggerSnapshot$.asObservable();
  webcamImage: WebcamImage | null = null;

  // ngx-image-cropper
  imageBase64: string | null = null;
  croppedBlob: Blob | null = null;
  croppedPreviewUrl: string | null = null;

  constructor(
    private eleveService: EleveService,
    private photoService: PhotoService,
    @Inject(PLATFORM_ID) private platformId: object
  ) {}

  ngOnInit(): void {
    this.loadEleves();
  }

  ngOnDestroy(): void {
    this.clearCaptured();
  }

  loadEleves(): void {
    this.loading = true;
    this.error = null;

    this.eleveService.getEleves({
      page: this.currentPage,
      per_page: 20,
      search: this.searchTerm || undefined,
      archive: false
    }).subscribe({
      next: (response: ElevesListResponse) => {
        if (response.success) {
          this.eleves = (response.data || []).slice();
          if (response.pagination) {
            this.totalPages = response.pagination.last_page;
            this.totalEleves = response.pagination.total;
          }
          this.applySort();
        } else {
          this.error = response.message || 'Impossible de charger les élèves';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement élèves', err);
        this.error = err?.error?.message || 'Impossible de charger les élèves';
        this.loading = false;
      }
    });
  }

  onSearch(): void {
    this.currentPage = 1;
    this.loadEleves();
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.totalPages) return;
    this.currentPage = page;
    this.loadEleves();
  }

  onSort(key: string): void {
    if (this.sortKey === key) {
      this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortKey = key;
      this.sortDir = 'asc';
    }
    this.applySort();
  }

  private applySort(): void {
    const dir = this.sortDir === 'asc' ? 1 : -1;
    const key = this.sortKey;

    this.eleves.sort((a, b) => {
      const av = (a?.[key] ?? '').toString().toLowerCase();
      const bv = (b?.[key] ?? '').toString().toLowerCase();
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  openCamera(eleve: any): void {
    this.selectedEleve = eleve;
    this.cameraError = null;
    this.isCameraOpen = true;
    this.clearCaptured();

    if (!isPlatformBrowser(this.platformId)) {
      this.cameraError = 'La caméra est disponible uniquement côté navigateur.';
      return;
    }
  }

  closeCamera(): void {
    this.isCameraOpen = false;
    this.selectedEleve = null;
    this.cameraError = null;
    this.clearCaptured();
  }

  capture(): void {
    this.cameraError = null;
    this.triggerSnapshot$.next();
  }

  handleImage(webcamImage: WebcamImage): void {
    this.webcamImage = webcamImage;
    this.imageBase64 = webcamImage.imageAsDataUrl;
    this.croppedBlob = null;
    this.refreshPreview();
  }

  imageCropped(event: ImageCroppedEvent): void {
    this.croppedBlob = (event as any).blob || null;
    if (!this.croppedBlob && event.base64) {
      this.croppedBlob = this.base64ToBlob(event.base64, 'image/jpeg');
    }
    this.refreshPreview();
  }

  private refreshPreview(): void {
    if (this.croppedPreviewUrl) {
      URL.revokeObjectURL(this.croppedPreviewUrl);
    }
    this.croppedPreviewUrl = this.croppedBlob ? URL.createObjectURL(this.croppedBlob) : null;
  }

  async upload(): Promise<void> {
    if (!this.selectedEleve || !this.croppedBlob) return;

    this.isUploading = true;
    this.error = null;

    this.photoService.uploadPhoto(this.selectedEleve.id, this.croppedBlob).subscribe({
      next: (response) => {
        if (response.success) {
          this.closeCamera();
          this.loadEleves();
        } else {
          this.error = response.message || "Erreur lors de l'upload";
        }
        this.isUploading = false;
      },
      error: (err) => {
        console.error('Erreur upload photo', err);
        this.error = err?.error?.message || "Erreur lors de l'upload";
        this.isUploading = false;
      }
    });
  }

  private clearCaptured(): void {
    this.webcamImage = null;
    this.imageBase64 = null;
    this.croppedBlob = null;
    if (this.croppedPreviewUrl) {
      URL.revokeObjectURL(this.croppedPreviewUrl);
    }
    this.croppedPreviewUrl = null;
  }

  private base64ToBlob(base64: string, contentType: string): Blob {
    const parts = base64.split(',');
    const raw = parts.length > 1 ? atob(parts[1]) : atob(parts[0]);
    const rawLength = raw.length;
    const uInt8Array = new Uint8Array(rawLength);
    for (let i = 0; i < rawLength; ++i) {
      uInt8Array[i] = raw.charCodeAt(i);
    }
    return new Blob([uInt8Array], { type: contentType });
  }
}
