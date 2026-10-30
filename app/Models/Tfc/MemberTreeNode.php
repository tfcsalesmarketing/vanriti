<?php

namespace App\Models\Tfc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberTreeNode extends Model
{
    use HasFactory;

    protected $table = 'member_tree_nodes';

    public $incrementing = false;
    protected $primaryKey = ['ancestor_id', 'descendant_id'];

    protected $fillable = [
        'ancestor_id',
        'descendant_id',
        'depth',
        'group_leg',
    ];

    public function ancestor(): BelongsTo
    {
        return $this->belongsTo(DirectSellingMember::class, 'ancestor_id');
    }

    public function descendant(): BelongsTo
    {
        return $this->belongsTo(DirectSellingMember::class, 'descendant_id');
    }
}
