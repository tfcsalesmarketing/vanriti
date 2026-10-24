@php
    $isNote = $msg->isInternalNote();
    $isOut = $msg->isOutbound();
    $mediaUrl = $msg->getResolvedMediaUrl();
@endphp

@if ($isNote)
<div class="d-flex justify-content-center my-2.5" id="msg-{{ $msg->id }}">
    <div class="rounded-4 p-3 border shadow-xs w-100" style="background-color: #fffbeb; border-color: #fde68a !important; max-width: 82%;">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-1.5" style="font-size: 0.72rem; color: #92400e;">
            <span class="fw-bold d-flex align-items-center gap-1.5">
                <i class="bi bi-lock-fill"></i> Internal Staff Note
            </span>
            <span>
                {{ $msg->sender?->name ?? 'Admin Staff' }} • {{ $msg->created_at->format('h:i A') }}
            </span>
        </div>
        <div class="text-dark small" style="white-space: pre-wrap; font-size: 0.84rem; line-height: 1.45;">{!! nl2br(e($msg->body)) !!}</div>
    </div>
</div>
@else
<div class="d-flex {{ $isOut ? 'justify-content-end' : 'justify-content-start' }} mb-2" id="msg-{{ $msg->id }}" data-message-id="{{ $msg->id }}">
    <div class="wa-bubble {{ $isOut ? 'wa-bubble-outbound' : 'wa-bubble-inbound' }} p-2.5 px-3">
        
        {{-- Image Message --}}
        @if ($msg->message_type === 'image' && $mediaUrl)
            <div class="mb-1.5 overflow-hidden rounded-3">
                <a href="{{ $mediaUrl }}" target="_blank" title="Click to view full image">
                    <img src="{{ $mediaUrl }}" alt="Attachment" class="img-fluid rounded-3" style="max-height: 240px; object-fit: cover;">
                </a>
            </div>
            @if ($msg->body)
                <div class="mb-1">{!! nl2br(e($msg->body)) !!}</div>
            @endif

        {{-- Document / PDF Message --}}
        @elseif ($msg->message_type === 'document')
            <div class="p-2 rounded bg-light-subtle d-flex align-items-center gap-2.5 mb-1.5 border">
                <i class="bi bi-file-earmark-pdf-fill fs-3 text-danger flex-shrink-0"></i>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold small text-truncate">{{ $msg->media_filename ?: 'Document.pdf' }}</div>
                    <span class="text-muted" style="font-size: 0.7rem;">Document</span>
                </div>
                @if ($mediaUrl)
                    <a href="{{ $mediaUrl }}" target="_blank" download class="btn btn-sm btn-light rounded-circle p-1.5 ms-auto" title="Download Document">
                        <i class="bi bi-download"></i>
                    </a>
                @endif
            </div>
            @if ($msg->body)
                <div class="mb-1">{!! nl2br(e($msg->body)) !!}</div>
            @endif

        {{-- Audio / Voice Note Message --}}
        @elseif ($msg->message_type === 'audio' && $mediaUrl)
            <div class="mb-1">
                <audio controls class="w-100" style="max-width: 250px;">
                    <source src="{{ $mediaUrl }}" type="{{ $msg->media_mime_type ?: 'audio/ogg' }}">
                    Your browser does not support audio playback.
                </audio>
            </div>
            @if ($msg->body)
                <div class="mb-1">{!! nl2br(e($msg->body)) !!}</div>
            @endif

        {{-- Interactive / Order Message Card --}}
        @elseif ($msg->message_type === 'order')
            <div class="p-2.5 rounded bg-white text-dark mb-1.5 border border-success-subtle shadow-xs">
                <div class="d-flex align-items-center gap-1.5 fw-bold text-success small mb-1">
                    <i class="bi bi-cart3"></i> WhatsApp Catalogue Cart
                </div>
                <div class="small text-secondary">{!! nl2br(e($msg->body)) !!}</div>
            </div>

        {{-- Plain Text / Interactive / Other --}}
        @else
            <div class="text-break">{!! nl2br(e($msg->body)) !!}</div>
        @endif

        {{-- Footer: Timestamp & Delivery Status --}}
        <div class="d-flex align-items-center {{ $isOut ? 'justify-content-end' : 'justify-content-start' }} gap-1 mt-1 text-muted" style="font-size: 0.68rem;">
            @if ($isOut && $msg->sender)
                <span class="fw-medium text-dark-emphasis me-1">{{ $msg->sender->name }} •</span>
            @elseif ($isOut && !empty($msg->metadata['is_bot']))
                <span class="badge bg-light text-success border border-success-subtle me-1" style="font-size: 0.62rem;">
                    <i class="bi bi-robot"></i> Bot •
                </span>
            @endif
            <span>{{ $msg->created_at->format('h:i A') }}</span>

            @if ($isOut)
                <span class="message-status-tick">
                    @if ($msg->status === 'read')
                        <i class="bi bi-check2-all text-primary fw-bold" title="Read by customer"></i>
                    @elseif ($msg->status === 'delivered')
                        <i class="bi bi-check2-all text-muted" title="Delivered to phone"></i>
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
