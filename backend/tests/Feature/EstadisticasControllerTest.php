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
 * EstadisticasController Feature Tests
 *
 * Applying Kent Beck's TDD + Ian Sommerville's systematic validation
 * Testing dashboard metrics, JSON structure, and calculated values
 */
class EstadisticasControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup test environment with comprehensive sample data
     * Following systematic testing approach with realistic scenarios
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test books with different availability states
        $this->libroDisponible = Libro::create([
            'titulo' => 'Available Book',
            'autor' => 'Test Author 1',
            'isbn' => '978-0123456789',
            'año_publicacion' => 2023,
            'genero' => 'Fiction',
            'numero_paginas' => 300,
            'disponible' => true
        ]);

        $this->libroEnPrestamo = Libro::create([
            'titulo' => 'Loaned Book',
            'autor' => 'Test Author 2',
            'isbn' => '978-0987654321',
            'año_publicacion' => 2022,
            'genero' => 'Non-Fiction',
            'numero_paginas' => 250,
            'disponible' => false
        ]);

        $this->libroDevuelto = Libro::create([
            'titulo' => 'Returned Book',
            'autor' => 'Test Author 3',
            'isbn' => '978-0456789123',
            'año_publicacion' => 2021,
            'genero' => 'Science',
            'numero_paginas' => 400,
            'disponible' => true
        ]);

        // Create test users
        $this->usuarioActivo = Usuario::create([
            'nombre' => 'Active User',
            'email' => 'active@example.com',
            'telefono' => '1234567890'
        ]);

        $this->usuarioVencido = Usuario::create([
            'nombre' => 'Overdue User',
            'email' => 'overdue@example.com',
            'telefono' => '9876543210'
        ]);
    }

    /**
     * Test: Should return complete dashboard statistics structure
     * Verifies GET /api/estadisticas endpoint basic functionality
     */
    public function test_should_return_complete_dashboard_statistics_structure()
    {
        // Arrange: Create comprehensive test scenario
        $this->createComprehensiveTestScenario();

        // Act: Make GET request to statistics endpoint
        $response = $this->getJson('/api/estadisticas');

        // Assert: Verify complete JSON structure
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'libros' => [
                            'total',
                            'disponibles',
                            'en_prestamo'
                        ],
                        'usuarios' => [
                            'total',
                            'con_prestamos_activos',
                            'con_prestamos_vencidos'
                        ],
                        'prestamos' => [
                            'total',
                            'activos',
                            'vencidos',
                            'criticos',
                            'completados'
                        ],
                        'actividad_reciente' => [
                            'prestamos_ultima_semana',
                            'devoluciones_ultima_semana'
                        ]
                    ]
                ]);
    }

    /**
     * Test: Should calculate correct book statistics
     * Verifies book counting logic and availability states
     */
    public function test_should_calculate_correct_book_statistics()
    {
        // Arrange: Create specific book scenario
        $this->createComprehensiveTestScenario();

        // Act: Get statistics
        $response = $this->getJson('/api/estadisticas');

        // Assert: Verify book statistics accuracy
        $response->assertStatus(200)
                ->assertJsonPath('data.libros.total', 3)
                ->assertJsonPath('data.libros.disponibles', 2)
                ->assertJsonPath('data.libros.en_prestamo', 1);
    }

    /**
     * Test: Should calculate correct user statistics
     * Verifies user counting with active and overdue loan states
     */
    public function test_should_calculate_correct_user_statistics()
    {
        // Arrange: Create scenario with different user states
        $this->createComprehensiveTestScenario();

        // Act: Get statistics
        $response = $this->getJson('/api/estadisticas');

        // Assert: Verify user statistics accuracy
        $response->assertStatus(200)
                ->assertJsonPath('data.usuarios.total', 2)
                ->assertJsonPath('data.usuarios.con_prestamos_activos', 1)
                ->assertJsonPath('data.usuarios.con_prestamos_vencidos', 1);
    }

    /**
     * Test: Should calculate correct loan statistics with all states
     * Verifies loan state calculations (activo, vencido, critico, completado)
     */
    public function test_should_calculate_correct_loan_statistics_with_all_states()
    {
        // Arrange: Create loans in different states
        $this->createComprehensiveTestScenario();

        // Act: Get statistics
        $response = $this->getJson('/api/estadisticas');

        // Assert: Verify loan statistics accuracy
        $response->assertStatus(200)
                ->assertJsonPath('data.prestamos.total', 4)
                ->assertJsonPath('data.prestamos.activos', 1)
                ->assertJsonPath('data.prestamos.vencidos', 1)
                ->assertJsonPath('data.prestamos.criticos', 1)
                ->assertJsonPath('data.prestamos.completados', 1);
    }

    /**
     * Test: Should calculate correct recent activity metrics
     * Verifies time-based activity calculations
     */
    public function test_should_calculate_correct_recent_activity_metrics()
    {
        // Arrange: Create recent activity scenario
        $this->createRecentActivityScenario();

        // Act: Get statistics
        $response = $this->getJson('/api/estadisticas');

        // Assert: Verify recent activity accuracy
        $response->assertStatus(200)
                ->assertJsonPath('data.actividad_reciente.prestamos_ultima_semana', 2)
                ->assertJsonPath('data.actividad_reciente.devoluciones_ultima_semana', 1);
    }

    /**
     * Test: Should return zero statistics when no data exists
     * Verifies edge case with empty database
     */
    public function test_should_return_zero_statistics_when_no_data_exists()
    {
        // Arrange: Empty database (no additional setup)

        // Act: Get statistics
        $response = $this->getJson('/api/estadisticas');

        // Assert: Verify zero values
        $response->assertStatus(200)
                ->assertJsonPath('data.libros.total', 0)
                ->assertJsonPath('data.libros.disponibles', 0)
                ->assertJsonPath('data.libros.en_prestamo', 0)
                ->assertJsonPath('data.usuarios.total', 0)
                ->assertJsonPath('data.usuarios.con_prestamos_activos', 0)
                ->assertJsonPath('data.usuarios.con_prestamos_vencidos', 0)
                ->assertJsonPath('data.prestamos.total', 0)
                ->assertJsonPath('data.prestamos.activos', 0)
                ->assertJsonPath('data.prestamos.vencidos', 0)
                ->assertJsonPath('data.prestamos.criticos', 0)
                ->assertJsonPath('data.prestamos.completados', 0);
    }

    /**
     * Test: Should handle mixed loan states correctly
     * Verifies complex scenarios with multiple users and loan states
     */
    public function test_should_handle_mixed_loan_states_correctly()
    {
        // Arrange: Create complex mixed scenario
        $libro1 = $this->libroDisponible;
        $libro2 = $this->libroEnPrestamo;
        $libro3 = $this->libroDevuelto;

        // Create another user for testing
        $usuario3 = Usuario::create([
            'nombre' => 'Third User',
            'email' => 'third@example.com',
            'telefono' => '5555555555'
        ]);

        // Active loan (normal)
        Prestamo::create([
            'libro_id' => $libro1->id,
            'usuario_id' => $this->usuarioActivo->id,
            'fecha_prestamo' => now()->subDays(5),
            'fecha_devolucion_esperada' => now()->addDays(9),
            'devuelto' => false
        ]);

        // Critical loan (due tomorrow)
        Prestamo::create([
            'libro_id' => $libro2->id,
            'usuario_id' => $usuario3->id,
            'fecha_prestamo' => now()->subDays(13),
            'fecha_devolucion_esperada' => now()->addDay(),
            'devuelto' => false
        ]);

        // Overdue loan
        Prestamo::create([
            'libro_id' => $libro3->id,
            'usuario_id' => $this->usuarioVencido->id,
            'fecha_prestamo' => now()->subDays(20),
            'fecha_devolucion_esperada' => now()->subDays(3),
            'devuelto' => false
        ]);

        // Act: Get statistics
        $response = $this->getJson('/api/estadisticas');

        // Assert: Verify mixed states handled correctly
        $response->assertStatus(200)
                ->assertJsonPath('data.prestamos.activos', 1)
                ->assertJsonPath('data.prestamos.criticos', 1)
                ->assertJsonPath('data.prestamos.vencidos', 1)
                ->assertJsonPath('data.usuarios.con_prestamos_activos', 2) // Active + Critical
                ->assertJsonPath('data.usuarios.con_prestamos_vencidos', 1);
    }

    /**
     * Test: Should calculate percentage metrics correctly
     * Verifies ratio calculations for dashboard display
     */
    public function test_should_calculate_percentage_metrics_correctly()
    {
        // Arrange: Create scenario with known ratios
        $this->createComprehensiveTestScenario();

        // Act: Get statistics
        $response = $this->getJson('/api/estadisticas');

        // Assert: Verify calculated values make sense
        $data = $response->json('data');

        // Books: 2 available out of 3 total (66.7%)
        $this->assertEquals(3, $data['libros']['total']);
        $this->assertEquals(2, $data['libros']['disponibles']);
        $this->assertEquals(1, $data['libros']['en_prestamo']);

        // Verify availability percentage logic
        $availabilityRate = ($data['libros']['disponibles'] / $data['libros']['total']) * 100;
        $this->assertGreaterThan(50, $availabilityRate);
    }

    /**
     * Test: Should return consistent JSON format
     * Verifies API response format stability
     */
    public function test_should_return_consistent_json_format()
    {
        // Arrange: Multiple requests with same data
        $this->createComprehensiveTestScenario();

        // Act: Make multiple requests
        $response1 = $this->getJson('/api/estadisticas');
        $response2 = $this->getJson('/api/estadisticas');

        // Assert: Verify consistent format and values
        $this->assertEquals(
            $response1->json('data'),
            $response2->json('data')
        );

        // Verify both responses have same structure
        $response1->assertJsonStructure([
            'data' => [
                'libros' => ['total', 'disponibles', 'en_prestamo'],
                'usuarios' => ['total', 'con_prestamos_activos', 'con_prestamos_vencidos'],
                'prestamos' => ['total', 'activos', 'vencidos', 'criticos', 'completados'],
                'actividad_reciente' => ['prestamos_ultima_semana', 'devoluciones_ultima_semana']
            ]
        ]);

        $response2->assertJsonStructure([
            'data' => [
                'libros' => ['total', 'disponibles', 'en_prestamo'],
                'usuarios' => ['total', 'con_prestamos_activos', 'con_prestamos_vencidos'],
                'prestamos' => ['total', 'activos', 'vencidos', 'criticos', 'completados'],
                'actividad_reciente' => ['prestamos_ultima_semana', 'devoluciones_ultima_semana']
            ]
        ]);
    }

    /**
     * Test: Should handle edge case with same-day activities
     * Verifies accurate date-based filtering
     */
    public function test_should_handle_edge_case_with_same_day_activities()
    {
        // Arrange: Create activities on exact boundary dates
        $exactlyOneWeekAgo = now()->subDays(7);
        $yesterdayPrestamo = Prestamo::create([
            'libro_id' => $this->libroDisponible->id,
            'usuario_id' => $this->usuarioActivo->id,
            'fecha_prestamo' => $exactlyOneWeekAgo,
            'fecha_devolucion_esperada' => now()->addDays(7),
            'devuelto' => false
        ]);

        // Act: Get statistics
        $response = $this->getJson('/api/estadisticas');

        // Assert: Verify edge case handling
        $response->assertStatus(200);

        // Should include the loan from exactly 7 days ago
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, $data['actividad_reciente']['prestamos_ultima_semana']);
    }

    /**
     * Helper method: Create comprehensive test scenario
     * Sets up complex data for thorough statistics testing
     */
    private function createComprehensiveTestScenario()
    {
        // Active loan (normal state)
        Prestamo::create([
            'libro_id' => $this->libroEnPrestamo->id,
            'usuario_id' => $this->usuarioActivo->id,
            'fecha_prestamo' => now()->subDays(5),
            'fecha_devolucion_esperada' => now()->addDays(9),
            'devuelto' => false
        ]);

        // Critical loan (due tomorrow)
        Prestamo::create([
            'libro_id' => $this->libroDisponible->id,
            'usuario_id' => $this->usuarioActivo->id,
            'fecha_prestamo' => now()->subDays(13),
            'fecha_devolucion_esperada' => now()->addDay(),
            'devuelto' => false
        ]);

        // Overdue loan
        Prestamo::create([
            'libro_id' => $this->libroDevuelto->id,
            'usuario_id' => $this->usuarioVencido->id,
            'fecha_prestamo' => now()->subDays(20),
            'fecha_devolucion_esperada' => now()->subDays(3),
            'devuelto' => false
        ]);

        // Completed loan
        Prestamo::create([
            'libro_id' => $this->libroDevuelto->id,
            'usuario_id' => $this->usuarioActivo->id,
            'fecha_prestamo' => now()->subDays(30),
            'fecha_devolucion_esperada' => now()->subDays(16),
            'fecha_devolucion_real' => now()->subDays(18),
            'devuelto' => true
        ]);

        // Update book availability based on active loans
        $this->libroEnPrestamo->update(['disponible' => false]);
        $this->libroDisponible->update(['disponible' => false]); // Critical loan
    }

    /**
     * Helper method: Create recent activity scenario
     * Sets up time-based test data for activity metrics
     */
    private function createRecentActivityScenario()
    {
        // Loan from 3 days ago (within last week)
        Prestamo::create([
            'libro_id' => $this->libroDisponible->id,
            'usuario_id' => $this->usuarioActivo->id,
            'fecha_prestamo' => now()->subDays(3),
            'fecha_devolucion_esperada' => now()->addDays(11),
            'devuelto' => false
        ]);

        // Loan from 6 days ago (within last week)
        Prestamo::create([
            'libro_id' => $this->libroEnPrestamo->id,
            'usuario_id' => $this->usuarioVencido->id,
            'fecha_prestamo' => now()->subDays(6),
            'fecha_devolucion_esperada' => now()->addDays(8),
            'devuelto' => false
        ]);

        // Return from 2 days ago (within last week)
        Prestamo::create([
            'libro_id' => $this->libroDevuelto->id,
            'usuario_id' => $this->usuarioActivo->id,
            'fecha_prestamo' => now()->subDays(15),
            'fecha_devolucion_esperada' => now()->subDays(1),
            'fecha_devolucion_real' => now()->subDays(2),
            'devuelto' => true
        ]);

        // Old loan (outside last week)
        Prestamo::create([
            'libro_id' => $this->libroDevuelto->id,
            'usuario_id' => $this->usuarioVencido->id,
            'fecha_prestamo' => now()->subDays(10),
            'fecha_devolucion_esperada' => now()->subDays(4),
            'devuelto' => false
        ]);
    }
}
