<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One minimal, privacy-conscious attribution fact about how a recommendation
 * was interacted with. Only identity and linkage are stored — never message
 * text, AI scores, safety codes or prompts.
 *
 *   impression   one card rendered in a conversation (deduplicated per
 *                conversation + product, so reloads never double count)
 *   click        the customer opened the product from the card
 *   add_to_cart  the existing cart.add flow succeeded for that product
 *   buy_now      the existing Buy Now flow succeeded for that product
 *   purchase     an order containing that product was placed
 */
class DadiRecommendationEvent extends Model
{
    use HasFactory;

    public const ACTION_IMPRESSION = 'impression';

    public const ACTION_CLICK = 'click';

    public const ACTION_ADD_TO_CART = 'add_to_cart';

    public const ACTION_BUY_NOW = 'buy_now';

    public const ACTION_PURCHASE = 'purchase';

    protected $fillable = [
        'reference',
        'conversation_id',
        'product_id',
        'action',
        'dedupe_key',
        'session_id',
        'guest_cart_key',
        'user_id',
        'cart_id',
        'cart_item_id',
        'order_number',
    ];

    public function conversation()
    {
        return $this->belongsTo(DadiConversation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
