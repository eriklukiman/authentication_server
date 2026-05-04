import { ComponentFixture, TestBed } from '@angular/core/testing';
import { SimpleChange } from '@angular/core';
import { LightboxComponent } from './lightbox.component';
import { ImageItem } from '../../services/image.service';

describe('LightboxComponent', () => {
  let fixture: ComponentFixture<LightboxComponent>;
  let component: LightboxComponent;

  const image: ImageItem = {
    id: 1,
    clientId: 'c1',
    eventId: 10,
    eventName: 'Event Name',
    location: 'Semarang',
    photographer: 'Photographer',
    url: 'https://example.com/image.jpg',
    alt: 'Sample image'
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LightboxComponent]
    }).compileComponents();

    fixture = TestBed.createComponent(LightboxComponent);
    component = fixture.componentInstance;
  });

  it('resets zoom and loading state when a new image is set', () => {
    component.zoom = 2;
    component.imageLoaded = true;
    component.image = image;

    component.ngOnChanges({
      image: new SimpleChange(null, image, true)
    });

    expect(component.zoom).toBe(1);
    expect(component.imageLoaded).toBeFalse();
  });

  it('resets loading state when image becomes null', () => {
    component.imageLoaded = true;
    component.image = null;

    component.ngOnChanges({
      image: new SimpleChange(image, null, false)
    });

    expect(component.imageLoaded).toBeFalse();
  });

  it('keeps zoom within min/max bounds', () => {
    for (let i = 0; i < 40; i++) {
      component.zoomIn();
    }
    expect(component.zoom).toBe(4);

    for (let i = 0; i < 40; i++) {
      component.zoomOut();
    }
    expect(component.zoom).toBe(0.5);
    expect(component.panX).toBe(0);
    expect(component.panY).toBe(0);
  });

  it('resets pan when zoom is reset', () => {
    component.zoom = 2;
    component.panX = 50;
    component.panY = -30;

    component.resetZoom();

    expect(component.zoom).toBe(1);
    expect(component.panX).toBe(0);
    expect(component.panY).toBe(0);
  });

  it('returns translate + scale transform string', () => {
    component.zoom = 2;
    component.panX = 12;
    component.panY = -8;

    expect(component.imageTransform).toBe('translate(12px, -8px) scale(2)');
  });

  it('drags image only when zoomed and image is loaded', () => {
    component.imageLoaded = true;
    component.zoom = 1;

    component.onDragStart({ button: 0, clientX: 100, clientY: 100, preventDefault: () => {} } as unknown as PointerEvent);
    expect(component.isDragging).toBeFalse();

    component.zoom = 2;
    component.onDragStart({ button: 0, clientX: 100, clientY: 100, preventDefault: () => {} } as unknown as PointerEvent);
    expect(component.isDragging).toBeTrue();

    component.onDragMove({ clientX: 130, clientY: 120 } as PointerEvent);
    expect(component.panX).toBe(30);
    expect(component.panY).toBe(20);

    component.onDragEnd();
    expect(component.isDragging).toBeFalse();
  });

  it('ends dragging on document pointer up', () => {
    component.isDragging = true;
    component.onDocumentPointerUp();
    expect(component.isDragging).toBeFalse();
  });

  it('handles keyboard shortcuts only when image is open', () => {
    spyOn(component, 'close');
    component.image = null;
    component.onKey(new KeyboardEvent('keydown', { key: 'Escape' }));
    expect(component.close).not.toHaveBeenCalled();

    component.image = image;
    component.zoom = 1;
    component.onKey(new KeyboardEvent('keydown', { key: '+' }));
    expect(component.zoom).toBeGreaterThan(1);

    component.onKey(new KeyboardEvent('keydown', { key: '-' }));
    expect(component.zoom).toBeCloseTo(1, 5);

    component.onKey(new KeyboardEvent('keydown', { key: 'Escape' }));
    expect(component.close).toHaveBeenCalled();
  });

  it('emits closed when close is called', () => {
    spyOn(component.closed, 'emit');
    component.close();
    expect(component.closed.emit).toHaveBeenCalled();
  });

  it('opens image in a new tab when download is called', () => {
    component.image = image;

    const mockWindow = { opener: {} as unknown as Window } as Window;
    const openSpy = spyOn(window, 'open').and.returnValue(mockWindow);

    component.download();

    expect(openSpy).toHaveBeenCalledWith(image.url, '_blank', 'noopener,noreferrer');
    expect(mockWindow.opener).toBeNull();
  });

  it('updates loading state on image load and error', () => {
    component.imageLoaded = false;
    component.onImageLoad();
    expect(component.imageLoaded).toBeTrue();

    component.imageLoaded = false;
    component.onImageError();
    expect(component.imageLoaded).toBeTrue();
  });

  it('closes only when backdrop itself is clicked', () => {
    spyOn(component, 'close');

    const backdrop = document.createElement('div');
    backdrop.classList.add('lb-backdrop');
    component.onBackdropClick({ target: backdrop } as unknown as MouseEvent);
    expect(component.close).toHaveBeenCalledTimes(1);

    const inner = document.createElement('div');
    component.onBackdropClick({ target: inner } as unknown as MouseEvent);
    expect(component.close).toHaveBeenCalledTimes(1);
  });

  it('restores body overflow on destroy', () => {
    document.body.style.overflow = 'hidden';
    component.ngOnDestroy();
    expect(document.body.style.overflow).toBe('');
  });
});
