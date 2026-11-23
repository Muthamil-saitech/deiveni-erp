<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectSite;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\ProjectSiteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProjectSiteRepository implements ProjectSiteRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Retrieve all project sites with client details ordered newest first.
     *
     * @return Collection<int, ProjectSite>
     */
    public function all(): Collection
    {
        return $this->scopeByCreator(ProjectSite::query()->with('client'))
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Find a project site by ID with client details.
     */
    public function findById(int $id): ?ProjectSite
    {
        return $this->scopeByCreator(
            ProjectSite::query()->with([
                'client',
                'siteCashes' => function ($query) {
                    $query->with('supplier')
                        ->orderBy('payment_date', 'desc')
                        ->orderBy('id', 'desc');
                },
                'dailyAllocations' => function ($query) {
                    $query->with(['creator', 'items.workerCategory', 'expenses'])
                        ->orderBy('date', 'desc')
                        ->orderBy('id', 'desc');
                },
                'materialUsages' => function ($query) {
                    $query->with(['creator', 'items.material', 'purchaseOrder.supplier'])
                        ->orderBy('usage_date', 'desc')
                        ->orderBy('id', 'desc');
                },
            ])
        )->find($id);
    }

    /**
     * Create a new project site record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProjectSite
    {
        return ProjectSite::query()->create($data)->load('client');
    }

    /**
     * Update an existing project site record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectSite $site, array $data): ProjectSite
    {
        $site->update($data);

        return $site->refresh()->load('client');
    }

    /**
     * Get statistics for project sites.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $sites = $this->scopeByCreator(ProjectSite::query())->get();

        return [
            'total' => $sites->count(),
            'in_progress' => $sites->where('status', 'In Progress')->count(),
            'planning' => $sites->where('status', 'Planning')->count(),
            'completed' => $sites->where('status', 'Completed')->count(),
            'on_hold' => $sites->where('status', 'On Hold')->count(),
        ];
    }
}
