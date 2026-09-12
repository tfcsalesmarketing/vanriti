<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_subscribe_creates_subscriber(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'fan@example.com'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('newsletters', [
            'email' => 'fan@example.com',
            'is_subscribed' => true,
        ]);
    }

    public function test_newsletter_subscribe_resubscribes_existing(): void
    {
        Newsletter::create([
            'email' => 'fan@example.com',
            'is_subscribed' => false,
            'unsubscribed_at' => now(),
        ]);

        $this->post(route('newsletter.subscribe'), ['email' => 'fan@example.com'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('newsletters', [
            'email' => 'fan@example.com',
            'is_subscribed' => true,
            'unsubscribed_at' => null,
        ]);
        $this->assertSame(1, Newsletter::where('email', 'fan@example.com')->count());
    }

    public function test_contact_store_creates_contact_message(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Sana Kapoor',
            'email' => 'sana@example.com',
            'mobile' => '9876543210',
            'subject' => 'Question about product',
            'message' => 'Can you tell me more about the ingredients of your face oil?',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'sana@example.com',
            'status' => 'new',
        ]);
    }

    public function test_contact_store_validates_required_fields(): void
    {
        $this->post(route('contact.store'), [
            'email' => 'sana@example.com',
            'message' => '',
        ])->assertSessionHasErrors(['name', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }
}
