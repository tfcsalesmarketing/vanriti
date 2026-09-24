<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dadi conversation context & memory
    |--------------------------------------------------------------------------
    |
    | Bounds for the conversational context handed to the future AI layer.
    | Limits are configuration-driven (no magic numbers in the domain) and
    | correspond to the three memory layers:
    |
    |   recent_messages_limit     Layer A window of immediate history
    |   historical_memory_limit   Layer C: active classified memory items
    |
    | Layer B (the authoritative ConversationState) is not truncated here; it is
    | the single structured understanding Laravel keeps for the conversation.
    |
    */

    'context' => [

        'recent_messages_limit' => (int) env('DADI_CONTEXT_RECENT_MESSAGES_LIMIT', 20),

        'historical_memory_limit' => (int) env('DADI_CONTEXT_MEMORY_LIMIT', 15),

    ],

    /*
    |--------------------------------------------------------------------------
    | Dadi conversational locale
    |--------------------------------------------------------------------------
    |
    | Default locale used by the minimal ConversationEngine seam when no
    | persisted conversation provides one. Real orchestration always passes the
    | conversation's own locale through DadiContext.
    |
    */

    'locale' => env('DADI_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Dadi AI provider layer
    |--------------------------------------------------------------------------
    |
    | Provider/model settings for the conversational engine. The provider
    | abstraction currently supports OpenAI; the API key itself lives in
    | services.openai (env: OPENAI_API_KEY) and never here. Defaults are tuned
    | for a cheap, JSON-capable model and safe conversational use.
    |
    */

    'ai' => [

        'provider' => env('DADI_AI_PROVIDER', 'openai'),

        'model' => env('DADI_AI_MODEL', 'gpt-4o-mini'),

        'timeout' => (int) env('DADI_AI_TIMEOUT', 15),

        'connect_timeout' => (int) env('DADI_AI_CONNECT_TIMEOUT', 5),

        'max_output_tokens' => (int) env('DADI_AI_MAX_OUTPUT_TOKENS', 900),

        'max_reply_length' => (int) env('DADI_AI_MAX_REPLY_LENGTH', 600),

        'temperature' => (float) env('DADI_AI_TEMPERATURE', 0.8),

        'json_mode' => (bool) env('DADI_AI_JSON_MODE', true),

        /*
        |--------------------------------------------------------------------------
        | Canonical AI vocabulary (trusted prompt content only)
        |--------------------------------------------------------------------------
        |
        | Concise bilingual trigger hints that teach the model which canonical
        | codes it may emit in "understanding". The authoritative code list is
        | the Section / Concern enums; hints below are display-only guidance that
        | never changes Laravel truth. Bounded deliberately: at most 3 short
        | hints per code are rendered into the prompt to keep every request small.
        | preference_hints / avoidance_hints mirror the exact keys of
        | dadi.recommendation.preferences / avoidances and only teach the model
        | their meaning — they are never used for matching.
        | No product, SKU, price, stock or internal reference ever appears here.
        |
        */

        'lexicon' => [

            'section_hints' => [

                'hair' => ['hair', 'baal', 'scalp'],

                'skin' => ['skin', 'twacha', 'face', 'chehra'],

                'wellness' => ['wellness', 'sehat', 'health', 'body'],

            ],

            'concern_hints' => [

                'hair_dryness' => ['dry hair', 'rukhe baal', 'khushk hair'],

                'hair_frizz' => ['frizzy hair', 'badarre baal'],

                'hair_roughness' => ['rough hair', 'khurdura baal'],

                'hair_fall' => ['hair fall', 'baal jhadna', 'baal gire'],

                'hair_breakage' => ['hair breakage', 'baal tootna'],

                'hair_dullness' => ['dull hair', 'behaal baal'],

                'dandruff' => ['dandruff', 'ruusi'],

                'oily_scalp' => ['oily scalp', 'chikna scalp', 'teliya scalp'],

                'hair_thinning' => ['thinning hair', 'patle baal'],

                'skin_dryness' => ['dry skin', 'ruki twacha', 'khushk skin'],

                'skin_roughness' => ['rough skin', 'khurdura twacha'],

                'skin_dullness' => ['dull skin', 'behaal skin'],

                'oiliness' => ['oily skin', 'chikni twacha'],

                'acne' => ['acne', 'pimples', 'muhase', 'daag'],

                'pigmentation' => ['pigmentation', 'dhabbe'],

                'tanning' => ['tanning', 'dhoop se kali twacha'],

                'signs_of_aging' => ['signs of aging', 'jhurriyan', 'wrinkles'],

                'skin_sensitivity' => ['sensitive skin', 'naazuk twacha'],

                'uneven_tone' => ['uneven tone', 'rang ka farak'],

                'open_pores' => ['open pores'],

                'stress_support' => ['stress', 'tension'],

                'sleep_support' => ['sleep', 'neend'],

                'immunity_support' => ['immunity', 'kamzor immunity'],

                'energy_support' => ['energy', 'thakaan'],

                'joint_comfort' => ['joint comfort', 'jod dard'],

                'digestion_comfort' => ['digestion', 'pet ki takleef'],

                'relaxation' => ['relaxation', 'sukoon', 'aaram'],

            ],

            'preference_hints' => [

                'fragrance_free' => ['fragrance free', 'unscented', 'bina khushbu'],

                'non_sticky' => ['non sticky feel', 'chikna nahi'],

                'lightweight' => ['light weight', 'halka'],

                'herbal' => ['ayurvedic jadi-buti', 'traditional herbs'],

                'gentle' => ['mild / soothing'],

            ],

            'avoidance_hints' => [

                'fragrance' => ['khushbu nahi', 'no perfume'],

                'sticky' => ['chiknas nahi', 'tacky nahi'],

                'heavy' => ['bhari nahi', 'weighty nahi'],

                'oily' => ['tel chikna nahi', 'greasiness nahi'],

                'paraben' => ['preservative nahi', 'no parabens'],

                'sulphate' => ['SLS nahi', 'no sulfates'],

            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Dadi deterministic concern recovery
    |--------------------------------------------------------------------------
    |
    | Laravel-owned, non-AI concern recovery that runs between AI output
    | validation and the authoritative state commit. It does two things:
    |
    |   1. filters AI-proposed concerns down to canonical Concern enum codes;
    |   2. when the customer language contains strong, supported evidence,
    |      recovers additional canonical concerns from the message.
    |
    | It never infers from vague or unrelated language, never fabricates, and
    | is fully deterministic: same message + same config -> same result.
    |
    |   enabled        master switch for language recovery (canonical filtering
    |                  always stays on so fabricated AI codes never persist)
    |   max_results    hard cap on canonical concerns after recovery — mirrors
    |                  the AI validator limit so recovery never exceeds state
    |                  bounds
    |   trigger_gap    how many filler tokens are allowed inside a multi-word
    |                  trigger (e.g. "baal bahut rough" -> "baal rough")
    |   concern_triggers  canonical code -> strong bilingual phrases. Keys are
    |                  validated against Concern::cases(); values are exact,
    |                  word-bounded trigger phrases (single words are allowed
    |                  only when the word is effectively unambiguous)
    |
    | No product, SKU, price, stock, disease or diagnosis vocabulary lives
    | here; recovery is conversational product relevance only.
    |
    */

    'recovery' => [

        'enabled' => (bool) env('DADI_RECOVERY_ENABLED', true),

        'max_results' => (int) env('DADI_RECOVERY_MAX_RESULTS', 12),

        'trigger_gap' => (int) env('DADI_RECOVERY_TRIGGER_GAP', 2),

        'concern_triggers' => [

            'hair_dryness' => [
                'dry hair', 'hair dry', 'dry baal', 'baal dry',
                'rukhe baal', 'baal rukhe', 'sukhe baal', 'baal sukhe',
                'khushk baal', 'baal khushk',
            ],

            'hair_frizz' => [
                'frizzy hair', 'hair frizzy', 'frizz', 'frizzy',
                'badarre baal', 'baal badarre',
            ],

            'hair_roughness' => [
                'rough hair', 'hair rough', 'rough baal', 'baal rough',
                'khurdura baal', 'baal khurdura', 'khurdura hair',
            ],

            'hair_fall' => [
                'hair fall', 'fall hair', 'hair loss', 'hairfall',
                'baal jhad', 'jhad baal', 'baal gir', 'gir baal',
                'jhad rahe', 'jhadna', 'baal jhadna',
            ],

            'hair_breakage' => [
                'hair breakage', 'hair breaking', 'baal toot', 'toot baal',
            ],

            'hair_dullness' => [
                'dull hair', 'hair dull', 'dull baal', 'baal dull',
                'behaal baal', 'baal behaal',
            ],

            'dandruff' => [
                'dandruff', 'ruusi', 'russi',
            ],

            'oily_scalp' => [
                'oily scalp', 'scalp oily', 'chikna scalp', 'scalp chikna',
                'teliya scalp', 'scalp teliya', 'sebum',
            ],

            'hair_thinning' => [
                'thinning hair', 'hair thinning', 'thinning', 'patle baal', 'baal patle',
            ],

            'skin_dryness' => [
                'dry skin', 'skin dry', 'dry twacha', 'twacha dry',
                'ruki twacha', 'twacha ruki', 'khushk skin', 'skin khushk',
            ],

            'skin_roughness' => [
                'rough skin', 'skin rough', 'khurdura twacha', 'twacha khurdura',
            ],

            'skin_dullness' => [
                'dull skin', 'skin dull', 'dull twacha', 'twacha dull',
                'behaal skin', 'skin behaal',
            ],

            'oiliness' => [
                'oily skin', 'skin oily', 'oily twacha', 'twacha oily',
                'chikni twacha', 'twacha chikni', 'oily face', 'face oily',
                'chikna chehra', 'chehra chikna', 'greasy face',
            ],

            'acne' => [
                'acne', 'pimples', 'pimple', 'muhase', 'muhaase',
            ],

            'pigmentation' => [
                'pigmentation', 'pigmented', 'dhabbe', 'dhabba', 'dark spots', 'dark patches',
            ],

            'tanning' => [
                'tanning', 'tan', 'tanned', 'suntan', 'sun tan',
            ],

            'signs_of_aging' => [
                'signs of aging', 'signs ageing', 'ageing', 'wrinkles', 'wrinkle', 'jhurriyan', 'jhurri',
            ],

            'skin_sensitivity' => [
                'sensitive skin', 'skin sensitive', 'sensitive twacha', 'twacha sensitive',
                'naazuk twacha', 'twacha naazuk',
            ],

            'uneven_tone' => [
                'uneven tone', 'uneven skin tone', 'skin tone uneven', 'rang ka farak',
            ],

            'open_pores' => [
                'open pores', 'enlarged pores', 'pores',
            ],

            'stress_support' => [
                'stress', 'stressed', 'stressful', 'tension', 'tensed', 'tension hai',
            ],

            'sleep_support' => [
                'sleep problem', 'sleep problems', 'sleep issue', 'sleepless',
                'insomnia', 'neend nahi', 'neend nahi a rahi',
            ],

            'immunity_support' => [
                'immunity', 'immune', 'kamzor immunity', 'weak immunity',
            ],

            'energy_support' => [
                'low energy', 'no energy', 'thakaan', 'thakan', 'fatigue',
                'tired', 'tiredness', 'kamzori', 'kamjori', 'thak gaye',
            ],

            'joint_comfort' => [
                'joint pain', 'joints', 'joint', 'jod dard', 'jodon', 'knee pain', 'ghutne',
            ],

            'digestion_comfort' => [
                'digestion', 'indigestion', 'gas', 'bloating', 'acidity', 'pet ki takleef',
            ],

            'relaxation' => [
                'relaxation', 'relax', 'relaxed', 'sukoon', 'aaram',
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Bounds for the human-approved product intelligence content that Dadi is
    | allowed to use. These mirror the immutable ProductProfile value object
    | defaults and are applied by DadiProductProfileStore when an admin authors
    | or edits a profile. Commerce fields (price, stock, SKU, ...) are never
    | accepted; they are always read live from the existing product catalogue.
    |
    */

    'product_intelligence' => [

        'max_positioning_length' => (int) env('DADI_PI_MAX_POSITIONING_LENGTH', 1000),

        'max_concerns' => (int) env('DADI_PI_MAX_CONCERNS', 12),

        'max_benefits' => (int) env('DADI_PI_MAX_BENEFITS', 15),

        'max_benefit_length' => (int) env('DADI_PI_MAX_BENEFIT_LENGTH', 300),

        'max_usage_context' => (int) env('DADI_PI_MAX_USAGE_CONTEXT', 10),

        'max_usage_length' => (int) env('DADI_PI_MAX_USAGE_LENGTH', 300),

        'max_precautions' => (int) env('DADI_PI_MAX_PRECAUTIONS', 10),

        'max_precaution_length' => (int) env('DADI_PI_MAX_PRECAUTION_LENGTH', 300),

        'max_suitability_notes' => (int) env('DADI_PI_MAX_SUITABILITY_NOTES', 10),

        'max_suitability_length' => (int) env('DADI_PI_MAX_SUITABILITY_LENGTH', 500),

    ],

    /*
    |--------------------------------------------------------------------------
    | Dadi recommendation engine
    |--------------------------------------------------------------------------
    |
    | Deterministic, Laravel-owned product recommendation. The engine consumes
    | the conversation state and the safety assessment, runs ONLY against
    | human-approved product profiles, and reads live catalogue availability at
    | recommendation time.
    |
    |   max_candidates   upper bound on candidates in one result (hard limit)
    |   weights          transparent scoring weights (section / concern / trait)
    |   preferences      closed lexicon: conversation preference code -> the
    |                    approved-text tokens that positively assert that trait
    |   avoidances       closed lexicon: memory "avoidance" slot -> approved-text
    |                    tokens that positively assert the avoided trait
    |
    | Only positively asserted approved-text traits count. A profile never gets
    | a preference/avoidance reading it does not actively state, so the engine
    | cannot fabricate a match out of silence.
    |
    */

    'recommendation' => [

        'max_candidates' => (int) env('DADI_RECOMMENDATION_MAX_CANDIDATES', 3),

        'weights' => [

            'section_match' => (int) env('DADI_RECOMMENDATION_WEIGHT_SECTION', 20),

            'concern_match' => (int) env('DADI_RECOMMENDATION_WEIGHT_CONCERN', 25),

            'preference_match' => (int) env('DADI_RECOMMENDATION_WEIGHT_PREFERENCE', 10),

        ],

        'preferences' => [

            'fragrance_free' => ['fragrance free', 'fragrance-free', 'unscented', 'no fragrance', 'without fragrance'],

            'non_sticky' => ['non sticky', 'non-sticky', 'not sticky'],

            'lightweight' => ['lightweight', 'light weight'],

            'herbal' => ['herbal', 'herb', 'herbs', 'ayurvedic', 'ayurveda', 'traditional'],

            'gentle' => ['gentle', 'gently', 'mild', 'soothing', 'delicate'],

        ],

        'avoidances' => [

            'fragrance' => ['fragrance', 'perfume', 'scented', 'perfumed'],

            'sticky' => ['sticky', 'tacky'],

            'heavy' => ['heavy', 'weighty'],

            'oily' => ['oily', 'greasy'],

            'paraben' => ['paraben', 'parabens'],

            'sulphate' => ['sulphate', 'sulfate'],

        ],

        /*
        |--------------------------------------------------------------------------
        | Recommendation explanation ("Why this product?")
        |--------------------------------------------------------------------------
        |
        | Deterministic, customer-facing explanation bounds consumed by the
        | RecommendationExplainer. Explanations are Laravel-shaped plain text:
        |
        |   max_length                hard character cap on one `why` string
        |   max_concern_labels        at most how many matched-concern labels
        |                             appear before truncation
        |   max_preference_labels     at most how many matched-preference labels
        |                             appear before truncation
        |   preference_labels         the canonical customer-facing labels for
        |                             the preference/avoidance codes of the
        |                             closed lexicon; keys mirror
        |                             `dadi.recommendation.preferences` exactly,
        |                             values are short trait nouns, never product
        |                             names, categories or commerce data
        |
        | No product, SKU, price, stock or internal identifier ever belongs
        | here; the explainer only turns match evidence + approved intelligence
        | into plain, bounded sentences.
        |
        */

        'explanation' => [

            'max_length' => (int) env('DADI_RECOMMENDATION_WHY_MAX_LENGTH', 160),

            'max_concern_labels' => (int) env('DADI_RECOMMENDATION_WHY_MAX_CONCERN_LABELS', 2),

            'max_preference_labels' => (int) env('DADI_RECOMMENDATION_WHY_MAX_PREFERENCE_LABELS', 2),

            'preference_labels' => [

                'fragrance_free' => 'fragrance-free',

                'non_sticky' => 'non-sticky',

                'lightweight' => 'lightweight',

                'herbal' => 'herbal',

                'gentle' => 'gentle and mild',

            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Dadi commerce attribution
    |--------------------------------------------------------------------------
    |
    | Minimal, privacy-conscious attribution for recommendation -> commerce.
    | Impressions are deduplicated per conversation + product at render time,
    | and click / add_to_cart / buy_now / purchase events are appended with
    | their own opaque references. No AI scores, reasoning or prompts are ever
    | stored; the Laravel cart / order lifecycle remains the single source of
    | truth. Set to false to disable the attribution layer entirely.
    |
    */

    'attribution' => [

        'enabled' => (bool) env('DADI_ATTRIBUTION_ENABLED', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Dadi customer UI bounds
    |--------------------------------------------------------------------------
    |
    | Presentation bounds for the customer-facing Dadi page. The page is a pure
    | front-end for the Laravel-owned conversation engine: it renders what the
    | domain persisted and submits what the customer types. No conversation,
    | safety, recommendation or product logic lives in the UI or in JavaScript.
    |
    |   message_max_length       soft cap on one customer message (validated by
    |                            the POST route, mirrored in the textarea)
    |   restored_messages_limit  how many recent messages the page restores when
    |                            a returning customer re-opens Dadi
    |
    */

    'ui' => [

        'message_max_length' => (int) env('DADI_UI_MESSAGE_MAX_LENGTH', 1000),

        'restored_messages_limit' => (int) env('DADI_UI_RESTORED_MESSAGES_LIMIT', 50),

    ],

    /*
    |--------------------------------------------------------------------------
    | Dadi conversation language preference
    |--------------------------------------------------------------------------
    |
    | The customer's chosen conversation language is Laravel-owned state stored
    | on the existing ConversationState (preferred_language and, for "Other",
    | preferred_language_name). It is created only by validated customer input
    | (the onboarding route) and is read-only for the AI: it flows
    | ConversationState -> DadiContextBuilder -> DadiPromptBuilder as trusted
    | context and can never be re-written by AI output.
    |
    |   max_name_length   bound on the free-text language name a customer can
    |                     supply when they pick "Other"
    |
    | No new table, no user-profile system and no separate persistence is used.
    |
    */

    'language' => [

        'max_name_length' => (int) env('DADI_LANGUAGE_MAX_NAME_LENGTH', 40),

    ],

];
