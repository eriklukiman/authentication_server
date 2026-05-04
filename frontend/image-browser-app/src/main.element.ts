import { provideHttpClient } from '@angular/common/http';
import { createCustomElement } from '@angular/elements';
import { createApplication } from '@angular/platform-browser';
import { GalleryElementComponent } from './app/elements/gallery-element.component';

async function bootstrapElement(): Promise<void> {
  const app = await createApplication({
    providers: [provideHttpClient()]
  });

  const element = createCustomElement(GalleryElementComponent, {
    injector: app.injector
  });

  const tagName = 'image-browser-gallery';
  if (!customElements.get(tagName)) {
    customElements.define(tagName, element);
  }
}

bootstrapElement().catch((error) => console.error(error));
