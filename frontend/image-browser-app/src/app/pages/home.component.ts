import { Component } from '@angular/core';

@Component({
  standalone: true,
  template: `
    <main class="container">
      <h1>Image Browser</h1>
      <p>This page is intentionally empty. Use /&lt;client_id&gt; or /&lt;client_id&gt;/browse.</p>
    </main>
  `,
  styles: [
    `
      .container {
        width: 100%;
        box-sizing: border-box;
        padding: 16px;
      }
    `
  ]
})
export class HomeComponent {}
