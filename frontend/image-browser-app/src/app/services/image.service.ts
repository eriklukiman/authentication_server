import { Injectable } from '@angular/core';
import { Observable, of } from 'rxjs';

export interface EventItem {
  id: number;
  clientId: string;
  name: string;
  date: string;
  location: string;
  photographer: string;
  coverImageUrl: string;
  imageCount: number;
}

export interface ImageItem {
  id: number;
  clientId: string;
  eventId: number;
  eventName: string;
  location: string;
  photographer: string;
  url: string;
  alt: string;
}

const CLIENT_A = '3e62298e298bc8e6215ba38e9c60e0620ac0c081';
const CLIENT_B = '42cd6d6b808ebf1d159d85a2354178315d265a12';

@Injectable({ providedIn: 'root' })
export class ImageService {
  private readonly events: EventItem[] = [
    // CLIENT_A events
    {
      id: 1,
      clientId: CLIENT_A,
      name: 'Semarang Heritage Walk',
      date: '2026-03-10',
      location: 'Semarang',
      photographer: 'Budi Santoso',
      coverImageUrl: 'https://picsum.photos/id/1024/800/600',
      imageCount: 11
    },
    {
      id: 2,
      clientId: CLIENT_A,
      name: 'PLN Industry Visit',
      date: '2026-04-05',
      location: 'Jakarta',
      photographer: 'Rina Wijaya',
      coverImageUrl: 'https://picsum.photos/id/1048/800/600',
      imageCount: 10
    },
    {
      id: 3,
      clientId: CLIENT_A,
      name: 'Mountain Expedition',
      date: '2026-04-13',
      location: 'Bandung',
      photographer: 'Dani Kusuma',
      coverImageUrl: 'https://picsum.photos/id/1060/800/600',
      imageCount: 10
    },
    // CLIENT_B events
    {
      id: 4,
      clientId: CLIENT_B,
      name: 'Coastal Survey',
      date: '2026-02-20',
      location: 'Surabaya',
      photographer: 'Siti Rahayu',
      coverImageUrl: 'https://picsum.photos/id/1039/800/600',
      imageCount: 9
    },
    {
      id: 5,
      clientId: CLIENT_B,
      name: 'Urban Architecture',
      date: '2026-03-28',
      location: 'Jakarta',
      photographer: 'Andi Putra',
      coverImageUrl: 'https://picsum.photos/id/1071/800/600',
      imageCount: 10
    }
  ];

