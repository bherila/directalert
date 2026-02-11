export default {
  // Test environment
  testEnvironment: 'jsdom',
  
  // Files to test
  testMatch: [
    '**/tests/js/**/*.test.js',
    '**/tests/js/**/*.spec.js',
    '**/tests/ts/**/*.test.ts',
    '**/tests/ts/**/*.spec.ts',
    '**/tests/ts/**/*.test.tsx',
    '**/tests/ts/**/*.spec.tsx',
  ],
  
  // Module paths
  moduleDirectories: ['node_modules', 'resources/js'],
  
  // Module name mapper for aliases and clean imports
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/resources/js/$1',
  },
  
  // Transform configuration
  transform: {
    '^.+\\.jsx?$': ['babel-jest', {
      configFile: './babel.config.cjs'
    }],
    '^.+\\.tsx?$': ['ts-jest', {
      useESM: true,
    }],
  },
  
  // Ignore transforms for these patterns
  transformIgnorePatterns: [
    '/node_modules/(?!(axios))',
  ],
  
  // Setup files
  setupFilesAfterEnv: ['<rootDir>/jest.setup.js'],
  
  // Coverage configuration
  collectCoverageFrom: [
    'resources/js/**/*.{js,jsx,ts,tsx}',
    '!resources/js/bootstrap.js',
    '!**/node_modules/**',
    '!**/vendor/**',
  ],
  
  // Clear mocks between tests
  clearMocks: true,
  resetMocks: true,
  
  // Verbose output
  verbose: true,
  
  // Extensions
  moduleFileExtensions: ['js', 'jsx', 'ts', 'tsx', 'json', 'node'],
};
