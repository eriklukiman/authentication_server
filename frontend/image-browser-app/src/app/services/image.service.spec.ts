import { TestBed } from '@angular/core/testing';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { ImageService } from './image.service';

describe('ImageService', () => {
  let service: ImageService;
  let httpMock: HttpTestingController;

  const clientA = '3e62298e298bc8e6215ba38e9c60e0620ac0c081';
  const clientB = '42cd6d6b808ebf1d159d85a2354178315d265a12';

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()]
    });
    service = TestBed.inject(ImageService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('fetches events by client id from API', () => {
    service.getEvents(clientA).subscribe((events) => {
      expect(events.length).toBe(2);
      expect(events.every((e) => e.clientId === clientA)).toBeTrue();
    });

    const listReq = httpMock.expectOne(`/event/${clientA}`);
    expect(listReq.request.method).toBe('GET');
    listReq.flush({
      data: [
        { msevId: 1, msevName: 'Semarang Heritage Walk', msevCreatedTime: '2026-03-10 08:00:00', msevUpdatedTime: '2026-03-10 08:00:00' },
        { msevId: 2, msevName: 'PLN Industry Visit', msevCreatedTime: '2026-04-05 08:00:00', msevUpdatedTime: '2026-04-05 08:00:00' }
      ],
      pagination: { page: 1, itemPerPage: 20, totalPage: 1, data: 2, totalData: 2 },
      status: { error: 0, errorCode: 0, message: '' }
    });
  });

  it('fetches event photos with search and location query params', () => {
    service.getEventPhotos(clientA, 'PLN Industry Visit', { search: 'meo', location: 'test' }).subscribe((result) => {
      expect(result.data.length).toBe(1);
      expect(result.data[0].url).toBe('http://example.com/filtered.jpg');
      expect(result.pagination.page).toBe(1);
    });

    const photoReq = httpMock.expectOne((req) =>
      req.url === `/event/photo/${clientA}/PLN%20Industry%20Visit` &&
      req.params.get('search') === 'meo' &&
      req.params.get('filters[mftgPhotoLocation]') === 'test' &&
      req.params.get('sorts[mftgId]') === 'desc'
    );
    photoReq.flush({
      data: [
        {
          mftgId: 300,
          mftgFotografer: 'meo',
          mftgEventName: 'PLN Industry Visit',
          mftgPhotoLocation: 'test',
          mftgUrl: 'http://example.com/filtered.jpg',
          mftgClientId: clientA,
          msevId: 2,
          msevName: 'PLN Industry Visit'
        }
      ],
      pagination: { page: 1, itemPerPage: 20, totalPage: 1, data: 1, totalData: 1 },
      status: { error: 0, errorCode: 0, message: '' }
    });
  });

  it('returns unique event names for a client', () => {
    service.getEventNames(clientA).subscribe((names) => {
      expect(names.length).toBe(2);
      expect(new Set(names).size).toBe(names.length);
      expect(names).toContain('Semarang Heritage Walk');
      expect(names).toContain('PLN Industry Visit');
    });

    const req = httpMock.expectOne(`/event/${clientA}`);
    expect(req.request.method).toBe('GET');
    req.flush({
      data: [
        { msevId: 1, msevName: 'Semarang Heritage Walk', msevCreatedTime: '2026-03-10 08:00:00', msevUpdatedTime: '2026-03-10 08:00:00' },
        { msevId: 2, msevName: 'PLN Industry Visit', msevCreatedTime: '2026-04-05 08:00:00', msevUpdatedTime: '2026-04-05 08:00:00' }
      ],
      pagination: { page: 1, itemPerPage: 20, totalPage: 1, data: 2, totalData: 2 },
      status: { error: 0, errorCode: 0, message: '' }
    });
  });
});
