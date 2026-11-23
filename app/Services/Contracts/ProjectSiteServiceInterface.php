<?php

namespace App\Services\Contracts;

use App\Models\Client;
use App\Models\ProjectSite;
use Illuminate\Database\Eloquent\Collection;

interface ProjectSiteServiceInterface
{
    /**
     * Retrieve all project sites.
     *
     * @return Collection<int, ProjectSite>
     */
    public function getAllSites(): Collection;

    /**
     * Retrieve all clients.
     *
     * @return Collection<int, Client>
     */
    public function getAllClients(): Collection;

    /**
     * Find a project site by ID.
     */
    public function getSiteById(int $id): ?ProjectSite;

    /**
     * Create a new project site and client record.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSite(array $data): ProjectSite;

    /**
     * Update an existing project site and client record.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSite(int $id, array $data): ProjectSite;

    /**
     * Get summary statistics for project sites.
     *
     * @return array<string, int>
     */
    public function getSiteStats(): array;
}
