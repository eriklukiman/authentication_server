import { Component, HostListener, Input, OnChanges, OnDestroy, SimpleChanges, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ImageItem, ImageService } from '../../services/image.service';
import { LightboxComponent } from '../lightbox/lightbox.component';
import { Subscription } from 'rxjs';
import { ImageGridItemComponent } from './image-grid-item.component';

@Component({
  selector: 'app-image-grid',
  standalone: true,
  imports: [CommonModule, LightboxComponent, ImageGridItemComponent],
  templateUrl: './image-grid.component.html',
  styleUrl: './image-grid.component.css'
})
export class ImageGridComponent implements OnChanges, OnDestroy {
  @Input() clientId = '';
  @Input() search = '';
  @Input() location = '';
  @Input() eventFilter = '';

  private readonly imageService = inject(ImageService);

  images = signal<ImageItem[]>([]);
  loading = signal(false);
  selectedImage: ImageItem | null = null;
  readonly pageSize = 20;
  visibleCount = this.pageSize;
  private imagesRequestSub: Subscription | null = null;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['clientId']) {
      this.loadImages();
      return;
    }

    if (changes['search'] || changes['location']) {
      this.loadImages();
      return;
    }

    if (changes['eventFilter']) {
      this.resetPagination();
    }
  }

  private loadImages(): void {
    this.imagesRequestSub?.unsubscribe();

    if (!this.clientId) {
      this.images.set([]);
      this.loading.set(false);
      return;
    }

    if (!this.eventFilter) {
      this.images.set([]);
      this.loading.set(false);
      return;
    }

    this.loading.set(true);
    this.images.set([]);
    this.resetPagination();
    this.imagesRequestSub = this.imageService.getEventPhotos(this.clientId, this.eventFilter, {
      search: this.search,
      location: this.location
    }).subscribe({
      next: (data) => {
        console.log('Loaded images:', data);
        this.images.set(data);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
      }
    });
  }

  ngOnDestroy(): void {
    this.imagesRequestSub?.unsubscribe();
  }

  private resetPagination(): void {
    this.visibleCount = this.pageSize;
  }

  @HostListener('window:scroll')
  onWindowScroll(): void {
    if (this.loading()) {
      return;
    }

    const threshold = 320;
    const viewportBottom = window.innerHeight + window.scrollY;
    const totalHeight = document.documentElement.scrollHeight;

    if (totalHeight - viewportBottom <= threshold) {
      // this.loadMore();
    }
  }

  trackById(_: number, item: ImageItem): number {
    return item.id;
  }

  openLightbox(image: ImageItem): void {
    this.selectedImage = image;
    document.body.style.overflow = 'hidden';
  }

  closeLightbox(): void {
    this.selectedImage = null;
    document.body.style.overflow = '';
  }
}