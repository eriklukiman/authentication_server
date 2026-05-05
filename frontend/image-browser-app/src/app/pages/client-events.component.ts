import { Component, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { DatePipe, DecimalPipe } from '@angular/common';
import { EventItem, ImageService } from '../services/image.service';

@Component({
  standalone: true,
  imports: [RouterLink, DatePipe, DecimalPipe],
  templateUrl: './client-events.component.html',
  styleUrl: './client-events.component.css'
})
export class ClientEventsComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly imageService = inject(ImageService);

  clientId = '';
  events = signal<EventItem[]>([]);
  loading = signal(true);

  ngOnInit(): void {
    this.clientId = this.route.snapshot.paramMap.get('client_id') ?? '';
    this.imageService.getEvents(this.clientId).subscribe((data) => {
      this.events.set(data);
      this.loading.set(false);
    });
  }
}
