import { describe, it, expect, beforeEach, afterEach } from '@jest/globals';

interface CustomWindow extends Window {
  axios?: any;
}

declare const window: CustomWindow;

describe('App Entry Point', () => {
  let originalWindow: any;

  beforeEach(() => {
    originalWindow = global.window;
    global.window = {
      axios: undefined,
    } as any;
  });

  afterEach(() => {
    global.window = originalWindow;
  });

  it('should import bootstrap module', () => {
    // Import app.js which imports bootstrap.js
    require('../../resources/js/app');

    // After importing app, bootstrap should have loaded axios into window
    expect(window.axios).toBeDefined();
  });

  it('should not throw errors during import', () => {
    expect(() => {
      require('../../resources/js/app');
    }).not.toThrow();
  });
});
