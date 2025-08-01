<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Libro;
use App\Models\Usuario;
use App\Models\Prestamo;

/**
 * LibroController Feature Tests
 *
 * Applying Kent Beck's TDD + Robert C. Martin's Clean Code principles
 * Testing CRUD operations, validations, and business rules
 */
class LibroControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup test environment with sample data
     * Following Ian Sommerville's systematic testing approach
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test data following Arrange pattern
        $this->testLibro = [
            'titulo' => 'Test Book Title',
            'autor' => 'Test Author',
            'isbn' => '978-0123456789',
            'año_publicacion' => 2023,
            'genero' => 'Fiction',
            'numero_paginas' => 300,
            'disponible' => true
        ];

        $this->testUsuario = Usuario::create([
            'nombre' => 'Test User',
            'email' => 'test@example.com',
            'telefono' => '1234567890'
        ]);
    }

    /**
     * Test: Should return all books when listing books
     * Verifies GET /api/libros endpoint basic functionality
     */
    public function test_should_return_all_books_when_listing_books()
    {
        // Arrange: Create test books
        Libro::create($this->testLibro);
        Libro::create([
            'titulo' => 'Second Book',
            'autor' => 'Second Author',
            'isbn' => '978-0987654321',
            'año_publicacion' => 2022,
            'genero' => 'Non-Fiction',
            'numero_paginas' => 250,
            'disponible' => true
        ]);

        // Act: Make GET request
        $response = $this->getJson('/api/libros');

        // Assert: Verify response structure and data
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'titulo',
                            'autor',
                            'isbn',
                            'año_publicacion',
                            'genero',
                            'numero_paginas',
                            'disponible',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ])
                ->assertJsonCount(2, 'data');
    }

    /**
     * Test: Should create book when valid data provided
     * Verifies POST /api/libros with valid payload
     */
    public function test_should_create_book_when_valid_data_provided()
    {
        // Arrange: Prepare valid book data
        $bookData = $this->testLibro;

        // Act: Make POST request
        $response = $this->postJson('/api/libros', $bookData);

        // Assert: Verify creation success
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'titulo',
                        'autor',
                        'isbn',
                        'año_publicacion',
                        'genero',
                        'numero_paginas',
                        'disponible'
                    ]
                ]);

        // Verify book exists in database
        $this->assertDatabaseHas('libros', [
            'titulo' => 'Test Book Title',
            'autor' => 'Test Author',
            'isbn' => '978-0123456789'
        ]);
    }

    /**
     * Test: Should reject creation when required fields missing
     * Verifies validation rules enforcement
     */
    public function test_should_reject_creation_when_required_fields_missing()
    {
        // Arrange: Prepare invalid data (missing required fields)
        $invalidData = [
            'autor' => 'Test Author'
            // Missing titulo, isbn, año_publicacion, etc.
        ];

        // Act: Make POST request with invalid data
        $response = $this->postJson('/api/libros', $invalidData);

        // Assert: Verify validation error
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['titulo', 'isbn', 'año_publicacion']);
    }

    /**
     * Test: Should update book when valid data provided
     * Verifies PUT /api/libros/{id} functionality
     */
    public function test_should_update_book_when_valid_data_provided()
    {
        // Arrange: Create existing book
        $libro = Libro::create($this->testLibro);

        $updateData = [
            'titulo' => 'Updated Title',
            'autor' => 'Updated Author',
            'isbn' => $libro->isbn, // Keep same ISBN
            'año_publicacion' => 2024,
            'genero' => 'Updated Genre',
            'numero_paginas' => 400,
            'disponible' => true
        ];

        // Act: Make PUT request
        $response = $this->putJson("/api/libros/{$libro->id}", $updateData);

        // Assert: Verify update success
        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'titulo' => 'Updated Title',
                        'autor' => 'Updated Author',
                        'año_publicacion' => 2024
                    ]
                ]);

        // Verify database updated
        $this->assertDatabaseHas('libros', [
            'id' => $libro->id,
            'titulo' => 'Updated Title',
            'autor' => 'Updated Author'
        ]);
    }

    /**
     * Test: Should delete book when no active loans exist
     * Verifies business rule: only delete available books
     */
    public function test_should_delete_book_when_no_active_loans_exist()
    {
        // Arrange: Create book with no loans
        $libro = Libro::create($this->testLibro);

        // Act: Make DELETE request
        $response = $this->deleteJson("/api/libros/{$libro->id}");

        // Assert: Verify deletion success
        $response->assertStatus(200);
        $this->assertDatabaseMissing('libros', ['id' => $libro->id]);
    }

    /**
     * Test: Should prevent deletion when active loans exist
     * Verifies critical business rule enforcement
     */
    public function test_should_prevent_deletion_when_active_loans_exist()
    {
        // Arrange: Create book with active loan
        $libro = Libro::create($this->testLibro);

        Prestamo::create([
            'libro_id' => $libro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now(),
            'fecha_devolucion_esperada' => now()->addDays(14),
            'devuelto' => false
        ]);

        // Act: Attempt to delete book with active loan
        $response = $this->deleteJson("/api/libros/{$libro->id}");

        // Assert: Verify deletion prevented
        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'No se puede eliminar un libro con préstamos activos'
                ]);

        // Verify book still exists
        $this->assertDatabaseHas('libros', ['id' => $libro->id]);
    }

    /**
     * Test: Should return 404 when book not found
     * Verifies error handling for non-existent resources
     */
    public function test_should_return_404_when_book_not_found()
    {
        // Act: Request non-existent book
        $response = $this->getJson('/api/libros/999');

        // Assert: Verify 404 response
        $response->assertStatus(404);
    }

    /**
     * Test: Should validate ISBN format
     * Verifies ISBN validation rule
     */
    public function test_should_validate_isbn_format()
    {
        // Arrange: Data with invalid ISBN
        $invalidData = array_merge($this->testLibro, [
            'isbn' => 'invalid-isbn-format'
        ]);

        // Act: Make POST request
        $response = $this->postJson('/api/libros', $invalidData);

        // Assert: Verify ISBN validation error
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['isbn']);
    }

    /**
     * Test: Should validate year publication range
     * Verifies year validation business rule
     */
    public function test_should_validate_year_publication_range()
    {
        // Arrange: Data with invalid year (future year)
        $invalidData = array_merge($this->testLibro, [
            'año_publicacion' => now()->year + 5
        ]);

        // Act: Make POST request
        $response = $this->postJson('/api/libros', $invalidData);

        // Assert: Verify year validation error
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['año_publicacion']);
    }
}
