<?php

namespace App\Dadi\Safety;

use App\Dadi\Contracts\SafetyAdjudicator;
use App\Dadi\Enums\SafetySignalCategory;
use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiContext;
use App\Dadi\ValueObjects\SafetyAssessment;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Deterministic, Laravel-owned safety adjudication.
 *
 * The verdict for a turn is derived only from rule-based signal detection over
 * the raw customer message plus the already-recorded authoritative safety
 * signals in ConversationState. No LLM is consulted and no insight is
 * requested: BLOCK > ADVISORY > CLEAR precedence is applied and a weaker
 * signal never downgrades a stronger one.
 *
 * Signals are stable codes (SafetySignalCategory values), never free text.
 * Previously held signals are sticky: an unrelated safe turn cannot erase a
 * recorded warning, and no AI output can clear it. Unknown codes carried in an
 * older/imported state are treated conservatively as advisory rather than
 * dropped.
 *
 * The AI safety flag is deliberately ignored for the verdict: the AI cannot
 * escalate a Clear to a BLOCK and cannot clear a Laravel BLOCK. It is logged
 * (with conversation id and signal codes only, never the message) so operators
 * can observe AI/backing mismatches without leaking conversational content.
 */
final class DadiSafetyAdjudicator implements SafetyAdjudicator
{
    /**
     * Signals detected from the raw customer message. Keys are ignored here:
     * we only need the array-of-patterns list per signal category.
     *
     * @var array<string,array<int,string>>
     */
    private const PATTERNS = [
        'emergency_breathing' => [
            '/\b(?:cannot|cant|can\x27t|can\s+not)\b.{0,40}\bbreathe\b/i',
            '/\b(?:difficult\w*|hard|trouble)\b.{0,25}\b(?:breathe|breathing|breath)\b/i',
            '/\bshortness of breath\b/i',
            '/\bsaans?\b.{0,30}\b(?:nahi|nhi|na|ruk|phool|takleef|dikkat|kharab)\b/i',
            '/\b(?:sans|saas)\b.{0,15}\b(?:ruk|phool|takleef|dikkat|kharab|nahi|nhi)\b/i',
            '/\bbreath\w*\b.{0,20}\b(?:fast|heavy|loud|rapid|shallow|problem|trouble|difficult\w*|cannot|cant)\b/i',
        ],
        'emergency_chest_pain' => [
            '/\bchest\b.{0,25}\b(?:pain|hurt\w*|tight\w*|pressure|strain|dard)\b/i',
            '/\b(?:seena|chhati|dil)\b.{0,15}\b(?:pain|dard|jhanjhan|bhaari)\b/i',
            '/\bheart\s*-?\s*attack\b/i',
            '/\b(?:pain|ache)\b.{0,15}\bchest\b/i',
        ],
        'emergency_swelling' => [
            '/\b(?:face|muh|mouth|throat|gala|neck|ankle|feet|foot|hand|arm|leg|body|skin|eyelid|lips|tongue|jaw)\b.{0,20}\b(?:swoll\w*|suj\w*|sooj\w*|inflam\w*)\b/i',
            '/\b(?:swoll\w*|suj\w*|sooj\w*)\b.{0,20}\b(?:face|throat|gala|lips|tongue|neck|eyelid|eye)\b/i',
        ],
        'emergency_bleeding' => [
            '/\b(?:uncontroll\w*|heavy|severe)\b.{0,15}\b(?:bleed\w*|blood)\b/i',
            '/\b(?:khoon|khun|blood)\b.{0,15}\b(?:nahi|nhi|na|jada|zyada|bahut|beh\w*|baht\w*|continu\w*)\b/i',
            '/\bbleed\w*\b.{0,20}\b(?:severe|heavy|uncontroll\w*)\b/i',
            '/\b(?:wound|ghav|zakhm)\b.{0,15}\b(?:bleed\w+|khoon|blood)\b/i',
        ],
        'emergency_reaction' => [
            '/\b(?:severe|serious|bad|strong|sudden|critical)\b.{0,15}\b(?:reaction|allergic\w*)\b/i',
            '/\banaphylax\w*\b/i',
            '/\b(?:allergic\w*|reaction)\b.{0,12}\b(?:severe|serious|bad)\b/i',
        ],
        'emergency_consciousness' => [
            '/\bfaint\w*\b/i',
            '/\b(?:unconscious|unresponsive|passed\s*out|black\w*\s*out)\b/i',
            '/\bbehosh\b/i',
            '/\bhosh\b.{0,10}\b(?:khoya|khoyi|gaya|gayi|nahi|utha|kho)\b/i',
        ],
        'medication_risk' => [
            '/\boverdose\b/i',
            '/\bdouble\s+(?:dose|tablet|goli|dawai|dava|medicine)\b/i',
            '/\bextra\s+(?:dose|tablet|goli|dawai|dava|medicine)\b/i',
            '/\bpoison\w*\b/i',
            '/\bgalti\s*se\b.{0,20}\b(?:goli|dawai|dava|medicine|tablet|dose|drug)\b/i',
            '/\b(?:goli|dawai|dava|medicine|tablet|dose|drug)\b.{0,20}\bgalti\s*se\b/i',
            '/\b(?:wrong|galat\w*)\b.{0,15}\b(?:medicine|medication|dawai|dava|tablet|goli|dose)\b/i',
        ],
        'severe_symptoms' => [
            '/\b(?:severe|extremely|serious|critical|very\s+bad)\b.{0,25}\b(?:pain|itch\w*|rash\w*|fever|burn\w*|symptom\w*|condition|swell\w*|reaction)\b/i',
            '/\b(?:pain|itch\w*|rash\w*|fever|burn\w*|symptom\w*|condition)\b.{0,20}\b(?:severe|extremely|serious|critical|very\s+bad|worsen\w*|getting\s+worse)\b/i',
            '/\b(?:bahut|zyada|jyada)\b.{0,12}\b(?:badh\w*|barh\w*|gaya|gayi|ho\s+gaya|ho\s+gayi)\b/i',
            '/\b(?:bahut|zyada|jyada)\s+(?:zyada|jyada|bahut|se)\s+(?:dard|pain|khujli|rash|bukhar|fever)\b/i',
            '/\bworsen\w*\b/i',
        ],
        'infection_signs' => [
            '/\binfection\w*\b/i',
            '/\b(?:high\s+fever|tez\s+bukhar)\b/i',
            '/\b(?:fever|bukhar)\b.{0,15}\b(?:infection|wound|pus)\b/i',
        ],
        'medication' => [
            '/\b(?:medicine|medication\w*|dawai|davai|dava|dav?aa?|tablet\w*|goli\w*|capsule\w*|syrup\w*|injection\w*|prescription|prescrib\w*|dosage\w*|dose\w*|drug\w*)\b/i',
            '/\b(?:replace|change|badal\w*|swap)\b.{0,20}\b(?:medicine|medication|dawai|dava|tablet|capsule|drug)\b/i',
            '/\b(?:medicine|medication|dawai|dava|tablet|capsule|drug)\b.{0,20}\b(?:replace|change|badal\w*|swap)\b/i',
            '/\b(?:stop|quit)\b.{0,25}\b(?:medicine|medication|dawai|dava|tablet|capsule)\b/i',
            '/\b(?:medicine|medication|dawai|dava|tablet|capsule)\b.{0,25}\b(?:stop|band)\b/i',
        ],
        'pregnancy' => [
            '/\bpregnan\w*\b/i',
            '/\bgarbh\w*\b/i',
            '/\bexpecting\b.{0,12}\bbaby\b/i',
        ],
        'breastfeeding' => [
            '/\bbreast\s*-?\s*feed\w*\b/i',
            '/\bbreastmilk\b/i',
            '/\bstill\s+feed\w*\b/i',
            '/\b(?:doodh|dudh)\s+pil\w+\b/i',
        ],
        'child' => [
            '/\b(?:child\w*|baby\w*|babies|toddler\w*|kids?|infant\w*)\b/i',
            '/\b(?:bachch[aei]|bachh[aei]|bachche|bacc[he]|balko|bache)\b/i',
        ],
        'allergy' => [
            '/\ballerg\w*\b/i',
            '/\bsensitiv\w*\b/i',
            '/\b(?:seh\w*\s+na|sahan\s+nahi|sahan\s+nhi|tolerate\s+nahi|na\s+sah\w*)\b/i',
        ],
        'diagnosis_request' => [
            '/\bdiagnos\w*\b/i',
            '/\bwhat\s+disease\b/i',
            '/\bwhat\s*(?:is|s|\x27s)?\s*(?:wrong|the\s+problem)\b/i',
            '/\bmujhe\s+kya\s+hua\s+hai\b/i',
            '/\b(?:kaun\s+sa|kaunsi?|kaunsa)\b.{0,10}\b(?:rog|bimari|disease)\b/i',
            '/\bis\s+it\b.{0,20}\b(?:psoriasis|eczema|infection|disease)\b/i',
        ],
    ];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function adjudicate(
        ConversationState $state,
        string $message,
        bool $aiSafetyFlag = false,
        ?DadiContext $context = null,
    ): SafetyAssessment {
        $signals = $this->heldSignals($state);

        foreach ($this->detect($message) as $code => $verdict) {
            $signals[$code] = $verdict;
        }

        if ($signals === []) {
            return SafetyAssessment::clear();
        }

        $verdict = in_array(SafetyVerdict::Block, $signals, true)
            ? SafetyVerdict::Block
            : SafetyVerdict::Advisory;

        $reasons = array_keys($signals);

        $this->log($verdict, $reasons, $context, $aiSafetyFlag);

        return new SafetyAssessment($verdict, $reasons);
    }

