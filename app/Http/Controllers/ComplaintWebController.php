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
    public function showForm(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return view('complaints.error', ['message' => 'Ce lien a expiré ou est invalide pour des raisons de sécurité. Veuillez demander un nouveau lien sur WhatsApp.']);
        }

        $nonce = $request->query('nonce');
        if (!Cache::has('complaint_nonce_' . $nonce)) {
            return view('complaints.error', ['message' => 'Ce formulaire a déjà été soumis avec succès pour cette requête.']);
        }

        $complaintTypes = ComplaintType::where('is_active', true)->get();

        return view('complaints.web_form', [
            'user_identifier' => $request->query('user_identifier'),
            'conversation_id' => $request->query('conversation_id'),
            'nonce' => $nonce,
            'complaintTypes' => $complaintTypes,
        ]);
    }

    public function submitForm(Request $request, ComplaintService $complaintService, WebhookService $webhookService)
    {
        if (!$request->hasValidSignature()) {
            return view('complaints.error', ['message' => 'Ce lien a expiré ou est invalide.']);
        }

        $nonce = $request->input('nonce');
        if (!Cache::has('complaint_nonce_' . $nonce)) {
            return view('complaints.error', ['message' => 'Ce formulaire a déjà été soumis avec succès pour cette requête.']);
        }

        $validated = $request->validate([
            'user_identifier' => 'required|string',
            'conversation_id' => 'required|integer',
            'complaint_type_code' => 'nullable|string',
            'urgency' => 'nullable|string|in:low,medium,high',
            'description' => 'required|string|max:2000',
        ]);

        $conversation = Conversation::find($validated['conversation_id']);
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

        // Invalidate nonce
        Cache::forget('complaint_nonce_' . $nonce);
        
        // Clean up flow metadata if it exists
        $metadata = $conversation->metadata;
        if (isset($metadata['complaint_flow'])) {
            unset($metadata['complaint_flow']);
            $conversation->update(['metadata' => $metadata]);
        }

        // Send WhatsApp confirmation
        $confirmationMessage = "Votre demande a été enregistrée avec succès sous la référence *" . $ticket->reference . "*. Notre équipe l'examinera dans les plus brefs délais.";
        $webhookService->sendWhatsAppMessage($validated['user_identifier'], $confirmationMessage);

        return view('complaints.success', [
            'reference' => $ticket->reference
        ]);
    }
}
