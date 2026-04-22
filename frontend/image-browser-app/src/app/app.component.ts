import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { Subscription, filter } from 'rxjs';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent implements OnInit, OnDestroy {
  private readonly router = inject(Router);
  private navSub?: Subscription;

  ngOnInit(): void {
    this.applyBackgroundFromCurrentUrl();

    this.navSub = this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe(() => this.applyBackgroundFromCurrentUrl());
  }

  ngOnDestroy(): void {
    this.navSub?.unsubscribe();
  }

  applyBackgroundFromQuery(bgParam: string | null): void {
    const value = bgParam?.trim();
    if (!value) {
      document.body.style.background = '';
      return;
    }

    const safe = this.sanitizeBackgroundValue(value);
    if (!safe) {
      return;
    }

    document.body.style.background = safe;
  }

  private applyBackgroundFromCurrentUrl(): void {
    const tree = this.router.parseUrl(this.router.url);
    this.applyBackgroundFromQuery(tree.queryParams['bg'] ?? null);
  }

  private sanitizeBackgroundValue(value: string): string | null {
    const isHex = /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/.test(value);
    const isNamedColor = /^[a-zA-Z]{3,30}$/.test(value);
    const isRgbOrHsl = /^(rgb|rgba|hsl|hsla)\([0-9.,%\s-]+\)$/i.test(value);
    const isGradient = /^(linear|radial)-gradient\([^;{}]+\)$/i.test(value);

    return isHex || isNamedColor || isRgbOrHsl || isGradient ? value : null;
  }
}