@php
    $rawWa = setting('whatsapp_number', '+91 9211946606');
    $cleanWa = preg_replace('/[^0-9]/', '', $rawWa);
    if (! $cleanWa) {
        $cleanWa = '919211946606';
    }
    $waUrl = 'https://wa.me/' . $cleanWa . '?text=' . urlencode('Hi Vanriti, I would like to inquire about your products.');
@endphp

<a href="{{ $waUrl }}"
   class="vr-whatsapp-fab"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="Chat with Vanriti on WhatsApp"
   id="vrWhatsAppFab"
   title="Chat on WhatsApp (+91 9211946606)">
    <div class="vr-whatsapp-fab-pulse"></div>
    <div class="vr-whatsapp-fab-icon">
        <i class="ri-whatsapp-fill"></i>
    </div>
    <span class="vr-whatsapp-fab-label">Chat with Us</span>
</a>
