import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-carte-preview',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './carte-preview.component.html',
  styleUrls: ['./carte-preview.component.scss']
})
export class CartePreviewComponent {
  @Input() eleve: any;
  @Input() etablissement: any;
  @Input() classe: any;
  @Input() photoUrl: string = '';
  @Input() logoUrl: string = '';
  @Input() anneeAcademique: string = '2025-2026';

  getCurrentDate(): string {
    const now = new Date();
    return now.toLocaleDateString('fr-FR');
  }

  getExpiryDate(): string {
    const now = new Date();
    const expiry = new Date(now.setFullYear(now.getFullYear() + 2));
    return expiry.toLocaleDateString('fr-FR');
  }
}
