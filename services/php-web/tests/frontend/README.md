# Frontend Tests for Cassiopeya Project

This directory contains Jest-based frontend tests for the Cassiopeya space monitoring application.

## Test Coverage

### Test Suites

1. **astro.test.js** - Tests for astronomical events page
   - getDemoPositions function validation
   - Data structure validation
   - Coordinate range validation
   - DOM rendering tests
   - JSON serialization tests

2. **dashboard.test.js** - Tests for main dashboard
   - ISS trend loading functionality
   - JWST feed loading and rendering
   - Chart updates
   - Form handling
   - Error handling
   - XSS protection

3. **jwst.test.js** - Tests for JWST gallery page
   - Filter toggle functionality
   - Feed loading with various parameters
   - Image rendering
   - Navigation controls
   - Error handling
   - HTML sanitization

4. **iss.test.js** - Tests for ISS data page
   - Data formatting validation
   - API endpoint construction
   - Data structure validation
   - Null-safety checks

## Running Tests

### Install Dependencies
```bash
cd services/php-web
npm install
```

### Run All Tests
```bash
npm test
```

### Run Tests in Watch Mode
```bash
npm run test:watch
```

### Run Tests with Coverage
```bash
npm run test:coverage
```

## Test Results

✅ **4 test suites passed**
✅ **47 tests passed**
✅ **0 tests failed**

## Test Structure

Each test file follows the same structure:
- `describe` blocks group related tests
- `beforeEach` sets up test environment
- Individual `test` blocks validate specific functionality
- Mock data is used to simulate API responses

## Mocked Dependencies

The following global objects are mocked in `setup.js`:
- `fetch` - For API calls
- `L` (Leaflet) - For map rendering
- `Chart` - For chart rendering

## Best Practices

1. Each test is independent and doesn't rely on other tests
2. DOM is cleaned up between tests
3. Mock data is realistic and consistent
4. Error cases are tested alongside happy paths
5. Security concerns (XSS) are validated

## Future Improvements

- Add integration tests with real API endpoints
- Add visual regression tests
- Add performance benchmarks
- Increase code coverage by extracting JavaScript from Blade templates