  private readonly images: ImageItem[] = [
    // Event 1 — Semarang Heritage Walk (mixed: landscape + portrait)
    { id: 1,  clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/id/1024/800/600', alt: 'Heritage walk 1 (landscape)' },
    { id: 2,  clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/id/1025/600/800', alt: 'Heritage walk 2 (portrait)' },
    { id: 3,  clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/id/1026/800/600', alt: 'Heritage walk 3 (landscape)' },
    { id: 4,  clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/id/1027/600/800', alt: 'Heritage walk 4 (portrait)' },
    { id: 5,  clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/id/1028/800/600', alt: 'Heritage walk 5 (landscape)' },
    { id: 6,  clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/id/1029/600/800', alt: 'Heritage walk 6 (portrait)' },
    // Event 2 — PLN Industry Visit (mixed: landscape + portrait)
    { id: 7,  clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/id/1048/800/600', alt: 'PLN visit 1 (landscape)' },
    { id: 8,  clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/id/1049/600/800', alt: 'PLN visit 2 (portrait)' },
    { id: 9,  clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/id/1050/800/600', alt: 'PLN visit 3 (landscape)' },
    { id: 10, clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/id/1051/600/800', alt: 'PLN visit 4 (portrait)' },
    { id: 11, clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/id/1052/800/600', alt: 'PLN visit 5 (landscape)' },
    // Event 3 — Mountain Expedition (mostly portrait — common for tall shots)
    { id: 12, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/id/1060/600/800', alt: 'Mountain expedition 1 (portrait)' },
    { id: 13, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/id/1061/600/800', alt: 'Mountain expedition 2 (portrait)' },
    { id: 14, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/id/1062/800/600', alt: 'Mountain expedition 3 (landscape)' },
    { id: 15, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/id/1063/600/800', alt: 'Mountain expedition 4 (portrait)' },
    { id: 16, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/id/1064/600/800', alt: 'Mountain expedition 5 (portrait)' },
    // Event 4 — Coastal Survey (mostly landscape — wide horizon shots)
    { id: 17, clientId: CLIENT_B, eventId: 4, eventName: 'Coastal Survey', location: 'Surabaya', photographer: 'Siti Rahayu', url: 'https://picsum.photos/id/1039/800/600', alt: 'Coastal survey 1 (landscape)' },
    { id: 18, clientId: CLIENT_B, eventId: 4, eventName: 'Coastal Survey', location: 'Surabaya', photographer: 'Siti Rahayu', url: 'https://picsum.photos/id/1040/600/800', alt: 'Coastal survey 2 (portrait)' },
    { id: 19, clientId: CLIENT_B, eventId: 4, eventName: 'Coastal Survey', location: 'Surabaya', photographer: 'Siti Rahayu', url: 'https://picsum.photos/id/1041/800/600', alt: 'Coastal survey 3 (landscape)' },
    { id: 20, clientId: CLIENT_B, eventId: 4, eventName: 'Coastal Survey', location: 'Surabaya', photographer: 'Siti Rahayu', url: 'https://picsum.photos/id/1042/800/600', alt: 'Coastal survey 4 (landscape)' },
    // Event 5 — Urban Architecture (mixed: portrait for building facades, landscape for street views)
    { id: 21, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/id/1071/600/800', alt: 'Urban architecture 1 (portrait)' },
    { id: 22, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/id/1072/800/600', alt: 'Urban architecture 2 (landscape)' },
    { id: 23, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/id/1073/600/800', alt: 'Urban architecture 3 (portrait)' },
    { id: 24, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/id/1074/800/600', alt: 'Urban architecture 4 (landscape)' },
    { id: 25, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/id/1075/600/800', alt: 'Urban architecture 5 (portrait)' },
    // Event 1 extras
    { id: 26, clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/seed/heritage-7/800/600', alt: 'Heritage walk 7 (landscape)' },
    { id: 27, clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/seed/heritage-8/600/800', alt: 'Heritage walk 8 (portrait)' },
    { id: 28, clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/seed/heritage-9/800/600', alt: 'Heritage walk 9 (landscape)' },
    { id: 29, clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/seed/heritage-10/600/800', alt: 'Heritage walk 10 (portrait)' },
    { id: 30, clientId: CLIENT_A, eventId: 1, eventName: 'Semarang Heritage Walk', location: 'Semarang', photographer: 'Budi Santoso', url: 'https://picsum.photos/seed/heritage-11/800/600', alt: 'Heritage walk 11 (landscape)' },
    // Event 2 extras
    { id: 31, clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/seed/pln-6/800/600', alt: 'PLN visit 6 (landscape)' },
    { id: 32, clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/seed/pln-7/600/800', alt: 'PLN visit 7 (portrait)' },
    { id: 33, clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/seed/pln-8/800/600', alt: 'PLN visit 8 (landscape)' },
    { id: 34, clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/seed/pln-9/600/800', alt: 'PLN visit 9 (portrait)' },
    { id: 35, clientId: CLIENT_A, eventId: 2, eventName: 'PLN Industry Visit', location: 'Jakarta', photographer: 'Rina Wijaya', url: 'https://picsum.photos/seed/pln-10/800/600', alt: 'PLN visit 10 (landscape)' },
    // Event 3 extras
    { id: 36, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/seed/mountain-6/600/800', alt: 'Mountain expedition 6 (portrait)' },
    { id: 37, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/seed/mountain-7/600/800', alt: 'Mountain expedition 7 (portrait)' },
    { id: 38, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/seed/mountain-8/800/600', alt: 'Mountain expedition 8 (landscape)' },
    { id: 39, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/seed/mountain-9/600/800', alt: 'Mountain expedition 9 (portrait)' },
    { id: 40, clientId: CLIENT_A, eventId: 3, eventName: 'Mountain Expedition', location: 'Bandung', photographer: 'Dani Kusuma', url: 'https://picsum.photos/seed/mountain-10/800/600', alt: 'Mountain expedition 10 (landscape)' },
    // Event 4 extras
    { id: 41, clientId: CLIENT_B, eventId: 4, eventName: 'Coastal Survey', location: 'Surabaya', photographer: 'Siti Rahayu', url: 'https://picsum.photos/seed/coastal-5/800/600', alt: 'Coastal survey 5 (landscape)' },
    { id: 42, clientId: CLIENT_B, eventId: 4, eventName: 'Coastal Survey', location: 'Surabaya', photographer: 'Siti Rahayu', url: 'https://picsum.photos/seed/coastal-6/600/800', alt: 'Coastal survey 6 (portrait)' },
    { id: 43, clientId: CLIENT_B, eventId: 4, eventName: 'Coastal Survey', location: 'Surabaya', photographer: 'Siti Rahayu', url: 'https://picsum.photos/seed/coastal-7/800/600', alt: 'Coastal survey 7 (landscape)' },
    { id: 44, clientId: CLIENT_B, eventId: 4, eventName: 'Coastal Survey', location: 'Surabaya', photographer: 'Siti Rahayu', url: 'https://picsum.photos/seed/coastal-8/800/600', alt: 'Coastal survey 8 (landscape)' },
    { id: 45, clientId: CLIENT_B, eventId: 4, eventName: 'Coastal Survey', location: 'Surabaya', photographer: 'Siti Rahayu', url: 'https://picsum.photos/seed/coastal-9/600/800', alt: 'Coastal survey 9 (portrait)' },
    // Event 5 extras
    { id: 46, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/seed/urban-6/600/800', alt: 'Urban architecture 6 (portrait)' },
    { id: 47, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/seed/urban-7/800/600', alt: 'Urban architecture 7 (landscape)' },
    { id: 48, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/seed/urban-8/600/800', alt: 'Urban architecture 8 (portrait)' },
    { id: 49, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/seed/urban-9/800/600', alt: 'Urban architecture 9 (landscape)' },
    { id: 50, clientId: CLIENT_B, eventId: 5, eventName: 'Urban Architecture', location: 'Jakarta', photographer: 'Andi Putra', url: 'https://picsum.photos/seed/urban-10/600/800', alt: 'Urban architecture 10 (portrait)' }
  ];

  getEvents(clientId: string): Observable<EventItem[]> {
    return of(this.events.filter((e) => e.clientId === clientId));
  }

  getImages(clientId: string): Observable<ImageItem[]> {
    return of(this.images.filter((img) => img.clientId === clientId));
  }

  getEventNames(clientId: string): Observable<string[]> {
    const names = [...new Set(this.images.filter((i) => i.clientId === clientId).map((i) => i.eventName))];
    return of(names);
  }
}