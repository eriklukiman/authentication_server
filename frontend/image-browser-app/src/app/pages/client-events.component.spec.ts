import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { of } from 'rxjs';
import { ClientEventsComponent } from './client-events.component';
import { ImageService } from '../services/image.service';
import { EventItem } from '../services/image.service';

const mockEvents: EventItem[] = [
  {
    id: 1,
    clientId: 'client-a',
    name: 'Semarang Heritage Walk',
    date: '2026-03-10',
    coverImageUrl: 'https://example.com/cover1.jpg',
    photoCount: 6
  },
  {
    id: 2,
    clientId: 'client-a',
    name: 'PLN Industry Visit',
    date: '2026-04-05',
    coverImageUrl: 'https://example.com/cover2.jpg',
    photoCount: 5
  }
];

describe('ClientEventsComponent', () => {
  let fixture: ComponentFixture<ClientEventsComponent>;
  let component: ClientEventsComponent;
  let eventsForTest: EventItem[];

  beforeEach(async () => {
    eventsForTest = [...mockEvents];

    await TestBed.configureTestingModule({
      imports: [ClientEventsComponent],
      providers: [
        {
          provide: ImageService,
          useValue: {
            getEvents: () => of(eventsForTest)
          }
        },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: convertToParamMap({ client_id: 'client-a' })
            }
          }
        }
      ]
    }).compileComponents();
  });

  it('loads events for route client id', () => {
    fixture = TestBed.createComponent(ClientEventsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();

    expect(component.clientId).toBe('client-a');
    expect(component.events().length).toBe(2);
    expect(component.loading()).toBeFalse();
    expect(fixture.nativeElement.querySelectorAll('.event-card').length).toBe(2);
  });

  it('shows empty message when no events are available', () => {
    eventsForTest = [];

    fixture = TestBed.createComponent(ClientEventsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();

    const empty = fixture.nativeElement.querySelector('.empty') as HTMLElement;
    expect(empty).not.toBeNull();
    expect(empty.textContent).toContain('No events found for this client.');
  });
});
