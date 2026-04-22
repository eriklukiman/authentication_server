import { Component, OnInit, inject } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { EventItem, ImageService } from '../services/image.service';

@Component({
  standalone: true,
  imports: [CommonModule, RouterLink],
  template: `
    <main class="container">
      <h2>Events</h2>
      <p class="sub">Client: <code>{{ clientId }}</code></p>

      <p *ngIf="events.length === 0" class="empty">No events found for this client.</p>

      <div class="event-grid">
        <a class="event-card" *ngFor="let event of events" [routerLink]="['/', clientId, 'browse']" [queryParams]="{ event: event.name }">
          <img [src]="event.coverImageUrl" [alt]="event.name" loading="lazy" />
          <div class="event-info">
            <strong>{{ event.name }}</strong>
            <span>{{ event.date | date:'mediumDate' }}</span>
            <span>{{ event.location }}</span>
            <span>{{ event.photographer }}</span>
            <span class="count">{{ event.imageCount }} photos</span>
          </div>
        </a>
      </div>

      <a class="browse-all" [routerLink]="['/', clientId, 'browse']">Browse all images &rarr;</a>
    </main>
  `,
  styles: [`
    .container { width: 100%; box-sizing: border-box; padding: 16px; }
    .sub { color: #666; font-size: 13px; margin-bottom: 24px; }
    .sub code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
    .empty { color: #999; }
    .event-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }
    .event-card {
      display: block;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,.12);
      text-decoration: none;
      color: inherit;
      transition: transform .2s;
    }
    .event-card:hover { transform: translateY(-3px); }
    .event-card img { width: 100%; height: 160px; object-fit: cover; display: block; }
    .event-info { padding: 12px; display: flex; flex-direction: column; gap: 4px; }
    .event-info strong { font-size: 15px; }
    .event-info span { font-size: 12px; color: #555; }
    .event-info .count { color: #0071e3; font-weight: 600; }
    .browse-all { display: inline-block; margin-top: 8px; color: #0071e3; text-decoration: none; font-weight: 500; }
    .browse-all:hover { text-decoration: underline; }
  `]
})
export class ClientEventsComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly imageService = inject(ImageService);

  clientId = '';
  events: EventItem[] = [];

  ngOnInit(): void {
    this.clientId = this.route.snapshot.paramMap.get('client_id') ?? '';
    this.imageService.getEvents(this.clientId).subscribe((events) => (this.events = events));
  }
}
