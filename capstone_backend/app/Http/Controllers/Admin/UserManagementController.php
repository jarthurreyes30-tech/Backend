<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ReactivationRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class UserManagementController extends Controller
{
    /**
     * Get all reactivation requests
     */
    public function getReactivationRequests(Request $request)
    {
        $requests = ReactivationRequest::with(['user', 'reviewer'])
            ->orderBy('requested_at', 'desc')
            ->paginate(20);

        return response()->json($requests);
    }

    /**
     * Approve reactivation request
     */
    public function approveReactivation(Request $request, $id)
    {
        $reactivationRequest = ReactivationRequest::with('user')->findOrFail($id);

        if ($reactivationRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request has already been processed'
            ], 422);
        }

        $user = $reactivationRequest->user;

        // Reactivate the user account
        $user->update(['status' => 'active']);

        // Update reactivation request
        $reactivationRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'admin_notes' => $request->input('notes')
        ]);

        // Send email to user immediately
        Mail::to($user->email)->send(
            new \App\Mail\Security\AccountReactivatedMail($user)
        );

        // Notify user via database notification
        \App\Services\NotificationHelper::accountReactivated($user);

        return response()->json([
            'success' => true,
            'message' => 'Account reactivated successfully'
        ]);
    }

    /**
     * Reject reactivation request
     */
    public function rejectReactivation(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:1000'
        ]);

        $reactivationRequest = ReactivationRequest::with('user')->findOrFail($id);

        if ($reactivationRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request has already been processed'
            ], 422);
        }

        // Update reactivation request
        $reactivationRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'admin_notes' => $validated['notes']
        ]);

        // Send rejection email to user immediately
        Mail::to($reactivationRequest->user->email)->send(
            new \App\Mail\Security\ReactivationRejectedMail($reactivationRequest->user, $validated['notes'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Reactivation request rejected'
        ]);
    }

    public function destroyUser(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);
            \App\Models\Charity::where('owner_id', $user->id)->update(['owner_id' => null]);
            // Reassign uploaded_by to the acting admin to satisfy NOT NULL FK
            DB::table('charity_documents')->where('uploaded_by', $user->id)->update(['uploaded_by' => $request->user()->id]);
            // Set nullable reviewer/moderator columns to NULL
            DB::table('charity_documents')->where('verified_by', $user->id)->update(['verified_by' => null]);
            DB::table('refund_requests')->where('reviewed_by', $user->id)->update(['reviewed_by' => null]);
            DB::table('account_retrieval_requests')->where('reviewed_by', $user->id)->update(['reviewed_by' => null]);
            DB::table('support_tickets')->where('assigned_to', $user->id)->update(['assigned_to' => null]);
            DB::table('campaign_comments')->where('moderated_by', $user->id)->update(['moderated_by' => null]);
            DB::table('reports')->where('reviewed_by', $user->id)->update(['reviewed_by' => null]);
            // Donor references can be nulled safely
            DB::table('donations')->where('donor_id', $user->id)->update(['donor_id' => null]);
            $user->delete();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function runMigrations(Request $request)
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            return response()->json([
                'success' => true,
                'output' => Artisan::output(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
