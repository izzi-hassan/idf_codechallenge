<?php declare(strict_types=1);

namespace App;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Support\Facades\DB;

/**
 * @property string $title
 * @property string $slug
 * @property Collection|Lesson[] $lessons
 * @property Collection|Quiz[] $quizzes
 */
final class Course extends Model
{
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function quizzes(): HasManyThrough
    {
        return $this->hasManyThrough(Quiz::class, Lesson::class)->orderBy('created_at');
    }

    public function enroll(Authenticatable $user): CourseEnrollment
    {
        return CourseEnrollment::create([
            'user_id' => $user->getAuthIdentifier(),
            'course_id' => $this->getKey(),
        ]);
    }

    public function enrolledUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_enrollments');
    }

    public function isCompletedBy(Authenticatable $user): bool
    {
        return $this->lessons->every(function(Lesson $lesson, int $key) use (&$user) {
            return $lesson->isCompletedBy($user);
        });
    }

    public function completedBy(): Collection
    {
        return $this->enrolledUsers->filter(function(Authenticatable $user, int $key) {
            return $this->isCompletedBy($user);
        })->map(function(Authenticatable $user, int $key) {
            $user->courseScore = $user->courseScore($this);
            return $user;
        });
    }

    public function userScore(Authenticatable $user): int
    {
        return $this->quizzes->sum(function($quiz) {
            return $quiz->getAnswerOf($user)->score;
        });
    }

    public function updateLeaderboard()
    {
        $cacheAddress = 'leaderboard.course-' . $this->id;

        /* Sort the users by course score */
        $users = DB::table('course_enrollments')
        ->join('users', 'course_enrollments.user_id', 'users.id')
        ->where('course_id', '=', $this->id)->get();

        $quizzes = DB::table('quizzes')
        ->join('lessons', function($join) {
            $join->on('quizzes.lesson_id', '=', 'lessons.id')
            ->where('lessons.course_id', '=', $this->id);
        })->pluck('quizzes.id');

        $numQuizzes = $quizzes->count();

        foreach ($users as $key => $user) {
            $quizAnswers = DB::table('quiz_answers')
            ->where('quiz_answers.user_id', '=', $user->id)
            ->whereIn('quiz_answers.quiz_id', $quizzes)
            ->get('score');

            if ($quizAnswers->count() == $numQuizzes) {
                $user->courseScore = $quizAnswers->sum('score');
            } else {
                unset($users[$key]);
            }
        }

        $leaderboard = $users->sortByDesc('courseScore');

        /* I'm using cache as this is data that would be fetched often and could be sped up with memcache */
        cache()->put($cacheAddress, $leaderboard);

        logger()->info('Leaderboard Updated for Course ID ' . $this->id . ': ' . $this->title);
    }

    public function getLeaderboardAttribute()
    {
        $cacheAddress = 'leaderboard.course-' . $this->id;

        if (! cache()->has($cacheAddress)) {
            $this->updateLeaderboard();
        }

        return cache()->get($cacheAddress);
    }
}
