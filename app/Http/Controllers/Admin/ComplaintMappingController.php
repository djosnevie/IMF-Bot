<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplaintType;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Http\Request;

class ComplaintMappingController extends Controller
{
    public function index()
    {
        $complaintTypes = ComplaintType::where('is_active', true)
            ->with('users')
            ->get();

        // Let's assume agents have the 'manage_complaints' permission. 
        // If not found, fallback to all users.
        try {
            $agents = User::permission('manage_complaints')->get();
        } catch (\Exception $e) {
            $agents = User::all();
        }

        return view('admin.complaint-mappings.index', compact('complaintTypes', 'agents'));
    }

    public function syncAgents(Request $request, ComplaintType $complaintType, WebhookService $webhookService)
    {
        $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->input('user_ids', []);

        $changes = $complaintType->users()->sync($userIds);

        // 1. WhatsApp Notifications for newly attached agents
        if (!empty($changes['attached'])) {
            $newAgents = User::whereIn('id', $changes['attached'])->get();
            $message = "Vous avez été affecté au traitement des plaintes de type *{$complaintType->name}*.";

            foreach ($newAgents as $agent) {
                if (!empty($agent->whatsapp_number)) {
                    $webhookService->sendWhatsAppMessage($agent->whatsapp_number, $message);
                }
            }
        }

        // 2. Activity Log
        if (function_exists('activity')) {
            activity()
                ->performedOn($complaintType)
                ->causedBy(auth()->user())
                ->withProperties([
                    'attached' => $changes['attached'],
                    'detached' => $changes['detached'],
                    'updated' => $changes['updated'],
                ])
                ->log('agents_synced');
        }

        return redirect()->route('admin.complaint-mappings.index')
            ->with('success', "Agents mis à jour avec succès pour la catégorie {$complaintType->name}");
    }
}
