import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { By } from '@angular/platform-browser';
import { of } from 'rxjs';
import { BrowseComponent } from './browse.component';
import { ImageService } from '../services/image.service';
import { ImageGridComponent } from '../components/image-grid/image-grid.component';

class MockImageService {
  getImages() {
    return of([
      {
        id: 1,
        clientId: 'client-a',
        eventId: 1,
        eventName: 'Semarang Heritage Walk',
        location: 'Semarang',
        photographer: 'Budi Santoso',
        url: 'https://example.com/1.jpg',
        alt: 'one'
      },
      {
        id: 2,
        clientId: 'client-a',
        eventId: 2,
        eventName: 'PLN Industry Visit',
        location: 'Jakarta',
        photographer: 'Rina Wijaya',
        url: 'https://example.com/2.jpg',
        alt: 'two'
      },
      {
        id: 3,
        clientId: 'client-a',
        eventId: 1,
        eventName: 'Semarang Heritage Walk',
        location: 'Semarang',
        photographer: 'Budi Santoso',
        url: 'https://example.com/3.jpg',
        alt: 'three'
      }
    ]);
  }
}

describe('BrowseComponent', () => {
  let fixture: ComponentFixture<BrowseComponent>;
  let component: BrowseComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BrowseComponent],
      providers: [
        { provide: ImageService, useClass: MockImageService },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: convertToParamMap({ client_id: 'client-a' }),
              queryParamMap: convertToParamMap({})
            }
          }
        }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(BrowseComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('initializes route values and deduplicated filter options', () => {
    expect(component.clientId).toBe('client-a');
    expect(component.locations).toEqual(['Semarang', 'Jakarta']);
  });

  it('passes selected search and location filters to app-image-grid', () => {
    component.search = 'rina';
    component.selectLocation('Jakarta');
    fixture.detectChanges();

    const gridDebug = fixture.debugElement.query(By.directive(ImageGridComponent));
    const gridComponent = gridDebug.componentInstance as ImageGridComponent;

    expect(gridComponent.clientId).toBe('client-a');
    expect(gridComponent.search).toBe('rina');
    expect(gridComponent.location).toBe('Jakarta');
    expect(gridComponent.eventFilter).toBe('');
  });

  it('renders location badges and toggles active badge on click', () => {
    fixture.detectChanges();

    const badges = fixture.nativeElement.querySelectorAll('.location-badge');
    expect(badges.length).toBe(3);
    expect(component.selectedLocation).toBe('');

    (badges[2] as HTMLButtonElement).click();
    fixture.detectChanges();

    expect(component.selectedLocation).toBe('Jakarta');
    expect((badges[2] as HTMLElement).classList.contains('active')).toBeTrue();
  });

  it('debounces search input for 300ms before applying filter', fakeAsync(() => {
    component.onSearchChange('BIB123');
    expect(component.search).toBe('');

    tick(299);
    expect(component.search).toBe('');

    tick(1);
    expect(component.search).toBe('BIB123');
  }));
});
