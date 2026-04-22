import { ComponentFixture, TestBed } from '@angular/core/testing';
import { SimpleChange } from '@angular/core';
import { of } from 'rxjs';
import { ImageGridComponent } from './image-grid.component';
import { ImageItem, ImageService } from '../../services/image.service';

class MockImageService {
  getImages(clientId: string) {
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

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ImageGridComponent],
      providers: [{ provide: ImageService, useClass: MockImageService }]
    }).compileComponents();

    fixture = TestBed.createComponent(ImageGridComponent);
    component = fixture.componentInstance;
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

  it('marks image loaded and classifies orientation', () => {
    const img = document.createElement('img');
    Object.defineProperty(img, 'naturalWidth', { configurable: true, value: 1600 });
    Object.defineProperty(img, 'naturalHeight', { configurable: true, value: 800 });

    component.onImageLoad({ target: img } as unknown as Event, mockImages[0]);
    expect(component.loadedSet.has(mockImages[0].id)).toBeTrue();
    expect(component.orientationMap[mockImages[0].id]).toBe('landscape');

    Object.defineProperty(img, 'naturalWidth', { configurable: true, value: 800 });
    Object.defineProperty(img, 'naturalHeight', { configurable: true, value: 1600 });
    component.onImageLoad({ target: img } as unknown as Event, mockImages[1]);
    expect(component.orientationMap[mockImages[1].id]).toBe('portrait');

    Object.defineProperty(img, 'naturalWidth', { configurable: true, value: 1000 });
    Object.defineProperty(img, 'naturalHeight', { configurable: true, value: 1000 });
    component.onImageLoad({ target: img } as unknown as Event, mockImages[2]);
    expect(component.orientationMap[mockImages[2].id]).toBe('square');
  });

  it('defaults to landscape class before orientation is known', () => {
    expect(component.getOrientationClass(mockImages[0])).toBe('landscape');
  });

  it('opens and closes lightbox while managing body scroll', () => {
    component.openLightbox(mockImages[0]);
    expect(component.selectedImage).toEqual(mockImages[0]);
    expect(document.body.style.overflow).toBe('hidden');

    component.closeLightbox();
    expect(component.selectedImage).toBeNull();
    expect(document.body.style.overflow).toBe('');
  });

  it('shows spinner before image load and hides it after load', () => {
    component.images = [mockImages[0]];
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.spinner')).not.toBeNull();

    component.loadedSet.add(mockImages[0].id);
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.spinner')).toBeNull();
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
    component.visibleCount = 40;

    component.search = 'rita';
    component.ngOnChanges({
      search: new SimpleChange('', 'rita', false)
    });

    expect(component.visibleCount).toBe(component.pageSize);
  });
});
