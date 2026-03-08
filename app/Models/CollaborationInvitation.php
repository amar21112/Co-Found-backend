<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollaborationInvitation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'collaboration_invitations';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'project_id',
        'invitation_type',
        'role',
        'message',
        'status',
        'expires_at',
        'responded_at',
        'response_message'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                    ->where('expires_at', '<=', now());
            });
    }

    public function scopeBySender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    public function scopeByRecipient($query, $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    public function accept($responseMessage = null)
    {
        $this->status = 'accepted';
        $this->responded_at = now();
        $this->response_message = $responseMessage;
        $this->save();

        // Create connection if it's a collaboration request
        if ($this->invitation_type === 'collaboration_request') {
            UserConnection::create([
                'requester_id' => $this->sender_id,
                'recipient_id' => $this->recipient_id,
                'status' => 'accepted',
                'connection_type' => 'collaborator'
            ]);
        }
    }

    public function decline($responseMessage = null)
    {
        $this->status = 'declined';
        $this->responded_at = now();
        $this->response_message = $responseMessage;
        $this->save();
    }

    public function expire()
    {
        $this->status = 'expired';
        $this->save();
    }

    public function withdraw()
    {
        $this->status = 'withdrawn';
        $this->save();
    }

    public function isPending()
    {
        return $this->status === 'pending' &&
            (!$this->expires_at || $this->expires_at > now());
    }

    public function isExpired()
    {
        return $this->status === 'expired' ||
            ($this->status === 'pending' && $this->expires_at && $this->expires_at <= now());
    }
}
