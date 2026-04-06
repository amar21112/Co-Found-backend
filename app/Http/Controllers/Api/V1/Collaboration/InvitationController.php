<?php

namespace App\Http\Controllers\Api\V1\Collaboration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collaboration\RespondInvitationRequest;
use App\Http\Requests\Collaboration\SendInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\CollaborationInvitation;
use App\Services\InvitationService;
use App\Traits\ResolvesUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    use ResolvesUser;

    public function __construct(private readonly InvitationService $invitationService) {}

    // =========================================================================
    // GET /api/invitations
    // =========================================================================

    /**
     * List all sent and received invitations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        ['sent' => $sent, 'received' => $received] = $this->invitationService->list($user);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'sent'     => InvitationResource::collection($sent),
                'received' => InvitationResource::collection($received),
            ],
        ]);
    }

    // =========================================================================
    // POST /api/invitations
    // =========================================================================

    /**
     * Send a new invitation.
     */
    public function store(SendInvitationRequest $request): JsonResponse
    {
        $user       = $this->resolveUser($request);
        $invitation = $this->invitationService->send($user, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Invitation sent successfully.',
            'data'    => new InvitationResource(
                $invitation->load(['sender', 'recipient', 'project'])
            ),
        ], 201);
    }

    // =========================================================================
    // PATCH /api/invitations/{invitation}/respond
    // =========================================================================

    /**
     * Respond to a received invitation (accept or decline).
     */
    public function respond(RespondInvitationRequest $request, CollaborationInvitation $invitation): JsonResponse
    {
        $user       = $this->resolveUser($request);
        $invitation = $this->invitationService->respond($user, $invitation, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Invitation ' . $invitation->status . ' successfully.',
            'data'    => new InvitationResource($invitation),
        ]);
    }

    // =========================================================================
    // PATCH /api/invitations/{invitation}/withdraw
    // =========================================================================

    /**
     * Withdraw a sent pending invitation.
     */
    public function withdraw(Request $request, CollaborationInvitation $invitation): JsonResponse
    {
        $user = $this->resolveUser($request);
        $this->invitationService->withdraw($user, $invitation);

        return response()->json([
            'status'  => 'success',
            'message' => 'Invitation withdrawn successfully.',
        ]);
    }
}
