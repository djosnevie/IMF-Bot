<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use App\Models\Conversation;
use App\Models\ComplaintType;
use App\Services\ComplaintService;
use App\Services\WebhookService;

class ComplaintWebController extends Controller
{
    public function showForm(Request $request, $token)
    {
        $data = Cache::get('complaint_token_' . $token);
        if (!$data) {
            return view('complaints.error', ['message' => 'Ce lien a expiré, est invalide, ou le formulaire a déjà été soumis. Veuillez demander un nouveau lien sur WhatsApp.']);
        }

        $complaintTypes = ComplaintType::where('is_active', true)->get();

        return view('complaints.web_form', [
            'user_identifier' => $data['user_identifier'],
            'conversation_id' => $data['conversation_id'],
            'token' => $token,
            'complaintTypes' => $complaintTypes,
        ]);
    }

    public function submitForm(Request $request, ComplaintService $complaintService, WebhookService $webhookService, $token)
    {
        $data = Cache::get('complaint_token_' . $token);
        if (!$data) {
            return view('complaints.error', ['message' => 'Ce lien a expiré, est invalide, ou le formulaire a déjà été soumis.']);
        }

        $validated = $request->validate([
            'complaint_type_code' => 'nullable|string',
            'urgency' => 'nullable|string|in:low,medium,high',
            'description' => 'required|string|max:2000',
        ]);

        $conversation = Conversation::find($data['conversation_id']);
        if (!$conversation) {
            return view('complaints.error', ['message' => 'Conversation introuvable.']);
        }

        // Create ticket
        $ticket = $complaintService->createFromWeb(
            $conversation,
            $validated['complaint_type_code'] ?? null,
            $validated['description'],
            $validated['urgency'] ?? 'medium'
        );

        // Invalidate token
        Cache::forget('complaint_token_' . $token);
        
        // Clean up flow metadata if it exists
        $metadata = $conversation->metadata;
        if (isset($metadata['complaint_flow'])) {
            unset($metadata['complaint_flow']);
            $conversation->update(['metadata' => $metadata]);
        }

        // Send WhatsApp confirmation
        $confirmationMessage = "Votre demande a été enregistrée avec succès sous la référence *" . $ticket->reference . "*. Notre équipe l'examinera dans les plus brefs délais.";
        $webhookService->sendWhatsAppMessage($conversation->user_identifier, $confirmationMessage);

        return view('complaints.success', [
            'reference' => $ticket->reference
        ]);
    }
}
