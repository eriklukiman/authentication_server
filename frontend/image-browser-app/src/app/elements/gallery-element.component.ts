import { Component, Input, OnDestroy, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Subscription } from 'rxjs';
import { ImageGridComponent } from '../components/image-grid/image-grid.component';
import { ImageService } from '../services/image.service';

@Component({
  selector: 'app-gallery-element',
  standalone: true,
  imports: [FormsModule, ImageGridComponent],
  templateUrl: './gallery-element.component.html',
  styleUrl: './gallery-element.component.css'
})
export class GalleryElementComponent implements OnInit, OnDestroy {
  private readonly imageService = inject(ImageService);
  private detailSub: Subscription | null = null;
  private searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

  @Input() clientId = '';
  @Input() eventId = '';
  @Input() background = '';
  @Input() apiBaseUrl = '';

  search = '';
  searchInput = '';
  selectedLocation = '';
  locations: string[] = [];

  ngOnInit(): void {
    this.applyRuntimeApiBaseUrl();
    this.loadLocations();
  }

  ngOnDestroy(): void {
    this.detailSub?.unsubscribe();
    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }
  }

  @Input('client-id')
  set clientIdAttr(value: string) {
    this.clientId = (value ?? '').trim();
    this.loadLocations();
  }

  @Input('event-id')
  set eventIdAttr(value: string) {
    this.eventId = (value ?? '').trim();
    this.loadLocations();
  }

  @Input('background')
  set backgroundAttr(value: string) {
    this.background = (value ?? '').trim();
  }

  @Input('api-base-url')
  set apiBaseUrlAttr(value: string) {
    this.apiBaseUrl = (value ?? '').trim();
    this.applyRuntimeApiBaseUrl();
    this.loadLocations();
  }

  selectLocation(location: string): void {
    this.selectedLocation = location;
  }

  onSearchChange(value: string): void {
    this.searchInput = value;

    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }

    this.searchDebounceTimer = setTimeout(() => {
      this.search = this.searchInput;
    }, 300);
  }

  private loadLocations(): void {
    this.detailSub?.unsubscribe();

    if (!this.clientId || !this.eventId) {
      this.locations = [];
      this.selectedLocation = '';
      return;
    }

    this.detailSub = this.imageService.getEventDetail(this.clientId, this.eventId).subscribe({
      next: (eventDetail) => {
        this.locations = eventDetail.locations
          .map((location) => location.mlocName)
          .filter((name) => !!name);

        if (this.selectedLocation && !this.locations.includes(this.selectedLocation)) {
          this.selectedLocation = '';
        }
      },
      error: () => {
        this.locations = [];
        this.selectedLocation = '';
      }
    });
  }

  private applyRuntimeApiBaseUrl(): void {
    const root = globalThis as { __IMAGE_BROWSER_API_BASE_URL?: string };
    const value = (this.apiBaseUrl || '').trim().replace(/\/$/, '');
    if (value) {
      root.__IMAGE_BROWSER_API_BASE_URL = value;
      return;
    }
    delete root.__IMAGE_BROWSER_API_BASE_URL;
  }
}
