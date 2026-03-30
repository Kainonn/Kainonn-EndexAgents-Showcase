<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignRunController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadReviewController;
use App\Http\Controllers\LeadWorkspaceController;
use App\Http\Controllers\ProspectoController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('endex')->name('endex.')->group(function () {
        Route::get('campaigns/builder', [CampaignController::class, 'builder'])->name('campaigns.builder');
        Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store');

        Route::get('campaigns/{campaign}/run', [CampaignRunController::class, 'show'])->name('campaigns.run.show');
        Route::post('campaigns/{campaign}/run', [CampaignRunController::class, 'start'])->name('campaigns.run.start');

        Route::get('prospectos', [ProspectoController::class, 'index'])->name('prospectos.index');
        Route::get('prospectos/lista-estatus', [ProspectoController::class, 'statusList'])->name('prospectos.status-list');
        Route::post('prospectos/analyze', [ProspectoController::class, 'analyze'])->name('prospectos.analyze');
        Route::patch('prospectos/{prospecto}/status', [ProspectoController::class, 'updateStatus'])->name('prospectos.update-status');

        Route::get('leads/inbox', [LeadController::class, 'inbox'])->name('leads.inbox');
        Route::get('leads/inbox/export-csv', [LeadController::class, 'exportCsv'])->name('leads.inbox.export-csv');
        Route::get('leads/{lead}/review', [LeadReviewController::class, 'show'])->name('leads.review.show');
        Route::post('leads/{lead}/review/reprocess', [LeadReviewController::class, 'reprocess'])->name('leads.review.reprocess');
        Route::get('leads/{lead}/review/progress', [LeadReviewController::class, 'progress'])->name('leads.review.progress');
        Route::post('leads/{lead}/review', [LeadReviewController::class, 'store'])->name('leads.review.store');
        Route::patch('leads/{lead}/commercial-status', [LeadReviewController::class, 'updateCommercialStatus'])->name('leads.commercial-status.update');
        Route::post('leads/{lead}/feedback', [LeadReviewController::class, 'storeFeedback'])->name('leads.feedback.store');
        Route::patch('leads/{lead}/message', [LeadReviewController::class, 'updateMessage'])->name('leads.message.update');
        Route::post('leads/{lead}/send-message', [LeadReviewController::class, 'sendMessage'])->name('leads.message.send');

        // Centro de Ataque Comercial
        Route::get('workspace', [LeadWorkspaceController::class, 'index'])->name('workspace.index');
        Route::get('workspace/leads/{lead}/detail', [LeadWorkspaceController::class, 'quickDetail'])->name('workspace.quick-detail');
        Route::patch('workspace/leads/{lead}/status', [LeadWorkspaceController::class, 'updateStatus'])->name('workspace.update-status');
        Route::patch('workspace/leads/{lead}/note', [LeadWorkspaceController::class, 'saveNote'])->name('workspace.save-note');
        Route::post('workspace/leads/{lead}/action', [LeadWorkspaceController::class, 'registerAction'])->name('workspace.register-action');
        Route::post('workspace/leads/{lead}/follow-up', [LeadWorkspaceController::class, 'scheduleFollowUp'])->name('workspace.schedule-follow-up');
        Route::get('workspace/leads/{lead}/prepare-contact', [LeadWorkspaceController::class, 'prepareContact'])->name('workspace.prepare-contact');
        Route::patch('workspace/leads/{lead}/message', [LeadWorkspaceController::class, 'updateMessage'])->name('workspace.update-message');
        Route::patch('workspace/leads/{lead}/contacts', [LeadWorkspaceController::class, 'saveContacts'])->name('workspace.save-contacts');
    });
});

require __DIR__.'/settings.php';
