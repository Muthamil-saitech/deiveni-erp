<?php

namespace App\Repositories\Eloquent;

use App\Models\Client;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ClientRepository implements ClientRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Retrieve all clients ordered alphabetically by name.
     *
     * @return Collection<int, Client>
     */
    public function all(): Collection
    {
        return $this->scopeByCreator(Client::query()->withCount('projectSites'))
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Find a client by ID.
     */
    public function findById(int $id): ?Client
    {
        return Client::query()->find($id);
    }

    /**
     * Find a client by phone number.
     */
    public function findByPhone(string $phone): ?Client
    {
        return $this->scopeByCreator(Client::query())
            ->where('phone', trim($phone))
            ->first();
    }

    /**
     * Find a client by name and phone.
     */
    public function findByNameAndPhone(string $name, string $phone): ?Client
    {
        return $this->scopeByCreator(Client::query())
            ->where('name', trim($name))
            ->where('phone', trim($phone))
            ->first();
    }

    /**
     * Create a new client record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client
    {
        return Client::query()->create($data);
    }

    /**
     * Update an existing client record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client->refresh();
    }

    /**
     * Delete a client record.
     */
    public function delete(Client $client): bool
    {
        return (bool) $client->delete();
    }
}
