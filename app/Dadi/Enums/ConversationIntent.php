<?php

namespace App\Dadi\Enums;

/**
 * Coarse conversational categories used ONLY for telemetry, analytics and
 * diagnostics.
 *
 * This enum is NEVER allowed to drive control flow. It must not be used to
 * build decision trees, question sequences or message branching. If a
 * conversation starts drifting toward flow control driven by these values,
 * stop and redesign. All category decisions belong to the AI.
 */
enum ConversationIntent: string
{
    case Greeting = 'greeting';
    case Concern = 'concern';
    case Clarification = 'clarification';
    case ProductQuestion = 'product_question';
    case PolicyQuestion = 'policy_question';
    case TopicSwitch = 'topic_switch';
    case RecommendationRequest = 'recommendation_request';
    case Safety = 'safety';
    case Smalltalk = 'smalltalk';
    case Gratitude = 'gratitude';
    case Farewell = 'farewell';
    case Unclear = 'unclear';
}
