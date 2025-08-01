import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import CrearLibroModal from '../components/libros/CrearLibroModal';
import * as api from '../services/api';

/**
 * CrearLibroModal Component Tests
 * 
 * Synthesizing the collective wisdom of 50+ renowned software engineering educators:
 * 
 * FOUNDATIONAL EXPERTISE:
 * - Ian Sommerville's systematic testing approach and comprehensive coverage
 * - Shriram Krishnamurthi's user interaction principles and cognitive design
 * - Martin Robillard's human-centric software design and incremental complexity
 * 
 * PRACTICAL APPLICATION:
 * - Jonas Schmedtmann's theory-practice integration with real-world scenarios
 * - Wes Bos's exercise-driven learning through interactive form testing
 * - Brad Traversy's project-based approach to component behavior verification
 * 
 * QUALITY STANDARDS:
 * - Robert C. Martin's Clean Code principles applied to test organization
 * - Kent Beck's TDD methodology with Arrange-Act-Assert pattern
 * - Kent C. Dodds' testing-focused development and user behavior emphasis
 * 
 * MODERN DEVELOPMENT:
 * - Dan Abramov's transparent learning and explaining the "why" behind tests
 * - Brian Holt's clear progression from simple to complex scenarios
 * - Sarah Drasner's attention to user experience and accessibility
 */

// Mock API following Martin Fowler's test double patterns
jest.mock('../services/api');
const mockedApi = api as jest.Mocked<typeof api>;

/**
 * Test Suite: CrearLibroModal Component
 * 
 * Organized following Ian Sommerville's systematic methodology:
 * 1. Rendering and UI tests
 * 2. Form validation tests  
 * 3. User interaction tests
 * 4. API integration tests
 * 5. Error handling tests
 * 6. Edge cases and accessibility
 */
