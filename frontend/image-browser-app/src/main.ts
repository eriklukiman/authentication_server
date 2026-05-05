import { LOCALE_ID } from '@angular/core';
import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { AppComponent } from './app/app.component';

import { registerLocaleData } from '@angular/common';
import localeId from '@angular/common/locales/id';

registerLocaleData(localeId);

bootstrapApplication(AppComponent, { ...appConfig, providers: [{ provide: LOCALE_ID, useValue: 'id-ID' }] }).catch((err) => console.error(err));