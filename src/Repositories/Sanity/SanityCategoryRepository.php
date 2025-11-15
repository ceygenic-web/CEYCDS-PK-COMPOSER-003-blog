<?php

namespace Ceygenic\Blog\Repositories\Sanity;

use Ceygenic\Blog\Contracts\Repositories\CategoryRepositoryInterface;
use GuzzleHttp\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SanityCategoryRepository implements CategoryRepositoryInterface
{
    protected string $projectId;
    protected string $dataset;
    protected ?string $token;

    public function __construct()
    {
        $this->projectId = config('blog.sanity.project_id', '');
        $this->dataset = config('blog.sanity.dataset', 'production');
        $this->token = config('blog.sanity.token');
    }

    protected function getSanityUrl(): string
    {
        return "https://{$this->projectId}.api.sanity.io/v2021-10-21/data/query/{$this->dataset}";
    }

    protected function query(string $query): array
    {
        $client = new Client();
        
        $response = $client->get($this->getSanityUrl(), [
            'query' => ['query' => $query],
            'headers' => $this->token ? ['Authorization' => "Bearer {$this->token}"] : [],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['result'] ?? [];
    }

    protected function transformSanityCategory(array $sanityCategory): object
    {
        return (object) [
            'id' => $sanityCategory['_id'] ?? null,
            'name' => $sanityCategory['name'] ?? '',
            'slug' => $sanityCategory['slug']['current'] ?? '',
            'description' => $sanityCategory['description'] ?? '',
        ];
    }

    public function all(): Collection
    {
        $query = '*[_type == "category"]{_id, name, slug, description}';
        $results = $this->query($query);
        
        return new Collection(array_map([$this, 'transformSanityCategory'], $results));
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $all = $this->all();
        $currentPage = request()->get('page', 1);
        $items = $all->forPage($currentPage, $perPage)->values();
        
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $all->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function find(int $id)
    {
        $query = "*[_type == 'category' && _id == '{$id}']{_id, name, slug, description}[0]";
        $result = $this->query($query);
        
        return !empty($result) ? $this->transformSanityCategory($result) : null;
    }

    public function findBySlug(string $slug)
    {
        $query = "*[_type == 'category' && slug.current == '{$slug}']{_id, name, slug, description}[0]";
        $result = $this->query($query);
        
        return !empty($result) ? $this->transformSanityCategory($result) : null;
    }

    public function create(array $data)
    {
        throw new \RuntimeException('Sanity create operation not yet implemented. Use Sanity Studio or implement mutations API.');
    }

    public function update(int $id, array $data): bool
    {
        throw new \RuntimeException('Sanity update operation not yet implemented. Use Sanity Studio or implement mutations API.');
    }

    public function delete(int $id): bool
    {
        throw new \RuntimeException('Sanity delete operation not yet implemented. Use Sanity Studio or implement mutations API.');
    }
}

