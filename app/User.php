<?php declare(strict_types=1);

namespace App;

use App\Course;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property int $country_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 *
 * @property Country $country
 * @property Collection|CourseEnrollment[] $courseEnrollments
 */
class User extends Authenticatable implements GraderInterface
{
    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'api_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $with = [
        'country'
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function courseScore(Course $course): int
    {
        return $course->quizzes->sum(function($quiz)
        {
            return $quiz->getAnswerOf($this)->score;
        });
    }
}
