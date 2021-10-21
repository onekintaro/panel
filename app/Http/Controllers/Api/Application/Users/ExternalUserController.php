<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Users;

use Pterodactyl\Transformers\Api\Application\UserTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Users\GetExternalUserRequest;

use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\User;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\ApiKeyTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\StoreApiKeyRequest;
use Pterodactyl\Services\Api\KeyCreationService;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Repositories\Eloquent\ApiKeyRepository;

class ExternalUserController extends ApplicationApiController
{
    /**
     * Retrieve a specific user from the database using their external ID.
     */
    public function index(GetExternalUserRequest $request): array
    {
        return $this->fractal->item($request->getUserModel())
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toArray();
    }

     /**
     * Store a new API key for a user's account.
     *
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreApiKeyRequest $request, $external_id)
    {
        $uid = User::where(['external_id' => $external_id])->first()->id;
        $keys = ApiKey::where(['user_id' => $uid, 'memo' => 'frontend key'])->get();

        if ($keys->count() >= 5) {
            throw new DisplayException('You have reached the account limit for number of API keys.');
        }

        $key = $this->keyCreationService->setKeyType(ApiKey::TYPE_ACCOUNT)->handle([
            'user_id' => $uid,
            'memo' => $request->input('description'),
            'allowed_ips' => [],
        ]);

        return $this->fractal->item($key)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->addMeta([
                'secret_token' => $this->encrypter->decrypt($key->token),
            ])
            ->toArray();
    }

    public function indexApi(ClientApiRequest $request, $external_id)
    {
        $uid = User::where(['external_id' => $external_id])->first()->id;
        $keys = ApiKey::where(['user_id' => $uid, 'memo' => 'frontend key'])->get();
        //dd($keys);

        return $this->fractal->collection($keys)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->toArray();
    }
}
