<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ProjectSite;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ProjectSiteRepositoryInterface;
use App\Services\Contracts\ProjectSiteServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectSiteService implements ProjectSiteServiceInterface
{
    public function __construct(
        public ProjectSiteRepositoryInterface $projectSiteRepository,
        public ClientRepositoryInterface $clientRepository
    ) {}

    /**
     * Retrieve all project sites with client relation.
     *
     * @return Collection<int, ProjectSite>
     */
    public function getAllSites(): Collection
    {
        return $this->projectSiteRepository->all();
    }

    /**
     * Retrieve all clients.
     *
     * @return Collection<int, Client>
     */
    public function getAllClients(): Collection
    {
        return $this->clientRepository->all();
    }

    /**
     * Find a project site by ID.
     */
    public function getSiteById(int $id): ?ProjectSite
    {
        return $this->projectSiteRepository->findById($id);
    }

    /**
     * Create a new project site and handle client creation/linking.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSite(array $data): ProjectSite
    {
        return DB::transaction(function () use ($data) {
            $clientId = null;

            // 1. If an existing client ID was provided
            if (! empty($data['client_id'])) {
                $client = $this->clientRepository->findById((int) $data['client_id']);
                if ($client) {
                    $clientId = $client->id;
                }
            }

            // 2. If no valid existing client ID was chosen, create or find client from input
            if (! $clientId && ! empty($data['client_name'])) {
                $clientName = trim((string) $data['client_name']);
                $clientPhone = trim((string) ($data['client_phone'] ?? ''));
                $clientAddress = ! empty($data['client_address']) ? trim((string) $data['client_address']) : null;

                $client = $this->clientRepository->findByNameAndPhone($clientName, $clientPhone);

                if (! $client) {
                    $client = $this->clientRepository->create([
                        'name' => $clientName,
                        'phone' => $clientPhone,
                        'address' => $clientAddress,
                        'created_by' => $data['created_by'] ?? Auth::id(),
                    ]);
                }

                $clientId = $client->id;
            }

            if (! $clientId) {
                throw new \InvalidArgumentException('A valid client or client details must be provided.');
            }

            // 3. Create the project site record
            return $this->projectSiteRepository->create([
                'client_id' => $clientId,
                'name' => trim((string) $data['name']),
                'location' => trim((string) $data['location']),
                'work_type' => $data['work_type'],
                'start_date' => ! empty($data['start_date']) ? $data['start_date'] : null,
                'end_date' => ! empty($data['end_date']) ? $data['end_date'] : null,
                'estimated_budget' => $data['estimated_budget'] ?? 0,
                'spent' => 0.00,
                'status' => $data['status'] ?? 'In Progress',
                'created_by' => $data['created_by'] ?? Auth::id(),
            ]);
        });
    }

    /**
     * Update an existing project site and client record.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSite(int $id, array $data): ProjectSite
    {
        return DB::transaction(function () use ($id, $data) {
            $site = $this->projectSiteRepository->findById($id);

            if (! $site) {
                throw new ModelNotFoundException("Project site with ID {$id} not found.");
            }

            $clientId = $site->client_id;

            // 1. If existing client ID explicitly changed
            if (! empty($data['client_id'])) {
                $chosenClient = $this->clientRepository->findById((int) $data['client_id']);
                if ($chosenClient) {
                    $clientId = $chosenClient->id;
                }
            } elseif (! empty($data['client_name'])) {
                // Update client details or link
                $clientName = trim((string) $data['client_name']);
                $clientPhone = trim((string) ($data['client_phone'] ?? ''));
                $clientAddress = ! empty($data['client_address']) ? trim((string) $data['client_address']) : null;

                if ($site->client) {
                    $this->clientRepository->update($site->client, [
                        'name' => $clientName,
                        'phone' => $clientPhone,
                        'address' => $clientAddress,
                    ]);
                    $clientId = $site->client->id;
                } else {
                    $newClient = $this->clientRepository->create([
                        'name' => $clientName,
                        'phone' => $clientPhone,
                        'address' => $clientAddress,
                    ]);
                    $clientId = $newClient->id;
                }
            }

            return $this->projectSiteRepository->update($site, [
                'client_id' => $clientId,
                'name' => trim((string) $data['name']),
                'location' => trim((string) $data['location']),
                'work_type' => $data['work_type'],
                'start_date' => ! empty($data['start_date']) ? $data['start_date'] : null,
                'end_date' => ! empty($data['end_date']) ? $data['end_date'] : null,
                'estimated_budget' => $data['estimated_budget'] ?? $site->estimated_budget,
                'status' => $data['status'] ?? $site->status,
            ]);
        });
    }

    /**
     * Get summary statistics for project sites.
     *
     * @return array<string, int>
     */
    public function getSiteStats(): array
    {
        return $this->projectSiteRepository->getStats();
    }
}
