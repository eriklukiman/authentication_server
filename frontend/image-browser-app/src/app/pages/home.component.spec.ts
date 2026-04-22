import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HomeComponent } from './home.component';

describe('HomeComponent', () => {
  let fixture: ComponentFixture<HomeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HomeComponent]
    }).compileComponents();

    fixture = TestBed.createComponent(HomeComponent);
    fixture.detectChanges();
  });

  it('renders default empty landing message', () => {
    const text = fixture.nativeElement.textContent as string;
    expect(text).toContain('Image Browser');
    expect(text).toContain('This page is intentionally empty.');
  });
});
