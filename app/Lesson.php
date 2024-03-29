<?php declare(strict_types=1);

namespace App;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $course_id
 * @property int $number
 * @property string $title
 *
 * @property Course $course
 * @property Collection|Quiz[] $quizes
 */
final class Lesson extends Model
{
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @todo quizes should be refactored as "quizzes"
     */
    public function quizes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function isCompletedBy(Authenticatable $user): bool
    {
        return $this->quizes->every(function(Quiz $quiz, int $key) use (&$user) {
            return $quiz->isAnsweredBy($user);
        });
    }
}
