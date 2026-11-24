<?php

declare(strict_types=1);

use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PublicEventController;
use App\Router;

$router = new Router();

// Public site.
$router->get('/', [PublicEventController::class, 'home']);
$router->get('/e/{slug}', [PublicEventController::class, 'show']);
$router->post('/e/{slug}/register', [PublicEventController::class, 'register']);
$router->get('/e/{slug}/registered/{reference}', [PublicEventController::class, 'registered']);
$router->post('/e/{slug}/registered/{reference}/cancel', [PublicEventController::class, 'cancel']);
$router->get('/e/{slug}/calendar.ics', [PublicEventController::class, 'calendar']);

// Auth.
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);
$router->get('/forgot-password', [AuthController::class, 'showForgot'], ['guest']);
$router->post('/forgot-password', [AuthController::class, 'sendReset'], ['guest']);
$router->get('/reset-password', [AuthController::class, 'showReset'], ['guest']);
$router->post('/reset-password', [AuthController::class, 'reset'], ['guest']);

// Organizer area.
$router->get('/dashboard', [DashboardController::class, 'index'], ['auth']);

$router->get('/events', [EventController::class, 'index'], ['auth']);
$router->get('/events/create', [EventController::class, 'create'], ['auth']);
$router->post('/events', [EventController::class, 'store'], ['auth']);
$router->get('/events/{event}', [EventController::class, 'show'], ['auth']);
$router->get('/events/{event}/edit', [EventController::class, 'edit'], ['auth']);
$router->post('/events/{event}', [EventController::class, 'update'], ['auth']);
$router->post('/events/{event}/publish', [EventController::class, 'publish'], ['auth']);
$router->post('/events/{event}/cancel', [EventController::class, 'cancel'], ['auth']);

$router->get('/events/{event}/attendees', [AttendeeController::class, 'index'], ['auth']);
$router->get('/events/{event}/attendees/export', [AttendeeController::class, 'export'], ['auth']);
$router->post('/events/{event}/attendees/{registration}/cancel', [AttendeeController::class, 'cancel'], ['auth']);

$router->get('/events/{event}/check-in', [CheckInController::class, 'index'], ['auth']);
$router->post('/events/{event}/check-in', [CheckInController::class, 'store'], ['auth']);
$router->post('/events/{event}/check-in/{registration}/undo', [CheckInController::class, 'undo'], ['auth']);

return $router;
