@php
    $isNote = $msg->isInternalNote();
    $isOut = $msg->isOutbound();
    $mediaUrl = $msg->getResolvedMediaUrl();
@endphp

@if ($isNote)
<div class="d-flex justify-content-center my-2.5 px-2" id="msg-{{ $msg->id }}">
    <div class="rounded-4 p-3 border shadow-xs w-100" style="background-color: #fffdf5; border-color: #fde68a !important; max-width: 82%;">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-1.5 pb-1 border-bottom" style="font-size: 0.72rem; color: #92400e; border-color: rgba(245, 158, 11, 0.2) !important;">
            <span class="fw-bold d-flex align-items-center gap-1.5">
                <i class="bi bi-lock-fill"></i> Internal Staff Note
            </span>
            <span class="text-muted">
                {{ $msg->sender?->name ?? 'Admin Staff' }} • {{ $msg->created_at->format('h:i A') }}
            </span>
        </div>
        <div class="text-dark small" style="white-space: pre-wrap; font-size: 0.85rem; line-height: 1.5;">{!! nl2br(e($msg->body)) !!}</div>
    </div>
</div>
@else
<div class="d-flex {{ $isOut ? 'justify-content-end' : 'justify-content-start' }} mb-2 px-1" id="msg-{{ $msg->id }}" data-message-id="{{ $msg->id }}">
    <div class="wa-bubble {{ $isOut ? 'wa-bubble-outbound' : 'wa-bubble-inbound' }} p-2.5 px-3">
        
        {{-- Image Message --}}
        @if ($msg->message_type === 'image' && $mediaUrl)
            <div class="mb-1.5 overflow-hidden rounded-3 position-relative">
                <a href="{{ $mediaUrl }}" target="_blank" title="Click to view full image" class="d-block">
                    <img src="{{ $mediaUrl }}" alt="Attachment" class="img-fluid rounded-3 w-100 shadow-2xs" style="max-height: 260px; object-fit: cover;">
                </a>
            </div>
            @if ($msg->body)
                <div class="mb-1 text-break" style="font-size: 0.88rem; line-height: 1.45;">{!! nl2br(e($msg->body)) !!}</div>
            @endif

        {{-- Document / PDF Message --}}
        @elseif ($msg->message_type === 'document')
            <div class="p-2.5 rounded-3 bg-white d-flex align-items-center gap-2.5 mb-1.5 border shadow-2xs">
                <div class="p-2 rounded-2 bg-danger-subtle text-danger flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                </div>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold small text-truncate text-dark">{{ $msg->media_filename ?: 'Document.pdf' }}</div>
                    <span class="text-muted" style="font-size: 0.7rem;">Document Attachment</span>
                </div>
                @if ($mediaUrl)
                    <a href="{{ $mediaUrl }}" target="_blank" download class="btn btn-sm btn-light border rounded-circle p-1.5 ms-auto flex-shrink-0" title="Download Document" style="width: 32px; height: 32px;">
                        <i class="bi bi-download text-dark"></i>
                    </a>
                @endif
            </div>
            @if ($msg->body)
                <div class="mb-1 text-break" style="font-size: 0.88rem; line-height: 1.45;">{!! nl2br(e($msg->body)) !!}</div>
            @endif

        {{-- Audio / Voice Note Message --}}
        @elseif ($msg->message_type === 'audio' && $mediaUrl)
            <div class="p-2 rounded-3 bg-white border mb-1.5 shadow-2xs">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-mic-fill text-success fs-5"></i>
                    <span class="small fw-semibold text-dark" style="font-size: 0.75rem;">Voice Note</span>
                </div>
                <audio controls class="w-100" style="max-width: 260px; height: 36px;">
                    <source src="{{ $mediaUrl }}" type="{{ $msg->media_mime_type ?: 'audio/ogg' }}">
                    Your browser does not support audio playback.
                </audio>
            </div>
            @if ($msg->body)
                <div class="mb-1 text-break" style="font-size: 0.88rem; line-height: 1.45;">{!! nl2br(e($msg->body)) !!}</div>
            @endif

        {{-- Interactive / Order Message Card --}}
        @elseif ($msg->message_type === 'order')
            <div class="p-2.5 rounded-3 bg-white text-dark mb-1.5 border border-success-subtle shadow-xs">
                <div class="d-flex align-items-center gap-1.5 fw-bold text-success small mb-1">
                    <i class="bi bi-cart3"></i> WhatsApp Catalogue Order
                </div>
                <div class="small text-secondary text-break">{!! nl2br(e($msg->body)) !!}</div>
            </div>

        {{-- Approved Meta Template Message --}}
        @elseif ($msg->message_type === 'template')
            @if ($msg->media_url)
                <div class="mb-1.5 overflow-hidden rounded-3">
                    <img src="{{ $msg->media_url }}" alt="Template Header" class="img-fluid rounded-3 w-100 shadow-2xs" style="max-height: 200px; object-fit: cover;">
                </div>
            @endif
            <div class="mb-1.5 d-flex align-items-center gap-1.5">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill fw-semibold" style="font-size: 0.65rem;">
                    <i class="bi bi-lightning-charge-fill me-1 text-warning"></i>{{ ucwords(str_replace('_', ' ', $msg->metadata['template_name'] ?? 'Meta Template')) }}
                </span>
            </div>
            <div class="text-break" style="font-size: 0.88rem; line-height: 1.45;">{!! nl2br(e($msg->body)) !!}</div>

        {{-- Plain Text / Interactive / Other --}}
        @else
            <div class="text-break" style="font-size: 0.88rem; line-height: 1.45;">{!! nl2br(e($msg->body)) !!}</div>
        @endif

        {{-- Footer: Timestamp & Delivery Status --}}
        <div class="d-flex align-items-center {{ $isOut ? 'justify-content-end' : 'justify-content-start' }} gap-1 mt-1 text-muted" style="font-size: 0.68rem; opacity: 0.85;">
            @if ($isOut && $msg->sender)
                <span class="fw-medium text-dark-emphasis me-0.5">{{ $msg->sender->name }} •</span>
            @elseif ($isOut && !empty($msg->metadata['is_bot']))
                <span class="badge bg-white text-success border border-success-subtle me-0.5 py-0 px-1" style="font-size: 0.6rem;">
                    <i class="bi bi-robot"></i> Bot
                </span>
            @endif
            <span>{{ $msg->created_at->format('h:i A') }}</span>

            @if ($isOut)
                <span class="message-status-tick ms-0.5">
                    @if ($msg->status === 'read')
                        <i class="bi bi-check2-all" style="color: #53bdeb; font-weight: 800;" title="Read by customer"></i>
                    @elseif ($msg->status === 'delivered')
                        <i class="bi bi-check2-all text-muted" title="Delivered to customer phone"></i>
                    @elseif ($msg->status === 'failed')
                        <i class="bi bi-exclamation-circle-fill text-danger" title="{{ $msg->error_message ?: 'Failed to send' }}"></i>
                    @else
                        <i class="bi bi-check2 text-muted" title="Sent to Meta server"></i>
                    @endif
                </span>
            @endif
        </div>
    </div>
</div>
@endif
