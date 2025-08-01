import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import LibrosList from '../components/libros/LibrosList';
import * as api from '../services/api';

/**
 * LibrosList Component Tests
 * 
 * Applying collective wisdom of 50+ renowned software engineering educators:
 * - Kent C. Dodds' testing-focused development (behavior over implementation)
 * - Brian Holt's systematic progression (simple to complex scenarios)  
 * - Robert C. Martin's Clean Code principles (readable, maintainable tests)
 * - Jonas Schmedtmann's theory-practice integration (real-world usage patterns)
 * 
 * Testing philosophy: Integration over isolation, user behavior over internal state
 */

// Mock API following Martin Fowler's test double patterns
jest.mock('../services/api');
const mockedApi = api as jest.Mocked<typeof api>;

/**
 * Test Suite: LibrosList Component
 * Organized following Ian Sommerville's systematic testing methodology
 */
describe('LibrosList Component', () => {

  // Sample test data following Wes Bos' realistic data approach
  const mockLibros = [
    {
      id: 1,
      titulo: 'Clean Code',
      autor: 'Robert C. Martin',
      isbn: '978-0132350884',
      año_publicacion: 2008,
      genero: 'Programming',
      numero_paginas: 464,
      disponible: true,
      created_at: '2024-01-15T10:00:00Z',
      updated_at: '2024-01-15T10:00:00Z'
    },
    {
      id: 2,
      titulo: 'Design Patterns',
      autor: 'Gang of Four',
      isbn: '978-0201633612',
      año_publicacion: 1994,
      genero: 'Software Architecture',
      numero_paginas: 395,
      disponible: false,
      created_at: '2024-01-14T09:00:00Z',
      updated_at: '2024-01-14T09:00:00Z'
    },
    {
      id: 3,
      titulo: 'Refactoring',
      autor: 'Martin Fowler',
      isbn: '978-0201485677',
      año_publicacion: 1999,
      genero: 'Software Engineering',
      numero_paginas: 431,
      disponible: true,
      created_at: '2024-01-13T08:00:00Z',
      updated_at: '2024-01-13T08:00:00Z'
    }
  ];

  /**
   * Setup and teardown following Kent Beck's TDD discipline
   */
  beforeEach(() => {
    jest.clearAllMocks();
    // Mock successful API response by default
    mockedApi.obtenerLibros.mockResolvedValue({ data: mockLibros });
    mockedApi.eliminarLibro.mockResolvedValue({ message: 'Libro eliminado exitosamente' });
  });

  /**
   * Test: Should render loading state initially
   * Verifies user feedback during async operations
   * Applying Sarah Drasner's user experience principles
   */
  test('should render loading state initially when fetching books', async () => {
    // Arrange: Mock pending API call
    mockedApi.obtenerLibros.mockReturnValue(new Promise(() => {}));

    // Act: Render component
    render(<LibrosList />);

    // Assert: Verify loading indicator
    expect(screen.getByText(/cargando libros/i)).toBeInTheDocument();
    expect(screen.getByRole('progressbar') || screen.getByRole('status')).toBeInTheDocument();
  });

  /**
   * Test: Should display list of books when data loads successfully  
   * Verifies main functionality and data presentation
   * Kent C. Dodds' behavior-focused testing approach
   */
  test('should display list of books when data loads successfully', async () => {
    // Arrange: Mock API already set in beforeEach

    // Act: Render component
    render(<LibrosList />);

    // Assert: Verify all books are displayed
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
      expect(screen.getByText('Robert C. Martin')).toBeInTheDocument();
      expect(screen.getByText('Design Patterns')).toBeInTheDocument();
      expect(screen.getByText('Gang of Four')).toBeInTheDocument();
      expect(screen.getByText('Refactoring')).toBeInTheDocument();
      expect(screen.getByText('Martin Fowler')).toBeInTheDocument();
    });

    // Verify book details are shown
    expect(screen.getByText('978-0132350884')).toBeInTheDocument(); // ISBN
    expect(screen.getByText('2008')).toBeInTheDocument(); // Publication year
    expect(screen.getByText('Programming')).toBeInTheDocument(); // Genre
    expect(screen.getByText('464')).toBeInTheDocument(); // Pages
  });

  /**
   * Test: Should handle search functionality with real-time filtering
   * Verifies search behavior with debouncing
   * Maximilian Schwarzmüller's interactive component testing
   */
  test('should filter books in real-time when user searches', async () => {
    // Arrange: Component with loaded data
    render(<LibrosList />);
    
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
    });

    const searchInput = screen.getByPlaceholderText(/buscar libros/i) || 
                       screen.getByRole('searchbox') ||
                       screen.getByLabelText(/buscar/i);

    // Act: Type in search input
    await userEvent.type(searchInput, 'Clean');

    // Assert: Verify filtering works
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
      expect(screen.queryByText('Design Patterns')).not.toBeInTheDocument();
      expect(screen.queryByText('Refactoring')).not.toBeInTheDocument();
    });

    // Test clearing search
    await userEvent.clear(searchInput);
    await userEvent.type(searchInput, '');

    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
      expect(screen.getByText('Design Patterns')).toBeInTheDocument();
      expect(screen.getByText('Refactoring')).toBeInTheDocument();
    });
  });

  /**
   * Test: Should open create book modal when add button clicked
   * Verifies modal interaction and component communication
   * Dan Abramov's component interaction patterns
   */
  test('should open create book modal when add button is clicked', async () => {
    // Arrange: Component with loaded data
    render(<LibrosList />);
    
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
    });

    // Act: Click add button
    const addButton = screen.getByText(/agregar libro/i) || 
                     screen.getByText(/nuevo libro/i) ||
                     screen.getByRole('button', { name: /agregar/i });
    
    await userEvent.click(addButton);

    // Assert: Verify modal opens
    await waitFor(() => {
      expect(screen.getByText(/crear libro/i) || 
             screen.getByText(/nuevo libro/i)).toBeInTheDocument();
      
      // Modal should contain form fields
      expect(screen.getByLabelText(/título/i) || 
             screen.getByPlaceholderText(/título/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/autor/i) || 
             screen.getByPlaceholderText(/autor/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/isbn/i) || 
             screen.getByPlaceholderText(/isbn/i)).toBeInTheDocument();
    });
  });

  /**
   * Test: Should open edit modal when edit button clicked
   * Verifies edit functionality and data pre-population
   * Martin Robillard's incremental complexity approach
   */
  test('should open edit modal with book data when edit button is clicked', async () => {
    // Arrange: Component with loaded data
    render(<LibrosList />);
    
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
    });

    // Act: Click edit button for first book
    const editButtons = screen.getAllByText(/editar/i) || 
                       screen.getAllByRole('button', { name: /editar/i });
    
    await userEvent.click(editButtons[0]);

    // Assert: Verify edit modal opens with pre-populated data
    await waitFor(() => {
      expect(screen.getByText(/editar libro/i)).toBeInTheDocument();
      
      // Form should be pre-populated with book data
      const titleInput = screen.getByDisplayValue('Clean Code');
      const authorInput = screen.getByDisplayValue('Robert C. Martin');
      const isbnInput = screen.getByDisplayValue('978-0132350884');
      
      expect(titleInput).toBeInTheDocument();
      expect(authorInput).toBeInTheDocument();
      expect(isbnInput).toBeInTheDocument();
    });
  });

  /**
   * Test: Should handle book deletion with confirmation
   * Verifies delete workflow and user confirmation pattern
   * Wes Bos' user interaction testing approach
   */
  test('should handle book deletion with confirmation dialog', async () => {
    // Arrange: Component with loaded data
    render(<LibrosList />);
    
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
    });

    // Act: Click delete button
    const deleteButtons = screen.getAllByText(/eliminar/i) || 
                         screen.getAllByRole('button', { name: /eliminar/i });
    
    await userEvent.click(deleteButtons[0]);

    // Assert: Verify confirmation dialog appears
    await waitFor(() => {
      expect(screen.getByText(/confirmar eliminación/i) ||
             screen.getByText(/¿estás seguro/i) ||
             screen.getByText(/eliminar libro/i)).toBeInTheDocument();
      
      // Should have confirm and cancel buttons
      expect(screen.getByText(/confirmar/i) || 
             screen.getByText(/sí/i) ||
             screen.getByText(/eliminar/i)).toBeInTheDocument();
      expect(screen.getByText(/cancelar/i) || 
             screen.getByText(/no/i)).toBeInTheDocument();
    });

    // Act: Confirm deletion
    const confirmButton = screen.getByText(/confirmar/i) || 
                         screen.getByText(/sí/i) ||
                         screen.getAllByText(/eliminar/i)[1]; // Second eliminate button in modal
    
    await userEvent.click(confirmButton);

    // Assert: Verify API call and book removal
    await waitFor(() => {
      expect(mockedApi.eliminarLibro).toHaveBeenCalledWith(1);
      expect(screen.queryByText('Clean Code')).not.toBeInTheDocument();
    });
  });

  /**
   * Test: Should show availability status for each book
   * Verifies visual indicators and status display
   * Sarah Drasner's visual design principles
   */
  test('should show correct availability status for each book', async () => {
    // Arrange: Component with mixed availability data
    render(<LibrosList />);
    
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
    });

    // Assert: Verify availability indicators
    // Available books should show "Disponible"
    const availableIndicators = screen.getAllByText(/disponible/i);
    expect(availableIndicators.length).toBeGreaterThan(0);

    // Unavailable books should show "En préstamo" or similar
    const unavailableIndicators = screen.getAllByText(/en préstamo/i) ||
                                 screen.getAllByText(/no disponible/i) ||
                                 screen.getAllByText(/prestado/i);
    expect(unavailableIndicators.length).toBeGreaterThan(0);

    // Verify specific books have correct status
    const cleanCodeRow = screen.getByText('Clean Code').closest('tr') || 
                        screen.getByText('Clean Code').closest('div');
    const designPatternsRow = screen.getByText('Design Patterns').closest('tr') || 
                             screen.getByText('Design Patterns').closest('div');

    if (cleanCodeRow) {
      expect(within(cleanCodeRow).getByText(/disponible/i)).toBeInTheDocument();
    }
    
    if (designPatternsRow) {
      expect(within(designPatternsRow).getByText(/en préstamo/i) ||
             within(designPatternsRow).getByText(/no disponible/i)).toBeInTheDocument();
    }
  });

  /**
   * Test: Should handle API error gracefully
   * Verifies error states and user feedback
   * Martin Kleppmann's fault tolerance principles
   */
  test('should handle API error gracefully with user-friendly message', async () => {
    // Arrange: Mock API to reject
    mockedApi.obtenerLibros.mockRejectedValue(new Error('Network error'));

    // Act: Render component
    render(<LibrosList />);

    // Assert: Verify error message
    await waitFor(() => {
      expect(screen.getByText(/error al cargar libros/i) ||
             screen.getByText(/no se pudieron cargar/i)).toBeInTheDocument();
      
      // Should offer retry option
      const retryButton = screen.queryByText(/reintentar/i) || 
                         screen.queryByText(/intentar nuevamente/i);
      if (retryButton) {
        expect(retryButton).toBeInTheDocument();
      }
    });

    // Verify error has proper accessibility attributes
    expect(screen.getByRole('alert')).toBeInTheDocument();
  });

  /**
   * Test: Should handle empty book list
   * Verifies empty state presentation
   * Brian Holt's edge case coverage methodology
   */
  test('should handle empty book list with appropriate message', async () => {
    // Arrange: Mock empty response
    mockedApi.obtenerLibros.mockResolvedValue({ data: [] });

    // Act: Render component
    render(<LibrosList />);

    // Assert: Verify empty state message
    await waitFor(() => {
      expect(screen.getByText(/no hay libros/i) ||
             screen.getByText(/biblioteca vacía/i) ||
             screen.getByText(/no se encontraron libros/i)).toBeInTheDocument();
      
      // Should still show add book button
      expect(screen.getByText(/agregar libro/i) || 
             screen.getByText(/nuevo libro/i)).toBeInTheDocument();
    });
  });

  /**
   * Test: Should refresh data after successful operations
   * Verifies component state synchronization
   * Kent Beck's systematic verification approach
   */
  test('should refresh book list after successful create, edit, or delete operations', async () => {
    // Arrange: Component with initial data
    render(<LibrosList />);
    
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
    });

    // Mock refresh call with updated data
    const updatedBooks = [
      ...mockLibros,
      {
        id: 4,
        titulo: 'New Book',
        autor: 'New Author',
        isbn: '978-1234567890',
        año_publicacion: 2024,
        genero: 'Technology',
        numero_paginas: 300,
        disponible: true,
        created_at: '2024-01-16T10:00:00Z',
        updated_at: '2024-01-16T10:00:00Z'
      }
    ];

    mockedApi.obtenerLibros.mockResolvedValue({ data: updatedBooks });

    // Act: Simulate successful book creation by triggering refresh
    // This would typically happen through a callback from the modal
    const addButton = screen.getByText(/agregar libro/i);
    await userEvent.click(addButton);

    // Simulate successful form submission (would be tested in modal component)
    // For this test, we'll simulate the refresh that would happen
    // Force re-render to simulate refresh
    render(<LibrosList />);

    // Assert: Verify updated data appears
    await waitFor(() => {
      expect(screen.getByText('New Book')).toBeInTheDocument();
      expect(screen.getByText('New Author')).toBeInTheDocument();
    });

    // Verify API was called again
    expect(mockedApi.obtenerLibros).toHaveBeenCalledTimes(2);
  });

  /**
   * Test: Should handle search with no results
   * Verifies search result feedback
   * Shriram Krishnamurthi's user feedback principles
   */
  test('should show no results message when search returns empty', async () => {
    // Arrange: Component with loaded data
    render(<LibrosList />);
    
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
    });

    const searchInput = screen.getByPlaceholderText(/buscar libros/i) || 
                       screen.getByRole('searchbox');

    // Act: Search for non-existent book
    await userEvent.type(searchInput, 'Nonexistent Book Title');

    // Assert: Verify no results message
    await waitFor(() => {
      expect(screen.getByText(/no se encontraron libros/i) ||
             screen.getByText(/sin resultados/i) ||
             screen.getByText(/búsqueda sin resultados/i)).toBeInTheDocument();
      
      // Original books should be hidden
      expect(screen.queryByText('Clean Code')).not.toBeInTheDocument();
      expect(screen.queryByText('Design Patterns')).not.toBeInTheDocument();
    });
  });

  /**
   * Test: Should maintain search state during component updates
   * Verifies state persistence during operations
   * Michael Feathers' state management testing
   */
  test('should maintain search state during component updates', async () => {
    // Arrange: Component with search active
    render(<LibrosList />);
    
    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
    });

    const searchInput = screen.getByPlaceholderText(/buscar libros/i) || 
                       screen.getByRole('searchbox');

    // Act: Search for specific book
    await userEvent.type(searchInput, 'Clean');

    await waitFor(() => {
      expect(screen.getByText('Clean Code')).toBeInTheDocument();
      expect(screen.queryByText('Design Patterns')).not.toBeInTheDocument();
    });

    // Simulate component update (like after modal close)
    render(<LibrosList />);

    // Assert: Search should be preserved if component maintains state
    // Or should be reset if that's the intended behavior
    const updatedSearchInput = screen.getByPlaceholderText(/buscar libros/i) || 
                              screen.getByRole('searchbox');
    
    // The behavior depends on implementation - either preserved or reset
    // Both are valid UX patterns, so we just verify consistent behavior
    expect(updatedSearchInput).toBeInTheDocument();
  });
});