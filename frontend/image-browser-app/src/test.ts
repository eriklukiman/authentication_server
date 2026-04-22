import 'zone.js/testing';
import { TestBed } from '@angular/core/testing';
import { BrowserTestingModule } from '@angular/platform-browser/testing';
import { platformBrowser } from '@angular/platform-browser';

TestBed.initTestEnvironment(
  BrowserTestingModule,
  platformBrowser()
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
