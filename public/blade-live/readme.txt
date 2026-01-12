
<h2>Welcome to Laravel LiveBlade</h2>
<p>A responsive solution for enabling real-time communication between Blade templates and traditional Laravel controllers.</p>
<a href="#about" class="btn-get-started">Get Started</a>

Imports

<script src="{{ asset('blade-live/forms/forms.min.js') }}" type="module"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

In the controllers
if ($request->ajax()) {
    return response()->json([
        'success' => true,
        'reload' => true,
        'component' => 'updateProfileForm',
        'redirect' => route('profile.edit'),
    ]);
} else {
    return response()->json([
        'success' => false,
        'message' => "something went wrong!"
    ]);
}

to refresh a component within a given blade file call from one controller method
simply make sure 'component' => 'updateProfileForm', is passed and the method looks for which view to be refreshed
eg 
public function edit(Request $request): View
{
    // Log or check if the 'viewBlade' parameter is being received
    $viewBlade = $request->query('viewBlade');
    // \Log::info('viewBlade received: ' . $viewBlade);

    switch ($viewBlade) {
        case 'updateProfileForm':
            return view('profile.partials.update-profile-information-form', ['user' => $request->user()]);
        default:
            return view('profile.edit', ['user' => $request->user()]);
    }
}
this will refresh on the component


#LiveBlade.js: Real-Time Communication for Laravel Blade

LiveBlade.js is a lightweight JavaScript library designed to enable seamless, real-time communication between Laravel Blade templates and traditional Laravel controllers. It simplifies AJAX-driven interactions, allowing dynamic updates to Blade views without the need for page reloads or relying on complex frontend frameworks like Livewire. Features:

Real-time, AJAX-based communication with Laravel controllers.
Keeps your Blade templates interactive and "live."
Lightweight and easy to integrate into any Laravel project.
Ideal for form submissions, real-time data updates, and other dynamic activities in Laravel.

Setup Instructions:

https://github.com/Eluk-Samuel-Kiira/Laravel-LiveBlade

Set up the environment by configuring the database to SQLite in your .env file:

makefile

DB_CONNECTION=sqlite

php artisan migrate

With LiveBlade.js, you can streamline real-time interactions in your Laravel applications, making them more dynamic and user-friendly with minimal effort.
