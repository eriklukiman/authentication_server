import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home.component';
import { ClientEventsComponent } from './pages/client-events.component';
import { BrowseComponent } from './pages/browse.component';

export const routes: Routes = [
  { path: '', component: HomeComponent },
  { path: ':client_id', component: ClientEventsComponent },
  { path: ':client_id/browse', component: BrowseComponent }
];