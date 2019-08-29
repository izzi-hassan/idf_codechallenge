<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', 'HomeController@index')
    ->name('home')
    ->middleware('auth');

Route::get('/courses/{slug}', 'CourseEnrollmentController@show')
    ->name('courseEnrollments.show')
    ->middleware('auth');

Route::post('/course-enrollments/{slug}', 'CourseEnrollmentController@store')
    ->name('courseEnrollments.store')
    ->middleware('auth');

Route::get('/courses/{slug}/{number}', 'LessonController@show')
    ->name('lessons.show')
    ->middleware('auth');

Route::post('/quiz-answers/{id}', 'QuizAnswerController@store')
    ->name('quizAnswers.store')
    ->middleware('auth');

Route::get('/random-login', 'Auth\LoginController@loginAsRandomUser')
    ->name('auth.randomLogin');

/* For Testing */
Route::get('/grade/{quizAnswer}/{score}', 'QuizAnswerController@grade')
    ->name('quizAnswers.grade')
    ->middleware('auth');

Route::get('/user', function(Request $request) {
    return response()->json(auth()->user());
});

Route::get('/update-leaderboards', function(Request $request) {
    $courses = App\Course::all();
    foreach ($courses as $course) {
        $course->updateLeaderboard();
        echo "Course Leaderboard Updated: " . $course->id . "<br>";
    }
});
