<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Libro;
use App\Models\Usuario;
use App\Models\Prestamo;
use Carbon\Carbon;

/**
 * PrestamoController Feature Tests
 *
 * Applying Kent Beck's TDD + Ian Sommerville's systematic testing
 * Testing loan workflow, business rules, and state calculations
 */
class PrestamoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $testLibro;
    protected $testUsuario;

    /**
     * Setup test environment with sample data
     * Following systematic testing approach
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test libro (available for lending)
        $this->testLibro = Libro::create([
            'titulo' => 'Test Book for Lending',
            'autor' => 'Test Author',
            'isbn' => '978-0123456789',
            'año_publicacion' => 2023,
            'genero' => 'Fiction',
            'numero_paginas' => 300,
            'disponible' => true
        ]);

        // Create test usuario
        $this->testUsuario = Usuario::create([
            'nombre' => 'Test User',
            'email' => 'test@example.com',
            'telefono' => '1234567890'
        ]);
    }

    /**
     * Test: Should return all loans when listing loans
     * Verifies GET /api/prestamos endpoint basic functionality
     */
    public function test_should_return_all_loans_when_listing_loans()
    {
        // Arrange: Create test loans
        Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now()->subDays(5),
            'fecha_devolucion_esperada' => now()->addDays(9),
            'devuelto' => false
        ]);

        Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now()->subDays(20),
            'fecha_devolucion_esperada' => now()->subDays(6),
            'fecha_devolucion_real' => now()->subDays(3),
            'devuelto' => true
        ]);

        // Act: Make GET request
        $response = $this->getJson('/api/prestamos');

        // Assert: Verify response structure and data
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'libro_id',
                            'usuario_id',
                            'fecha_prestamo',
                            'fecha_devolucion_esperada',
                            'fecha_devolucion_real',
                            'devuelto',
                            'dias_restantes',
                            'estado',
                            'libro',
                            'usuario'
                        ]
                    ]
                ])
                ->assertJsonCount(2, 'data');
    }

    /**
     * Test: Should create loan when valid data provided
     * Verifies POST /api/prestamos with business rule compliance
     */
    public function test_should_create_loan_when_valid_data_provided()
    {
        // Arrange: Prepare valid loan data
        $loanData = [
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_devolucion_esperada' => now()->addDays(14)->format('Y-m-d')
        ];

        // Act: Make POST request
        $response = $this->postJson('/api/prestamos', $loanData);

        // Assert: Verify creation success
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'libro_id',
                        'usuario_id',
                        'fecha_prestamo',
                        'fecha_devolucion_esperada',
                        'devuelto',
                        'dias_restantes',
                        'estado',
                        'libro',
                        'usuario'
                    ]
                ]);

        // Verify loan exists in database
        $this->assertDatabaseHas('prestamos', [
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'devuelto' => false
        ]);

        // Verify book marked as unavailable
        $this->testLibro->refresh();
        $this->assertFalse($this->testLibro->disponible);
    }

    /**
     * Test: Should reject loan when book already loaned
     * Verifies business rule: one active loan per book
     */
    public function test_should_reject_loan_when_book_already_loaned()
    {
        // Arrange: Create existing active loan
        Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now(),
            'fecha_devolucion_esperada' => now()->addDays(14),
            'devuelto' => false
        ]);

        // Mark book as unavailable
        $this->testLibro->update(['disponible' => false]);

        $newUser = Usuario::create([
            'nombre' => 'Second User',
            'email' => 'second@example.com',
            'telefono' => '9876543210'
        ]);

        // Act: Attempt second loan
        $response = $this->postJson('/api/prestamos', [
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $newUser->id,
            'fecha_devolucion_esperada' => now()->addDays(14)->format('Y-m-d')
        ]);

        // Assert: Verify rejection
        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'El libro no está disponible para préstamo'
                ]);
    }

    /**
     * Test: Should reject loan when user has overdue loans
     * Verifies business rule: no new loans with overdue books
     */
    public function test_should_reject_loan_when_user_has_overdue_loans()
    {
        // Arrange: Create second book for new loan attempt
        $secondBook = Libro::create([
            'titulo' => 'Second Test Book',
            'autor' => 'Test Author 2',
            'isbn' => '978-0987654321',
            'año_publicacion' => 2022,
            'genero' => 'Non-Fiction',
            'numero_paginas' => 250,
            'disponible' => true
        ]);

        // Create overdue loan for user
        Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now()->subDays(20),
            'fecha_devolucion_esperada' => now()->subDays(5), // Overdue
            'devuelto' => false
        ]);

        // Act: Attempt new loan with overdue books
        $response = $this->postJson('/api/prestamos', [
            'libro_id' => $secondBook->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_devolucion_esperada' => now()->addDays(14)->format('Y-m-d')
        ]);

        // Assert: Verify rejection due to overdue loans
        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'El usuario tiene préstamos vencidos. Debe devolverlos antes de solicitar nuevos préstamos'
                ]);
    }

    /**
     * Test: Should mark loan as returned when valid return requested
     * Verifies PUT /api/prestamos/{id}/devolver functionality
     */
    public function test_should_mark_loan_as_returned_when_valid_return_requested()
    {
        // Arrange: Create active loan
        $prestamo = Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now()->subDays(7),
            'fecha_devolucion_esperada' => now()->addDays(7),
            'devuelto' => false
        ]);

        // Mark book as unavailable
        $this->testLibro->update(['disponible' => false]);

        // Act: Mark as returned
        $response = $this->putJson("/api/prestamos/{$prestamo->id}/devolver");

        // Assert: Verify return success
        $response->assertStatus(200)
                ->assertJsonPath('data.devuelto', true)
                ->assertJsonPath('data.fecha_devolucion_real', now()->format('Y-m-d'));

        // Verify database updated
        $prestamo->refresh();
        $this->assertTrue($prestamo->devuelto);
        $this->assertNotNull($prestamo->fecha_devolucion_real);

        // Verify book marked as available
        $this->testLibro->refresh();
        $this->assertTrue($this->testLibro->disponible);
    }

    /**
     * Test: Should calculate correct loan state for active loan
     * Verifies state calculation logic (activo, vencido, critico)
     */
    public function test_should_calculate_correct_loan_state_for_active_loan()
    {
        // Arrange: Create loan due in 3 days (should be "activo")
        $prestamo = Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now()->subDays(11),
            'fecha_devolucion_esperada' => now()->addDays(3),
            'devuelto' => false
        ]);

        // Act: Get loan details
        $response = $this->getJson("/api/prestamos/{$prestamo->id}");

        // Assert: Verify state calculation
        $response->assertStatus(200)
                ->assertJsonPath('data.estado', 'activo')
                ->assertJsonPath('data.dias_restantes', 3);
    }

    /**
     * Test: Should calculate correct loan state for overdue loan
     * Verifies state calculation for vencido status
     */
    public function test_should_calculate_correct_loan_state_for_overdue_loan()
    {
        // Arrange: Create overdue loan (due 2 days ago)
        $prestamo = Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now()->subDays(16),
            'fecha_devolucion_esperada' => now()->subDays(2),
            'devuelto' => false
        ]);

        // Act: Get loan details
        $response = $this->getJson("/api/prestamos/{$prestamo->id}");

        // Assert: Verify overdue state
        $response->assertStatus(200)
                ->assertJsonPath('data.estado', 'vencido')
                ->assertJsonPath('data.dias_restantes', -2);
    }

    /**
     * Test: Should calculate correct loan state for critical loan
     * Verifies state calculation for critico status (due today or tomorrow)
     */
    public function test_should_calculate_correct_loan_state_for_critical_loan()
    {
        // Arrange: Create loan due tomorrow (should be "critico")
        $prestamo = Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now()->subDays(13),
            'fecha_devolucion_esperada' => now()->addDay(),
            'devuelto' => false
        ]);

        // Act: Get loan details
        $response = $this->getJson("/api/prestamos/{$prestamo->id}");

        // Assert: Verify critical state
        $response->assertStatus(200)
                ->assertJsonPath('data.estado', 'critico')
                ->assertJsonPath('data.dias_restantes', 1);
    }

    /**
     * Test: Should prevent return of already returned loan
     * Verifies business rule: cannot return twice
     */
    public function test_should_prevent_return_of_already_returned_loan()
    {
        // Arrange: Create already returned loan
        $prestamo = Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now()->subDays(20),
            'fecha_devolucion_esperada' => now()->subDays(6),
            'fecha_devolucion_real' => now()->subDays(3),
            'devuelto' => true
        ]);

        // Act: Attempt to return again
        $response = $this->putJson("/api/prestamos/{$prestamo->id}/devolver");

        // Assert: Verify error
        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Este préstamo ya ha sido devuelto'
                ]);
    }

    /**
     * Test: Should include libro and usuario relationships
     * Verifies eager loading of related models
     */
    public function test_should_include_libro_and_usuario_relationships()
    {
        // Arrange: Create loan
        $prestamo = Prestamo::create([
            'libro_id' => $this->testLibro->id,
            'usuario_id' => $this->testUsuario->id,
            'fecha_prestamo' => now(),
            'fecha_devolucion_esperada' => now()->addDays(14),
            'devuelto' => false
        ]);

        // Act: Get loan with relationships
        $response = $this->getJson("/api/prestamos/{$prestamo->id}");

        // Assert: Verify relationships included
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'libro' => [
                            'id',
                            'titulo',
                            'autor',
                            'isbn'
                        ],
                        'usuario' => [
                            'id',
                            'nombre',
                            'email'
                        ]
                    ]
                ]);
    }

    /**
     * Test: Should validate required fields for loan creation
     * Verifies validation rules enforcement
     */
    public function test_should_validate_required_fields_for_loan_creation()
    {
        // Arrange: Invalid data (missing required fields)
        $invalidData = [
            'usuario_id' => $this->testUsuario->id
            // Missing libro_id and fecha_devolucion_esperada
        ];

        // Act: Make POST request with invalid data
        $response = $this->postJson('/api/prestamos', $invalidData);

        // Assert: Verify validation errors
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['libro_id', 'fecha_devolucion_esperada']);
    }
}
