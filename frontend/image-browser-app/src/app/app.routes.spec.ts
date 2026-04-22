import { routes } from './app.routes';
import { HomeComponent } from './pages/home.component';
import { ClientEventsComponent } from './pages/client-events.component';
import { BrowseComponent } from './pages/browse.component';

describe('app routes', () => {
  it('defines expected route paths and components', () => {
    expect(routes.length).toBe(3);

    expect(routes[0].path).toBe('');
    expect(routes[0].component).toBe(HomeComponent);

    expect(routes[1].path).toBe(':client_id');
    expect(routes[1].component).toBe(ClientEventsComponent);

    expect(routes[2].path).toBe(':client_id/browse');
    expect(routes[2].component).toBe(BrowseComponent);
  });
});
