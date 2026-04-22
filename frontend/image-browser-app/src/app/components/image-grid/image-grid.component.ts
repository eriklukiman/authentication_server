import { Component, HostListener, Input, OnChanges, SimpleChanges, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ImageItem, ImageService } from '../../services/image.service';
import { LightboxComponent } from '../lightbox/lightbox.component';

@Component({
  selector: 'app-image-grid',
  standalone: true,
  imports: [CommonModule, LightboxComponent],
  templateUrl: './image-grid.component.html',
  styleUrl: './image-grid.component.css'
})
export class ImageGridComponent implements OnChanges {
  @Input() clientId = '';
  @Input() search = '';
  @Input() location = '';
  @Input() eventFilter = '';

  private readonly imageService = inject(ImageService);

  images: ImageItem[] = [];
  loading = false;
  selectedImage: ImageItem | null = null;
  orientationMap: Record<number, 'landscape' | 'portrait' | 'square'> = {};
  loadedSet = new Set<number>();
  readonly pageSize = 20;
  visibleCount = this.pageSize;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['clientId']) {
      this.loadImages();
      return;
    }

    if (changes['search'] || changes['location'] || changes['eventFilter']) {
      this.resetPagination();
    }
  }

  private loadImages(): void {
    if (!this.clientId) {
      this.images = [];
      return;
    }
    this.loading = true;
    this.loadedSet = new Set<number>();
    this.orientationMap = {};
    this.imageService.getImages(this.clientId).subscribe((data) => {
      this.images = data;
      this.resetPagination();
      this.loading = false;
    });
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

  onImageLoad(event: Event, image: ImageItem): void {
    const target = event.target as HTMLImageElement;
    target.classList.add('loaded');
    this.loadedSet.add(image.id);

    const { naturalWidth, naturalHeight } = target;
    if (!naturalWidth || !naturalHeight) {
      return;
    }

    const ratio = naturalWidth / naturalHeight;
    if (ratio > 1.15) {
      this.orientationMap[image.id] = 'landscape';
      return;
    }
    if (ratio < 0.87) {
      this.orientationMap[image.id] = 'portrait';
      return;
    }
    this.orientationMap[image.id] = 'square';
  }

  getOrientationClass(image: ImageItem): string {
    return this.orientationMap[image.id] ?? 'landscape';
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