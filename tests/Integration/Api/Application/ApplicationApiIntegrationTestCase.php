<?php

namespace Pterodactyl\Tests\Integration\Api\Application;

use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Tests\Traits\Integration\CreatesTestModels;
use Pterodactyl\Tests\Traits\Http\IntegrationJsonRequestAssertions;

abstract class ApplicationApiIntegrationTestCase extends IntegrationTestCase
{
    use CreatesTestModels;
    use DatabaseTransactions;
    use IntegrationJsonRequestAssertions;

    /**
     * @var \Pterodactyl\Models\ApiKey
     */
    private $key;

    /**
     * @var \Pterodactyl\Models\User
     */
    private $user;

    /**
     * Bootstrap application API tests. Creates a default admin user and associated API key
     * and also sets some default headers required for accessing the API.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createApiUser();
        $this->key = $this->createApiKey($this->user);

        $this->withHeader('Accept', 'application/vnd.pterodactyl.v1+json');
        $this->withHeader('Authorization', 'Bearer ' . $this->getApiKey()->identifier . decrypt($this->getApiKey()->token));

        $this->withMiddleware('api..key:' . ApiKey::TYPE_APPLICATION);
    }

    /**
     * @return \Pterodactyl\Models\User
     */
    public function getApiUser(): User
    {
        return $this->user;
    }

    /**
     * @return \Pterodactyl\Models\ApiKey
     */
    public function getApiKey(): ApiKey
    {
        return $this->key;
    }

    /**
     * Creates a new default API key and refreshes the headers using it.
     *
     * @param \Pterodactyl\Models\User $user
     * @param array $permissions
     *
     * @return \Pterodactyl\Models\ApiKey
     */
    protected function createNewDefaultApiKey(User $user, array $permissions = []): ApiKey
    {
        $this->key = $this->createApiKey($user, $permissions);
        $this->refreshHeaders($this->key);

        return $this->key;
    }

    /**
     * Refresh the authorization header for a request to use a different API key.
     *
     * @param \Pterodactyl\Models\ApiKey $key
     */
    protected function refreshHeaders(ApiKey $key)
    {
        $this->withHeader('Authorization', 'Bearer ' . $key->identifier . decrypt($key->token));
    }

    /**
     * Create an administrative user.
     *
     * @return \Pterodactyl\Models\User
     */
    protected function createApiUser(): User
    {
        return User::factory()->create([
            'root_admin' => true,
        ]);
    }

    /**
     * Create a new application API key for a given user model.
     *
     * @param \Pterodactyl\Models\User $user
     * @param array $permissions
     *
     * @return \Pterodactyl\Models\ApiKey
     */
    protected function createApiKey(User $user, array $permissions = []): ApiKey
    {
        return ApiKey::factory()->create(array_merge([
            'user_id' => $user->id,
            'key_type' => ApiKey::TYPE_APPLICATION,
            'r_servers' => AdminAcl::READ | AdminAcl::WRITE,
            'r_nodes' => AdminAcl::READ | AdminAcl::WRITE,
            'r_allocations' => AdminAcl::READ | AdminAcl::WRITE,
            'r_users' => AdminAcl::READ | AdminAcl::WRITE,
            'r_locations' => AdminAcl::READ | AdminAcl::WRITE,
            'r_nests' => AdminAcl::READ | AdminAcl::WRITE,
            'r_eggs' => AdminAcl::READ | AdminAcl::WRITE,
            'r_database_hosts' => AdminAcl::READ | AdminAcl::WRITE,
            'r_server_databases' => AdminAcl::READ | AdminAcl::WRITE,
        ], $permissions));
    }

    /**
     * Return a transformer that can be used for testing purposes.
     *
     * @param string $abstract
     *
     * @return \Pterodactyl\Transformers\Api\Transformer
     */
    protected function getTransformer(string $abstract): Transformer
    {
        return new $abstract;
    }
}
