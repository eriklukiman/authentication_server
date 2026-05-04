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
  readonly pageSize = 50;
  currentPage = 1;
  totalPages = 1;
  totalData = 0;
  isLoadingMore = false;
  hasMore = false;
  private imagesRequestSub: Subscription | null = null;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['clientId'] || changes['search'] || changes['location'] || changes['eventFilter']) {
      this.resetPagination();
      this.loadImages(1, true);
    }
  }

  private loadImages(page = 1, replace = false): void {
    this.imagesRequestSub?.unsubscribe();

    if (!this.clientId || !this.eventFilter) {
      this.images.set([]);
      this.loading.set(false);
      this.currentPage = 1;
      this.totalPages = 1;
      return;
    }

    this.loading.set(true);

    if (page === 1) {
      this.images.set([]);
    }

    this.imagesRequestSub = this.imageService.getEventPhotos(this.clientId, this.eventFilter, {
      search: this.search,
      location: this.location,
      page,
      max: this.pageSize
    }).subscribe({
      next: (response) => {
        console.log('Received response:', response);
        const images = response.data;
        const pagination = response.pagination || { page: 1, totalPage: 1 };
        this.totalData = pagination.totalData || 0;
        this.hasMore = pagination.page < pagination.totalPage;
        if (replace || page === 1) {
          this.images.set(images);
        } else {
          this.images.set([...this.images(), ...images]);
        }
        this.currentPage = pagination.page;
        this.totalPages = pagination.totalPage;
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
    this.currentPage = 1;
    this.totalPages = 1;
  }

  @HostListener('window:scroll')
  onWindowScroll(): void {
    if (this.loading() || this.currentPage >= this.totalPages) {
      return;
    }

    console.log('Scroll event detected');

    const threshold = 320;
    const viewportBottom = window.innerHeight + window.scrollY;
    const totalHeight = document.documentElement.scrollHeight;

    console.log(`Viewport bottom: ${viewportBottom}, Total height: ${totalHeight}, Threshold: ${threshold}`);

    if (totalHeight - viewportBottom <= threshold && this.hasMore) {
      this.loadMore();
    }
  }

  loadMore(): void {
    if (this.loading() || this.isLoadingMore || this.currentPage >= this.totalPages) {
      return;
    }
    this.loading.set(true);
    this.loadImages(this.currentPage + 1, false);
  }

  trackById(index: number, item: ImageItem): string {
    // Composite key: id + eventName (or fallback to id + location), fallback to index if not unique
    const key = `${item.id}-${item.eventName || item.location || ''}`;
    // If there are still duplicates, fallback to index for uniqueness
    return key || String(index);
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