import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Eleve } from '../../../core/models/eleve';

@Component({
  selector: 'app-eleve-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './eleve-modal.component.html',
  styleUrls: ['./eleve-modal.component.scss']
})
export class EleveModalComponent implements OnInit {
  @Input() eleve: Eleve | null = null;
  @Input() isVisible: boolean = false;
  @Output() close = new EventEmitter<void>();

  ngOnInit(): void {
    // Gérer l'overflow du body quand la modal est ouverte
    if (this.isVisible) {
      document.body.style.overflow = 'hidden';
    }
  }

  ngOnChanges(): void {
    if (this.isVisible) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'auto';
    }
  }

  ngOnDestroy(): void {
    document.body.style.overflow = 'auto';
  }

  onClose(): void {
    this.close.emit();
  }

  onBackdropClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) {
      this.onClose();
    }
  }

  getPhotoUrl(): string {
    if (this.eleve?.photoActive?.url) {
      return this.eleve.photoActive.url;
    }
    // Image par défaut en fonction du sexe
    return this.eleve?.sexe === 'F'
      ? 'assets/images/avatar-female.png'
      : 'assets/images/avatar-male.png';
  }
}
