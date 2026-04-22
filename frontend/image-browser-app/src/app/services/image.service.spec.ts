import { TestBed } from '@angular/core/testing';
import { ImageService } from './image.service';

describe('ImageService', () => {
  let service: ImageService;

  const clientA = '3e62298e298bc8e6215ba38e9c60e0620ac0c081';
  const clientB = '42cd6d6b808ebf1d159d85a2354178315d265a12';

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(ImageService);
  });

  it('returns events filtered by client id', () => {
    service.getEvents(clientA).subscribe((events) => {
      expect(events.length).toBe(3);
      expect(events.every((e) => e.clientId === clientA)).toBeTrue();
    });

    service.getEvents(clientB).subscribe((events) => {
      expect(events.length).toBe(2);
      expect(events.every((e) => e.clientId === clientB)).toBeTrue();
    });
  });

  it('returns images filtered by client id', () => {
    service.getImages(clientA).subscribe((images) => {
      expect(images.length).toBe(31);
      expect(images.every((img) => img.clientId === clientA)).toBeTrue();
    });

    service.getImages(clientB).subscribe((images) => {
      expect(images.length).toBe(19);
      expect(images.every((img) => img.clientId === clientB)).toBeTrue();
    });
  });

  it('returns unique event names for a client', () => {
    service.getEventNames(clientA).subscribe((names) => {
      expect(names.length).toBe(3);
      expect(new Set(names).size).toBe(names.length);
      expect(names).toContain('Semarang Heritage Walk');
      expect(names).toContain('PLN Industry Visit');
      expect(names).toContain('Mountain Expedition');
    });
  });
});
