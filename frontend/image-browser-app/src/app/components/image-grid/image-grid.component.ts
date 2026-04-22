import { Component, HostListener, Input, OnChanges, OnDestroy, SimpleChanges, inject } from '@angular/core';
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

  images: ImageItem[] = [];
  loading = false;
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
      this.images = [];
      this.loading = false;
      return;
    }

    this.loading = true;
    this.images = [];
    this.resetPagination();
    this.imagesRequestSub = this.imageService.getImages(this.clientId, {
      search: this.search,
      location: this.location
    }).subscribe({
      next: (data) => {
        this.images = data;
      },
      error: () => {
        this.loading = false;
      },
      complete: () => {
        this.loading = false;
      }
    });
  }

  ngOnDestroy(): void {
    this.imagesRequestSub?.unsubscribe();
  }

  private resetPagination(): void {
    this.visibleCount = this.pageSize;
  }

  get filteredImages(): ImageItem[] {
    const q = this.search.trim().toLowerCase();
    return this.images.filter((img) => {
      const byLocation = !this.location || img.location === this.location;
      const byEvent = !this.eventFilter || img.eventName === this.eventFilter;
      const bySearch =
        !q ||
        img.eventName.toLowerCase().includes(q) ||
        img.location.toLowerCase().includes(q) ||
        img.photographer.toLowerCase().includes(q);
      return byLocation && byEvent && bySearch;
    });
  }

  get visibleImages(): ImageItem[] {
    return this.filteredImages.slice(0, this.visibleCount);
  }

  get hasMore(): boolean {
    return this.visibleCount < this.filteredImages.length;
  }

  loadMore(): void {
    if (!this.hasMore) {
      return;
    }

    this.visibleCount = Math.min(this.visibleCount + this.pageSize, this.filteredImages.length);
  }

  @HostListener('window:scroll')
  onWindowScroll(): void {
    if (this.loading || !this.hasMore) {
      return;
    }

    const threshold = 320;
    const viewportBottom = window.innerHeight + window.scrollY;
    const totalHeight = document.documentElement.scrollHeight;

    if (totalHeight - viewportBottom <= threshold) {
      this.loadMore();
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