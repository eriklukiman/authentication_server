import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, forkJoin, from, of } from 'rxjs';
import { catchError, map, mergeMap, scan, switchMap } from 'rxjs/operators';
import { environment } from '../../environments/environment';

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

interface ApiStatus {
  error: boolean | number;
  errorCode: string | number;
  message: string;
}

interface ApiPagination {
  page: number;
  itemPerPage: number;
  totalPage: number;
  data: number;
  totalData: number;
}

interface ApiEvent {
  msevId: number;
  msevName: string;
  msevCreatedTime: string;
  msevUpdatedTime: string;
}

export interface EventLocation {
  mlocId: number;
  mlocMsevId: number;
  mlocName: string;
  mlocCreatedClientId: string;
  mlocAppVersion: string;
  mlocGuiVersion: string;
  mlocMainVersion: string;
  mlocCreatedTime: string;
  mlocUpdatedTime: string;
}

export interface EventDetail {
  msevId: number;
  msevName: string;
  msevCreatedTime: string;
  msevUpdatedTime: string;
  locations: EventLocation[];
}

interface EventListResponse {
  data: ApiEvent[];
  pagination: ApiPagination;
  status: ApiStatus;
}

interface EventDetailResponse {
  data: EventDetail;
  status: ApiStatus;
}

interface ApiPhoto {
  mftgId: number;
  mftgFotografer: string;
  mftgEventName: string;
  mftgPhotoLocation: string;
  mftgUrl: string;
  mftgClientId: string;
  msevId: number;
  msevName: string;
}

interface PhotoListResponse {
  data: ApiPhoto[];
  pagination: ApiPagination;
  status: ApiStatus;
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

export interface ImageQueryOptions {
  search?: string;
  location?: string;
  page?: number;
  max?: number;
}

const CLIENT_A = '3e62298e298bc8e6215ba38e9c60e0620ac0c081';
const CLIENT_B = '42cd6d6b808ebf1d159d85a2354178315d265a12';

@Injectable({ providedIn: 'root' })
export class ImageService {
  private readonly http = inject(HttpClient);

  private readonly events: EventItem[] = [];

  private readonly images: ImageItem[] = [];

  getEvents(clientId: string): Observable<EventItem[]> {
    const eventApiBase = this.buildEventApiBase();
    return this.http
      .get<EventListResponse>(`${eventApiBase}/${encodeURIComponent(clientId)}`)
      .pipe(
        map((response) => this.mapEvents(response.data, clientId))
      );
  }

  getEventDetail(clientId: string, eventId: number | string): Observable<EventDetail> {
    const eventApiBase = this.buildEventApiBase();
    return this.http
      .get<EventDetailResponse>(`${eventApiBase}/${encodeURIComponent(clientId)}/${encodeURIComponent(String(eventId))}`)
      .pipe(map((response) => response.data));
  }

  getEventNames(clientId: string): Observable<string[]> {
    return this.getEvents(clientId).pipe(
      map((events) => [...new Set(events.map((event) => event.name))])
    );
  }

  getEventPhotos(clientId: string, eventName: string, options: ImageQueryOptions): Observable<{ data: ImageItem[]; pagination: ApiPagination }> {
    const eventApiBase = this.buildEventApiBase();
    const url = `${eventApiBase}/photo/${encodeURIComponent(clientId)}/${encodeURIComponent(eventName)}`;
    let params = new HttpParams().set('sorts[mftgId]', 'desc');
    const search = options.search?.trim();
    const location = options.location?.trim();
    const page = options.page ?? 1;
    const max = options.max ?? 20;

    if (search) {
      params = params.set('search', search);
    }
    if (location) {
      params = params.set('filters[mftgPhotoLocation]', location);
    }

    if (page) {
      params = params.set('page', String(page));
    }

    if (max) {
      params = params.set('max', String(max));
    }

    return this.http.get<PhotoListResponse>(url, { params }).pipe(
      map((response) => ({
        data: response.data.map((photo) => this.mapPhoto(photo, clientId, eventName)),
        pagination: response.pagination
      })),
      catchError(() => of({ data: [], pagination: { page: 1, itemPerPage: max, totalPage: 1, data: 0, totalData: 0 } }))
    );
  }

  private mapPhoto(photo: ApiPhoto, clientId: string, eventName: string): ImageItem {
    const resolvedEventName = photo.mftgEventName || photo.msevName || eventName;
    return {
      id: photo.mftgId,
      clientId: photo.mftgClientId || clientId,
      eventId: photo.msevId,
      eventName: resolvedEventName,
      location: photo.mftgPhotoLocation || 'Unknown',
      photographer: photo.mftgFotografer || 'Unknown',
      url: photo.mftgUrl,
      alt: `${resolvedEventName} #${photo.mftgId}`
    };
  }

  private mapEvents(apiEvents: ApiEvent[], clientId: string): EventItem[] {
    return apiEvents.map((event) => ({
      id: event.msevId,
      clientId,
      name: event.msevName,
      date: event.msevCreatedTime,
      location: 'Unknown',
      photographer: 'Unknown',
      coverImageUrl: `https://picsum.photos/seed/event-${event.msevId}/800/600`,
      imageCount: 0
    }));
  }

  private buildEventApiBase(): string {
    const root = globalThis as { __IMAGE_BROWSER_API_BASE_URL?: string };
    const runtimeBase = (root.__IMAGE_BROWSER_API_BASE_URL || '').trim();
    const base = (runtimeBase || environment.apiBaseUrl).replace(/\/$/, '');
    return `${base}/event`;
  }
}