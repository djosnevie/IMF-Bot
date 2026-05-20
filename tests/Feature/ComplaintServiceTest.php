<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ComplaintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ComplaintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComplaintService();
    }

    /**
     * Test nominal : création d'une plainte et d'un ticket.
     */
    public function test_create_from_conversation_creates_complaint_and_ticket(): void
    {
        $conversation = Conversation::create([
            'user_identifier' => '243811234567',
            'platform' => 'whatsapp',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $ticket = $this->service->createFromConversation(
            $conversation,
            'Problème de remboursement',
            'Je n\'arrive pas à rembourser mon crédit depuis 2 semaines.',
            'credit'
        );

        // Vérifier le ticket
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertNotNull($ticket->reference);
        $this->assertEquals('new', $ticket->status);
        $this->assertEquals('medium', $ticket->priority);

        // Vérifier la plainte
        $this->assertDatabaseHas('complaints', [
            'conversation_id' => $conversation->id,
            'whatsapp_number' => '243811234567',
            'subject' => 'Problème de remboursement',
            'category' => 'credit',
            'status' => 'pending',
        ]);

        // Vérifier le ticket en base
        $this->assertDatabaseHas('tickets', [
            'complaint_id' => $ticket->complaint_id,
            'reference' => $ticket->reference,
            'status' => 'new',
        ]);
    }

    /**
     * Test que la référence du ticket suit le format TKT-YYYY-XXXX.
     */
    public function test_ticket_reference_format(): void
    {
        $conversation = Conversation::create([
            'user_identifier' => '243811234567',
            'platform' => 'whatsapp',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $ticket = $this->service->createFromConversation(
            $conversation,
            'Test sujet',
            'Test description',
            'other'
        );

        $year = now()->year;
        $this->assertMatchesRegularExpression("/^TKT-{$year}-\d{4}$/", $ticket->reference);
    }

    /**
     * Test que les références sont séquentielles.
     */
    public function test_ticket_references_are_sequential(): void
    {
        $conversation = Conversation::create([
            'user_identifier' => '243811234567',
            'platform' => 'whatsapp',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $ticket1 = $this->service->createFromConversation($conversation, 'Sujet 1', 'Desc 1', 'other');
        $ticket2 = $this->service->createFromConversation($conversation, 'Sujet 2', 'Desc 2', 'other');

        $year = now()->year;
        $this->assertEquals("TKT-{$year}-0001", $ticket1->reference);
        $this->assertEquals("TKT-{$year}-0002", $ticket2->reference);
    }

    /**
     * Test de détection de catégorie : crédit.
     */
    public function test_detect_category_credit(): void
    {
        $this->assertEquals('credit', $this->service->detectCategory('Mon crédit', 'Problème de remboursement'));
        $this->assertEquals('credit', $this->service->detectCategory('Prêt personnel', 'Je veux un prêt'));
        $this->assertEquals('credit', $this->service->detectCategory('Échéance', 'Mon échéance est en retard'));
    }

    /**
     * Test de détection de catégorie : compte.
     */
    public function test_detect_category_account(): void
    {
        $this->assertEquals('account', $this->service->detectCategory('Mon compte', 'Erreur sur mon solde'));
        $this->assertEquals('account', $this->service->detectCategory('Épargne', 'Problème d\'épargne'));
        $this->assertEquals('account', $this->service->detectCategory('Virement', 'Mon virement n\'a pas été effectué'));
    }

    /**
     * Test de détection de catégorie : service.
     */
    public function test_detect_category_service(): void
    {
        $this->assertEquals('service', $this->service->detectCategory('Accueil', 'Mauvais accueil à l\'agence'));
        $this->assertEquals('service', $this->service->detectCategory('Horaire', 'L\'agence est toujours fermée'));
    }

    /**
     * Test de détection de catégorie : other (par défaut).
     */
    public function test_detect_category_other(): void
    {
        $this->assertEquals('other', $this->service->detectCategory('Divers', 'Question générale'));
        $this->assertEquals('other', $this->service->detectCategory('Bonjour', 'Je ne sais pas'));
    }

    /**
     * Test d'erreur : conversation invalide.
     */
    public function test_create_from_conversation_with_invalid_data(): void
    {
        $this->expectException(\Exception::class);

        // Créer une conversation avec un ID qui n'existe pas en base pour forcer une erreur FK
        $fakeConversation = new Conversation();
        $fakeConversation->id = 99999;
        $fakeConversation->user_identifier = '000000000';

        $this->service->createFromConversation(
            $fakeConversation,
            'Sujet',
            'Description',
            'other'
        );
    }
}