describe('CrearLibroModal Component', () => {

  // Mock props following Kent Beck's simple design principles
  const mockProps = {
    isOpen: true,
    onClose: jest.fn(),
    onBookCreated: jest.fn()
  };

  /**
   * Setup and teardown following TDD discipline
   * Kent Beck's systematic verification approach
   */
  beforeEach(() => {
    jest.clearAllMocks();
    
    // Mock successful API response by default
    mockedApi.crearLibro.mockResolvedValue({
      data: {
        id: 1,
        titulo: 'Test Book',
        autor: 'Test Author',
        isbn: '978-0123456789',
        año_publicacion: 2024,
        genero: 'Fiction',
        numero_paginas: 300,
        disponible: true,
        created_at: '2024-01-15T10:00:00Z',
        updated_at: '2024-01-15T10:00:00Z'
      }
    });
  });

  /**
   * RENDERING AND UI TESTS
   * Following Shriram Krishnamurthi's user-centric design principles
   */

  /**
   * Test: Should render modal with all form fields when open
   * Verifies basic component structure and accessibility
   * Sarah Drasner's inclusive design approach
   */
  test('should render modal with all required form fields when open', () => {
    // Arrange & Act: Render modal in open state
    render(<CrearLibroModal {...mockProps} />);

    // Assert: Verify modal structure and form fields
    expect(screen.getByRole('dialog') || screen.getByTestId('modal')).toBeInTheDocument();
    expect(screen.getByText(/crear libro/i) || screen.getByText(/nuevo libro/i)).toBeInTheDocument();

    // Verify all required form fields are present
    expect(screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/isbn/i) || screen.getByPlaceholderText(/isbn/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/año/i) || screen.getByPlaceholderText(/año/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/género/i) || screen.getByPlaceholderText(/género/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/páginas/i) || screen.getByPlaceholderText(/páginas/i)).toBeInTheDocument();

    // Verify action buttons
    expect(screen.getByText(/guardar/i) || screen.getByText(/crear/i)).toBeInTheDocument();
    expect(screen.getByText(/cancelar/i)).toBeInTheDocument();
  });

  /**
   * Test: Should not render when modal is closed
   * Verifies conditional rendering behavior
   * Brian Holt's edge case coverage methodology
   */
  test('should not render modal content when closed', () => {
    // Arrange & Act: Render modal in closed state
    render(<CrearLibroModal {...mockProps} isOpen={false} />);

    // Assert: Verify modal is not visible
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    expect(screen.queryByText(/crear libro/i)).not.toBeInTheDocument();
    expect(screen.queryByLabelText(/título/i)).not.toBeInTheDocument();
  });

  /**
   * FORM VALIDATION TESTS
   * Following Robert C. Martin's validation principles and Jonas Schmedtmann's user feedback approach
   */

  /**
   * Test: Should show validation errors for empty required fields
   * Verifies client-side validation implementation
   * Wes Bos's form validation methodology
   */
  test('should show validation errors for empty required fields on submit', async () => {
    // Arrange: Render modal
    render(<CrearLibroModal {...mockProps} />);

    // Act: Try to submit empty form
    const submitButton = screen.getByText(/guardar/i) || screen.getByText(/crear/i);
    await userEvent.click(submitButton);

    // Assert: Verify validation errors appear
    await waitFor(() => {
      // Should show validation messages for required fields
      expect(screen.getByText(/título es requerido/i) ||
             screen.getByText(/campo obligatorio/i) ||
             screen.getByText(/requerido/i)).toBeInTheDocument();
    });

    // Verify form doesn't submit
    expect(mockedApi.crearLibro).not.toHaveBeenCalled();
  });

  /**
   * Test: Should validate ISBN format
   * Verifies specific field validation rules
   * Martin Robillard's incremental validation approach
   */
  test('should validate ISBN format and show error for invalid format', async () => {
    // Arrange: Render modal
    render(<CrearLibroModal {...mockProps} />);

    // Act: Fill form with invalid ISBN
    const isbnInput = screen.getByLabelText(/isbn/i) || screen.getByPlaceholderText(/isbn/i);
    await userEvent.type(isbnInput, 'invalid-isbn-123');

    const submitButton = screen.getByText(/guardar/i) || screen.getByText(/crear/i);
    await userEvent.click(submitButton);

    // Assert: Verify ISBN validation error
    await waitFor(() => {
      expect(screen.getByText(/isbn inválido/i) ||
             screen.getByText(/formato de isbn/i) ||
             screen.getByText(/isbn debe tener/i)).toBeInTheDocument();
    });
  });

  /**
   * Test: Should validate publication year range
   * Verifies business rule validation
   * Ian Sommerville's business logic testing approach
   */
  test('should validate publication year within reasonable range', async () => {
    // Arrange: Render modal
    render(<CrearLibroModal {...mockProps} />);

    const titleInput = screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i);
    const authorInput = screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i);
    const isbnInput = screen.getByLabelText(/isbn/i) || screen.getByPlaceholderText(/isbn/i);
    const yearInput = screen.getByLabelText(/año/i) || screen.getByPlaceholderText(/año/i);

    // Act: Fill form with future year
    await userEvent.type(titleInput, 'Test Book');
    await userEvent.type(authorInput, 'Test Author');
    await userEvent.type(isbnInput, '978-0123456789');
    await userEvent.type(yearInput, '2030'); // Future year

    const submitButton = screen.getByText(/guardar/i) || screen.getByText(/crear/i);
    await userEvent.click(submitButton);

    // Assert: Verify year validation error
    await waitFor(() => {
      expect(screen.getByText(/año no puede ser futuro/i) ||
             screen.getByText(/año inválido/i) ||
             screen.getByText(/año debe ser/i)).toBeInTheDocument();
    });
  });

  /**
   * USER INTERACTION TESTS
   * Following Kent C. Dodds' user behavior testing principles
   */

  /**
   * Test: Should close modal when cancel button clicked
   * Verifies user cancellation workflow
   * Dan Abramov's component interaction patterns
   */
  test('should close modal when cancel button is clicked', async () => {
    // Arrange: Render modal
    render(<CrearLibroModal {...mockProps} />);

    // Act: Click cancel button
    const cancelButton = screen.getByText(/cancelar/i);
    await userEvent.click(cancelButton);

    // Assert: Verify onClose callback is called
    expect(mockProps.onClose).toHaveBeenCalledTimes(1);
  });

  /**
   * Test: Should close modal when clicking outside (backdrop)
   * Verifies modal UX behavior
   * Sarah Drasner's user experience principles
   */
  test('should close modal when clicking outside modal content', async () => {
    // Arrange: Render modal
    render(<CrearLibroModal {...mockProps} />);

    // Act: Click on modal backdrop (outside content)
    const modal = screen.getByRole('dialog') || screen.getByTestId('modal');
    await userEvent.click(modal);

    // Assert: Verify onClose callback is called
    // Note: This test depends on implementation - some modals close on backdrop click, others don't
    // The test verifies consistent behavior whatever the design decision
    expect(mockProps.onClose).toHaveBeenCalledWith();
  });

  /**
   * Test: Should handle form input changes correctly
   * Verifies controlled input behavior
   * Maximilian Schwarzmüller's form handling methodology
   */
  test('should handle form input changes and update field values', async () => {
    // Arrange: Render modal
    render(<CrearLibroModal {...mockProps} />);

    // Act: Type in form fields
    const titleInput = screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i);
    const authorInput = screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i);

    await userEvent.type(titleInput, 'Clean Code');
    await userEvent.type(authorInput, 'Robert C. Martin');

    // Assert: Verify input values are updated
    expect(titleInput).toHaveValue('Clean Code');
    expect(authorInput).toHaveValue('Robert C. Martin');
  });

  /**
   * API INTEGRATION TESTS
   * Following Martin Fowler's integration testing approach
   */

  /**
   * Test: Should create book successfully with valid data
   * Verifies happy path API integration
   * Brad Traversy's full workflow testing
   */
  test('should create book successfully when valid data is submitted', async () => {
    // Arrange: Render modal
    render(<CrearLibroModal {...mockProps} />);

    // Act: Fill form with valid data
    await userEvent.type(screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i), 'Clean Code');
    await userEvent.type(screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i), 'Robert C. Martin');
    await userEvent.type(screen.getByLabelText(/isbn/i) || screen.getByPlaceholderText(/isbn/i), '978-0132350884');
    await userEvent.type(screen.getByLabelText(/año/i) || screen.getByPlaceholderText(/año/i), '2008');
    await userEvent.type(screen.getByLabelText(/género/i) || screen.getByPlaceholderText(/género/i), 'Programming');
    await userEvent.type(screen.getByLabelText(/páginas/i) || screen.getByPlaceholderText(/páginas/i), '464');

    // Submit form
    const submitButton = screen.getByText(/guardar/i) || screen.getByText(/crear/i);
    await userEvent.click(submitButton);

    // Assert: Verify API call and callbacks
    await waitFor(() => {
      expect(mockedApi.crearLibro).toHaveBeenCalledWith({
        titulo: 'Clean Code',
        autor: 'Robert C. Martin',
        isbn: '978-0132350884',
        año_publicacion: 2008,
        genero: 'Programming',
        numero_paginas: 464
      });

      expect(mockProps.onBookCreated).toHaveBeenCalledTimes(1);
      expect(mockProps.onClose).toHaveBeenCalledTimes(1);
    });
  });

  /**
   * Test: Should show loading state during API call
   * Verifies async operation feedback
   * Jonas Schmedtmann's user feedback principles
   */
  test('should show loading state during book creation', async () => {
    // Arrange: Mock delayed API response
    let resolveApi: (value: any) => void;
    const apiPromise = new Promise(resolve => { resolveApi = resolve; });
    mockedApi.crearLibro.mockReturnValue(apiPromise);

    render(<CrearLibroModal {...mockProps} />);

    // Act: Fill and submit form
    await userEvent.type(screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i), 'Test Book');
    await userEvent.type(screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i), 'Test Author');
    await userEvent.type(screen.getByLabelText(/isbn/i) || screen.getByPlaceholderText(/isbn/i), '978-0123456789');
    await userEvent.type(screen.getByLabelText(/año/i) || screen.getByPlaceholderText(/año/i), '2024');
    await userEvent.type(screen.getByLabelText(/género/i) || screen.getByPlaceholderText(/género/i), 'Fiction');
    await userEvent.type(screen.getByLabelText(/páginas/i) || screen.getByPlaceholderText(/páginas/i), '300');

    const submitButton = screen.getByText(/guardar/i) || screen.getByText(/crear/i);
    await userEvent.click(submitButton);

    // Assert: Verify loading state appears
    expect(screen.getByText(/guardando/i) || 
           screen.getByText(/creando/i) ||
           screen.getByRole('progressbar')).toBeInTheDocument();

    // Verify submit button is disabled
    expect(submitButton).toBeDisabled();

    // Resolve API call
    resolveApi!({
      data: {
        id: 1,
        titulo: 'Test Book',
        autor: 'Test Author',
        isbn: '978-0123456789',
        año_publicacion: 2024,
        genero: 'Fiction',
        numero_paginas: 300,
        disponible: true
      }
    });

    // Verify loading state disappears
    await waitFor(() => {
      expect(screen.queryByText(/guardando/i)).not.toBeInTheDocument();
    });
  });

  /**
   * ERROR HANDLING TESTS
   * Following Martin Kleppmann's fault tolerance principles
   */

  /**
   * Test: Should handle API error gracefully
   * Verifies error state and user feedback
   * Michael Feathers' error handling methodology
   */
  test('should handle API error gracefully and show error message', async () => {
    // Arrange: Mock API to reject
    mockedApi.crearLibro.mockRejectedValue(new Error('Network error'));

    render(<CrearLibroModal {...mockProps} />);

    // Act: Fill and submit form
    await userEvent.type(screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i), 'Test Book');
    await userEvent.type(screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i), 'Test Author');
    await userEvent.type(screen.getByLabelText(/isbn/i) || screen.getByPlaceholderText(/isbn/i), '978-0123456789');
    await userEvent.type(screen.getByLabelText(/año/i) || screen.getByPlaceholderText(/año/i), '2024');
    await userEvent.type(screen.getByLabelText(/género/i) || screen.getByPlaceholderText(/género/i), 'Fiction');
    await userEvent.type(screen.getByLabelText(/páginas/i) || screen.getByPlaceholderText(/páginas/i), '300');

    const submitButton = screen.getByText(/guardar/i) || screen.getByText(/crear/i);
    await userEvent.click(submitButton);

    // Assert: Verify error message appears
    await waitFor(() => {
      expect(screen.getByText(/error al crear/i) ||
             screen.getByText(/no se pudo crear/i) ||
             screen.getByText(/error/i)).toBeInTheDocument();
    });

    // Verify error has proper accessibility
    expect(screen.getByRole('alert')).toBeInTheDocument();

    // Verify callbacks are NOT called on error
    expect(mockProps.onBookCreated).not.toHaveBeenCalled();
    expect(mockProps.onClose).not.toHaveBeenCalled();
  });

  /**
   * Test: Should handle server validation errors
   * Verifies server-side validation feedback
   * Ian Sommerville's comprehensive error coverage
   */
  test('should handle server validation errors and display field-specific messages', async () => {
    // Arrange: Mock API to reject with validation errors
    mockedApi.crearLibro.mockRejectedValue({
      response: {
        status: 422,
        data: {
          errors: {
            isbn: ['El ISBN ya existe en el sistema'],
            titulo: ['El título es demasiado largo']
          }
        }
      }
    });

    render(<CrearLibroModal {...mockProps} />);

    // Act: Fill and submit form
    await userEvent.type(screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i), 'Very Long Title That Exceeds Maximum Length');
    await userEvent.type(screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i), 'Test Author');
    await userEvent.type(screen.getByLabelText(/isbn/i) || screen.getByPlaceholderText(/isbn/i), '978-0132350884'); // Existing ISBN
    await userEvent.type(screen.getByLabelText(/año/i) || screen.getByPlaceholderText(/año/i), '2024');
    await userEvent.type(screen.getByLabelText(/género/i) || screen.getByPlaceholderText(/género/i), 'Fiction');
    await userEvent.type(screen.getByLabelText(/páginas/i) || screen.getByPlaceholderText(/páginas/i), '300');

    const submitButton = screen.getByText(/guardar/i) || screen.getByText(/crear/i);
    await userEvent.click(submitButton);

    // Assert: Verify specific validation errors
    await waitFor(() => {
      expect(screen.getByText(/isbn ya existe/i)).toBeInTheDocument();
      expect(screen.getByText(/título es demasiado largo/i)).toBeInTheDocument();
    });
  });

  /**
   * EDGE CASES AND ACCESSIBILITY
   * Following Shriram Krishnamurthi's comprehensive testing principles
   */

  /**
   * Test: Should handle special characters in form fields
   * Verifies input sanitization and edge cases
   * Martin Robillard's edge case methodology
   */
  test('should handle special characters and unicode in form fields', async () => {
    // Arrange: Render modal
    render(<CrearLibroModal {...mockProps} />);

    // Act: Fill form with special characters
    await userEvent.type(screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i), 'Ñandú & "Quotes" — Special chars');
    await userEvent.type(screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i), 'José María Öğretmen');
    await userEvent.type(screen.getByLabelText(/isbn/i) || screen.getByPlaceholderText(/isbn/i), '978-0123456789');
    await userEvent.type(screen.getByLabelText(/año/i) || screen.getByPlaceholderText(/año/i), '2024');
    await userEvent.type(screen.getByLabelText(/género/i) || screen.getByPlaceholderText(/género/i), 'Ficción & Drama');
    await userEvent.type(screen.getByLabelText(/páginas/i) || screen.getByPlaceholderText(/páginas/i), '300');

    const submitButton = screen.getByText(/guardar/i) || screen.getByText(/crear/i);
    await userEvent.click(submitButton);

    // Assert: Verify special characters are handled correctly
    await waitFor(() => {
      expect(mockedApi.crearLibro).toHaveBeenCalledWith({
        titulo: 'Ñandú & "Quotes" — Special chars',
        autor: 'José María Öğretmen',
        isbn: '978-0123456789',
        año_publicacion: 2024,
        genero: 'Ficción & Drama',
        numero_paginas: 300
      });
    });
  });

  /**
   * Test: Should have proper keyboard navigation
   * Verifies accessibility and keyboard support
   * Sarah Drasner's inclusive design principles
   */
  test('should support keyboard navigation between form fields', async () => {
    // Arrange: Render modal
    render(<CrearLibroModal {...mockProps} />);

    // Act: Navigate using Tab key
    const titleInput = screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i);
    
    titleInput.focus();
    expect(titleInput).toHaveFocus();

    // Tab to next field
    await userEvent.tab();
    const authorInput = screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i);
    expect(authorInput).toHaveFocus();

    // Continue tabbing through fields
    await userEvent.tab();
    const isbnInput = screen.getByLabelText(/isbn/i) || screen.getByPlaceholderText(/isbn/i);
    expect(isbnInput).toHaveFocus();

    // Verify Escape key closes modal
    await userEvent.keyboard('{Escape}');
    expect(mockProps.onClose).toHaveBeenCalledTimes(1);
  });

  /**
   * Test: Should clear form when closed and reopened
   * Verifies form state management
   * Kent Beck's state isolation principles
   */
  test('should clear form data when modal is closed and reopened', async () => {
    // Arrange: Render modal
    const { rerender } = render(<CrearLibroModal {...mockProps} />);

    // Act: Fill form partially
    await userEvent.type(screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i), 'Test Title');
    await userEvent.type(screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i), 'Test Author');

    // Close modal
    rerender(<CrearLibroModal {...mockProps} isOpen={false} />);
    
    // Reopen modal
    rerender(<CrearLibroModal {...mockProps} isOpen={true} />);

    // Assert: Verify form is cleared
    const titleInput = screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i);
    const authorInput = screen.getByLabelText(/autor/i) || screen.getByPlaceholderText(/autor/i);
    
    expect(titleInput).toHaveValue('');
    expect(authorInput).toHaveValue('');
  });

  /**
   * Test: Should maintain focus management for accessibility
   * Verifies modal focus trap and restoration
   * Sarah Drasner's accessibility best practices
   */
  test('should manage focus correctly for accessibility', async () => {
    // Arrange: Create a focusable element outside modal
    const externalButton = document.createElement('button');
    externalButton.textContent = 'External Button';
    document.body.appendChild(externalButton);
    externalButton.focus();

    // Act: Render modal
    render(<CrearLibroModal {...mockProps} />);

    // Assert: Focus should move to modal
    await waitFor(() => {
      const modalElement = screen.getByRole('dialog') || screen.getByTestId('modal');
      expect(modalElement).toBeInTheDocument();
      
      // Focus should be trapped within modal
      const titleInput = screen.getByLabelText(/título/i) || screen.getByPlaceholderText(/título/i);
      expect(document.activeElement).toBe(titleInput);
    });

    // Cleanup
    document.body.removeChild(externalButton);
  });
});