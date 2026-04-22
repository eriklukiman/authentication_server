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
}

const CLIENT_A = '3e62298e298bc8e6215ba38e9c60e0620ac0c081';
const CLIENT_B = '42cd6d6b808ebf1d159d85a2354178315d265a12';

@Injectable({ providedIn: 'root' })
export class ImageService {
  private readonly http = inject(HttpClient);
  private readonly eventApiBase = this.buildEventApiBase();

  private readonly events: EventItem[] = [];

  private readonly images: ImageItem[] = [];

  getEvents(clientId: string): Observable<EventItem[]> {
    return this.http
      .get<EventListResponse>(`${this.eventApiBase}/${encodeURIComponent(clientId)}`)
      .pipe(
        map((response) => this.mapEvents(response.data, clientId))
      );
  }

  getEventDetail(clientId: string, eventId: number | string): Observable<EventDetail> {
    return this.http
      .get<EventDetailResponse>(`${this.eventApiBase}/${encodeURIComponent(clientId)}/${encodeURIComponent(String(eventId))}`)
      .pipe(map((response) => response.data));
  }

  getImages(clientId: string, options: ImageQueryOptions = {}): Observable<ImageItem[]> {
    return this.getEvents(clientId).pipe(
      switchMap((events) =>
        events.length === 0
          ? of([])
          : from(events).pipe(
              // Stream each event's photo list as soon as it arrives.
              mergeMap((event) => this.getEventPhotos(clientId, event.name, options)),
              scan((allPhotos, photos) => allPhotos.concat(photos), [] as ImageItem[])
            )
      ),
      map((images) => images)
    );
  }

  getEventNames(clientId: string): Observable<string[]> {
    return this.getEvents(clientId).pipe(
      map((events) => [...new Set(events.map((event) => event.name))])
    );
  }

  getEventPhotos(clientId: string, eventName: string, options: ImageQueryOptions): Observable<ImageItem[]> {
    const url = `${this.eventApiBase}/photo/${encodeURIComponent(clientId)}/${encodeURIComponent(eventName)}`;
    let params = new HttpParams().set('sorts[mftgId]', 'desc');
    const search = options.search?.trim();
    const location = options.location?.trim();

    if (search) {
      params = params.set('search', search);
    }
    if (location) {
      params = params.set('filters[mftgPhotoLocation]', location);
    }

    return this.http.get<PhotoListResponse>(url, { params }).pipe(
      map((response) => response.data.map((photo) => this.mapPhoto(photo, clientId, eventName))),
      catchError(() => of([]))
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
    const base = environment.apiBaseUrl.replace(/\/$/, '');
    return `${base}/event`;
  }
}