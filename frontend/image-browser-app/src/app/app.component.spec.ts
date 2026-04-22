import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { AppComponent } from './app.component';

describe('AppComponent', () => {
  let fixture: ComponentFixture<AppComponent>;
  let component: AppComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AppComponent],
      providers: [provideRouter([])]
    }).compileComponents();

    fixture = TestBed.createComponent(AppComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('creates the app shell', () => {
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('applies supported background values from query param', () => {
    component.applyBackgroundFromQuery('#f4f4f4');
    expect(document.body.style.background).toContain('244');

    component.applyBackgroundFromQuery('linear-gradient(90deg, #111 0%, #333 100%)');
    expect(document.body.style.background).toContain('gradient');
  });

  it('clears background when param is empty', () => {
    document.body.style.background = 'red';
    component.applyBackgroundFromQuery('');
    expect(document.body.style.background).toBe('');
  });

  it('ignores unsafe background values', () => {
    document.body.style.background = 'red';
    component.applyBackgroundFromQuery('url(javascript:alert(1))');
    expect(document.body.style.background).toContain('red');
  });
});