    /**
     * Signals already recorded in the authoritative state. They are sticky:
     * detection runs first so a fresh emergency can still escalate, but a safe
     * turn can never clear a previously recorded warning. Unknown codes are
     * treated conservatively as advisory.
     *
     * @return array<string,SafetyVerdict>
     */
    private function heldSignals(ConversationState $state): array
    {
        $signals = [];

        foreach ($state->safetySignals as $code) {
            $category = SafetySignalCategory::tryFrom((string) $code);

            $signals[(string) $code] = $category?->verdict() ?? SafetyVerdict::Advisory;
        }

        return $signals;
    }

    /**
     * @return array<string,SafetyVerdict>
     */
    private function detect(string $message): array
    {
        $signals = [];

        foreach (self::PATTERNS as $code => $patterns) {
            if ($patterns === [] || ! $this->matches($message, $patterns)) {
                continue;
            }

            $category = SafetySignalCategory::tryFrom($code);

            if ($category !== null) {
                $signals[$code] = $category->verdict();
            }
        }

        return $signals;
    }

    /**
     * @param  array<int,string>  $patterns
     */
    private function matches(string $message, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int,string>  $reasons
     */
    private function log(
        SafetyVerdict $verdict,
        array $reasons,
        ?DadiContext $context,
        bool $aiSafetyFlag,
    ): void {
        $logger = $this->logger;

        if ($logger === null) {
            try {
                $logger = Log::getFacadeRoot();
            } catch (\Throwable) {
                $logger = null;
            }
        }

        $logger?->log('warning', 'Dadi: safety assessment', [
            'conversation' => $context?->conversationId,
            'verdict' => $verdict->value,
            'signals' => $reasons,
            'ai_flag' => $aiSafetyFlag,
        ]);
    }
}
