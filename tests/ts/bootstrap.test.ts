import { describe, it, expect, beforeEach, afterEach } from '@jest/globals';
import axios from 'axios';

interface CustomWindow extends Window {
  axios?: any;
  Echo?: any;
  Pusher?: any;
}

declare const window: CustomWindow;

// Mock the global window object before importing bootstrap
describe('Bootstrap Configuration', () => {
  let originalWindow: any;

  beforeEach(() => {
    // Save original window
    originalWindow = global.window;

    // Setup window for testing
    global.window = {
      axios: undefined,
    } as any;
  });

  afterEach(() => {
    // Restore original window
    global.window = originalWindow;
  });

  describe('Axios Configuration', () => {
    it('should load axios into window object', () => {
      // Import bootstrap which should set window.axios
      require('../../resources/js/bootstrap');

      expect(window.axios).toBeDefined();
      expect(window.axios).toBe(axios);
    });

    it('should configure default headers for X-Requested-With', () => {
      require('../../resources/js/bootstrap');

      expect(window.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest');
    });

    it('should have axios instance with proper configuration', () => {
      require('../../resources/js/bootstrap');

      expect(window.axios.interceptors).toBeDefined();
      expect(typeof window.axios.get).toBe('function');
      expect(typeof window.axios.post).toBe('function');
      expect(typeof window.axios.put).toBe('function');
      expect(typeof window.axios.delete).toBe('function');
    });
  });

  describe('Laravel Echo (Disabled)', () => {
    it('should not load Echo when commented out', () => {
      require('../../resources/js/bootstrap');

      // Echo should not be in window when it's commented out in bootstrap.js
      expect(window.Echo).toBeUndefined();
      expect(window.Pusher).toBeUndefined();
    });
  });
});
