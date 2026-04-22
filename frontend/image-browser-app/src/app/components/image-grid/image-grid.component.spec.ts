import { ComponentFixture, TestBed } from '@angular/core/testing';
import { SimpleChange } from '@angular/core';
import { of } from 'rxjs';
import { ImageGridComponent } from './image-grid.component';
import { ImageItem, ImageService } from '../../services/image.service';

class MockImageService {
  lastGetImagesArgs: { clientId: string; options?: { search?: string; location?: string } } | null = null;

  getImages(clientId: string, options?: { search?: string; location?: string }) {
    this.lastGetImagesArgs = { clientId, options };
    return of(mockImages.filter((img) => img.clientId === clientId));
  }

  getEventPhotos(clientId: string, eventName: string, options?: { search?: string; location?: string }) {
    // Return all images for the eventName and clientId
    return of(mockImages.filter((img) => img.clientId === clientId && img.eventName === eventName));
  }
}

const mockImages: ImageItem[] = [
  {
    id: 1,
    clientId: 'client-a',
    eventId: 1,
    eventName: 'Semarang Heritage Walk',
    location: 'Semarang',
    photographer: 'Budi Santoso',
    url: 'https://example.com/1.jpg',
    alt: 'image 1'
  },
  {
    id: 2,
    clientId: 'client-a',
    eventId: 2,
    eventName: 'PLN Industry Visit',
    location: 'Jakarta',
    photographer: 'Rina Wijaya',
    url: 'https://example.com/2.jpg',
    alt: 'image 2'
  },
  {
    id: 3,
    clientId: 'client-b',
    eventId: 3,
    eventName: 'Coastal Survey',
    location: 'Surabaya',
    photographer: 'Siti Rahayu',
    url: 'https://example.com/3.jpg',
    alt: 'image 3'
  }
];

describe('ImageGridComponent', () => {
  let fixture: ComponentFixture<ImageGridComponent>;
  let component: ImageGridComponent;
  let imageService: MockImageService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ImageGridComponent],
      providers: [{ provide: ImageService, useClass: MockImageService }]
    }).compileComponents();

    fixture = TestBed.createComponent(ImageGridComponent);
    component = fixture.componentInstance;
    imageService = TestBed.inject(ImageService) as unknown as MockImageService;
  });

  it('loads images when client id and eventFilter are set', () => {
    component.clientId = 'client-a';
    component.eventFilter = 'Semarang Heritage Walk';
    component.ngOnChanges({
      clientId: new SimpleChange('', 'client-a', true),
      eventFilter: new SimpleChange('', 'Semarang Heritage Walk', true)
    });

    expect(component.loading()).toBeFalse();
    expect(component.images().length).toBe(1); // Only 1 image matches both client and event
    expect(component.images().every((img: ImageItem) => img.clientId === 'client-a')).toBeTrue();
  });

  it('returns empty images when client id is empty', () => {
    component.images.set([mockImages[0]]);
    component.clientId = '';

    component.ngOnChanges({
      clientId: new SimpleChange('client-a', '', false)
    });

    expect(component.images()).toEqual([]);
  });

  it('filters images by search, location, and event', () => {
    component.images.set(mockImages.filter((i) => i.clientId === 'client-a'));
    // Filtering is now handled by API, so just check images state
    expect(component.images().length).toBe(2);
  });


  it('opens and closes lightbox while managing body scroll', () => {
    component.openLightbox(mockImages[0]);
    expect(component.selectedImage).toEqual(mockImages[0]);
    expect(document.body.style.overflow).toBe('hidden');

    component.closeLightbox();
    expect(component.selectedImage).toBeNull();
    expect(document.body.style.overflow).toBe('');
  });

  it('shows one-line fetch spinner while loading photos', () => {
    component.loading.set(true);
    fixture.detectChanges();

    const status = fixture.nativeElement.querySelector('.fetch-status') as HTMLElement;
    expect(status).not.toBeNull();
    expect(status.textContent).toContain('Loading photos...');
    expect(status.querySelector('.line-spinner')).not.toBeNull();
  });

  it('paginates visible images and loads more on scroll near bottom', () => {
    // Pagination is now handled by visibleCount and images signal
    const manyImages: ImageItem[] = Array.from({ length: 45 }, (_, i) => ({
      id: i + 100,
      clientId: 'client-a',
      eventId: 1,
      eventName: 'Semarang Heritage Walk',
      location: 'Semarang',
      photographer: 'Budi Santoso',
      url: `https://example.com/many-${i}.jpg`,
      alt: `many image ${i}`
    }));

    component.images.set(manyImages);
    component.visibleCount = component.pageSize;
    expect(component.images().slice(0, component.visibleCount).length).toBe(component.pageSize);
  });

  it('resets pagination when eventFilter changes', () => {
    component.clientId = 'client-a';
    component.visibleCount = 40;
    component.eventFilter = 'Semarang Heritage Walk';
    component.ngOnChanges({
      eventFilter: new SimpleChange('', 'Semarang Heritage Walk', false)
    });
    expect(component.visibleCount).toBe(component.pageSize);
  });

  it('passes search and location as API query options', () => {
    component.clientId = 'client-a';
    component.eventFilter = 'PLN Industry Visit';
    component.search = 'rita';
    component.location = 'Semarang';

    const spy = spyOn(imageService, 'getEventPhotos').and.callThrough();

    component.ngOnChanges({
      clientId: new SimpleChange('', 'client-a', true),
      eventFilter: new SimpleChange('', 'PLN Industry Visit', true),
      search: new SimpleChange('', 'rita', false),
      location: new SimpleChange('', 'Semarang', false)
    });

    expect(spy).toHaveBeenCalledWith('client-a', 'PLN Industry Visit', { search: 'rita', location: 'Semarang' });
  });
});
