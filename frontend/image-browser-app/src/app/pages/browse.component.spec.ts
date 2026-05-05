import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { By } from '@angular/platform-browser';
import { of } from 'rxjs';
import { BrowseComponent } from './browse.component';
import { EventItem, ImageService } from '../services/image.service';
import { ImageGridComponent } from '../components/image-grid/image-grid.component';

class MockImageService {
  getEvents() {
    return of<EventItem[]>([
      {
        id: 1, clientId: 'client-a', name: 'Semarang Heritage Walk',
        date: '2026-03-10', coverImageUrl: '', photoCount: 6
      },
      {
        id: 2, clientId: 'client-a', name: 'PLN Industry Visit',
        date: '2026-04-05', coverImageUrl: '', photoCount: 5
      },
      {
        id: 3, clientId: 'client-a', name: 'Semarang Heritage Walk',
        date: '2026-03-10', coverImageUrl: '', photoCount: 4
      }
    ]);
  }

  getImages() {
    return of([]);
  }

  getEventNames() {
    return of([]);
  }

  getEventDetail(clientId: string, eventId: string) {
    return of({
      msevId: 1,
      msevName: 'Semarang Heritage Walk',
      msevCreatedTime: '',
      msevUpdatedTime: '',
      locations: [
        { mlocId: 1, mlocMsevId: 1, mlocName: 'Semarang', mlocCreatedClientId: clientId, mlocAppVersion: '', mlocGuiVersion: '', mlocMainVersion: '', mlocCreatedTime: '', mlocUpdatedTime: '' },
        { mlocId: 2, mlocMsevId: 2, mlocName: 'Jakarta', mlocCreatedClientId: clientId, mlocAppVersion: '', mlocGuiVersion: '', mlocMainVersion: '', mlocCreatedTime: '', mlocUpdatedTime: '' }
      ]
    });
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
