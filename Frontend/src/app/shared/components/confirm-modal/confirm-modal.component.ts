import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-confirm-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './confirm-modal.component.html',
  styleUrls: ['./confirm-modal.component.scss']
})
export class ConfirmModalComponent {
  @Input() isVisible: boolean = false;
  @Input() title: string = 'Confirmation';
  @Input() message: string = 'Êtes-vous sûr de vouloir continuer ?';
  @Input() confirmText: string = 'Oui';
  @Input() cancelText: string = 'Non';
  @Input() type: 'warning' | 'danger' | 'info' | 'success' = 'warning';

  @Output() confirm = new EventEmitter<void>();
  @Output() cancel = new EventEmitter<void>();

  onConfirm(): void {
    this.confirm.emit();
  }

  onCancel(): void {
    this.cancel.emit();
  }

  onBackdropClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) {
      this.onCancel();
    }
  }

  getIconClass(): string {
    switch (this.type) {
      case 'warning': return 'bi-exclamation-triangle-fill text-warning';
      case 'danger': return 'bi-x-circle-fill text-danger';
      case 'info': return 'bi-info-circle-fill text-info';
      case 'success': return 'bi-check-circle-fill text-success';
      default: return 'bi-exclamation-triangle-fill text-warning';
    }
  }

  getButtonClass(): string {
    switch (this.type) {
      case 'warning': return 'btn-warning';
      case 'danger': return 'btn-danger';
      case 'info': return 'btn-info';
      case 'success': return 'btn-success';
      default: return 'btn-warning';
    }
  }
}
