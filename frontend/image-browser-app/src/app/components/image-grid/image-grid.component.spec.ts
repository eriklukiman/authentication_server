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

  it('loads images when client id changes', () => {
    component.clientId = 'client-a';
    component.ngOnChanges({
      clientId: new SimpleChange('', 'client-a', true)
    });

    expect(component.loading).toBeFalse();
    expect(component.images.length).toBe(2);
    expect(component.images.every((img) => img.clientId === 'client-a')).toBeTrue();
  });

  it('returns empty images when client id is empty', () => {
    component.images = [mockImages[0]];
    component.clientId = '';

    component.ngOnChanges({
      clientId: new SimpleChange('client-a', '', false)
    });

    expect(component.images).toEqual([]);
  });

  it('filters images by search, location, and event', () => {
    component.images = mockImages.filter((i) => i.clientId === 'client-a');

    component.search = 'rina';
    expect(component.filteredImages.length).toBe(1);
    expect(component.filteredImages[0].photographer).toBe('Rina Wijaya');

    component.search = '';
    component.location = 'Semarang';
    expect(component.filteredImages.length).toBe(1);
    expect(component.filteredImages[0].location).toBe('Semarang');

    component.location = '';
    component.eventFilter = 'PLN Industry Visit';
    expect(component.filteredImages.length).toBe(1);
    expect(component.filteredImages[0].eventName).toBe('PLN Industry Visit');
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
    component.loading = true;
    fixture.detectChanges();

    const status = fixture.nativeElement.querySelector('.fetch-status') as HTMLElement;
    expect(status).not.toBeNull();
    expect(status.textContent).toContain('Loading photos...');
    expect(status.querySelector('.line-spinner')).not.toBeNull();
  });

  it('paginates visible images and loads more on scroll near bottom', () => {
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

    component.images = manyImages;
    component.visibleCount = component.pageSize;

    expect(component.visibleImages.length).toBe(component.pageSize);
    expect(component.hasMore).toBeTrue();

    spyOnProperty(window, 'innerHeight', 'get').and.returnValue(1000);
    spyOnProperty(window, 'scrollY', 'get').and.returnValue(2000);
    Object.defineProperty(document.documentElement, 'scrollHeight', {
      configurable: true,
      value: 3300
    });

    component.onWindowScroll();

    expect(component.visibleImages.length).toBe(40);
    expect(component.hasMore).toBeTrue();

    component.loadMore();
    expect(component.visibleImages.length).toBe(45);
    expect(component.hasMore).toBeFalse();
  });

  it('resets pagination when filters change', () => {
    component.clientId = 'client-a';
    component.visibleCount = 40;

    component.search = 'rita';
    component.ngOnChanges({
      search: new SimpleChange('', 'rita', false)
    });

    expect(component.visibleCount).toBe(component.pageSize);
  });

  it('passes search and location as API query options', () => {
    component.clientId = 'client-a';
    component.search = 'rita';
    component.location = 'Semarang';

    component.ngOnChanges({
      clientId: new SimpleChange('', 'client-a', true),
      search: new SimpleChange('', 'rita', false),
      location: new SimpleChange('', 'Semarang', false)
    });

    expect(imageService.lastGetImagesArgs).toEqual({
      clientId: 'client-a',
      options: { search: 'rita', location: 'Semarang' }
    });
  });
});
