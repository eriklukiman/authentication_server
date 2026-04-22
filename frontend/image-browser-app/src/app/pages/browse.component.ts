import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ImageGridComponent } from '../components/image-grid/image-grid.component';
import { ImageService } from '../services/image.service';

@Component({
  standalone: true,
  imports: [CommonModule, FormsModule, ImageGridComponent],
  template: `
    <main class="container">
      <input
        type="text"
        placeholder="Search your BIB here..."
        [(ngModel)]="searchInput"
        (ngModelChange)="onSearchChange($event)"
      />

      <div class="location-badges" *ngIf="locations.length > 0">
        <button
          type="button"
          class="location-badge"
          [class.active]="selectedLocation === ''"
          (click)="selectLocation('')"
        >
          All locations
        </button>
        <button
          type="button"
          class="location-badge"
          *ngFor="let location of locations"
          [class.active]="selectedLocation === location"
          (click)="selectLocation(location)"
        >
          {{ location }}
        </button>
      </div>

      <app-image-grid
        [clientId]="clientId"
        [search]="search"
        [location]="selectedLocation"
      />
    </main>
  `,
  styles: [`
    .container { width: 100%; box-sizing: border-box; padding: 16px; }
    .sub { color: #666; font-size: 13px; margin-bottom: 20px; }
    .sub code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
    input { display: block; width: 100%; margin-bottom: 12px; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
    .location-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .location-badge { border: 1px solid #d0d0d0; background: #f4f4f4; color: #333; border-radius: 999px; padding: 6px 12px; font-size: 12px; cursor: pointer; }
    .location-badge:hover { background: #e8e8e8; }
    .location-badge.active { background: #111; color: #fff; border-color: #111; }
    app-image-grid { display: block; margin-top: 16px; }
  `]
})
export class BrowseComponent implements OnInit, OnDestroy {
  private readonly route = inject(ActivatedRoute);
  private readonly imageService = inject(ImageService);

  clientId = '';
  search = '';
  searchInput = '';
  selectedLocation = '';
  locations: string[] = [];
  private searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

  ngOnInit(): void {
    this.clientId = this.route.snapshot.paramMap.get('client_id') ?? '';

    this.imageService.getImages(this.clientId).subscribe((images) => {
      this.locations = [...new Set(images.map((x) => x.location).filter(Boolean))];
    });
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

  ngOnDestroy(): void {
    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }
  }
}
