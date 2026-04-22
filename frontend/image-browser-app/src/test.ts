import 'zone.js/testing';
import { getTestBed } from '@angular/core/testing';
import {
  BrowserDynamicTestingModule,
  platformBrowserDynamicTesting
} from '@angular/platform-browser-dynamic/testing';

getTestBed().initTestEnvironment(
  BrowserDynamicTestingModule,
  platformBrowserDynamicTesting()
);

const printedSuites = new Set<string>();

jasmine.getEnv().addReporter({
  suiteStarted: (result) => {
    if (!printedSuites.has(result.description)) {
      // Desired human-readable heading for each spec suite.
      // Example: "Test ImageService"
      console.log(`Test ${result.description}`);
      printedSuites.add(result.description);
    }
  },
  specDone: (result) => {
    const icon = result.status === 'passed' ? '✅' : '❌';
    console.log(`${icon} ${result.description}`);

    if (result.status === 'failed') {
      result.failedExpectations.forEach((f) => {
        console.log(`  ↳ ${f.message}`);
      });
    }
  }
});
