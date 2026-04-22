import {
  Component,
  Input,
  Output,
  EventEmitter,
  OnChanges,
  OnDestroy,
  SimpleChanges,
  HostListener
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { ImageItem } from '../../services/image.service';

@Component({
  selector: 'app-lightbox',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './lightbox.component.html',
  styleUrl: './lightbox.component.css'
})
export class LightboxComponent implements OnChanges, OnDestroy {
  @Input() image: ImageItem | null = null;
  @Output() closed = new EventEmitter<void>();

  zoom = 1;
  imageLoaded = false;
  panX = 0;
  panY = 0;
  isDragging = false;
  private dragStartX = 0;
  private dragStartY = 0;
  private startPanX = 0;
  private startPanY = 0;
  private readonly ZOOM_STEP = 0.25;
  private readonly ZOOM_MIN = 0.5;
  private readonly ZOOM_MAX = 4;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['image'] && this.image) {
      this.zoom = 1;
      this.imageLoaded = false;
      this.resetPan();
    }
    if (changes['image'] && !this.image) {
      this.imageLoaded = false;
      this.resetPan();
    }
  }

  ngOnDestroy(): void {
    document.body.style.overflow = '';
  }

  @HostListener('document:keydown', ['$event'])
  onKey(e: KeyboardEvent): void {
    if (!this.image) return;
    if (e.key === 'Escape') this.close();
    if (e.key === '+') this.zoomIn();
    if (e.key === '-') this.zoomOut();
  }

  zoomIn(): void {
    this.zoom = Math.min(this.ZOOM_MAX, +(this.zoom + this.ZOOM_STEP).toFixed(2));
  }

  zoomOut(): void {
    this.zoom = Math.max(this.ZOOM_MIN, +(this.zoom - this.ZOOM_STEP).toFixed(2));
    if (this.zoom <= 1) {
      this.resetPan();
    }
  }

  resetZoom(): void {
    this.zoom = 1;
    this.resetPan();
  }

  get imageTransform(): string {
    return `translate(${this.panX}px, ${this.panY}px) scale(${this.zoom})`;
  }

  onDragStart(e: PointerEvent): void {
    if (e.button !== 0 || !this.imageLoaded || this.zoom <= 1) {
      return;
    }

    this.isDragging = true;
    this.dragStartX = e.clientX;
    this.dragStartY = e.clientY;
    this.startPanX = this.panX;
    this.startPanY = this.panY;
    e.preventDefault();
  }

  onDragMove(e: PointerEvent): void {
    if (!this.isDragging) {
      return;
    }

    this.panX = this.startPanX + (e.clientX - this.dragStartX);
    this.panY = this.startPanY + (e.clientY - this.dragStartY);
  }

  onDragEnd(): void {
    this.isDragging = false;
  }

  @HostListener('document:pointerup')
  onDocumentPointerUp(): void {
    this.onDragEnd();
  }

  download(): void {
    if (!this.image) return;
    const a = document.createElement('a');
    a.href = this.image.url;
    a.download = this.image.alt ?? 'image';
    a.target = '_blank';
    a.rel = 'noopener noreferrer';
    a.click();
  }

  close(): void {
    this.closed.emit();
  }

  onImageLoad(): void {
    this.imageLoaded = true;
  }

  onImageError(): void {
    // Hide spinner if the image fails to load so the UI doesn't block.
    this.imageLoaded = true;
  }

  onBackdropClick(e: MouseEvent): void {
    if ((e.target as HTMLElement).classList.contains('lb-backdrop')) {
      this.close();
    }
  }

  private resetPan(): void {
    this.panX = 0;
    this.panY = 0;
    this.isDragging = false;
  }
}
