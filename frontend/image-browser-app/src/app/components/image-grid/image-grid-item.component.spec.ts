import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ImageGridItemComponent } from './image-grid-item.component';
import { ImageItem } from '../../services/image.service';

const mockImage: ImageItem = {
  id: 1,
  clientId: 'client-a',
  eventId: 1,
  eventName: 'Semarang Heritage Walk',
  location: 'Semarang',
  photographer: 'Budi Santoso',
  url: 'https://example.com/1.jpg',
  alt: 'image 1'
};

describe('ImageGridItemComponent', () => {
  let fixture: ComponentFixture<ImageGridItemComponent>;
  let component: ImageGridItemComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ImageGridItemComponent]
    }).compileComponents();

    fixture = TestBed.createComponent(ImageGridItemComponent);
    component = fixture.componentInstance;
    component.image = mockImage;
    fixture.detectChanges();
  });

  it('shows spinner before image is loaded', () => {
    expect(fixture.nativeElement.querySelector('.spinner')).not.toBeNull();
  });

  it('shows no photo placeholder when image fails to load', () => {
    component.onImageError();
    fixture.detectChanges();

    const placeholder = fixture.nativeElement.querySelector('.no-photo') as HTMLElement;
    expect(placeholder).not.toBeNull();
    expect(placeholder.textContent).toContain('No photo');
    expect(fixture.nativeElement.querySelector('img')).toBeNull();
    expect(fixture.nativeElement.querySelector('.spinner')).toBeNull();
  });

  it('classifies orientation on image load', () => {
    const img = document.createElement('img');
    Object.defineProperty(img, 'naturalWidth', { configurable: true, value: 1600 });
    Object.defineProperty(img, 'naturalHeight', { configurable: true, value: 800 });

    component.onImageLoad({ target: img } as unknown as Event);
    fixture.detectChanges();

    expect(component.orientation).toBe('landscape');
  });

  it('emits opened when clicked and image is valid', () => {
    let openedImage: any = null;
    component.opened.subscribe((image) => {
      openedImage = image;
    });

    component.open();

    expect(openedImage).toEqual(mockImage);
  });

  it('does not emit opened when image has failed', () => {
    let emitCount = 0;
    component.opened.subscribe(() => {
      emitCount += 1;
    });

    component.onImageError();
    component.open();

    expect(emitCount).toBe(0);
  });
});
