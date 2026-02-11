// Mock Vite's import.meta for non-browser environments
Object.defineProperty(globalThis, 'import', {
  value: {
    meta: {
      url: 'http://localhost/',
      env: {
        VITE_APP_NAME: 'DirectAlert',
      },
      glob: jest.fn(() => ({})),
    },
  },
  configurable: true,
});

// Mock Laravel Echo since it's commented out
jest.mock('laravel-echo', () => ({
  __esModule: true,
  default: jest.fn(),
}), { virtual: true });

// Mock Pusher
jest.mock('pusher-js', () => ({
  __esModule: true,
  default: jest.fn(),
}), { virtual: true });

// Setup jsdom globals for window object
if (typeof window !== 'undefined') {
  window.matchMedia = window.matchMedia || (() => ({
    matches: false,
    addListener: jest.fn(),
    removeListener: jest.fn(),
  }));
}
