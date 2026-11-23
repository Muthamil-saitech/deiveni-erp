<?php

namespace App\Repositories\Contracts;

use App\Models\ProjectSite;
use Illuminate\Database\Eloquent\Collection;

interface ProjectSiteRepositoryInterface
{
    /**
     * Retrieve all project sites with client details ordered newest first.
     *
     * @return Collection<int, ProjectSite>
     */
    public function all(): Collection;

    /**
     * Find a project site by ID with client details.
     */
    public function findById(int $id): ?ProjectSite;

    /**
     * Create a new project site record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProjectSite;

    /**
     * Update an existing project site record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectSite $site, array $data): ProjectSite;

    /**
     * Get statistics for project sites.
     *
     * @return array<string, int>
     */
    public function getStats(): array;
}
