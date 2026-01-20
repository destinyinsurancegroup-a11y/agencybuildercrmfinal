<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

use App\Models\Event;
use App\Models\GideonOpportunity;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\NoteController;

use App\Http\Controllers\LeadController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ServiceController;

// ✅ CONTACT MESSAGES (Phase 2 + Phase 3 + Bulk)
use App\Http\Controllers\ContactMessageController;

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

use App\Services\Gideon\GideonLlmClient;
use App\Services\Gideon\OpportunityScanner;

use App\Http\Controllers\Gideon\GideonOpportunitiesController;
use App\Http\Controllers\Gideon\GideonInsightsController;

use App\Http\Controllers\GideonScanController;
use App\Http\Controllers\BillingController;

// ✅ SETTINGS (Tier 1 tabs + Profile actions)
use App\Http\Controllers\SettingsController;
// use App\Http\Controllers\Settings\ProfileSettingsController; // ❌ File does not exist (disabled for now)

// ✅ SETTINGS (Messaging - Universal SMS Providers)
use App\Http\Controllers\Settings\SmsProvidersController;

// ✅ Message Templates Controller
use App\Http\Controllers\Settings\MessageTemplateController;

// ✅ SETTINGS Drip Campaigns Controller (CRUD)
use App\Http\Controllers\Settings\DripCampaignController;

// ✅ DRIPS Enrollment Controller (Step 4C)
use App\Http\Controllers\Drips\EnrollmentController;

// ✅ ATTACHMENTS (Contacts / Book / Service share same Contact model)
use App\Http\Controllers\ContactAttachmentController;

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->name('login.store');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| DEBUG / TEST (OPTIONAL – not behind auth)
|--------------------------------------------------------------------------
*/

Route::get('/debug-laravel-log', function () {
    $path = storage_path('logs/laravel.log');
    if (! file_exists($path)) {
        return "No laravel.log file found.";
    }
    return nl2br(e(file_get_contents($path)));
});

Route::get('/test', fn () => 'ROUTES ARE WORKING');

Route::get('/debug-contact-columns', function () {
    $columns = Schema::getColumnListing('contacts');
    return response()->json($columns);
});

