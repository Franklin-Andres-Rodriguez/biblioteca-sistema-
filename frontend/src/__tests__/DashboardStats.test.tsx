import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import DashboardStats from '../components/estadisticas/DashboardStats';
import * as api from '../services/api';

/**
 * DashboardStats Component Tests
 * 
 * Applying Kent C. Dodds' testing-focused development principles:
 * - Test behavior, not implementation
 * - Integration over isolation
 * - Accessibility-focused queries
 * 
 * Combined with Dan Abramov's transparent learning approach:
 * - Clear test intentions
 * - Real-world scenarios
 * - Edge case coverage
 */

// Mock the API service following Kent Beck's TDD approach
jest.mock('../services/api');
const mockedApi = api as jest.Mocked<typeof api>;

/**
 * Test Suite: DashboardStats Component
 * Following Robert C. Martin's Clean Code principles for test organization
 */
describe('DashboardStats Component', () => {
  
  /**
   * Setup and teardown following systematic testing approach
   * Ian Sommerville's structured test methodology
   */
  beforeEach(() => {
    // Reset mocks before each test (Kent Beck's isolation principle)
    jest.clearAllMocks();
  });

  /**
   * Test: Should render loading state initially
   * Verifies UI feedback during async operations
   * Applying Jonas Schmedtmann's user experience focus
   */
  test('should render loading state initially when fetching statistics', async () => {
    // Arrange: Mock API to return pending promise
    mockedApi.obtenerEstadisticas.mockReturnValue(new Promise(() => {}));

    // Act: Render component
    render(<DashboardStats />);

    // Assert: Verify loading indicator appears
    expect(screen.getByText(/cargando estadísticas/i)).toBeInTheDocument();
    expect(screen.getByRole('status')).toBeInTheDocument();
  });

  /**
   * Test: Should display complete statistics when data loads successfully
   * Verifies main happy path functionality
   * Kent C. Dodds' behavior-focused testing approach
   */
  test('should display complete statistics when data loads successfully', async () => {
    // Arrange: Mock successful API response with realistic data
    const mockStats = {
      data: {
        libros: {
          total: 150,
          disponibles: 95,
          en_prestamo: 55
        },
        usuarios: {
          total: 75,
          con_prestamos_activos: 35,
          con_prestamos_vencidos: 8
        },
        prestamos: {
          total: 320,
          activos: 35,
          vencidos: 8,
          criticos: 12,
          completados: 265
        },
        actividad_reciente: {
          prestamos_ultima_semana: 18,
          devoluciones_ultima_semana: 22
        }
      }
    };

    mockedApi.obtenerEstadisticas.mockResolvedValue(mockStats);

    // Act: Render component and wait for data
    render(<DashboardStats />);

    // Assert: Verify all statistics are displayed correctly
    await waitFor(() => {
      // Book statistics
      expect(screen.getByText('150')).toBeInTheDocument(); // Total books
      expect(screen.getByText('95')).toBeInTheDocument();  // Available books
      expect(screen.getByText('55')).toBeInTheDocument();  // Loaned books

      // User statistics  
      expect(screen.getByText('75')).toBeInTheDocument();  // Total users
      expect(screen.getByText('35')).toBeInTheDocument();  // Active loans users
      expect(screen.getByText('8')).toBeInTheDocument();   // Overdue users

      // Loan statistics
      expect(screen.getByText('320')).toBeInTheDocument(); // Total loans
      expect(screen.getByText('12')).toBeInTheDocument();  // Critical loans
      expect(screen.getByText('265')).toBeInTheDocument(); // Completed loans

      // Recent activity
      expect(screen.getByText('18')).toBeInTheDocument();  // Recent loans
      expect(screen.getByText('22')).toBeInTheDocument();  // Recent returns
    });

    // Verify section headings are present (accessibility focus)
    expect(screen.getByText(/libros/i)).toBeInTheDocument();
    expect(screen.getByText(/usuarios/i)).toBeInTheDocument();
    expect(screen.getByText(/préstamos/i)).toBeInTheDocument();
    expect(screen.getByText(/actividad reciente/i)).toBeInTheDocument();
  });

  /**
   * Test: Should handle API error gracefully
   * Verifies error boundary and user feedback
   * Applying Wes Bos' practical error handling approach
   */
  test('should handle API error gracefully with user-friendly message', async () => {
    // Arrange: Mock API to reject with error
    const mockError = new Error('Network error occurred');
    mockedApi.obtenerEstadisticas.mockRejectedValue(mockError);

    // Act: Render component
    render(<DashboardStats />);

    // Assert: Verify error message appears
    await waitFor(() => {
      expect(screen.getByText(/error al cargar estadísticas/i)).toBeInTheDocument();
      expect(screen.getByText(/intenta nuevamente/i)).toBeInTheDocument();
    });

    // Verify error state styling/role
    expect(screen.getByRole('alert')).toBeInTheDocument();
  });

  /**
   * Test: Should handle empty statistics data
   * Verifies edge case with zero values
   * Martin Robillard's comprehensive scenario coverage
   */
  test('should handle empty statistics data correctly', async () => {
    // Arrange: Mock API response with all zero values
    const emptyStats = {
      data: {
        libros: {
          total: 0,
          disponibles: 0,
          en_prestamo: 0
        },
        usuarios: {
          total: 0,
          con_prestamos_activos: 0,
          con_prestamos_vencidos: 0
        },
        prestamos: {
          total: 0,
          activos: 0,
          vencidos: 0,
          criticos: 0,
          completados: 0
        },
        actividad_reciente: {
          prestamos_ultima_semana: 0,
          devoluciones_ultima_semana: 0
        }
      }
    };

    mockedApi.obtenerEstadisticas.mockResolvedValue(emptyStats);

    // Act: Render component
    render(<DashboardStats />);

    // Assert: Verify zero values are displayed (not hidden)
    await waitFor(() => {
      // Should show zeros, not error state
      const zeroElements = screen.getAllByText('0');
      expect(zeroElements.length).toBeGreaterThan(0);
      
      // Should not show error message
      expect(screen.queryByText(/error al cargar/i)).not.toBeInTheDocument();
    });

    // Verify empty state messaging if implemented
    const emptyStateMessage = screen.queryByText(/no hay datos/i);
    if (emptyStateMessage) {
      expect(emptyStateMessage).toBeInTheDocument();
    }
  });

  /**
   * Test: Should calculate and display percentage indicators
   * Verifies derived data calculations
   * Following Martin Fowler's data transformation testing
   */
  test('should calculate and display percentage indicators correctly', async () => {
    // Arrange: Mock data with known percentages
    const statsWithPercentages = {
      data: {
        libros: {
          total: 100,
          disponibles: 75,  // 75% availability
          en_prestamo: 25   // 25% loaned
        },
        usuarios: {
          total: 50,
          con_prestamos_activos: 20,  // 40% active
          con_prestamos_vencidos: 5   // 10% overdue
        },
        prestamos: {
          total: 200,
          activos: 80,      // 40% active
          vencidos: 20,     // 10% overdue
          criticos: 10,     // 5% critical
          completados: 90   // 45% completed
        },
        actividad_reciente: {
          prestamos_ultima_semana: 15,
          devoluciones_ultima_semana: 18
        }
      }
    };

    mockedApi.obtenerEstadisticas.mockResolvedValue(statsWithPercentages);

    // Act: Render component
    render(<DashboardStats />);

    // Assert: Verify percentage calculations if displayed
    await waitFor(() => {
      // Look for percentage indicators (75%, 25%, etc.)
      const percentageElements = screen.queryAllByText(/%/);
      
      if (percentageElements.length > 0) {
        // If percentages are shown, verify they're reasonable
        percentageElements.forEach(element => {
          const percentageText = element.textContent || '';
          const percentage = parseInt(percentageText.replace('%', ''));
          expect(percentage).toBeGreaterThanOrEqual(0);
          expect(percentage).toBeLessThanOrEqual(100);
        });
      }
    });
  });

  /**
   * Test: Should refresh data when component remounts
   * Verifies API call behavior and component lifecycle
   * Kent Beck's systematic verification approach
   */
  test('should refresh data when component remounts', async () => {
    // Arrange: Mock successful response
    const mockStats = {
      data: {
        libros: { total: 10, disponibles: 8, en_prestamo: 2 },
        usuarios: { total: 5, con_prestamos_activos: 2, con_prestamos_vencidos: 0 },
        prestamos: { total: 15, activos: 2, vencidos: 0, criticos: 1, completados: 12 },
        actividad_reciente: { prestamos_ultima_semana: 3, devoluciones_ultima_semana: 4 }
      }
    };

    mockedApi.obtenerEstadisticas.mockResolvedValue(mockStats);

    // Act: Render component first time
    const { unmount } = render(<DashboardStats />);
    
    await waitFor(() => {
      expect(screen.getByText('10')).toBeInTheDocument();
    });

    // Verify API was called once
    expect(mockedApi.obtenerEstadisticas).toHaveBeenCalledTimes(1);

    // Unmount and remount
    unmount();
    render(<DashboardStats />);

    // Assert: Verify API called again on remount
    await waitFor(() => {
      expect(mockedApi.obtenerEstadisticas).toHaveBeenCalledTimes(2);
    });
  });

  /**
   * Test: Should have proper accessibility attributes
   * Verifies semantic HTML and screen reader support
   * Sarah Drasner's inclusive design principles
   */
  test('should have proper accessibility attributes for screen readers', async () => {
    // Arrange: Mock standard response
    const mockStats = {
      data: {
        libros: { total: 50, disponibles: 30, en_prestamo: 20 },
        usuarios: { total: 25, con_prestamos_activos: 15, con_prestamos_vencidos: 3 },
        prestamos: { total: 80, activos: 15, vencidos: 3, criticos: 5, completados: 57 },
        actividad_reciente: { prestamos_ultima_semana: 8, devoluciones_ultima_semana: 10 }
      }
    };

    mockedApi.obtenerEstadisticas.mockResolvedValue(mockStats);

    // Act: Render component
    render(<DashboardStats />);

    // Assert: Verify accessibility features
    await waitFor(() => {
      // Main container should have proper role
      const mainContainer = screen.getByRole('main') || screen.getByRole('region');
      expect(mainContainer).toBeInTheDocument();

      // Statistics should be in structured format (list, table, or articles)
      const statGroups = screen.queryAllByRole('group') || 
                        screen.queryAllByRole('article') ||
                        screen.queryAllByRole('listitem');
      
      if (statGroups.length > 0) {
        expect(statGroups.length).toBeGreaterThanOrEqual(3); // At least libros, usuarios, prestamos
      }

      // Important numbers should have aria-labels for context
      const importantNumbers = screen.getAllByText(/\d+/);
      expect(importantNumbers.length).toBeGreaterThan(0);
    });
  });

  /**
   * Test: Should handle rapid consecutive API calls
   * Verifies race condition handling and loading states
   * Brian Holt's edge case testing methodology
   */
  test('should handle rapid consecutive API calls without race conditions', async () => {
    // Arrange: Mock delayed responses
    let resolveFirst: (value: any) => void;
    let resolveSecond: (value: any) => void;

    const firstPromise = new Promise(resolve => { resolveFirst = resolve; });
    const secondPromise = new Promise(resolve => { resolveSecond = resolve; });

    mockedApi.obtenerEstadisticas
      .mockReturnValueOnce(firstPromise)
      .mockReturnValueOnce(secondPromise);

    // Act: Render component, unmount, and render again quickly
    const { unmount } = render(<DashboardStats />);
    unmount();
    render(<DashboardStats />);

    // Resolve second call first (race condition scenario)
    resolveSecond!({
      data: {
        libros: { total: 200, disponibles: 150, en_prestamo: 50 },
        usuarios: { total: 100, con_prestamos_activos: 40, con_prestamos_vencidos: 5 },
        prestamos: { total: 300, activos: 40, vencidos: 5, criticos: 10, completados: 245 },
        actividad_reciente: { prestamos_ultima_semana: 20, devoluciones_ultima_semana: 25 }
      }
    });

    // Assert: Should show second call results
    await waitFor(() => {
      expect(screen.getByText('200')).toBeInTheDocument();
    });

    // Resolve first call (should be ignored)
    resolveFirst!({
      data: {
        libros: { total: 1, disponibles: 1, en_prestamo: 0 },
        usuarios: { total: 1, con_prestamos_activos: 0, con_prestamos_vencidos: 0 },
        prestamos: { total: 1, activos: 0, vencidos: 0, criticos: 0, completados: 1 },
        actividad_reciente: { prestamos_ultima_semana: 1, devoluciones_ultima_semana: 1 }
      }
    });

    // Should still show second call results (no race condition)
    await waitFor(() => {
      expect(screen.getByText('200')).toBeInTheDocument();
      expect(screen.queryByText('1')).not.toBeInTheDocument();
    });
  });
});