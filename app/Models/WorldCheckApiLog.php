<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldCheckApiLog extends Model
{
    protected $table = 'worldcheck_api_logs';

    protected $fillable = [
        'user_id',
        'client_id',
        'client_type',
        'client_name',
        'entity_label',
        'method',
        'endpoint',
        'url',
        'http_status',
        'duration_ms',
        'attempt',
        'request_payload',
        'request_headers',
        'response_body',
        'response_headers',
        'error_message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function clientEditUrl(): ?string
    {
        if (!$this->client_id) {
            return null;
        }

        $type = $this->client_type ?: optional($this->client)->type;
        if ($type === 'company') {
            return url('/client-management/clients/company/edit/' . $this->client_id);
        }

        return url('/client-management/clients/individual/edit/' . $this->client_id . '/');
    }

    public function displayClientName(): string
    {
        return $this->client_name
            ?: optional($this->client)->name
            ?: ($this->entity_label ?: '—');
    }

    public function getDecodedRequestPayloadAttribute()
    {
        $raw = $this->request_payload;
        if ($raw === null || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }

    public function getDecodedResponseBodyAttribute()
    {
        $raw = $this->response_body;
        if ($raw === null || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }
};
