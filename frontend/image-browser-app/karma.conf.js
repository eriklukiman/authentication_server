// Karma configuration for Angular unit tests.
module.exports = function (config) {
  function ChecklistReporter(baseReporterDecorator) {
    baseReporterDecorator(this);

    let lastSuiteTitle = '';

    this.onSpecComplete = function (_, result) {
      const suiteTitle = (result.suite && result.suite.length > 0) ? result.suite[0] : 'Tests';

      if (suiteTitle !== lastSuiteTitle) {
        this.writeCommonMsg(`\nTest ${suiteTitle}\n`);
        lastSuiteTitle = suiteTitle;
      }

      const icon = result.success ? '✅' : '❌';
      this.writeCommonMsg(`${icon} ${result.description}\n`);

      if (!result.success && result.log && result.log.length > 0) {
        result.log.forEach((line) => this.writeCommonMsg(`  ↳ ${line}\n`));
      }
    };
  }

  ChecklistReporter.$inject = ['baseReporterDecorator'];

  config.set({
    basePath: '',
    frameworks: ['jasmine'],
    plugins: [
      require('karma-jasmine'),
      require('karma-chrome-launcher'),
      require('karma-jasmine-html-reporter'),
      require('karma-coverage'),
      {
        'reporter:checklist': ['type', ChecklistReporter]
      }
    ],
    client: {
      jasmine: {
        random: false
      },
      clearContext: false
    },
    jasmineHtmlReporter: {
      suppressAll: true
    },
    coverageReporter: {
      dir: require('path').join(__dirname, './coverage/image-browser-app'),
      subdir: '.',
      reporters: [{ type: 'html' }, { type: 'text-summary' }]
    },
    reporters: ['checklist'],
    browsers: ['ChromeHeadless'],
    restartOnFileChange: true,
    singleRun: false
  });
};
