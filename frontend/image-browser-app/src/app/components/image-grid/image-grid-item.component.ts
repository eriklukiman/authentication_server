import { Component, EventEmitter, Input, OnChanges, Output, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ImageItem } from '../../services/image.service';

@Component({
  selector: 'app-image-grid-item',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './image-grid-item.component.html',
  styleUrl: './image-grid-item.component.css',
  host: {
    '[class.landscape]': "orientation === 'landscape'",
    '[class.portrait]': "orientation === 'portrait'",
    '[class.square]': "orientation === 'square'"
  }
})
export class ImageGridItemComponent implements OnChanges {
  @Input({ required: true }) image!: ImageItem;
  @Output() opened = new EventEmitter<ImageItem>();

  loading = true;
  failed = false;
  imageLoaded = false;
  orientation: 'landscape' | 'portrait' | 'square' = 'landscape';

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['image']) {
      this.loading = true;
      this.failed = false;
      this.imageLoaded = false;
      this.orientation = 'landscape';
    }
  }

  open(): void {
    if (this.failed) {
      return;
    }
    this.opened.emit(this.image);
  }

  onImageLoad(event: Event): void {
    this.loading = false;
    this.failed = false;
    this.imageLoaded = true;

    const target = event.target as HTMLImageElement;
    const { naturalWidth, naturalHeight } = target;
    if (!naturalWidth || !naturalHeight) {
      return;
    }

    const ratio = naturalWidth / naturalHeight;
    if (ratio > 1.15) {
      this.orientation = 'landscape';
      return;
    }
    if (ratio < 0.87) {
      this.orientation = 'portrait';
      return;
    }
    this.orientation = 'square';
  }

  onImageError(): void {
    this.loading = false;
    this.failed = true;
    this.imageLoaded = false;
    this.orientation = 'square';
  }
}