Route::get('/gideon-test', function (GideonLlmClient $client) {
    return $client->testPing();
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED APPLICATION ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | SETTINGS (Tier 1): Profile / Messaging / Billing
    |--------------------------------------------------------------------------
    */
    Route::get('/settings', [SettingsController::class, 'redirect'])->name('settings');

    Route::get('/settings/profile', [SettingsController::class, 'profile'])
        ->name('settings.profile');

    /*
    |--------------------------------------------------------------------------
    | SETTINGS -> MESSAGING LANDING
    |--------------------------------------------------------------------------
    */
    Route::get('/settings/messaging', fn () => redirect()->route('settings.messaging.sms_providers'))
        ->name('settings.messaging');

    /*
    |--------------------------------------------------------------------------
    | SETTINGS -> MESSAGING -> SMS PROVIDERS (Universal)
    |--------------------------------------------------------------------------
    */
    Route::get('/settings/messaging/sms-providers', [SmsProvidersController::class, 'index'])
        ->name('settings.messaging.sms_providers');

    Route::get('/settings/messaging/sms-providers/{provider}/configure', [SmsProvidersController::class, 'configure'])
        ->whereIn('provider', ['twilio', 'telnyx', 'plivo', 'vonage'])
        ->name('settings.messaging.sms_providers.configure');

    /*
    |--------------------------------------------------------------------------
    | ✅ MESSAGE TEMPLATES (Chooser + Email Library + SMS Library + JSON)
    |--------------------------------------------------------------------------
    | IMPORTANT ORDER:
    | - /json routes must be ABOVE /{messageTemplate}/... routes
    | - /create must be ABOVE /{messageTemplate}/... routes
    */

    Route::get('/settings/messaging/templates', [MessageTemplateController::class, 'choose'])
        ->name('settings.messaging.templates.index');

    Route::get('/settings/messaging/templates/choose', [MessageTemplateController::class, 'choose'])
        ->name('settings.messaging.templates.choose');

    Route::get('/settings/messaging/templates/email', [MessageTemplateController::class, 'emailIndex'])
        ->name('settings.messaging.templates.email');

    Route::get('/settings/messaging/templates/sms', [MessageTemplateController::class, 'smsIndex'])
        ->name('settings.messaging.templates.sms');

    Route::get('/settings/messaging/templates/json', [MessageTemplateController::class, 'jsonIndex'])
        ->name('settings.messaging.templates.json');

    Route::get('/settings/messaging/templates/{messageTemplate}/json', [MessageTemplateController::class, 'jsonShow'])
        ->whereNumber('messageTemplate')
        ->name('settings.messaging.templates.json.show');

    Route::get('/settings/messaging/templates/create', [MessageTemplateController::class, 'create'])
        ->name('settings.messaging.templates.create');

    Route::post('/settings/messaging/templates', [MessageTemplateController::class, 'store'])
        ->name('settings.messaging.templates.store');

    Route::get('/settings/messaging/templates/{messageTemplate}/edit', [MessageTemplateController::class, 'edit'])
        ->whereNumber('messageTemplate')
        ->name('settings.messaging.templates.edit');

    Route::put('/settings/messaging/templates/{messageTemplate}', [MessageTemplateController::class, 'update'])
        ->whereNumber('messageTemplate')
        ->name('settings.messaging.templates.update');

    Route::delete('/settings/messaging/templates/{messageTemplate}', [MessageTemplateController::class, 'destroy'])
        ->whereNumber('messageTemplate')
        ->name('settings.messaging.templates.destroy');

    /*
    |--------------------------------------------------------------------------
    | ✅ SETTINGS -> MESSAGING -> DRIP CAMPAIGNS (CRUD)
    |--------------------------------------------------------------------------
    */
    Route::get('/settings/messaging/drips', [DripCampaignController::class, 'index'])
        ->name('settings.messaging.drips.index');

    Route::get('/settings/messaging/drips/create', [DripCampaignController::class, 'create'])
        ->name('settings.messaging.drips.create');

    Route::post('/settings/messaging/drips', [DripCampaignController::class, 'store'])
        ->name('settings.messaging.drips.store');

    Route::get('/settings/messaging/drips/{dripCampaign}/edit', [DripCampaignController::class, 'edit'])
        ->whereNumber('dripCampaign')
        ->name('settings.messaging.drips.edit');

    Route::put('/settings/messaging/drips/{dripCampaign}', [DripCampaignController::class, 'update'])
        ->whereNumber('dripCampaign')
        ->name('settings.messaging.drips.update');

    /*
    |--------------------------------------------------------------------------
    | ✅ SETTINGS -> MESSAGING -> DRIP CAMPAIGNS -> STEPS (Step Builder)
    |--------------------------------------------------------------------------
    */
    Route::get('/settings/messaging/drips/{dripCampaign}/steps', [DripCampaignController::class, 'steps'])
        ->whereNumber('dripCampaign')
        ->name('settings.messaging.drips.steps');

    Route::post('/settings/messaging/drips/{dripCampaign}/steps', [DripCampaignController::class, 'storeStep'])
        ->whereNumber('dripCampaign')
        ->name('settings.messaging.drips.steps.store');

    Route::put('/settings/messaging/drips/{dripCampaign}/steps/{dripStep}', [DripCampaignController::class, 'updateStep'])
        ->whereNumber('dripCampaign')
        ->whereNumber('dripStep')
        ->name('settings.messaging.drips.steps.update');

    Route::delete('/settings/messaging/drips/{dripCampaign}/steps/{dripStep}', [DripCampaignController::class, 'destroyStep'])
        ->whereNumber('dripCampaign')
        ->whereNumber('dripStep')
        ->name('settings.messaging.drips.steps.destroy');

    /*
    |--------------------------------------------------------------------------
    | ✅ DRIPS: Enrollment endpoints (single + bulk)
    |--------------------------------------------------------------------------
    */
    Route::prefix('drips')->group(function () {
        Route::post('/enroll', [EnrollmentController::class, 'enroll'])
            ->name('drips.enroll');

        Route::post('/enroll/bulk', [EnrollmentController::class, 'enrollBulk'])
            ->name('drips.enroll.bulk');
    });

    Route::get('/settings/billing', [SettingsController::class, 'billing'])
        ->name('settings.billing');

    /*
    |--------------------------------------------------------------------------
    | BILLING (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');

    /*
    |--------------------------------------------------------------------------
    | CONTACT MESSAGES (Phase 2 + Phase 3 + Bulk)
    |--------------------------------------------------------------------------
    */
    Route::get('/contacts/{contact}/messages', [ContactMessageController::class, 'index'])
        ->name('contacts.messages.index');

    Route::post('/contacts/{contact}/messages', [ContactMessageController::class, 'store'])
        ->name('contacts.messages.store');

    Route::post('/contacts/messages/bulk', [ContactMessageController::class, 'bulkStore'])
        ->name('contacts.messages.bulk-store');

    /*
    |--------------------------------------------------------------------------
    | ✅ ATTACHMENTS (Contacts / Book / Service)
    |--------------------------------------------------------------------------
    | Option A: Attachments belong to the Contact record.
    | Book + Service are just views of the same Contact, so files appear everywhere.
    |
    | Upload is a standard form submit (Option 1).
    */
    Route::post('/contacts/{contact}/attachments', [ContactAttachmentController::class, 'store'])
        ->name('contacts.attachments.store');

    Route::get('/attachments/{attachment}', [ContactAttachmentController::class, 'show'])
        ->whereNumber('attachment')
        ->name('attachments.show');

    Route::get('/attachments/{attachment}/download', [ContactAttachmentController::class, 'download'])
        ->whereNumber('attachment')
        ->name('attachments.download');

    Route::delete('/attachments/{attachment}', [ContactAttachmentController::class, 'destroy'])
        ->whereNumber('attachment')
        ->name('attachments.destroy');

    /*
    |--------------------------------------------------------------------------
    | CONTACTS (FULL CRUD + AJAX RIGHT PANEL)
    |--------------------------------------------------------------------------
    */
    Route::get('/all-contacts', fn () => redirect()->route('contacts.index'));

    Route::get('/contacts/create-panel', fn () => view('contacts.partials.create'))
        ->name('contacts.create.panel');

    Route::resource('contacts', ContactsController::class);

    Route::post('/contacts/import', [ContactsController::class, 'import'])
        ->name('contacts.import');

    /*
    |--------------------------------------------------------------------------
    | CONTACT NOTES
    |--------------------------------------------------------------------------
    */
    Route::get('/contacts/{contact}/notes', [NoteController::class, 'index'])
        ->name('contacts.notes.index');

    Route::post('/contacts/{contact}/notes', [NoteController::class, 'store'])
        ->name('contacts.notes.store');

    Route::get('/contacts/{contact}/notes/list', [NoteController::class, 'list'])
        ->name('contacts.notes.list');

    /*
    |--------------------------------------------------------------------------
    | LEADS
    |--------------------------------------------------------------------------
    */
    Route::get('/leads',          [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/archived', [LeadController::class, 'archived'])->name('leads.archived');
    Route::get('/leads/create',   [LeadController::class, 'create'])->name('leads.create');

    Route::post('/leads/import', [LeadController::class, 'import'])
        ->name('leads.import');

    Route::get('/leads/import/template', [LeadController::class, 'downloadTemplate'])
        ->name('leads.import.template');

    Route::get('/leads/{id}', [LeadController::class, 'show'])->name('leads.show');

    Route::post('/leads/{contact}/sold', [LeadController::class, 'markSold'])
        ->name('leads.sold');

    Route::post('/leads/{contact}/archive', [LeadController::class, 'archive'])
        ->name('leads.archive');

    /*
    |--------------------------------------------------------------------------
    | LEAD NOTES (reuse BookController note logic)
    |--------------------------------------------------------------------------
    */
    Route::post('/leads/{client}/notes', [BookController::class, 'storeNote'])
        ->name('leads.notes.store');

    Route::put('/leads/{client}/notes/{note}', [BookController::class, 'updateNote'])
        ->name('leads.notes.update');

    Route::delete('/leads/{client}/notes/{note}', [BookController::class, 'destroyNote'])
        ->name('leads.notes.destroy');

    /*
    |--------------------------------------------------------------------------
    | BOOK OF BUSINESS
    |--------------------------------------------------------------------------
    */
    Route::prefix('book')->group(function () {

        Route::get('/', [BookController::class, 'index'])->name('book.index');

        Route::get('/open/{client}', function ($client) {
            return redirect()->route('book.index', ['selected' => $client]);
        })->name('book.open');

        Route::get('/create-panel', [BookController::class, 'createPanel'])->name('book.create.panel');
        Route::post('/', [BookController::class, 'store'])->name('book.store');
        Route::get('/{client}', [BookController::class, 'show'])->name('book.show');
        Route::get('/{client}/edit-panel', [BookController::class, 'editPanel'])->name('book.edit.panel');
        Route::put('/{client}', [BookController::class, 'update'])->name('book.update');

        Route::post('/{client}/notes', [BookController::class, 'storeNote'])
            ->name('book.notes.store');

        Route::put('/{client}/notes/{note}', [BookController::class, 'updateNote'])
            ->name('book.notes.update');

        Route::delete('/{client}/notes/{note}', [BookController::class, 'destroyNote'])
            ->name('book.notes.destroy');

        Route::post('/import', [BookController::class, 'import'])->name('book.import');

        Route::post('/{client}/send-to-service', [BookController::class, 'sendToService'])
            ->name('book.send-to-service');
    });

    /*
    |--------------------------------------------------------------------------
    | SERVICE
    |--------------------------------------------------------------------------
    */
    Route::prefix('service')->group(function () {

        Route::get('/', [ServiceController::class, 'index'])->name('service.index');

        Route::get('/open/{client}', function ($client) {
            return redirect()->route('service.index', ['open' => $client]);
        })->name('service.open');

        Route::get('/create-panel', [ServiceController::class, 'createPanel'])->name('service.create.panel');
        Route::post('/', [ServiceController::class, 'store'])->name('service.store');

        Route::post('/import', [ServiceController::class, 'import'])
            ->name('service.import');

        Route::get('/archive', [ServiceController::class, 'archive'])
            ->name('service.archive');

        Route::get('/archive/not-saved', [ServiceController::class, 'notSavedArchive'])
            ->name('service.archive.not-saved');

        Route::get('/{client}', [ServiceController::class, 'show'])->name('service.show');
        Route::get('/{client}/edit-panel', [ServiceController::class, 'editPanel'])->name('service.edit.panel');
        Route::put('/{client}', [ServiceController::class, 'update'])->name('service.update');

        Route::get('/{client}/follow-up', [ServiceController::class, 'followUp'])
            ->name('service.follow-up');

        Route::post('/{client}/saved', [ServiceController::class, 'markSaved'])
            ->name('service.saved');

        Route::post('/{client}/back-on-books', [ServiceController::class, 'markBackOnBooks'])
            ->name('service.back-on-books');

        Route::post('/{client}/not-interested', [ServiceController::class, 'markNotInterested'])
            ->name('service.not-interested');

        Route::post('/{client}/cancelled', [ServiceController::class, 'markCancelled'])
            ->name('service.cancelled');

        Route::post('/{client}/archive', [ServiceController::class, 'archiveSingle'])
            ->name('service.archive-single');
    });

    /*
    |--------------------------------------------------------------------------
    | SERVICE NOTES
    |--------------------------------------------------------------------------
    */
    Route::post('/service/{client}/notes', [BookController::class, 'storeNote'])
        ->name('service.notes.store');

    Route::put('/service/{client}/notes/{note}', [BookController::class, 'updateNote'])
        ->name('service.notes.update');

    Route::delete('/service/{client}/notes/{note}', [BookController::class, 'destroyNote'])
        ->name('service.notes.destroy');

    /*
    |--------------------------------------------------------------------------
    | SERVICE Beneficiary / Emergency DELETE
    |--------------------------------------------------------------------------
    */
    Route::delete('/service/{client}/beneficiaries/{beneficiary}', [BookController::class, 'deleteBeneficiary'])
        ->name('service.beneficiaries.destroy');

    Route::delete('/service/{client}/emergencies/{contact}', [BookController::class, 'deleteEmergency'])
        ->name('service.emergencies.destroy');

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY
    |--------------------------------------------------------------------------
    */
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/activity/popup', [ActivityController::class, 'popup'])->name('activity.popup');
    Route::post('/activity/store', [ActivityController::class, 'store'])->name('activity.store');

    Route::get('/activity/totals/{range}', [ActivityController::class, 'totals'])
        ->name('activity.totals');

    /*
    |--------------------------------------------------------------------------
    | CALENDAR
    |--------------------------------------------------------------------------
    */
    Route::get('/calendar', fn () => view('calendar.index'));

    Route::get('/calendar/events', function (Request $request) {
        return Event::all();
    });

    Route::post('/calendar/events', function (Request $request) {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'start'      => 'required|string',
            'location'   => 'nullable|string|max:255',
            'contact_id' => 'nullable|integer|exists:contacts,id',
        ]);

        $event = Event::create([
            'title'      => $data['title'],
            'start'      => $data['start'],
            'end'        => $data['start'],
            'location'   => $data['location'] ?? null,
            'contact_id' => $data['contact_id'] ?? null,
        ]);

        return response()->json($event, 201);
    });

    Route::put('/calendar/events/{id}', function (Request $request, $id) {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'start'      => 'required|string',
            'location'   => 'nullable|string|max:255',
            'contact_id' => 'nullable|integer|exists:contacts,id',
        ]);

        $event = Event::findOrFail($id);

        $event->update([
            'title'      => $data['title'],
            'start'      => $data['start'],
            'end'        => $data['start'],
            'location'   => $data['location'] ?? null,
            'contact_id' => $data['contact_id'] ?? $event->contact_id,
        ]);

        return response()->json(['success' => true, 'event' => $event]);
    });

    Route::delete('/calendar/events/{id}', function ($id) {
        Event::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    });

    /*
    |--------------------------------------------------------------------------
    | GIDEON
    |--------------------------------------------------------------------------
    */
    Route::get('/gideon/opportunities', [GideonOpportunitiesController::class, 'index'])
        ->name('gideon.opportunities.index');

    Route::get('/gideon/opportunities/groups', [GideonOpportunitiesController::class, 'groups'])
        ->name('gideon.opportunities.groups');

    Route::get('/gideon/opportunities/group-items', [GideonOpportunitiesController::class, 'groupItems'])
        ->name('gideon.opportunities.groupItems');

    Route::prefix('gideon/opportunities')->group(function () {
        Route::post('/{opportunity}/complete', [GideonOpportunitiesController::class, 'complete'])
            ->name('gideon.opportunities.complete');

        Route::post('/{opportunity}/snooze', [GideonOpportunitiesController::class, 'snooze'])
            ->name('gideon.opportunities.snooze');

        Route::post('/{opportunity}/unsnooze', [GideonOpportunitiesController::class, 'unsnooze'])
            ->name('gideon.opportunities.unsnooze');

        Route::post('/{opportunity}/dismiss', [GideonOpportunitiesController::class, 'dismiss'])
            ->name('gideon.opportunities.dismiss');
    });

    Route::prefix('gideon')->group(function () {
        Route::post('/scan', [GideonScanController::class, 'scan'])->name('gideon.scan');
        Route::post('/scan/deep', [GideonScanController::class, 'deepScan'])->name('gideon.scan.deep');
        Route::get('/top', [GideonScanController::class, 'top'])->name('gideon.top');
    });

    Route::get('/gideon/second-brain', [GideonInsightsController::class, 'index'])
        ->name('gideon.second_brain');

    Route::get('/debug-gideon-create-opportunity', function () {
        $user = auth()->user();
        $agencyId = $user->agency_id ?? 1;

        $opp = GideonOpportunity::create([
            'agency_id'           => $agencyId,
            'user_id'             => $user->id ?? null,
            'entity_type'         => 'debug',
            'entity_id'           => null,
            'category'            => 'test',
            'title'               => 'Test Gideon Opportunity',
            'short_reason'        => 'This is a fake opportunity created to verify the Gideon DB wiring.',
            'recommended_action'  => 'No action needed – this is only a test.',
            'score'               => 50,
            'status'              => 'open',
            'source_snapshot'     => [
                'note' => 'Created by /debug-gideon-create-opportunity route.',
            ],
        ]);

        return response()->json($opp);
    });

    Route::get('/gideon/run-opportunity-scan', function (OpportunityScanner $scanner) {
        $user = auth()->user();
        $result = $scanner->runForUser($user);
        return response()->json($result);
    });

    /*
    |--------------------------------------------------------------------------
    | MAINTENANCE
    |--------------------------------------------------------------------------
    */
    Route::get('/migrate', function () {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            return nl2br(e("MIGRATE OUTPUT:\n\n" . $output));
        } catch (\Throwable $e) {
            return nl2br(e(
                "MIGRATION ERROR:\n\n" .
                $e->getMessage() . "\n\n" .
                $e->getTraceAsString()
            ));
        }
    });

    Route::get('/debug-gideon-schema', function () {
        return Schema::hasTable('gideon_opportunities')
            ? 'gideon_opportunities table EXISTS'
            : 'gideon_opportunities table DOES NOT EXIST';
    });

    Route::get('/clear-cache', fn () => tap('Laravel cache cleared!', function () {
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
    }));
});
