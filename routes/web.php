use App\Http\Controllers\BookOfBusinessController;

Route::get('/book-of-business', [BookOfBusinessController::class, 'index'])
    ->name('book.business');
