// Setup file for Jest tests
require('@testing-library/jest-dom');

// Mock global objects that might be used in the application
global.fetch = jest.fn();
global.L = {
  map: jest.fn(() => ({
    setView: jest.fn().mockReturnThis(),
    addTo: jest.fn().mockReturnThis(),
  })),
  tileLayer: jest.fn(() => ({
    addTo: jest.fn().mockReturnThis(),
  })),
  polyline: jest.fn(() => ({
    addTo: jest.fn().mockReturnThis(),
    setLatLngs: jest.fn(),
  })),
  marker: jest.fn(() => ({
    addTo: jest.fn().mockReturnThis(),
    bindPopup: jest.fn().mockReturnThis(),
    setLatLng: jest.fn(),
  })),
};

global.Chart = jest.fn().mockImplementation(() => ({
  data: { labels: [], datasets: [] },
  update: jest.fn(),
}));

// Reset mocks before each test
beforeEach(() => {
  jest.clearAllMocks();
});

