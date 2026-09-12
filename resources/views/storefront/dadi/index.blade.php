@extends('storefront.layouts.app')

@section('title', 'Dadi - Baat Karein')
@section('robots', 'noindex, follow')
@section('meta_description', 'Baal, skin aur wellness ki baatein VANRITI Dadi ke saath - aapke sawaal, humari seekh.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dadi.css') }}?v={{ time() }}">
@endpush

@section('content')

<script>document.body.classList.add('dadi-active');</script>

<div class="dadi-page" id="dadiApp"
      data-endpoint="{{ route('dadi.message') }}"
      data-click-endpoint="{{ route('dadi.recommendation.click') }}"
      data-onboarding-endpoint="{{ route('dadi.onboarding') }}"
      data-conversation-id="{{ $conversation?->getKey() }}"
      aria-busy="false">

    <div class="dadi-shell">

        <header class="dadi-header">
            @include('storefront.dadi._portrait')
            <div class="dadi-identity">
                <h1 class="dadi-title">Dadi</h1>
            </div>
        </header>

        @if ($conversation === null)
            <section class="dadi-onboard" id="dadiOnboarding" aria-labelledby="dadiOnboardHeading">
                <div class="dadi-onboard-inner">
                    <header class="dadi-onboard-head">
                        <p class="dadi-onboard-eyebrow">Vanriti Dadi se pehli baar</p>
                        <h2 class="dadi-onboard-title" id="dadiOnboardHeading">Chaliye, pehle aapko jaan lein.</h2>
                    </header>

                    <form id="dadiOnboardForm" class="dadi-onboard-form" method="POST" action="{{ route('dadi.onboarding') }}" novalidate>
                        @csrf

                        <div class="dadi-onboard-steps">

                            <fieldset class="dadi-onboard-step" data-step="1">
                                <legend class="dadi-onboard-step-label">
                                    <span class="dadi-onboard-step-num">1</span>
                                    Aap kya bhaasha mein baat karna chahenge?
                                </legend>

                                <div class="dadi-onboard-options">
                                    <label class="dadi-onboard-option">
                                        <input type="radio" name="language" value="english" class="dadi-onboard-radio">
                                        <span class="dadi-onboard-option-body">
                                            <strong>English</strong>
                                            <small>English mein baat karein</small>
                                        </span>
                                    </label>
                                    <label class="dadi-onboard-option">
                                        <input type="radio" name="language" value="hindi" class="dadi-onboard-radio">
                                        <span class="dadi-onboard-option-body">
                                            <strong>हिन्दी</strong>
                                            <small>Shuddh Hindi mein</small>
                                        </span>
                                    </label>
                                    <label class="dadi-onboard-option">
                                        <input type="radio" name="language" value="hinglish" class="dadi-onboard-radio">
                                        <span class="dadi-onboard-option-body">
                                            <strong>Hinglish</strong>
                                            <small>Jaisi aap roz bolte hain</small>
                                        </span>
                                    </label>
                                    <label class="dadi-onboard-option">
                                        <input type="radio" name="language" value="other" class="dadi-onboard-radio">
                                        <span class="dadi-onboard-option-body">
                                            <strong>Apni bhaasha</strong>
                                            <small>Koi aur bhaasha likhein</small>
                                        </span>
                                    </label>
                                </div>

                                <div class="dadi-onboard-other" id="dadiOnboardOther" hidden>
                                    <label class="dadi-onboard-other-label" for="dadiLanguageName">Apni bhaasha ka naam likhein</label>
                                    <input
                                        type="text"
                                        id="dadiLanguageName"
                                        name="language_name"
                                        class="dadi-onboard-input"
                                        maxlength="{{ (int) config('dadi.language.max_name_length', 40) }}"
                                        placeholder="Jaise Marathi, Bengali, Gujarati"
                                        autocomplete="off"
                                    >
                                </div>
                            </fieldset>

                            <fieldset class="dadi-onboard-step" data-step="2" hidden>
                                <legend class="dadi-onboard-step-label">
                                    <span class="dadi-onboard-step-num">2</span>
                                    Kya baat kar sakte hain?
                                </legend>

                                <ul class="dadi-onboard-list">
                                    <li class="dadi-onboard-list-item">
                                        <span class="dadi-onboard-list-icon"><i class="ri-leaf-fill"></i></span>
                                        <span class="dadi-onboard-list-body">
                                            <strong>Baal</strong>
                                            <small>Jhadna, dryness, dandruff, oiliness</small>
                                        </span>
                                    </li>
                                    <li class="dadi-onboard-list-item">
                                        <span class="dadi-onboard-list-icon"><i class="ri-drop-fill"></i></span>
                                        <span class="dadi-onboard-list-body">
                                            <strong>Skin</strong>
                                            <small>Dryness, sensitiveness, clear aajkal</small>
                                        </span>
                                    </li>
                                    <li class="dadi-onboard-list-item">
                                        <span class="dadi-onboard-list-icon"><i class="ri-sparkling-2-fill"></i></span>
                                        <span class="dadi-onboard-list-body">
                                            <strong>Wellness</strong>
                                            <small>Neend, stress, energy, dincharya</small>
                                        </span>
                                    </li>
                                </ul>

                                <p class="dadi-onboard-note">Dadi bechti nahi — samajhti hai.</p>
                            </fieldset>

                            <fieldset class="dadi-onboard-step" data-step="3" hidden>
                                <legend class="dadi-onboard-step-label">
                                    <span class="dadi-onboard-step-num">3</span>
                                    Jab aap Dadi se baat karte hain
                                </legend>

                                <ul class="dadi-onboard-list dadi-onboard-list-pillars">
                                    <li class="dadi-onboard-list-item">
                                        <span class="dadi-onboard-list-icon"><i class="ri-ear-line"></i></span>
                                        <span class="dadi-onboard-list-body">
                                            <strong>Sunna</strong>
                                            <small>Pehle aapko poora suna jaata hai.</small>
                                        </span>
                                    </li>
                                    <li class="dadi-onboard-list-item">
                                        <span class="dadi-onboard-list-icon"><i class="ri-chat-smile-3-line"></i></span>
                                        <span class="dadi-onboard-list-body">
                                            <strong>Samajhna</strong>
                                            <small>Samajh ke baat hoti hai, andaaza nahi.</small>
                                        </span>
                                    </li>
                                    <li class="dadi-onboard-list-item">
                                        <span class="dadi-onboard-list-icon"><i class="ri-compass-3-line"></i></span>
                                        <span class="dadi-onboard-list-body">
                                            <strong>Rasta dena</strong>
                                            <small>Halke, aasaan se raste.</small>
                                        </span>
                                    </li>
                                    <li class="dadi-onboard-list-item">
                                        <span class="dadi-onboard-list-icon"><i class="ri-arrow-right-circle-line"></i></span>
                                        <span class="dadi-onboard-list-body">
                                            <strong>Saath dena</strong>
                                            <small>Jaroorat ho toh ek sahi option.</small>
                                        </span>
                                    </li>
                                </ul>

                                <p class="dadi-onboard-trust">
                                    Aap hamesha decide karte hain. Dadi kuch bechti nahi — bas saath deti hain.
                                </p>
                            </fieldset>

                        </div>

                        <div class="dadi-onboard-controls">
                            <button type="button" class="dadi-onboard-back" id="dadiOnboardBack" hidden>Peche</button>
                            <div class="dadi-onboard-dots" id="dadiOnboardDots" aria-label="Onboarding steps">
                                <span class="dadi-onboard-dot" data-step-dot="1"></span>
                                <span class="dadi-onboard-dot" data-step-dot="2"></span>
                                <span class="dadi-onboard-dot" data-step-dot="3"></span>
                            </div>
                            <div class="dadi-onboard-actions">
                                <button type="button" class="dadi-onboard-next" id="dadiOnboardNext">Aage</button>
                                <button type="submit" class="dadi-onboard-start" id="dadiOnboardStart" hidden>
                                    Start talking to Dadi
                                </button>
                            </div>
                        </div>

                        <p class="dadi-onboard-error" id="dadiOnboardError" role="alert" hidden></p>
                    </form>
                </div>
            </section>
        @endif

        <section class="dadi-stage" id="dadiStage" role="log" aria-live="polite" aria-label="Dadi conversation" @if ($conversation === null) hidden @endif>
            @forelse ($stream as $item)
                <div class="dadi-msg {{ $item['message']->role === 'user' ? 'user' : 'dadi' }}">
                    <span class="dadi-msg-role">{{ $item['message']->role === 'user' ? 'Aap' : 'Dadi' }}</span>
                    <div class="dadi-msg-inner">{{ $item['message']->content }}</div>
                </div>
                @include('storefront.dadi._recs', ['cards' => $item['cards']])
            @empty
                @unless ($isLocked)
                    @if ($conversation === null)
                        {{-- First visit: the onboarding above is the entry point, so the
                             language-choice and empty-welcome prompt stay out of the way. --}}
                    @else
                        <div class="dadi-empty" id="dadiEmpty">
                            <h2 class="dadi-empty-title">{{ $welcomeTitle ?? 'Batao beta, kya pareshaan kar raha hai?' }}</h2>
                            <p class="dadi-empty-copy">
                                Baal, skin, wellness — jo mann mein ho, batao. Main sun rahi hoon.
                            </p>
                            <div class="dadi-chips">
                                <button type="button" class="dadi-chip" data-message="Baal bahut toot rahe hain, kya karun?">Baal bahut toot rahe hain</button>
                                <button type="button" class="dadi-chip" data-message="Meri skin kaafi dry ho gayi hai, koi aaram milega?">Skin kaafi dry ho gayi hai</button>
                                <button type="button" class="dadi-chip" data-message="Dadi, mere liye kuch achhi salah do">Mujhe kuch salah chahiye</button>
                            </div>
                        </div>
                    @endif
                @endunless
            @endforelse
        </section>

        @if ($isLocked)
            <div class="dadi-locked" role="status">
                <i class="ri-shield-user-fill"></i>
                <div>
                    <strong>Beta, aur baatein yahan abhi band hain.</strong>
                    <br>
                    Aapki pehli baatein mein kuch aisi baat nikli thi jo Dadi ke daayre se bahar hai. Upar
                    jo salah likhi hai, wahi aapke liye sahi raah hai — aur kisi bhi ilaaj ke liye apne doctor
                    se zaroor milein.
                </div>
            </div>
        @else
            <div class="dadi-composer" @if ($conversation === null) hidden @endif>
                <div class="dadi-error" id="dadiError" role="alert" hidden>
                    <i class="ri-alert-fill dadi-error-icon"></i>
                    <span class="dadi-error-text"></span>
                </div>
                <form id="dadiForm" class="dadi-composer-form" novalidate>
                    <div class="dadi-composer-field">
                        <label class="visually-hidden" for="dadiMessage">Dadi ko likhein</label>
                        <textarea
                            id="dadiMessage"
                            class="dadi-composer-input"
                            rows="1"
                            maxlength="{{ $messageMaxLength }}"
                            placeholder="Batao beta... baal, skin, wellness — jo mann mein ho."
                            aria-describedby="dadiHint"
                        ></textarea>
                        <button type="submit" id="dadiSend" class="dadi-send" aria-label="Sandesh bhejein">
                            <i class="ri-arrow-up-line"></i>
                        </button>
                    </div>
                    <div class="dadi-composer-meta">
                        <p class="dadi-hint" id="dadiHint">Enter se bhejein &middot; Shift+Enter se nayi line</p>
                        <span class="dadi-count" id="dadiCount">0 / {{ $messageMaxLength }}</span>
                    </div>
                </form>
            </div>
        @endif

    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/dadi.js') }}?v={{ time() }}"></script>
@endpush