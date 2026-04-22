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
      expect(events.length).toBe(3);
      expect(events.every((e) => e.clientId === clientA)).toBeTrue();
      expect(events[0].id).toBe(1);
      expect(events[0].name).toBe('Semarang Heritage Walk');
      expect(events[0].date).toBe('2026-03-10 08:00:00');
      expect(events[0].coverImageUrl).toContain('event-1');
      expect(events[0].imageCount).toBe(0);
      expect(events[0].location).toBe('Semarang');
      expect(events[1].location).toBe('Jakarta');
      expect(events[2].location).toBe('Bandung');
    });

    const listReq = httpMock.expectOne(`/event/${clientA}`);
    expect(listReq.request.method).toBe('GET');
    listReq.flush({
      data: [
        { msevId: 1, msevName: 'Semarang Heritage Walk', msevCreatedTime: '2026-03-10 08:00:00', msevUpdatedTime: '2026-03-10 08:00:00' },
        { msevId: 2, msevName: 'PLN Industry Visit',     msevCreatedTime: '2026-04-05 08:00:00', msevUpdatedTime: '2026-04-05 08:00:00' },
        { msevId: 3, msevName: 'Mountain Expedition',    msevCreatedTime: '2026-04-13 08:00:00', msevUpdatedTime: '2026-04-13 08:00:00' }
      ],
      pagination: { page: 1, itemPerPage: 20, totalPage: 1, data: 3, totalData: 3 },
      status: { error: 0, errorCode: 0, message: '' }
    });

    const detailBase = `/event/${clientA}`;
    const makeDetail = (id: number, name: string, locationName: string) => ({
      data: {
        msevId: id, msevName: name,
        msevCreatedTime: '', msevUpdatedTime: '',
        locations: [{ mlocId: id, mlocMsevId: id, mlocName: locationName, mlocCreatedClientId: clientA,
          mlocAppVersion: '', mlocGuiVersion: '', mlocMainVersion: '', mlocCreatedTime: '', mlocUpdatedTime: '' }]
      },
      status: { error: 0, errorCode: 0, message: '' }
    });

    httpMock.expectOne(`${detailBase}/Semarang%20Heritage%20Walk`).flush(makeDetail(1, 'Semarang Heritage Walk', 'Semarang'));
    httpMock.expectOne(`${detailBase}/PLN%20Industry%20Visit`).flush(makeDetail(2, 'PLN Industry Visit', 'Jakarta'));
    httpMock.expectOne(`${detailBase}/Mountain%20Expedition`).flush(makeDetail(3, 'Mountain Expedition', 'Bandung'));
  });

  it('fetches event detail by client and event name', () => {
    service.getEventDetail(clientA, 'PLN Industry Visit').subscribe((eventDetail) => {
      expect(eventDetail.msevId).toBe(2);
      expect(eventDetail.msevName).toBe('PLN Industry Visit');
      expect(eventDetail.locations.length).toBe(1);
      expect(eventDetail.locations[0].mlocName).toBe('Jakarta');
    });

    const req = httpMock.expectOne(`/event/${clientA}/PLN%20Industry%20Visit`);
    expect(req.request.method).toBe('GET');
    req.flush({
      data: {
        msevId: 2,
        msevName: 'PLN Industry Visit',
        msevCreatedTime: '2026-04-05 08:00:00',
        msevUpdatedTime: '2026-04-05 08:00:00',
        locations: [
          {
            mlocId: 1,
            mlocMsevId: 2,
            mlocName: 'Jakarta',
            mlocCreatedClientId: clientA,
            mlocAppVersion: '',
            mlocGuiVersion: '',
            mlocMainVersion: '',
            mlocCreatedTime: '2026-04-05 08:00:00',
            mlocUpdatedTime: '2026-04-05 08:00:00'
          }
        ]
      },
      status: { error: 0, errorCode: 0, message: '' }
    });
  });

  it('returns images filtered by client id', () => {
    let latestImages: any[] = [];
    service.getImages(clientA).subscribe((images) => {
      latestImages = images;
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

    httpMock.expectOne(`/event/${clientA}/Semarang%20Heritage%20Walk`).flush({
      data: {
        msevId: 1,
        msevName: 'Semarang Heritage Walk',
        msevCreatedTime: '',
        msevUpdatedTime: '',
        locations: []
      },
      status: { error: 0, errorCode: 0, message: '' }
    });

    httpMock.expectOne(`/event/${clientA}/PLN%20Industry%20Visit`).flush({
      data: {
        msevId: 2,
        msevName: 'PLN Industry Visit',
        msevCreatedTime: '',
        msevUpdatedTime: '',
        locations: []
      },
      status: { error: 0, errorCode: 0, message: '' }
    });

    httpMock.expectOne((req) =>
      req.url === `/event/photo/${clientA}/Semarang%20Heritage%20Walk` &&
      req.params.get('sorts[mftgId]') === 'desc'
    ).flush({
      data: [
        {
          mftgId: 101,
          mftgFotografer: 'Budi',
          mftgEventName: 'Semarang Heritage Walk',
          mftgPhotoLocation: 'Semarang',
          mftgUrl: 'http://example.com/a1.jpg',
          mftgClientId: clientA,
          msevId: 1,
          msevName: 'Semarang Heritage Walk'
        },
        {
          mftgId: 102,
          mftgFotografer: 'Budi',
          mftgEventName: 'Semarang Heritage Walk',
          mftgPhotoLocation: 'Semarang',
          mftgUrl: 'http://example.com/a2.jpg',
          mftgClientId: clientA,
          msevId: 1,
          msevName: 'Semarang Heritage Walk'
        }
      ],
      pagination: { page: 1, itemPerPage: 20, totalPage: 1, data: 2, totalData: 2 },
      status: { error: 0, errorCode: 0, message: '' }
    });

    httpMock.expectOne((req) =>
      req.url === `/event/photo/${clientA}/PLN%20Industry%20Visit` &&
      req.params.get('sorts[mftgId]') === 'desc'
    ).flush({
      data: [
        {
          mftgId: 201,
          mftgFotografer: 'Rina',
          mftgEventName: 'PLN Industry Visit',
          mftgPhotoLocation: 'Jakarta',
          mftgUrl: 'http://example.com/a3.jpg',
          mftgClientId: clientA,
          msevId: 2,
          msevName: 'PLN Industry Visit'
        }
      ],
      pagination: { page: 1, itemPerPage: 20, totalPage: 1, data: 1, totalData: 1 },
      status: { error: 0, errorCode: 0, message: '' }
    });

    expect(latestImages.length).toBe(3);
    expect(latestImages.every((img: any) => img.clientId === clientA)).toBeTrue();
    expect(latestImages[0].url).toBe('http://example.com/a1.jpg');
    expect(latestImages[1].url).toBe('http://example.com/a2.jpg');
    expect(latestImages[2].url).toBe('http://example.com/a3.jpg');
  });

  it('sends search and location query params for photo API', () => {
    service.getImages(clientA, { search: 'meo', location: 'test' }).subscribe((images) => {
      expect(images.length).toBe(1);
      expect(images[0].url).toBe('http://example.com/filtered.jpg');
    });

    const listReq = httpMock.expectOne(`/event/${clientA}`);
    listReq.flush({
      data: [
        { msevId: 2, msevName: 'PLN Industry Visit', msevCreatedTime: '2026-04-05 08:00:00', msevUpdatedTime: '2026-04-05 08:00:00' }
      ],
      pagination: { page: 1, itemPerPage: 20, totalPage: 1, data: 1, totalData: 1 },
      status: { error: 0, errorCode: 0, message: '' }
    });

    httpMock.expectOne(`/event/${clientA}/PLN%20Industry%20Visit`).flush({
      data: {
        msevId: 2,
        msevName: 'PLN Industry Visit',
        msevCreatedTime: '',
        msevUpdatedTime: '',
        locations: []
      },
      status: { error: 0, errorCode: 0, message: '' }
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

    httpMock.expectOne(`/event/${clientA}/Semarang%20Heritage%20Walk`).flush({
      data: {
        msevId: 1,
        msevName: 'Semarang Heritage Walk',
        msevCreatedTime: '',
        msevUpdatedTime: '',
        locations: []
      },
      status: { error: 0, errorCode: 0, message: '' }
    });

    httpMock.expectOne(`/event/${clientA}/PLN%20Industry%20Visit`).flush({
      data: {
        msevId: 2,
        msevName: 'PLN Industry Visit',
        msevCreatedTime: '',
        msevUpdatedTime: '',
        locations: []
      },
      status: { error: 0, errorCode: 0, message: '' }
    });
  });
});
