<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model {
    protected $fillable = [
        'title',
        'description',
        'igdb_id',
        'critic_score',
        'user_score',
        'main_story_completion_time',
        'completionist_time',
        'platforms',
    ];
}
