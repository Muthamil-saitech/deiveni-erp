<?php

namespace App\Repositories\Contracts;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

interface ClientRepositoryInterface
{
    /**
     * Retrieve all clients ordered alphabetically by name.
     *
     * @return Collection<int, Client>
     */
    public function all(): Collection;

    /**
     * Find a client by ID.
     */
    public function findById(int $id): ?Client;

    /**
     * Find a client by phone number.
     */
    public function findByPhone(string $phone): ?Client;

    /**
     * Find a client by name and phone.
     */
    public function findByNameAndPhone(string $name, string $phone): ?Client;

    /**
     * Create a new client record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client;

    /**
     * Update an existing client record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Client $client, array $data): Client;

    /**
     * Delete a client record.
     */
    public function delete(Client $client): bool;
}
