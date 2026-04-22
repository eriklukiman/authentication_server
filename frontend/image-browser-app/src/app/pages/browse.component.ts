import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ImageGridComponent } from '../components/image-grid/image-grid.component';
import { ImageService } from '../services/image.service';

@Component({
  standalone: true,
  imports: [FormsModule, ImageGridComponent],
  templateUrl: './browse.component.html',
  styleUrl: './browse.component.css'
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

    this.imageService.getEvents(this.clientId).subscribe((events) => {
      this.locations = [...new Set(events.map((e) => e.location).filter(Boolean))];
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
