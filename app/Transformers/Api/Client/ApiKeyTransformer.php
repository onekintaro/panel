<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\ApiKey;
use Illuminate\Contracts\Encryption\Encrypter;

class ApiKeyTransformer extends BaseClientTransformer
{
    public function __construct(
        Encrypter $encrypter,
    ) {
        parent::__construct();

        $this->encrypter = $encrypter;
    }
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return ApiKey::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     *
     * @return array
     */
    public function transform(ApiKey $model)
    {
        return [
            'identifier' => $model->identifier,
            'description' => $model->memo,
            'token' => $model->identifier . $this->encrypter->decrypt($model->token),
            'allowed_ips' => $model->allowed_ips,
            'last_used_at' => $model->last_used_at ? $model->last_used_at->toIso8601String() : null,
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }
}
